<?php
session_start();
require_once "../includes/db_connect.php";

// ตรวจสอบสิทธิ์เฉพาะ author
if (!isset($_SESSION["author_id"])) {
    header("Location: ../author/author_login.php");
    exit;
}

$author_id = $_SESSION["author_id"];
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    header("Location: ../author/manage_books.php");
    exit;
}

// ดึงข้อมูลหนังสือ (รวม genre ด้วย)
$stmt = $conn->prepare("SELECT title, description, genre, cover_image FROM books WHERE book_id = ? AND author_id = ?");
$stmt->bind_param("ii", $book_id, $author_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: ../author/manage_books.php");
    exit;
}

$book = $result->fetch_assoc();
$error = "";

// ถ้ากดบันทึก (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $genre       = trim($_POST["genre"]);
    $newImage    = $book['cover_image']; // ใช้ภาพเก่าโดยดีฟอลต์

    // ตรวจสอบไฟล์ภาพใหม่ (ถ้ามี)
    if (!empty($_FILES["cover_image"]["name"]) && $_FILES["cover_image"]["error"] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES["cover_image"]["tmp_name"];
        $name = basename($_FILES["cover_image"]["name"]);
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $newImage = uniqid("cover_", true) . "." . $ext;

        $upload_dir = __DIR__ . "/../uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $dest = $upload_dir . $newImage;

        if (!move_uploaded_file($tmp, $dest)) {
            $error = "ไม่สามารถอัปโหลดไฟล์ภาพใหม่ได้";
        } else {
            // ลบภาพเก่าถ้ามี
            if ($book['cover_image'] && file_exists($upload_dir . $book['cover_image'])) {
                @unlink($upload_dir . $book['cover_image']);
            }
        }
    }

    // ถ้าไม่มี error ให้อัปเดตฐานข้อมูล
    if (empty($error)) {
        $update = $conn->prepare("
            UPDATE books 
            SET title = ?, description = ?, genre = ?, cover_image = ?
            WHERE book_id = ? AND author_id = ?
        ");
        $update->bind_param("ssssii", $title, $description, $genre, $newImage, $book_id, $author_id);

        if ($update->execute()) {
            header("Location: ../author/manage_books.php");
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
    <title>แก้ไขหนังสือของคุณ</title>
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
        .form-container {
            background: #fff;
            width: 100%;
            max-width: 700px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        label {
            font-weight: bold;
            color: #34495e;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }
        textarea { resize: vertical; }
        button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: #2980b9; }
        .error {
            background: #fce4e4;
            color: #e74c3c;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }
        .cover-preview {
            display: block;
            margin: 10px auto 20px auto;
            max-width: 180px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #3498db;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="form-container">
    <h1>แก้ไขหนังสือ</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="title">ชื่อเรื่อง:</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>

        <label for="genre">แนว:</label>
        <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>" required>

        <label for="description">คำอธิบาย:</label>
        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($book['description']); ?></textarea>

        <label>ปกปัจจุบัน:</label>
        <?php if (!empty($book['cover_image'])): ?>
            <img src="../uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" class="cover-preview" alt="ปกหนังสือ">
        <?php endif; ?>

        <label for="cover_image">อัปโหลดรูปปกใหม่ (ไม่ต้องเลือกถ้าไม่เปลี่ยน):</label>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png">

        <button type="submit">บันทึกการเปลี่ยนแปลง</button>
    </form>

    <a href="../author/manage_books.php" class="back-link">← กลับไปจัดการหนังสือ</a>
</div>
</body>
</html>
