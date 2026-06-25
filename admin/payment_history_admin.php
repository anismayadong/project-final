<?php
session_start();
require_once '../includes/db_connect.php';

// ตรวจสอบสิทธิ์แอดมิน (หากมีระบบ login แอดมินอยู่แล้ว)
// if (!isset($_SESSION['is_admin'])) { header("Location: admin_login.php"); exit; }

$sql = "SELECT p.*, u.username 
        FROM payments p 
        JOIN users u ON p.user_id = u.user_id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ประวัติการชำระเงินทั้งหมด | Admin</title>
    <style>

            body { font-family: Arial, sans-serif; background: #f4f7fb; padding: 20px; color: #333; }
            h1 { color: #2c3e50; margin-bottom: 1rem; }
            table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
            th { background: #3498db; color: #fff; }
            .status { font-weight: bold; padding: 4px 8px; border-radius: 4px; }
            .status.success { background: #2ecc71; color: white; }
            .status.pending { background: #f1c40f; color: #333; }
            .status.failed { background: #e74c3c; color: white; }
            .btn { display:inline-block; margin-top:12px; padding:8px 16px; background:#3498db; color:#fff; text-decoration:none; border-radius:6px; }
            .btn:hover { background:#2980b9; }
    </style>
</head>
<body>
<h1> ประวัติการชำระเงินทั้งหมด</h1>

<table>
<tr>
    <th>ID</th>
    <th>ผู้ใช้</th>
    <th>แต้มที่ซื้อ</th>
    <th>ยอดเงิน (บาท)</th>
    <th>ช่องทาง</th>
    <th>สถานะ</th>
    <th>วันที่ทำรายการ</th>
    <th>วันที่ยืนยัน</th>
</tr>
<?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <?php
            $status_class = strtolower($row['status']);
            $verified = $row['verified_at'] ? date("d/m/Y H:i", strtotime($row['verified_at'])) : '-';
        ?>
        <tr>
            <td><?= $row['payment_id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= number_format($row['points_purchased']) ?></td>
            <td><?= number_format($row['amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['payment_method']) ?></td>
            <td><span class="status <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
            <td><?= date("d/m/Y H:i", strtotime($row['created_at'])) ?></td>
            <td><?= $verified ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="8">ไม่มีประวัติการชำระเงิน</td></tr>
<?php endif; ?>
</table>

<a href="user_status.php" class="btn"> กลับไปรายการรอยืนยัน</a>
<a href="../admin/admin_dashboard.php" class="btn"> กลับหน้าหลักแอดมิน</a>
</body>
</html>
