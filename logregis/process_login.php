<?php
session_start();
require_once "../includes/db_connect.php"; // เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST["login"]); // ชื่อผู้ใช้หรืออีเมล
    $password = $_POST["password"];

    // เตรียมคำสั่ง SQL เพื่อตรวจสอบผู้ใช้
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["phone_number"] = $user["phone_number"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["points"] = $user["points"];
            $_SESSION["role_type"] = $user["role_type"]; // เพิ่ม role_type ลง session

            // ตรวจสอบว่าเป็นแอดมินหรือไม่
            if ($user["role_type"] === "admin") {
                header("Location: ../admin/admin_dashboard.php");
            } else {
                header("Location: ../pages/Home.php");
            }
            exit;
        } else {
            $_SESSION["login_error"] = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $_SESSION["login_error"] = "ไม่พบผู้ใช้งาน";
    }

    // กลับไปหน้าเดิมพร้อม error
    header("Location: ../pages/Home.php");
    exit;
}
?>
