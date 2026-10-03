<?php
session_start();
include_once '../includes/db_connect.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/Home.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$stmt = $conn->prepare("SELECT username, full_name, phone_number, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// อัปเดตข้อมูล
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username    = trim($_POST["username"]);
    $full_name   = trim($_POST["full_name"]);
    $phone       = trim($_POST["phone_number"]);
    $email       = trim($_POST["email"]);
    $password    = trim($_POST["password"]);

    // ถ้ามีการกรอกรหัสใหม่ → เข้ารหัส
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username=?, full_name=?, phone_number=?, email=?, password=? WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $username, $full_name, $phone, $email, $hashed, $user_id);
    } else {
        $sql = "UPDATE users SET username=?, full_name=?, phone_number=?, email=? WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $full_name, $phone, $email, $user_id);
    }

    if ($stmt->execute()) {
        // อัปเดต SESSION ด้วย
        $_SESSION["username"] = $username;
        $_SESSION["full_name"] = $full_name;
        $_SESSION["phone_number"] = $phone;
        $_SESSION["email"] = $email;

        echo "<script>alert('อัปเดตข้อมูลเรียบร้อย'); window.location.href='../user/profile.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดต');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโปรไฟล์ | MangAnimeHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .edit-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; color: #333; }
        input {
            width: 100%; padding: 10px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 6px;
        }
        button {
            margin-top: 20px; padding: 10px; width: 100%;
            background: #3498db; color: white;
            border: none; border-radius: 6px; cursor: pointer;
        }
        button:hover { background: #2980b9; }
        a { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
<div class="edit-container">
    <h2>แก้ไขโปรไฟล์</h2>
    <form method="post">
        <label>ชื่อผู้ใช้:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>

        <label>ชื่อ-นามสกุล:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" required>

        <label>เบอร์โทร:</label>
        <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']); ?>">

        <label>อีเมล:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

        <label>รหัสผ่านใหม่ (ปล่อยว่างถ้าไม่เปลี่ยน):</label>
        <input type="password" name="password">

        <button type="submit">บันทึกการเปลี่ยนแปลง</button>
    </form>
    <a href="../user/profile.php">← กลับไปโปรไฟล์</a>
</div>
</body>
</html>
