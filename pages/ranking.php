<?php
include_once '../includes/db_connect.php';
include_once '../includes/header.php';

// รับค่าการจัดอันดับจาก URL
$sort = $_GET['sort'] ?? 'views'; // default = views

// กำหนด ORDER BY ตามค่าที่เลือก
switch($sort) {
    case 'rating':
        $order_by = "b.rating DESC, b.views DESC";
        break;
    case 'views':
    default:
        $order_by = "b.views DESC, b.rating DESC";
        break;
}

// ดึงข้อมูลหนังสือพร้อมผู้แต่งเรียงตามการจัดอันดับ
$sql = "
SELECT b.book_id, b.title, b.views, b.rating, a.full_name AS author
FROM books b
LEFT JOIN authors a ON b.author_id = a.author_id
ORDER BY $order_by
LIMIT 100
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>อันดับหนังสือ | MangAnime Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
<main class="ranking-page">
    <h2>🏆 อันดับหนังสือ</h2>

    <!-- แบบเลือกการจัดอันดับ -->
    <form method="get" id="sortForm">
        <label for="sortSelect">เรียงตาม:</label>
        <select id="sortSelect" name="sort" onchange="document.getElementById('sortForm').submit()">
            <option value="views" <?php if($sort == 'views') echo 'selected'; ?>>จำนวนผู้ชมมากที่สุด</option>
            <option value="rating" <?php if($sort == 'rating') echo 'selected'; ?>>เรตติ้งสูงสุด</option>
        </select>
    </form>

    <table class="ranking-table">
        <thead>
            <tr>
                <th>อันดับ</th>
                <th>ชื่อเรื่อง</th>
                <th>ผู้แต่ง</th>
                <th>จำนวนผู้ชม</th>
                <th>เรตติ้ง</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$rank}</td>";
                    echo "<td><a href='../pages/book_detail.php?book_id=" . $row['book_id'] . "'>" . htmlspecialchars($row['title']) . "</a></td>";
                    echo "<td>" . htmlspecialchars($row['author'] ?? 'ไม่ระบุ') . "</td>";
                    echo "<td>" . number_format($row['views']) . "</td>";
                    echo "<td>" . number_format($row['rating'], 1) . " <i class='fas fa-star'></i></td>";
                    echo "</tr>";
                    $rank++;
                }
            } else {
                echo "<tr><td colspan='5'>ไม่มีข้อมูลการจัดอันดับ</td></tr>";
            }
            ?>
        </tbody>
    </table>
</main>
</body>
</html>
<?php
// เรียกใช้ไฟล์ footer.php
include_once '../includes/footer.php';
?>