<?php
session_start();
require_once '../includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM authors WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $author = $result->fetch_assoc();

            if (password_verify($password, $author["password"])) {
                $_SESSION["author_id"] = $author["author_id"];
                $_SESSION["author_name"] = $author["full_name"];
                header("Location: ../author/author_dashboard.php");
                exit;
            } else {
                $error = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ไม่พบชื่อผู้ใช้";
        }
    } else {
        $error = "เกิดข้อผิดพลาดในการเข้าสู่ระบบ";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบผู้แต่ง</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #ffecd2, #e05cb2ff);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 350px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .login-container input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        .login-container button {
            background: #b52ad1ff;
            border: none;
            color: white;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .login-container button:hover {
            background: #eb6750;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .login-container a {
            display: inline-block;
            margin-top: 10px;
            color: #555;
            text-decoration: none;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>เข้าสู่ระบบผู้แต่ง</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="../logregis/process_author_login.php" method="post">
            <input type="text" name="username" required placeholder="ชื่อผู้ใช้">
            <input type="password" name="password" required placeholder="รหัสผ่าน">
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
            <?php
            if (isset($_SESSION["login_error"])) {
                echo "<p style='color:red'>" . $_SESSION["login_error"] . "</p>";
                unset($_SESSION["login_error"]);
                }
            ?>
        <a href="../pages/Home.php">← กลับหน้าหลัก</a>
    </div>
</body>
</html>

