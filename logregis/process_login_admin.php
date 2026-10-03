<?php
session_start();
require_once "../includes/db_connect.php"; // ใช้ตัวแปร $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST["login"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['role_type'] !== 'admin') {
            $_SESSION["login_error"] = "บัญชีนี้ไม่ใช่ผู้ดูแลระบบ";
            header("Location: ../admin/admin_login.php");
            exit;
        }

        $passwordMatches = password_verify($password, $user['password']);
        if (!$passwordMatches && $password === trim($user['password'])) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $upd->bind_param("si", $newHash, $user['user_id']);
            $upd->execute();
            $upd->close();
            $passwordMatches = true;
        }

        if ($passwordMatches) {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role_type"] = $user["role_type"];

            header("Location: ../admin/admin_dashboard.php");
            exit;
        } else {
            $_SESSION["login_error"] = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $_SESSION["login_error"] = "ไม่พบผู้ใช้งาน";
    }

    header("Location: ../admin/admin_login.php");
    exit;
}
?>
