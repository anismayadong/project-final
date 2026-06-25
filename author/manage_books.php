<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION["author_id"])) {
    header("Location: ../author/author_login.php");
    exit;
}

$author_id = $_SESSION["author_id"];

// ดึงข้อมูลหนังสือทั้งหมดของผู้แต่งนี้
$sql = "SELECT * FROM books WHERE author_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $author_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการหนังสือ</title>
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
            max-width: 950px;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .add-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #3498db;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .add-btn:hover { background: #2980b9; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table th, table td {
            padding: 12px 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        table th {
            background: #3498db;
            color: #fff;
        }
        table tr:nth-child(even) { background: #f9f9f9; }
        table img {
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .actions a {
            margin: 0 5px;
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
            transition: background 0.3s;
        }
        .edit-btn { background: #f39c12; }
        .edit-btn:hover { background: #d35400; }
        .delete-btn { background: #e74c3c; }
        .delete-btn:hover { background: #c0392b; }
        .chapter-btn { background: #2ecc71; }
        .chapter-btn:hover { background: #27ae60; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            text-decoration: none;
            color: #3498db;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>หนังสือของคุณ</h1>
    <a href="../author/add_book.php" class="add-btn"> เพิ่มหนังสือใหม่</a>

    <table>
        <tr>
            <th>ชื่อหนังสือ</th>
            <th>หมวดหมู่</th>
            <th>ภาพปก</th>
            <th>การจัดการ</th>
        </tr>
        <?php while ($book = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($book['title']) ?></td>
                <td><?= isset($book['genre']) ? htmlspecialchars($book['genre']) : '-' ?></td>
                <td>
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($book['cover_image']) ?>" width="80">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="../author/edit_book_author.php?id=<?= $book['book_id'] ?>" class="edit-btn">แก้ไข</a>
                    <a href="../author/author_delete_book.php?id=<?= $book['book_id'] ?>" class="delete-btn" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบหนังสือเล่มนี้?')">ลบ</a>
                    <a href="../author/manage_chapters.php?book_id=<?= $book['book_id'] ?>" class="chapter-btn">ตอน</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="../author/author_dashboard.php" class="back-link">กลับหน้าหลัก</a>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
