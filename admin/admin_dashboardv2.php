<?php
include_once '../includes/db_connect.php';
session_start();

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

/* -----------------------------------------------------
   1) รายได้ของแอดมินจาก "การซื้อแต้ม"
   ----------------------------------------------------- */
$sql_points = "SELECT SUM(amount * 0.30) AS admin_income_points
               FROM payments WHERE status = 'success'";
$res_points = $conn->query($sql_points);
$row_points = $res_points->fetch_assoc();
$admin_income_points = $row_points['admin_income_points'] ?? 0;

/* -----------------------------------------------------
   2) รายได้ของแอดมินจาก "การปลดล็อคตอน"
   ----------------------------------------------------- */
$sql_unlock = "SELECT SUM(admin_share) AS admin_income_unlock
               FROM revenue_share";
$res_unlock = $conn->query($sql_unlock);
$row_unlock = $res_unlock->fetch_assoc();
$admin_income_unlock = $row_unlock['admin_income_unlock'] ?? 0;

/* -----------------------------------------------------
   3) รวมรายได้ทั้งหมด
   ----------------------------------------------------- */
$total_admin_income = $admin_income_points + $admin_income_unlock;

/* -----------------------------------------------------
   4) รายได้ของนักเขียนแต่ละคน
   ----------------------------------------------------- */
$sql_authors = "
    SELECT 
    a.full_name, 
    COALESCE(SUM(r.author_share),0) AS income
    FROM authors a
    LEFT JOIN revenue_share r ON a.author_id = r.author_id
    GROUP BY a.author_id
    ORDER BY income DESC";
$res_authors = $conn->query($sql_authors);

/* -----------------------------------------------------
   5) รายได้ตามหนังสือ
   ----------------------------------------------------- */
$sql_books = "
    SELECT b.title, COALESCE(SUM(r.admin_share + r.author_share),0) AS total_income
    FROM books b
    LEFT JOIN revenue_share r ON b.book_id = r.book_id
    GROUP BY b.book_id
    ORDER BY total_income DESC";
$res_books = $conn->query($sql_books);

/* -----------------------------------------------------
   6) รายได้ตามตอน (Chapters)
   ----------------------------------------------------- */
$sql_chapters = "
    SELECT 
        b.title AS book_title,
        c.chapter_number,
        c.title AS chapter_title,
        COALESCE(SUM(r.admin_share + r.author_share),0) AS total_income
    FROM chapters c
    JOIN books b ON c.book_id = b.book_id
    LEFT JOIN revenue_share r ON c.chapter_id = r.chapter_id
    GROUP BY c.chapter_id
    ORDER BY total_income DESC LIMIT 10";
$res_chapters = $conn->query($sql_chapters);

/* -----------------------------------------------------
   7) รายได้รายเดือน (Admin)
   ----------------------------------------------------- */
$sql_monthly = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(admin_share) AS income
    FROM revenue_share
    GROUP BY month
    ORDER BY month ASC";
$res_monthly = $conn->query($sql_monthly);

$labels = [];
$data = [];
if ($res_monthly && $res_monthly->num_rows > 0) {
    while ($row = $res_monthly->fetch_assoc()) {
        $labels[] = $row['month'];
        $data[] = $row['income'];
    }
}

/* -----------------------------------------------------
   8) รายละเอียดการปลดล็อคตอน (Unlock Details)
   ----------------------------------------------------- */
$sql_unlock_details = "
    SELECT 
        r.created_at,
        u.username,
        b.title AS book_title,
        c.chapter_number,
        c.title AS chapter_title,
        r.points_spent,
        r.admin_share,
        r.author_share
    FROM revenue_share r
    JOIN users u ON r.user_id = u.user_id
    JOIN books b ON r.book_id = b.book_id
    JOIN chapters c ON r.chapter_id = c.chapter_id
    ORDER BY r.created_at DESC
    LIMIT 10";
