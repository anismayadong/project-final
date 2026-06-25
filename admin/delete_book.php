<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($book_id <= 0) {
    header("Location: ../admin/admin_dashboard.php");
    exit;
}

// โฟลเดอร์ uploads
$uploadDir = __DIR__ . '/../uploads/';

try {
    // เริ่ม transaction
    $conn->begin_transaction();

    // 1) ดึงข้อมูลไฟล์ปกของหนังสือ
    $stmt = $conn->prepare("SELECT cover_image FROM books WHERE book_id = ?");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        throw new Exception("ไม่พบหนังสือที่ต้องการลบ");
    }
    $bookRow = $res->fetch_assoc();
    $coverImage = $bookRow['cover_image'];
    $stmt->close();

    // 2) ดึง chapter_id และ image_path ทั้งหมดของหนังสือ
    $chapStmt = $conn->prepare("SELECT chapter_id, image_path FROM chapters WHERE book_id = ?");
    if (!$chapStmt) throw new Exception("Prepare failed: " . $conn->error);
    $chapStmt->bind_param("i", $book_id);
    $chapStmt->execute();
    $chapRes = $chapStmt->get_result();

    $chapterIds = [];
    $chapterImages = [];
    while ($r = $chapRes->fetch_assoc()) {
        $chapterIds[] = intval($r['chapter_id']);
        if (!empty($r['image_path'])) $chapterImages[] = $r['image_path'];
    }
    $chapStmt->close();

    $chapIn = '';
    if (count($chapterIds) > 0) {
        $chapIn = implode(',', $chapterIds);
    }

    // 3) ลบตารางลูกที่อ้างถึง chapter_id
    if ($chapIn !== '') {
        // ลบ unlock_history
        if ($conn->query("DELETE FROM unlock_history WHERE chapter_id IN ($chapIn)") === false) {
            throw new Exception($conn->error);
        }

        // ลบ reading_history
        if ($conn->query("DELETE FROM reading_history WHERE chapter_id IN ($chapIn)") === false) {
            throw new Exception($conn->error);
        }

        // ลบ revenue_share
        if ($conn->query("DELETE FROM revenue_share WHERE chapter_id IN ($chapIn)") === false) {
            throw new Exception($conn->error);
        }

        // ถ้ามีตารางอื่น ๆ ที่อ้างถึง chapter_id ให้ลบเพิ่มที่นี่
    }

    // 4) ลบตารางที่อ้างถึง book_id โดยตรง
    $delPrepared = $conn->prepare("DELETE FROM reviews WHERE book_id = ?");
    if (!$delPrepared) throw new Exception("Prepare failed: " . $conn->error);
    $delPrepared->bind_param("i", $book_id);
    if (!$delPrepared->execute()) throw new Exception($delPrepared->error);
    $delPrepared->close();

    $delPrepared = $conn->prepare("DELETE FROM revenue_share WHERE book_id = ?");
    if (!$delPrepared) throw new Exception("Prepare failed: " . $conn->error);
    $delPrepared->bind_param("i", $book_id);
    if (!$delPrepared->execute()) throw new Exception($delPrepared->error);
    $delPrepared->close();

    // 5) ลบ chapters
    $delCh = $conn->prepare("DELETE FROM chapters WHERE book_id = ?");
    if (!$delCh) throw new Exception("Prepare failed: " . $conn->error);
    $delCh->bind_param("i", $book_id);
    if (!$delCh->execute()) throw new Exception($delCh->error);
    $delCh->close();

    // 6) ลบ books
    $delBook = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    if (!$delBook) throw new Exception("Prepare failed: " . $conn->error);
    $delBook->bind_param("i", $book_id);
    if (!$delBook->execute()) throw new Exception($delBook->error);
    $delBook->close();

    // 7) ลบไฟล์จริงในโฟลเดอร์ uploads
    if (!empty($coverImage)) {
        $coverFile = $uploadDir . basename($coverImage);
        if (file_exists($coverFile)) @unlink($coverFile);
    }

    foreach ($chapterImages as $imgPath) {
        $file = $uploadDir . basename($imgPath);
        if (file_exists($file)) @unlink($file);
    }

    // commit
    $conn->commit();

    header("Location: ../admin/admin_dashboard.php?msg=deleted");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo "<h3>เกิดข้อผิดพลาดในการลบหนังสือ</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>MySQL error: " . htmlspecialchars($conn->error) . "</p>";
    exit;
}
