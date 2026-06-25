<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['author_id'])) {
    header("Location: ../author/author_login.php");
    exit;
}

$author_id = $_SESSION['author_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $genre       = trim($_POST['genre']);
    $cover_image = null;

    // ตรวจสอบไฟล์ภาพที่อัปโหลด (ถ้ามี)
   if (!empty($_FILES["cover_image"]["name"]) && $_FILES["cover_image"]["error"] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES["cover_image"]["tmp_name"];
    $name = basename($_FILES["cover_image"]["name"]);
    $ext  = pathinfo($name, PATHINFO_EXTENSION);
    $cover_image = uniqid("cover_", true) . "." . $ext;

    $upload_dir = __DIR__ . "/../uploads/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $dest = $upload_dir . $cover_image;

    if (!move_uploaded_file($tmp, $dest)) {
        $message = "ไม่สามารถอัปโหลดรูปภาพได้";
    }
}

    // ตรวจสอบค่าที่ป้อน
    if (empty($title)) {
        $message = "กรุณากรอกชื่อหนังสือ";
    } elseif (empty($message)) {
        $sql = "INSERT INTO books (title, description, genre, cover_image, author_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $title, $description, $genre, $cover_image, $author_id);

        if ($stmt->execute()) {
            header("Location: ../author/manage_books.php");
            exit;
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มหนังสือใหม่</title>
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
            max-width: 650px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        form label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: bold;
            color: #34495e;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }
        textarea { resize: vertical; }
        input[type="submit"] {
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
        input[type="submit"]:hover { background: #2980b9; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            background: #fce4e4;
            color: #c0392b;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>เพิ่มหนังสือใหม่</h1>

    <?php if ($message): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="../author/add_book.php" method="post" enctype="multipart/form-data">
        <label for="title">ชื่อหนังสือ:</label>
        <input type="text" id="title" name="title" required>

        <label for="genre">แนว:</label>
        <input type="text" id="genre" name="genre" placeholder="เช่น Action, Romance" required>

        <label for="description">คำอธิบาย:</label>
        <textarea id="description" name="description" rows="4"></textarea>

        <label for="cover_image">อัปโหลดรูปปก:</label>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png">

        <input type="submit" value="บันทึกหนังสือ">
    </form>

    <a href="../author/manage_books.php" class="back-link">⬅ กลับไปจัดการหนังสือ</a>
</div>
</body>
</html>
