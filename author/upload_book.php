<?php
session_start();
require_once "../includes/db_connect.php";

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

// ดึงรายชื่อผู้แต่ง 5 คน
$authors = [];
$res = $conn->query("SELECT author_id, full_name FROM authors ORDER BY full_name LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $authors[] = $row;
    }
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $author_id   = intval($_POST["author_id"]);
    $genre       = trim($_POST["genre"]);

    // ตรวจสอบข้อมูลเบื้องต้น
    if ($title === "" || !$author_id || $genre === "") {
        $error = "กรุณากรอกให้ครบทุกช่อง (ชื่อเรื่อง, ผู้แต่ง, แนว)";
    } elseif (!isset($_FILES["cover_image"]) || $_FILES["cover_image"]["error"] !== UPLOAD_ERR_OK) {
        $error = "กรุณาเลือกไฟล์รูปปกที่ถูกต้อง";
    } else {
        // จัดการอัปโหลดภาพ
        $tmp   = $_FILES["cover_image"]["tmp_name"];
        $name  = basename($_FILES["cover_image"]["name"]);
        $ext   = pathinfo($name, PATHINFO_EXTENSION);
        $newImage = uniqid("cover_", true) . "." . $ext;
        $dest  = __DIR__ . "/../uploads/" . $newImage;

        if (!move_uploaded_file($tmp, $dest)) {
            $error = "อัปโหลดรูปปกไม่สำเร็จ";
        } else {
            // บันทึกลงฐานข้อมูล
            $stmt = $conn->prepare("
                INSERT INTO books
                    (title, description, cover_image, author_id, genre, created_at)
                VALUES
                    (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssis",
                $title,        // s
                $description,  // s
                $newImage,     // s
                $author_id,    // i
                $genre         // s
            );

            if ($stmt->execute()) {
                header("Location: ../admin/admin_dashboard.php");
                exit;
            } else {
                $error = "บันทึกฐานข้อมูลล้มเหลว: " . $stmt->error;
                // ลบไฟล์ภาพที่อัปโหลดแล้วทิ้ง
                @unlink($dest);
            }
            $stmt->close();
        }
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
            max-width: 700px;
            padding: 25px 30px;
            border-radius: 10px;
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
        textarea {
            resize: vertical;
        }
        select {
            background: #fff;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .alert-danger {
            background: #ffe5e5;
            color: #c0392b;
            border: 1px solid #e74c3c;
        }
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
        small {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>เพิ่มหนังสือใหม่</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>ชื่อเรื่อง:</label>
        <input type="text" name="title" required>

        <label>คำอธิบาย:</label>
        <textarea name="description" rows="4"></textarea>

        <label>ผู้แต่ง:</label>
        <select name="author_id" required>
            <option value="">-- เลือกผู้แต่ง --</option>
            <?php foreach ($authors as $a): ?>
                <option value="<?php echo $a['author_id']; ?>">
                    <?php echo htmlspecialchars($a['full_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small>แสดงแค่ 5 คน (ล่าสุด)</small>

        <label>แนว (Genre):</label>
        <input type="text" name="genre" placeholder="เช่น Action, Fantasy" required>

        <label>รูปปก (JPG/PNG):</label>
        <input type="file" name="cover_image" accept="image/jpeg,image/png" required>

        <button type="submit"> บันทึกหนังสือ</button>
    </form>

    <a href="../admin/admin_dashboard.php" class="back-link"> กลับหน้าจัดการหนังสือ</a>
</div>
</body>
</html>

