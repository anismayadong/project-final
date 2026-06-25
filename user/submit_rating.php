<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    die("คุณต้องเข้าสู่ระบบก่อนให้คะแนน");
}

$user_id = $_SESSION['user_id'];
$book_id = intval($_POST['book_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);

if ($book_id > 0 && $rating >= 1 && $rating <= 5) {
    // เช็คว่าผู้ใช้เคยให้คะแนนหนังสือนี้แล้วหรือไม่
    $stmt = $conn->prepare("SELECT id FROM book_ratings WHERE book_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $book_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // อัปเดตคะแนน
        $stmt->close();
        $stmt = $conn->prepare("UPDATE book_ratings SET rating = ?, created_at = NOW() WHERE book_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $rating, $book_id, $user_id);
        $stmt->execute();
    } else {
        // แทรกคะแนนใหม่
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO book_ratings (book_id, user_id, rating) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $book_id, $user_id, $rating);
        $stmt->execute();
    }
    $stmt->close();

    // อัปเดตค่าเฉลี่ยเรตติ้งในตาราง books
    $result = $conn->query("SELECT AVG(rating) AS avg_rating FROM book_ratings WHERE book_id = $book_id");
    $row = $result->fetch_assoc();
    $avg_rating = round($row['avg_rating'], 1);

    $conn->query("UPDATE books SET rating = $avg_rating WHERE book_id = $book_id");

    header("Location: ../pages/book_detail.php?book_id=$book_id&msg=rating_success");
    exit();
} else {
    die("ข้อมูลไม่ถูกต้อง");
}
?>
