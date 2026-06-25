<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบการเข้าสู่ระบบของผู้แต่ง
if (!isset($_SESSION['author_id'])) {
    echo "<p>กรุณาเข้าสู่ระบบในฐานะผู้แต่ง</p>";
    exit;
}

$author_id = $_SESSION['author_id'];
// ใช้ intval เพื่อให้แน่ใจว่าเป็นตัวเลขและป้องกัน SQL Injection ในระดับหนึ่ง
$chapter_id = intval($_GET['chapter_id'] ?? 0);
$book_id = intval($_GET['book_id'] ?? 0);

// ตรวจสอบสิทธิ์การลบ: ตรวจสอบว่าบทนี้เป็นของผู้แต่งคนปัจจุบันจริงหรือไม่
$sql = "SELECT c.*, b.author_id 
        FROM chapters c 
        JOIN books b ON c.book_id = b.book_id 
        WHERE c.chapter_id = ? AND b.book_id = ?"; // เพิ่ม book_id เพื่อความปลอดภัยอีกชั้น
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $chapter_id, $book_id);
$stmt->execute();
$result = $stmt->get_result();
$chapter = $result->fetch_assoc();
$stmt->close();

// ถ้าไม่พบบท หรือบทนี้ไม่ใช่ของผู้แต่งคนปัจจุบัน
if (!$chapter || $chapter['author_id'] != $author_id) {
    echo "<p>คุณไม่มีสิทธิ์ลบตอนนี้ หรือไม่พบตอนนี้</p>";
    exit;
}

// --- เริ่มต้นการจัดการ Foreign Key Constraint ---

// เริ่มต้น Transaction เพื่อให้การดำเนินการลบเป็นแบบ Atomic (สำเร็จทั้งหมดหรือล้มเหลวทั้งหมด)
$conn->begin_transaction();
$delete_successful = false;

try {
    // 1. ลบข้อมูลที่เกี่ยวข้องในตาราง `unlock_history` ก่อน
    // เนื่องจาก `unlock_history` มี Foreign Key ชี้มาที่ `chapters`
    $delete_unlock_history = $conn->prepare("DELETE FROM unlock_history WHERE chapter_id = ?");
    $delete_unlock_history->bind_param("i", $chapter_id);

    if (!$delete_unlock_history->execute()) {
        throw new Exception("เกิดข้อผิดพลาดในการลบประวัติการปลดล็อก: " . $delete_unlock_history->error);
    }
    $delete_unlock_history->close();

    // 2. ลบข้อมูลในตาราง `chapters`
    $delete_chapter = $conn->prepare("DELETE FROM chapters WHERE chapter_id = ?");
    $delete_chapter->bind_param("i", $chapter_id);

    if (!$delete_chapter->execute()) {
        throw new Exception("เกิดข้อผิดพลาดในการลบตอน: " . $delete_chapter->error);
    }
    $delete_chapter->close();

    // ถ้าทุกอย่างสำเร็จ ให้ยืนยันการเปลี่ยนแปลงในฐานข้อมูล
    $conn->commit();
    $delete_successful = true;
    echo "<p>ลบตอนสำเร็จ</p>"; // ข้อความนี้จะแสดงชั่วคราวก่อน redirect
} catch (Exception $e) {
    // หากมีข้อผิดพลาดใดๆ ให้ยกเลิกการเปลี่ยนแปลงทั้งหมด
    $conn->rollback();
    echo "<p>เกิดข้อผิดพลาดในการลบ: " . $e->getMessage() . "</p>";
}

$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล

// --- สิ้นสุดการจัดการ Foreign Key Constraint ---

// ย้ายกลับหน้า manage_chapters.php หลังจากพยายามลบ (ไม่ว่าจะสำเร็จหรือไม่ก็ตาม)
// คุณอาจต้องการเพิ่มเงื่อนไขการ redirect หากต้องการให้ผู้ใช้เห็นข้อผิดพลาดนานขึ้น
header("Location: ../author/manage_chapters.php?book_id=$book_id");
exit;
?>