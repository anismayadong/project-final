<?php
require_once '../includes/db_connect.php'; // เชื่อมต่อฐานข้อมูล

// รับค่าจากฟอร์ม
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// ตรวจสอบ username และ password แบบไม่เข้ารหัส
$sql = "SELECT * FROM authors WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $author = $result->fetch_assoc();
    session_start();
        $_SESSION['author_id'] = $author['author_id'];
        $_SESSION['username'] = $author['username'];
        $_SESSION['author_name'] = $author['full_name'];
    header("Location: ../author/author_dashboard.php");
    exit();
} else {
    echo "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}

$stmt->close();
$conn->close();
?>
