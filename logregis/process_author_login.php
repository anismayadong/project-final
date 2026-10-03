<?php
<<<<<<< HEAD
session_start();
require_once '../includes/db_connect.php'; // เชื่อมต่อฐานข้อมูล

// รับค่าจากฟอร์ม
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION["login_error"] = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    header("Location: ../author/author_login.php");
    exit();
}

// ตรวจสอบ username
$sql = "SELECT * FROM authors WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $author = $result->fetch_assoc();

    $passwordMatches = password_verify($password, $author['password']);
    if (!$passwordMatches && $password === trim($author['password'])) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE authors SET password = ? WHERE author_id = ?");
        $upd->bind_param("si", $newHash, $author['author_id']);
        $upd->execute();
        $upd->close();
        $passwordMatches = true;
    }

    if ($passwordMatches) {
        $_SESSION['author_id'] = $author['author_id'];
        $_SESSION['username'] = $author['username'];
        $_SESSION['author_name'] = $author['full_name'];
        header("Location: ../author/author_dashboard.php");
        exit();
    } else {
        $_SESSION["login_error"] = "รหัสผ่านไม่ถูกต้อง";
    }
} else {
    $_SESSION["login_error"] = "ไม่พบชื่อผู้ใช้นี้";
=======
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
>>>>>>> c741e256a0c1ebb368512bbb7360e64e5260d250
}

$stmt->close();
$conn->close();
<<<<<<< HEAD

header("Location: ../author/author_login.php");
exit();
=======
>>>>>>> c741e256a0c1ebb368512bbb7360e64e5260d250
?>
