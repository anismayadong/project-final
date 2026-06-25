<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['author_id'])) {
    header("Location: author_login.php");
    exit;
}

$author_id = $_SESSION['author_id'];

// ดึงรายชื่อหนังสือของผู้แต่ง
$books_sql = "SELECT * FROM books WHERE author_id = ?";
$books_stmt = $conn->prepare($books_sql);
$books_stmt->bind_param("i", $author_id);
$books_stmt->execute();
$books_result = $books_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการตอนทั้งหมด</title>
    <link rel="stylesheet" href="../assets/css/styles1.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 30px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        h2 {
            margin-top: 30px;
            color: #34495e;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
            background: #3498db;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .back-link:hover { background: #2980b9; }
        .add-btn {
            text-decoration: none;
            background: #27ae60;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .add-btn:hover { background: #1e8449; }
        .chapter-list {
            list-style: none;
            padding: 0;
        }
        .chapter-item {
            background: #ecf0f1;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chapter-actions a {
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-left: 8px;
        }
        .chapter-actions a.edit {
            background: #f39c12;
            color: white;
        }
        .chapter-actions a.edit:hover { background: #d68910; }
        .chapter-actions a.delete {
            background: #e74c3c;
            color: white;
        }
        .chapter-actions a.delete:hover { background: #c0392b; }
        .empty {
            color: #7f8c8d;
            font-style: italic;
            margin-top: 5px;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 30px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1> จัดการตอนทั้งหมดของคุณ</h1>
    <a href="author_dashboard.php" class="back-link">← กลับแดชบอร์ด</a>
    <hr>

    <?php while ($book = $books_result->fetch_assoc()): ?>
        <h2><?php echo htmlspecialchars($book['title']); ?></h2>
        <a href="add_chapter.php?book_id=<?= $book['book_id'] ?>" class="add-btn">เพิ่มตอนใหม่</a>

        <?php
        // ดึงตอนของแต่ละเล่ม
        $chapters_stmt = $conn->prepare("SELECT * FROM chapters WHERE book_id = ? ORDER BY chapter_number ASC");
        $chapters_stmt->bind_param("i", $book['book_id']);
        $chapters_stmt->execute();
        $chapters_result = $chapters_stmt->get_result();
        ?>

        <?php if ($chapters_result->num_rows > 0): ?>
            <ul class="chapter-list">
                <?php while ($chapter = $chapters_result->fetch_assoc()): ?>
                    <li class="chapter-item">
                        <div>
                            <strong>ตอนที่ <?php echo $chapter['chapter_number']; ?>:</strong> 
                            <?php echo htmlspecialchars($chapter['title']); ?>
                        </div>
                        <div class="chapter-actions">
                            <a href="edit_chapter.php?chapter_id=<?= $chapter['chapter_id'] ?>" class="edit">แก้ไข</a>
                            <a href="delete_chapter.php?chapter_id=<?= $chapter['chapter_id'] ?>&book_id=<?= $book['book_id'] ?>" 
                               class="delete" 
                               onclick="return confirm('คุณแน่ใจว่าต้องการลบตอนนี้?');"> ลบ</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="empty">ยังไม่มีตอนในเล่มนี้</p>
        <?php endif; ?>
        <hr>
    <?php endwhile; ?>
</div>
</body>
</html>
