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

// ตรวจสอบว่า book นี้เป็นของ author จริงไหม
$book_check = $conn->prepare("SELECT * FROM books WHERE book_id = ? AND author_id = ?");
$book_check->bind_param("ii", $book_id, $author_id);
$book_check->execute();
$book_result = $book_check->get_result();

if ($book_result->num_rows === 0) {
    echo "<p>คุณไม่มีสิทธิ์จัดการหนังสือนี้</p>";
    exit;
}

$book = $book_result->fetch_assoc();

// ดึงข้อมูลตอนทั้งหมด
$chapters = $conn->prepare("SELECT * FROM chapters WHERE book_id = ? ORDER BY chapter_number ASC");
$chapters->bind_param("i", $book_id);
$chapters->execute();
$chapter_result = $chapters->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการตอน - <?php echo htmlspecialchars($book['title']); ?></title>
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
        .chapter-container {
            background: #fff;
            width: 100%;
            max-width: 800px;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .actions a {
            text-decoration: none;
            background: #3498db;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .actions a:hover { background: #2980b9; }
        .chapter-list {
            list-style: none;
            padding: 0;
        }
        .chapter-item {
            background: #ecf0f1;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chapter-item strong { color: #2c3e50; }
        .chapter-actions a {
            margin-left: 10px;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 14px;
        }
        .chapter-actions a.edit {
            background: #27ae60;
            color: #fff;
        }
        .chapter-actions a.edit:hover { background: #1e8449; }
        .chapter-actions a.delete {
            background: #e74c3c;
            color: #fff;
        }
        .chapter-actions a.delete:hover { background: #c0392b; }
        .empty {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="chapter-container">
    <h2>จัดการตอน: <?php echo htmlspecialchars($book['title']); ?></h2>
    
    <div class="actions">
        <a href="../author/add_chapter.php?book_id=<?php echo $book_id; ?>"> เพิ่มตอนใหม่</a>
        <a href="../author/manage_books.php">← กลับไปหนังสือของฉัน</a>
    </div>

    <?php if ($chapter_result->num_rows > 0): ?>
        <ul class="chapter-list">
            <?php while ($chapter = $chapter_result->fetch_assoc()): ?>
                <li class="chapter-item">
                    <div>
                        <strong>ตอนที่ <?php echo $chapter['chapter_number']; ?>:</strong> 
                        <?php echo htmlspecialchars($chapter['title']); ?>
                    </div>
                    <div class="chapter-actions">
                        <a href="../author/edit_chapter.php?chapter_id=<?php echo $chapter['chapter_id']; ?>" class="edit"> แก้ไข</a>
                        <a href="../author/delete_chapter.php?chapter_id=<?php echo $chapter['chapter_id']; ?>&book_id=<?php echo $book_id; ?>" 
                           class="delete" 
                           onclick="return confirm('คุณแน่ใจว่าต้องการลบตอนนี้?');"> ลบ</a>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="empty">ยังไม่มีตอนในเล่มนี้</p>
    <?php endif; ?>
</div>
</body>
</html>