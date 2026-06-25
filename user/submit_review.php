<?php
session_start();
include_once '../includes/db_connect.php';

if (!isset($_SESSION["user_id"])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนรีวิว'); window.location.href='../pages/Home.php';</script>";
    exit();
}

$user_id = $_SESSION["user_id"];
$book_id = intval($_POST["book_id"] ?? 0);
$rating = intval($_POST["rating"] ?? 0);
$review_text = trim($_POST["review_text"] ?? '');

if ($book_id <= 0 || $rating < 1 || $rating > 5 || empty($review_text)) {
    echo "<script>alert('ข้อมูลไม่ถูกต้อง'); window.history.back();</script>";
    exit();
}

// ✅ 1. ตรวจสอบว่าผู้ใช้เคยรีวิวแล้วหรือยัง
$check_sql = "SELECT review_id FROM reviews WHERE user_id = ? AND book_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $book_id);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows > 0) {
    // 👉 เคยรีวิวแล้ว → อัปเดต
    $sql = "UPDATE reviews SET rating = ?, review_text = ?, created_at = NOW() 
            WHERE user_id = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $rating, $review_text, $user_id, $book_id);
} else {
    // 👉 ยังไม่เคยรีวิว → เพิ่มใหม่
    $sql = "INSERT INTO reviews (book_id, user_id, rating, review_text, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $book_id, $user_id, $rating, $review_text);
}

// ✅ 2. บันทึกหรืออัปเดตรีวิว
if ($stmt->execute()) {
    // ✅ 3. คำนวณค่าเฉลี่ยใหม่
    $avg_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE book_id = ?";
    $stmt_avg = $conn->prepare($avg_sql);
    $stmt_avg->bind_param("i", $book_id);
    $stmt_avg->execute();
    $avg_result = $stmt_avg->get_result()->fetch_assoc();
    $avg_rating = round($avg_result['avg_rating'], 1);

    // ✅ 4. อัปเดต books.rating
    $update_sql = "UPDATE books SET rating = ? WHERE book_id = ?";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("di", $avg_rating, $book_id);
    $stmt_update->execute();

    echo "<script>alert('บันทึกรีวิวเรียบร้อย!'); window.location.href='../pages/book_detail.php?book_id=$book_id';</script>";
} else {
    echo "<script>alert('เกิดข้อผิดพลาดในการรีวิว'); window.history.back();</script>";
}
?>
