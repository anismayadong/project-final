<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบสิทธิ์ว่าเป็น author ที่ล็อกอิน
if (!isset($_SESSION['author_id'])) {
    header("Location: ../author/author_login.php");
    exit;
}

$author_id = $_SESSION['author_id'];

// รับ book_id จาก URL
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($book_id <= 0) {
    header("Location: ../author/manage_books.php");
    exit;
}

// ตรวจสอบว่าเป็นหนังสือของ author คนนี้จริงไหม
$stmt = $conn->prepare("SELECT cover_image FROM books WHERE book_id = ? AND author_id = ?");
$stmt->bind_param("ii", $book_id, $author_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // ไม่พบหรือไม่ใช่ของคนนี้
    header("Location: ../author/manage_books.php");
    exit;
}

$book = $result->fetch_assoc();

// ลบรูปภาพ
if (!empty($book['cover_image']) && file_exists(__DIR__ . "../uploads/" . $book['cover_image'])) {
    @unlink(__DIR__ . "../uploads/" . $book['cover_image']);
}

// ลบจากฐานข้อมูล
$delete = $conn->prepare("DELETE FROM books WHERE book_id = ? AND author_id = ?");
$delete->bind_param("ii", $book_id, $author_id);
$delete->execute();

header("Location: ../author/manage_books.php");
exit;
?>
