<?php
session_start();
require_once "../includes/db_connect.php";

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มเกมใหม่ - ผู้ดูแลระบบ</title>
    <link rel="stylesheet" href="../assets/css/styles1.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            color: #333;
        }
        .container {
            background: #fff;
            width: 100%;
            max-width: 700px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        form label {
            display: block;
            margin-top: 15px;
            margin-bottom: 8px;
            font-weight: bold;
            color: #34495e;
        }
        input[type="text"], textarea, select, input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }
        textarea { resize: vertical; }
        select { background: #fff; }
        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 6px;
            border: none;
            background: #3498db;
            color: #fff;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: #2980b9; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>🎮 เพิ่มเกมสะสมแต้มใหม่</h2>
    <form action="../game/upload_game_process.php" method="POST" enctype="multipart/form-data">
        <label>ชื่อเกม:</label>
        <input type="text" name="game_name" required>

        <label>คำอธิบาย:</label>
        <textarea name="description" rows="5"></textarea>

        <label>ระดับความยาก:</label>
        <select name="difficulty">
            <option value="easy">ง่าย</option>
            <option value="medium">ปานกลาง</option>
            <option value="hard">ยาก</option>
        </select>

        <label>ลิงก์ URL ไปยัง WebGL เกม:</label>
        <input type="text" name="game_url" required placeholder="เช่น ../games/mygame/index.html">

        <label>ภาพหน้าปกเกม (ถ้ามี):</label>
        <input type="file" name="cover_image" accept="image/*">

        <button type="submit">💾 บันทึกเกม</button>
    </form>
    <a href="../admin/admin_dashboard.php" class="back-link">⬅️ กลับไปยังแผงควบคุม</a>
</div>
</body>
</html>

