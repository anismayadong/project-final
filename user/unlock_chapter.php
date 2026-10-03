<?php
session_start();
include_once '../includes/db_connect.php';
include_once '../includes/config.php'; // ✅ ใช้ $UNLOCK_COST, $FREE_CHAPTERS

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='../pages/Home.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$chapter_id = intval($_POST['chapter_id'] ?? 0);

function unlockChapter($user_id, $chapter_id, $conn, $unlock_cost, $FREE_CHAPTERS) {
    // ✅ ตรวจสอบว่าปลดล็อกไปแล้วหรือยัง
    $check = $conn->prepare("SELECT 1 FROM unlock_history WHERE user_id = ? AND chapter_id = ?");
    if (!$check) die("SQL Error (check unlock): " . $conn->error);
    $check->bind_param("ii", $user_id, $chapter_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        return true; // เคยปลดล็อกแล้ว
    }

    // ✅ ดึงข้อมูลตอน + หนังสือ + ผู้แต่ง
    $stmt = $conn->prepare("
        SELECT c.chapter_number, c.is_locked, c.book_id, b.author_id
        FROM chapters c
        JOIN books b ON c.book_id = b.book_id
        WHERE c.chapter_id = ?
    ");
    if (!$stmt) die("SQL Error (chapter): " . $conn->error);
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $chapter = $stmt->get_result()->fetch_assoc();

    if (!$chapter) return false;

    // ✅ ตอนฟรี
    if ($chapter['chapter_number'] <= $FREE_CHAPTERS) {
        return true;
    }

    // ✅ ถ้าไม่ล็อก
    if ($chapter['is_locked'] == 0) {
        return true;
    }

    // ✅ เช็คแต้มผู้ใช้
    $stmt = $conn->prepare("SELECT points FROM points WHERE user_id = ?");
    if (!$stmt) die("SQL Error (points): " . $conn->error);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || $user['points'] < $unlock_cost) {
        return false; // แต้มไม่พอ
    }

    // ✅ ดึง config revenue share (share_percentage) ของหนังสือ
    $config_stmt = $conn->prepare("
        SELECT share_percentage 
        FROM revenue_share 
        WHERE book_id = ? AND chapter_id IS NULL 
        LIMIT 1
    ");
    if (!$config_stmt) die("SQL Error (config): " . $conn->error);
    $config_stmt->bind_param("i", $chapter['book_id']);
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();

    $share_percentage = $config ? $config['share_percentage'] : 70.00; // default 70%

    // ✅ คำนวณรายได้
    $points_spent   = $unlock_cost;
    $author_amount  = $points_spent * ($share_percentage / 100);
    $admin_amount   = $points_spent - $author_amount;

    // ✅ เริ่ม transaction
    $conn->begin_transaction();

    try {
        // 1. หักแต้ม
        $stmt = $conn->prepare("UPDATE points SET points = points - ? WHERE user_id = ?");
        if (!$stmt) die("SQL Error (deduct points): " . $conn->error);
        $stmt->bind_param("ii", $unlock_cost, $user_id);
        $stmt->execute();

        // 2. บันทึก unlock_history
        $stmt = $conn->prepare("INSERT INTO unlock_history (user_id, chapter_id) VALUES (?, ?)");
        if (!$stmt) die("SQL Error (unlock history): " . $conn->error);
        $stmt->bind_param("ii", $user_id, $chapter_id);
        $stmt->execute();

        // 3. บันทึก transaction ลง revenue_share
        $stmt = $conn->prepare("
            INSERT INTO revenue_share 
            (book_id, author_id, chapter_id, user_id, points_spent, author_share, admin_share) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) die("SQL Error (revenue share insert): " . $conn->error);
        $stmt->bind_param(
            "iiiiidd", 
            $chapter['book_id'], 
            $chapter['author_id'], 
            $chapter_id, 
            $user_id, 
            $points_spent, 
            $author_amount, 
            $admin_amount
        );
        $stmt->execute();

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// ✅ เรียกฟังก์ชัน
if (unlockChapter($user_id, $chapter_id, $conn, $UNLOCK_COST, $FREE_CHAPTERS)) {
    header("Location: ../pages/read_chapter.php?chapter_id=$chapter_id&unlocked=success");
    exit();
} else {
    echo "<script>alert('แต้มไม่เพียงพอหรือเกิดข้อผิดพลาด'); window.location.href='../pages/games.php';</script>";
      
}
?>