$res_unlock_details = $conn->query($sql_unlock_details);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แดชบอร์ดรายได้แอดมิน</title>
<style>
body { font-family: Arial, sans-serif; background: #f6f8fa; margin: 0; padding: 30px; }
.dashboard { max-width: 1100px; margin: auto; background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
h1 { text-align: center; color: #333; }
h2 { margin-top: 40px; color: #2c3e50; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
th { background: #eaf2f8; }
.summary { background: #e8f5e9; padding: 15px; border-radius: 10px; margin-top: 20px; }
.summary p { margin: 5px 0; font-size: 17px; }
canvas { margin-top: 20px; }
</style>
</head>
<body>

<div class="dashboard">
    <h1> สรุปรายได้ของแอดมิน</h1>

    <div class="summary">
        <p> <b>รายได้รวมทั้งหมด:</b> <?php echo number_format($total_admin_income, 2); ?> บาท</p>
        <p> จากการซื้อแต้ม: <?php echo number_format($admin_income_points, 2); ?> บาท</p>
        <p> จากการปลดล็อคตอน: <?php echo number_format($admin_income_unlock, 2); ?> บาท</p>
    </div>

    <h2> รายได้แอดมินรายเดือน</h2>
    <canvas id="chartMonthly" height="100"></canvas>

    <h2> รายได้ของนักเขียน</h2>
    <table>
        <thead><tr><th>ชื่อนักเขียน</th><th>รายได้ (บาท)</th></tr></thead>
        <tbody>
        <?php if ($res_authors->num_rows > 0): ?>
            <?php while($row = $res_authors->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo number_format($row['income'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2">ยังไม่มีข้อมูลรายได้</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2> รายได้ตามหนังสือ</h2>
    <table>
        <thead><tr><th>ชื่อหนังสือ</th><th>รายได้รวม (บาท)</th></tr></thead>
        <tbody>
        <?php if ($res_books->num_rows > 0): ?>
            <?php while($row = $res_books->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo number_format($row['total_income'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2">ยังไม่มีข้อมูลรายได้จากหนังสือ</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2> รายได้ตามตอน (Chapters)</h2>
    <table>
        <thead><tr><th>ชื่อหนังสือ</th><th>ตอนที่</th><th>ชื่อตอน</th><th>รายได้รวม (บาท)</th></tr></thead>
        <tbody>
        <?php if ($res_chapters->num_rows > 0): ?>
            <?php while($row = $res_chapters->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                    <td><?php echo $row['chapter_number']; ?></td>
                    <td><?php echo htmlspecialchars($row['chapter_title']); ?></td>
                    <td><?php echo number_format($row['total_income'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">ยังไม่มีข้อมูลรายได้ของตอน</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2> รายละเอียดการปลดล็อคตอน (ล่าสุด)</h2>
    <table>
        <thead>
            <tr>
                <th>วันที่</th>
                <th>ผู้ใช้</th>
                <th>หนังสือ</th>
                <th>ตอน</th>
                <th>แต้มที่ใช้</th>
                <th>รายได้แอดมิน</th>
                <th>รายได้นักเขียน</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($res_unlock_details->num_rows > 0): ?>
            <?php while($row = $res_unlock_details->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                    <td>ตอนที่ <?php echo $row['chapter_number']; ?>: <?php echo htmlspecialchars($row['chapter_title']); ?></td>
                    <td><?php echo $row['points_spent']; ?> แต้ม</td>
                    <td><?php echo number_format($row['admin_share'], 2); ?> บาท</td>
                    <td><?php echo number_format($row['author_share'], 2); ?> บาท</td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">ยังไม่มีข้อมูลการปลดล็อค</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== Chart.js ====== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartMonthly');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'รายได้ต่อเดือน (บาท)',
            data: <?php echo json_encode($data); ?>,
            backgroundColor: '#3498db'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
<a href="../admin/admin_dashboard.php" >กลับหน้าหลัก</a>
</body>
</html>
