<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['author_id'])) {
    echo "<p>กรุณาเข้าสู่ระบบในฐานะผู้แต่ง</p>";
    exit;
}

$author_id = $_SESSION['author_id'];
$chapter_id = intval($_GET['chapter_id'] ?? 0);

// ✅ ตรวจสอบสิทธิ์เจ้าของ
$sql = "SELECT c.*, b.author_id, b.book_id 
        FROM chapters c 
        JOIN books b ON c.book_id = b.book_id 
        WHERE c.chapter_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chapter_id);
$stmt->execute();
$result = $stmt->get_result();
$chapter = $result->fetch_assoc();

if (!$chapter || $chapter['author_id'] != $author_id) {
    echo "<p>คุณไม่มีสิทธิ์แก้ไขตอนนี้</p>";
    exit;
}

// ✅ ถ้ากดลบภาพ
if (isset($_GET['delete_image'])) {
    $img_id = intval($_GET['delete_image']);
    $del = $conn->prepare("DELETE FROM chapter_images WHERE image_id = ? AND chapter_id = ?");
    $del->bind_param("ii", $img_id, $chapter_id);
    $del->execute();
    header("Location: edit_chapter.php?chapter_id=$chapter_id");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $chapter_number = intval($_POST['chapter_number']);

    // ✅ อัปเดตข้อมูลตอน
    $update = $conn->prepare("UPDATE chapters SET title = ?, chapter_number = ? WHERE chapter_id = ?");
    $update->bind_param("sii", $title, $chapter_number, $chapter_id);
    $update->execute();

    // ✅ ถ้ามีการอัปโหลดรูปเพิ่ม
    if (!empty($_FILES['new_images']['name'][0])) {
        $target_dir = "../uploads/chapters/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['new_images']['name'][$key], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $page_number = $key + 1;

                    $img_stmt = $conn->prepare("INSERT INTO chapter_images (chapter_id, page_number, image_path) VALUES (?, ?, ?)");
                    $img_stmt->bind_param("iis", $chapter_id, $page_number, $target_file);
                    $img_stmt->execute();
                }
            }
        }
    }

    header("Location: ../author/manage_chapters.php?book_id=" . $chapter['book_id']);
    exit;
}

// ✅ ดึงรูปทั้งหมดในตอนนี้
$images = $conn->prepare("SELECT * FROM chapter_images WHERE chapter_id = ? ORDER BY page_number ASC");
$images->bind_param("i", $chapter_id);
$images->execute();
$img_result = $images->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขตอน</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 30px;
            color: #333;
        }
        .container {
            max-width: 750px;
            margin: auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        label {
            font-weight: bold;
            display: block;
            margin: 12px 0 6px;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .image-list {
            margin-top: 15px;
        }
        .image-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #fafafa;
        }
        .image-item img {
            max-width: 120px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .delete-btn {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
        }
        .delete-btn:hover {
            text-decoration: underline;
        }
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
        .btn-submit:hover {
            background: #1e8449;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #3498db;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2> แก้ไขตอน: <?php echo htmlspecialchars($chapter['title']); ?></h2>

    <form method="post" enctype="multipart/form-data">
        <label>ชื่อตอน:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($chapter['title']); ?>" required>

        <label>เลขตอน:</label>
        <input type="number" name="chapter_number" value="<?php echo $chapter['chapter_number']; ?>" required>

        <h3>รูปภาพในตอนนี้</h3>
        <div class="image-list">
        <?php if ($img_result->num_rows > 0): ?>
            <?php while ($img = $img_result->fetch_assoc()): ?>
                <div class="image-item">
                    <img src="<?php echo $img['image_path']; ?>" alt="chapter image">
                    <a href="edit_chapter.php?chapter_id=<?php echo $chapter_id; ?>&delete_image=<?php echo $img['image_id']; ?>" 
                       class="delete-btn"
                       onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบรูปนี้?')">ลบรูปนี้</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>ยังไม่มีภาพในตอนนี้</p>
        <?php endif; ?>
        </div>

        <h3> เพิ่มรูปใหม่</h3>
        <input type="file" name="new_images[]" multiple accept="image/*">

        <button type="submit" class="btn-submit">บันทึกการแก้ไข</button>
    </form>

    <a href="../author/manage_chapters.php?book_id=<?php echo $chapter['book_id']; ?>" class="back-link">← ย้อนกลับ</a>
</div>
</body>
</html>
