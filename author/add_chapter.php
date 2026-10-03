<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['author_id'])) {
    echo "<p>กรุณาเข้าสู่ระบบในฐานะผู้แต่ง</p>";
    exit;
}

$author_id = $_SESSION['author_id'];
$book_id = intval($_GET['book_id'] ?? 0);
$message = "";

// ตรวจสอบว่า book นี้เป็นของ author จริงไหม
$book_check = $conn->prepare("SELECT * FROM books WHERE book_id = ? AND author_id = ?");
$book_check->bind_param("ii", $book_id, $author_id);
$book_check->execute();
$book_result = $book_check->get_result();

if ($book_result->num_rows === 0) {
    echo "<p>คุณไม่มีสิทธิ์เพิ่มตอนในหนังสือนี้</p>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $chapter_number = intval($_POST['chapter_number']);
    $title = trim($_POST['title']);

    if (empty($chapter_number) || empty($title) || empty($_FILES['chapter_images']['name'][0])) {
        $message = "⚠️ กรุณากรอกข้อมูลให้ครบ และเลือกรูปภาพอย่างน้อย 1 รูป";
    } else {
        // ตรวจสอบว่าหนังสือนี้มีตอนกี่ตอนแล้ว
        $count_sql = "SELECT COUNT(*) AS total FROM chapters WHERE book_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $book_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $row = $count_result->fetch_assoc();
        $existing_chapter_count = $row['total'];

        // ล็อกตอนที่ 6 เป็นต้นไป
        $is_locked = ($existing_chapter_count >= 5) ? 1 : 0;
        $unlock_cost = ($is_locked) ? 100 : 0;

        // ✅ เพิ่มตอนใหม่
        $stmt = $conn->prepare("INSERT INTO chapters (book_id, chapter_number, title, is_locked, unlock_cost)
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisii", $book_id, $chapter_number, $title, $is_locked, $unlock_cost);

        if ($stmt->execute()) {
            $chapter_id = $stmt->insert_id;

            // ✅ โฟลเดอร์อัปโหลด
            $target_dir = "../uploads/chapters/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // ✅ อัปโหลดหลายไฟล์
            foreach ($_FILES['chapter_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['chapter_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = uniqid() . "_" . basename($_FILES['chapter_images']['name'][$key]);
                    $target_file = $target_dir . $file_name;

                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($imageFileType, $allowed_types)) {
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $page_number = $key + 1;

                            $img_stmt = $conn->prepare("INSERT INTO chapter_images (chapter_id, page_number, image_path) 
                                                        VALUES (?, ?, ?)");
                            $img_stmt->bind_param("iis", $chapter_id, $page_number, $target_file);
                            $img_stmt->execute();
                        }
                    }
                }
            }

            header("Location: ../author/manage_chapters.php?book_id=$book_id");
            exit;
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึกตอน: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มตอนใหม่</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 30px;
            color: #333;
        }
        .container {
            max-width: 650px;
            margin: auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        form label {
            font-weight: bold;
            display: block;
            margin: 12px 0 6px;
        }
        form input[type="number"],
        form input[type="text"],
        form textarea,
        form input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        form textarea { resize: vertical; }
        .btn-submit {
            margin-top: 20px;
            padding: 12px 20px;
            width: 100%;
            border: none;
            border-radius: 8px;
            background: #27ae60;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-submit:hover { background: #1e8449; }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #3498db;
        }
        .back-link:hover { text-decoration: underline; }
        .message {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>เพิ่มตอนใหม่ในหนังสือ</h2>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>เลขตอน:</label>
        <input type="number" name="chapter_number" required>

        <label>ชื่อตอน:</label>
        <input type="text" name="title" required>

        <label>อัปโหลดรูปภาพ (เลือกได้หลายไฟล์):</label>
        <input type="file" name="chapter_images[]" multiple accept="image/*" required>

        <button type="submit" class="btn-submit">บันทึกตอนใหม่</button>
    </form>

    <a href="../author/manage_chapters.php?book_id=<?= $book_id ?>" class="back-link">← กลับไปหน้าจัดการตอน</a>
</div>
</body>
</html>
