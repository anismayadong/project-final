<?php
session_start();
require_once '../includes/db_connect.php';

<<<<<<< HEAD
// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}
=======
// สมมุติว่ามีระบบ login แอดมินแล้ว
// if (!isset($_SESSION['is_admin'])) { header("Location: admin_login.php"); exit; }
>>>>>>> c741e256a0c1ebb368512bbb7360e64e5260d250

// ✅ เมื่อแอดมินกดยืนยัน
if (isset($_GET['approve'])) {
    $payment_id = intval($_GET['approve']);

    // ดึงข้อมูลการชำระ
    $sql = "SELECT * FROM payments WHERE payment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();

    if ($payment && $payment['status'] === 'pending') {
        $user_id = $payment['user_id'];
        $points = $payment['points_purchased'];

        // ✅ เพิ่มแต้มให้ผู้ใช้
        $upd = $conn->prepare("UPDATE points SET points = points + ? WHERE user_id = ?");
        $upd->bind_param("ii", $points, $user_id);
        $upd->execute();
        if ($upd->affected_rows === 0) {
            $ins = $conn->prepare("INSERT INTO points (user_id, points) VALUES (?, ?)");
            $ins->bind_param("ii", $user_id, $points);
            $ins->execute();
            $ins->close();
        }
        $upd->close();

        // ✅ อัปเดตสถานะใน payments
        $done = $conn->prepare("UPDATE payments SET status='success', verified_at=NOW() WHERE payment_id=?");
        $done->bind_param("i", $payment_id);
        $done->execute();

        echo "<script>alert('ยืนยันการชำระสำเร็จ! เพิ่มแต้มให้ผู้ใช้แล้ว'); window.location='user_status.php';</script>";
        exit;
    }
}

// ✅ ดึงข้อมูลการชำระทั้งหมดที่ยังไม่อนุมัติ
$sql = "SELECT p.*, 
        u.username
        FROM payments p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการการชำระเงิน | Admin</title>
<style>
body { font-family: Arial, sans-serif; background: #f6f9fc; padding: 20px; }
h1 { color: #2c3e50; }
table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background: #3498db; color: #fff; }
a.btn { background: #2ecc71; color: white; padding: 6px 12px; text-decoration: none; border-radius: 5px; }
a.btn:hover { background: #27ae60; }
</style>
</head>
<body>
<h1> รายการรอยืนยันการชำระ</h1>
<a href="payment_history_admin.php" class="btn"> ดูประวัติการชำระทั้งหมด</a>


<table>
<tr>
    <th>ID</th>
    <th>ผู้ใช้</th>
    <th>จำนวนแต้ม</th>
    <th>ยอดเงิน (บาท)</th>
    <th>ช่องทาง</th>
    <th>สถานะ</th>
    <th>วันที่ทำรายการ</th>
    <th>การจัดการ</th>
   
</tr>
<?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['payment_id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= $row['points_purchased'] ?></td>
            <td><?= number_format($row['amount'],2) ?></td>
            <td><?= htmlspecialchars($row['payment_method']) ?></td>
            <td><b style="color:orange;">รอยืนยัน</b></td>
            <td><?= $row['created_at'] ?></td>
            <td><a href="?approve=<?= $row['payment_id'] ?>" class="btn">✅ ยืนยัน</a></td>
           
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="8">ไม่มีรายการรอยืนยัน</td></tr>
<?php endif; ?>
</table>
<a href="../admin/admin_dashboard.php">กลับหน้าหลัก</a>
</body>
</html>
