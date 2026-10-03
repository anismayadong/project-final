<?php
session_start();
$login_error = $_SESSION["login_error"] ?? "";
unset($_SESSION["login_error"]);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบแอดมิน</title>
    <link rel="stylesheet" href="../assets/css/styles1.css"> 
</head>
<body>
    <div class="login-container">
        <h2>เข้าสู่ระบบผู้ดูแลระบบ</h2>

        <?php if (!empty($login_error)): ?>
            <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>

        <form method="POST" action="../logregis/process_login_admin.php">
            <label for="login">ชื่อผู้ใช้หรืออีเมล:</label>
            <input type="text" name="login" required>

            <label for="password">รหัสผ่าน:</label>
            <input type="password" name="password" required>

            <button type="submit">เข้าสู่ระบบแอดมิน</button>
        </form>
        <p><a href="../pages/Home.php">กลับไปหน้าผู้ใช้ทั่วไป</a></p>
    </div>
</body>
</html>
