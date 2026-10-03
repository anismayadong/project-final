<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION["author_id"])) {
    header("Location: ../author/author_login.php");
    exit;
}

$author_id = $_SESSION["author_id"];
$authorName = $_SESSION["author_name"] ?? "ผู้แต่ง";

// ==========================
// 1) รายได้รวมทั้งหมด
// ==========================
$sql_income = "SELECT COALESCE(SUM(author_share), 0) AS total_income 
               FROM revenue_share 
               WHERE author_id = ?";
$stmt = $conn->prepare($sql_income);
$stmt->bind_param("i", $author_id);
$stmt->execute();
$result_income = $stmt->get_result();
$total_income = $result_income->fetch_assoc()["total_income"] ?? 0;

// ==========================
// 2) รายได้ตามหนังสือ
// ==========================
$sql_books = "
    SELECT b.title, SUM(r.author_share) AS income
    FROM revenue_share r
    JOIN books b ON r.book_id = b.book_id
    WHERE r.author_id = ?
    GROUP BY b.book_id, b.title
    ORDER BY income DESC";
$stmt_books = $conn->prepare($sql_books);
$stmt_books->bind_param("i", $author_id);
$stmt_books->execute();
$result_books = $stmt_books->get_result();

// ==========================
// 3) รายได้ตามตอน
// ==========================
$sql_chapters = "
    SELECT c.chapter_number, c.title AS chapter_title, SUM(r.author_share) AS income
    FROM revenue_share r
    JOIN chapters c ON r.chapter_id = c.chapter_id
    WHERE r.author_id = ?
    GROUP BY c.chapter_id, c.chapter_number, c.title
    ORDER BY income DESC";
$stmt_chapters = $conn->prepare($sql_chapters);
$stmt_chapters->bind_param("i", $author_id);
$stmt_chapters->execute();
$result_chapters = $stmt_chapters->get_result();

// ==========================
// 4) ประวัติการปลดล็อคล่าสุด
// ==========================
$sql_unlocks = "
    SELECT r.created_at, u.username, b.title AS book_title, 
           c.chapter_number, c.title AS chapter_title, 
           r.points_spent, r.author_share
    FROM revenue_share r
    JOIN users u ON r.user_id = u.user_id
    JOIN books b ON r.book_id = b.book_id
    JOIN chapters c ON r.chapter_id = c.chapter_id
    WHERE r.author_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10";
$stmt_unlocks = $conn->prepare($sql_unlocks);
$stmt_unlocks->bind_param("i", $author_id);
$stmt_unlocks->execute();
$result_unlocks = $stmt_unlocks->get_result();

// ==========================
// 5) รายได้สรุปรายเดือน (กราฟ)
// ==========================
$sql_monthly = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(author_share) AS monthly_income
    FROM revenue_share
    WHERE author_id = ?
    GROUP BY month
    ORDER BY month ASC";
$stmt_monthly = $conn->prepare($sql_monthly);
$stmt_monthly->bind_param("i", $author_id);
$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result();

$labels = [];
$data = [];
while ($row = $result_monthly->fetch_assoc()) {
    $labels[] = $row['month'];
    $data[] = $row['monthly_income'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายได้ของนักเขียน</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 30px; }
        .dashboard { max-width: 900px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
        .summary { margin-bottom: 20px; font-size: 18px; }
        canvas { margin-top: 20px; }
        a.btn { display: inline-block; margin-top: 15px; padding: 10px 20px; background: #4CAF50; color: white; border-radius: 5px; text-decoration: none; }
        a.btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>รายได้ของคุณ <?php echo htmlspecialchars($authorName); ?></h1>
        <div class="summary">
            รายได้รวมทั้งหมด: <b><?php echo number_format($total_income, 2); ?> บาท</b>
        </div>

        <h2>📚 รายได้ตามหนังสือ</h2>
        <table>
            <tr><th>ชื่อเรื่อง</th><th>รายได้ (บาท)</th></tr>
            <?php if ($result_books->num_rows > 0): while($row = $result_books->fetch_assoc()): ?>
                <tr><td><?php echo htmlspecialchars($row["title"]); ?></td><td><?php echo number_format($row["income"], 2); ?></td></tr>
            <?php endwhile; else: ?>
                <tr><td colspan="2">ยังไม่มีรายได้จากหนังสือ</td></tr>
            <?php endif; ?>
        </table>

        <h2>📖 รายได้ตามตอน</h2>
        <table>
            <tr><th>ตอนที่</th><th>ชื่อ</th><th>รายได้ (บาท)</th></tr>
            <?php if ($result_chapters->num_rows > 0): while($row = $result_chapters->fetch_assoc()): ?>
                <tr><td><?php echo $row["chapter_number"]; ?></td><td><?php echo htmlspecialchars($row["chapter_title"]); ?></td><td><?php echo number_format($row["income"], 2); ?></td></tr>
            <?php endwhile; else: ?>
                <tr><td colspan="3">ยังไม่มีรายได้จากตอน</td></tr>
            <?php endif; ?>
        </table>

        <h2>🕒 ประวัติการปลดล็อคล่าสุด</h2>
        <table>
            <tr><th>วันที่</th><th>ผู้ใช้</th><th>หนังสือ</th><th>ตอน</th><th>แต้มใช้</th><th>รายได้ (บาท)</th></tr>
            <?php if ($result_unlocks->num_rows > 0): while($row = $result_unlocks->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                    <td>ตอน <?php echo $row['chapter_number']; ?>: <?php echo htmlspecialchars($row['chapter_title']); ?></td>
                    <td><?php echo $row['points_spent']; ?> แต้ม</td>
                    <td><?php echo number_format($row['author_share'], 2); ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6">ยังไม่มีการปลดล็อค</td></tr>
            <?php endif; ?>
        </table>

        <h2>📅 รายได้รายเดือน</h2>
        <canvas id="monthlyChart" height="100"></canvas>

        <a href="../author/author_dashboard.php" class="btn"> กลับหน้าหลัก</a>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('monthlyChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'รายได้ (บาท)',
            data: <?php echo json_encode($data); ?>,
            backgroundColor: '#2ecc71'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
