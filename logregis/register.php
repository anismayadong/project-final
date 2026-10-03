<?php
include_once '../includes/db_connect.php';

$message = '';
$initial_points = 50;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $phone_number = trim($_POST["phone_number"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // ตรวจสอบว่าอีเมลซ้ำหรือไม่
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "❌ อีเมลนี้ถูกใช้ไปแล้ว";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, phone_number, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $full_name, $phone_number, $email, $password);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $conn->query("INSERT INTO points (user_id, points, updated_at) VALUES ($user_id, $initial_points, NOW())");
            $message = "✅ ลงทะเบียนสำเร็จ! <a href='../pages/Home.php'>เข้าสู่ระบบที่นี่</a>";
        } else {
            $message = "⚠️ เกิดข้อผิดพลาด: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลงทะเบียน - MangAnime Hub</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #ffffffff, #ce49e9ff);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 0;
        }
        .register-container {
            background: white;
            width: 380px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 5px 20px rgba(0,0,0,0.15);
        }
        .register-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #000000ff;
        }
        .register-container label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
            color: #333;
        }
        .register-container input[type="text"],
        .register-container input[type="email"],
        .register-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        .register-container input[type="submit"] {
            background: #000000ff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            margin-top: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .register-container input[type="submit"]:hover {
            background: #85369bff;
        }
        .message {
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        .message a {
            color: #ff7e5f;
            text-decoration: none;
        }
        .message a:hover {
            text-decoration: underline;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>สมัครสมาชิก</h2>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'สำเร็จ') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>ชื่อผู้ใช้:</label>
            <input type="text" name="username" required>

            <label>ชื่อ-นามสกุล:</label>
            <input type="text" name="full_name" required>

            <label>เบอร์โทรศัพท์:</label>
            <input type="text" name="phone_number" required>

            <label>อีเมล:</label>
            <input type="email" name="email" required>

            <label>รหัสผ่าน:</label>
            <input type="password" name="password" required>

            <input type="submit" value="สมัครสมาชิก">
        </form>
        
    </div>
</body>
</html>


