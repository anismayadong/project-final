<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

$sql = "
SELECT b.*, a.full_name AS author
FROM books b
LEFT JOIN authors a ON b.author_id = a.author_id
ORDER BY b.created_at DESC";
$result = $conn->query($sql);

$username = htmlspecialchars($_SESSION["username"]);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการหนังสือ - ผู้ดูแลระบบ</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f6f9;
            color: #333;
        }
        header {
            background: #2c3e50;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h2 { margin: 0; }
        header a {
            color: #fff;
            margin-left: 15px;
            text-decoration: none;
        }
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            border-left: 5px solid #3498db;
            padding-left: 10px;
            color: #3498db;
        }
        a.btn {
            display: inline-block;
            margin: 10px 0;
            padding: 8px 15px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        a.btn:hover { background: #2ecc71; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #f1f1f1;
            font-weight: bold;
        }
        img {
            border-radius: 6px;
            max-height: 120px;
        }
        .actions a {
    display: inline-block;   /* บังคับเป็นกล่อง */
    padding: 6px 12px;       /* ขยายพื้นที่ในปุ่ม */
    margin: 5px;             /* เว้นระยะรอบปุ่ม */
    border-radius: 5px;
    text-decoration: none;
    color: #fff;
    font-size: 13px;
}
        .edit { background: #f39c12; }
        .delete { background: #e74c3c; }
        .edit:hover { background: #e67e22; }
        .delete:hover { background: #c0392b; }
    </style>
</head>
<body>
    <header>
        <h2> แผงควบคุมผู้ดูแลระบบ</h2>
        <div>
            <span>สวัสดี คุณ <?php echo $username; ?></span>
            <a href="../admin/admin_dashboardv2.php"> รายได้รวม</a>
            <a href="../admin/user_status.php">การยืนยันการชำระเงิน</a>
            <a href="../logregis/logout.php" class="logout"onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?');" > ออกจากระบบ</a>
        </div>
    </header>

    <div class="container">
        <h1> จัดการหนังสือ</h1>
        <a href="../author/upload_book.php" class="btn">+ เพิ่มหนังสือใหม่</a>

        <table>
            <thead>
                <tr>
                    <th>ภาพปก</th>
                    <th>ชื่อเรื่อง</th>
                    <th>ผู้แต่ง</th>
                    <th>แนว</th>
                    <th>คำอธิบาย</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($book = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="ปก <?php echo htmlspecialchars($book['title']); ?>">
                                <?php else: ?>
                                    ไม่มีภาพ
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['genre']); ?></td>
                            <td style="text-align:left;"><?php echo nl2br(htmlspecialchars($book['description'])); ?></td>
                            <td class="actions">
                                <a class="edit" href="../admin/edit_book.php?id=<?php echo $book['book_id']; ?>">แก้ไข</a>
                                <a class="delete" href="../admin/delete_book.php?id=<?php echo $book['book_id']; ?>" onclick="return confirm('คุณแน่ใจว่าต้องการลบหนังสือนี้?');">ลบ</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">ยังไม่มีหนังสือในระบบ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="container">
        <h1> จัดการเกมสะสมแต้ม</h1>
        <a href="../game/upload_game.php" class="btn">+ เพิ่มเกมใหม่</a>

        <?php
        $sql_games = "SELECT * FROM games ORDER BY created_at DESC";
        $result_games = $conn->query($sql_games);
        ?>

        <table>
            <thead>
                <tr>
                    <th>ชื่อเกม</th>
                    <th>คำอธิบาย</th>
                    <th>ระดับความยาก</th>
                    <th>URL เกม</th>
                    <th>ภาพหน้าปก</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_games && $result_games->num_rows > 0): ?>
                    <?php while ($game = $result_games->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['game_name']); ?></td>
                            <td style="text-align:left;"><?php echo nl2br(htmlspecialchars($game['description'])); ?></td>
                            <td><?php echo htmlspecialchars($game['difficulty']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($game['game_url']); ?>" target="_blank">🔗 เปิดเกม</a></td>
                            <td>
                                <?php if (!empty($game['cover_image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($game['cover_image']); ?>" alt="หน้าปกเกม">
                                <?php else: ?>
                                    ไม่มีภาพ
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a class="edit" href="../game/edit_game.php?id=<?php echo $game['game_id']; ?>">แก้ไข</a>
                                <a class="delete" href="../game/delete_game.php?id=<?php echo $game['game_id']; ?>" onclick="return confirm('คุณแน่ใจว่าต้องการลบเกมนี้?');">ลบ</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">ยังไม่มีเกมในระบบ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
