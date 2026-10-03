<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['author_id'])) {
    echo "<p>กรุณาเข้าสู่ระบบในฐานะผู้แต่ง</p>";
    exit;
}

$author_id = $_SESSION['author_id'];
$chapter_id = intval($_GET['chapter_id'] ?? 0);
$book_id = intval($_GET['book_id'] ?? 0);

// ตรวจสอบว่าสิทธิ์ถูกต้อง
$sql = "SELECT c.*, b.author_id FROM chapters c JOIN books b ON c.book_id = b.book_id WHERE c.chapter_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chapter_id);
$stmt->execute();
$result = $stmt->get_result();
$chapter = $result->fetch_assoc();

if (!$chapter || $chapter['author_id'] != $author_id) {
    echo "<p>คุณไม่มีสิทธิ์ลบตอนนี้</p>";
    exit;
}

// ลบ
$delete = $conn->prepare("DELETE FROM chapters WHERE chapter_id = ?");
$delete->bind_param("i", $chapter_id);
$delete->execute();

header("Location: manage_chapters.php?book_id=$book_id");
exit;
?>
