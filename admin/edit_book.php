<?php
session_start();
require_once "../includes/db_connect.php";

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

// ดึงค่าพารามิเตอร์ id
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($book_id <= 0) {
    header("Location: ../admin/admin_dashboard.php");
    exit;
}

// ดึงข้อมูลหนังสือต้นฉบับ
$stmt = $conn->prepare("SELECT title, description, cover_image, genre FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: ../admin/admin_dashboard.php");
    exit;
}
$book = $result->fetch_assoc();
$error = "";

// ถ้ากดบันทึก (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = trim($_POST["title"]);
    $genre       = trim($_POST["genre"]);
    $description = trim($_POST["description"]);
    $newImage    = $book['cover_image']; // ค่าเก่าเป็นดีฟอลต์

    // ตรวจสอบไฟล์ภาพใหม่ (ถ้ามี)
    if (!empty($_FILES["cover_image"]["name"]) && $_FILES["cover_image"]["error"] === UPLOAD_ERR_OK) {

        // สร้างโฟลเดอร์ถ้าไม่มี
        $uploadDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // ตรวจสอบชนิดไฟล์
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($_FILES["cover_image"]["tmp_name"]);
        if (!in_array($fileType, $allowedTypes)) {
            $error = "อนุญาตเฉพาะไฟล์ JPG หรือ PNG เท่านั้น";
        } else {
            $ext  = strtolower(pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION));
            $newImage = uniqid("cover_", true) . "." . $ext;
            $dest = $uploadDir . $newImage;

            if (!move_uploaded_file($_FILES["cover_image"]["tmp_name"], $dest)) {
                $error = "ไม่สามารถอัปโหลดไฟล์ภาพใหม่ได้";
            } else {
                // ลบภาพเก่าถ้ามี
                $oldFile = $uploadDir . $book['cover_image'];
                if ($book['cover_image'] && file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }
    }

    // ถ้าไม่มี error ให้อัปเดตฐานข้อมูล
    if (empty($error)) {
        $update = $conn->prepare("
            UPDATE books 
            SET title = ?, description = ?, cover_image = ?, genre = ?
            WHERE book_id = ?
        ");
        $update->bind_param("ssssi", $title, $description, $newImage, $genre, $book_id);
        if ($update->execute()) {
            header("Location: ../admin/admin_dashboard.php");
            exit;
        } else {
            $error = "อัปเดตฐานข้อมูลล้มเหลว: " . $update->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขหนังสือ #<?php echo $book_id; ?></title>
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
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
        }
        form label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
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
        textarea {
            resize: vertical;
        }
        img {
            border-radius: 6px;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .error {
            background: #ffe5e5;
            color: #c0392b;
            border: 1px solid #e74c3c;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        button {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1> แก้ไขหนังสือ</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>ชื่อเรื่อง:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>

        <label>แนว (Genre):</label>
        <input type="text" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>" required>

        <label>คำอธิบาย:</label>
        <textarea name="description" rows="4"><?php echo htmlspecialchars($book['description']); ?></textarea>

        <label>ปกปัจจุบัน:</label>
        <?php if (!empty($book['cover_image'])): ?>
            <img src="../uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" width="150" alt="ปก">
        <?php else: ?>
            <p style="color:#7f8c8d;">ยังไม่มีปก</p>
        <?php endif; ?>

        <label>อัปโหลดรูปปกใหม่ (ไม่เลือกถ้าไม่เปลี่ยน):</label>
        <input type="file" name="cover_image" accept="image/jpeg,image/png">

        <button type="submit"> บันทึกการเปลี่ยนแปลง</button>
    </form>

    <a href="../admin/admin_dashboard.php" class="back-link">⬅ กลับหน้าจัดการหนังสือ</a>
</div>
</body>
</html>

