<?php
session_start();
if (!isset($_SESSION["author_id"])) {
    header("Location: ../author/author_login.php");
    exit;
}

// สำหรับแสดงชื่อผู้แต่ง
$authorName = $_SESSION["author_name"] ?? "ผู้แต่ง";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดผู้แต่ง</title>
    <link rel="stylesheet" href="../assets/css/styles1.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background-color: #f9f9f9;
            color: #333;
        }
        h1 {
            color: #444;
        }
        .dashboard {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashboard a {
            display: block;
            padding: 12px;
            margin: 10px 0;
            background: #4CAF50;
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
        }
        .dashboard a:hover {
            background: #45a049;
        }
        .dashboard a.logout {
            background-color: #e74c3c;
        }
        .dashboard a.logout:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>ยินดีต้อนรับ <?php echo htmlspecialchars($authorName); ?></h1>

        <a href="../author/manage_books.php"> จัดการหนังสือของฉัน</a>
        <a href="../author/manage_all_chapters.php">จัดการตอนทั้งหมด</a>
        <a href="../author/author_earnings.php"> รายได้ทั้งหมด</a>
        <a href="../logregis/logout.php" class="logout" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?');">🔓 ออกจากระบบ</a>
    </div>
</body>
</html>
