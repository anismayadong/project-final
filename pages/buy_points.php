<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$step = 0;

// ขั้นตอนการสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? '0';

    if ($step === '1') {
        $points = intval($_POST['points']);
        $payment_method = $_POST['payment_method'];

        // ราคาพิเศษบางแพ็กเกจ (ปรับได้)
        $price_list = [
            10 => 10, 
            50 => 50, 
            100 => 100,
            300 => 300, 
            500 => 500, 
            1000 => 850,
            1500 => 1200
        ];
        $amount = $price_list[$points] ?? $points;

        // เก็บข้อมูลชั่วคราวไว้ใน session
        $_SESSION['buy_points'] = [
            'points' => $points,
            'amount' => $amount,
            'method' => $payment_method
        ];

        // ไปแสดง QR / ช่องทางชำระ
        $step = 2;

    // 🔹 เปลี่ยนส่วนนี้ใน elseif ($step === '2') { ... }

} elseif ($step === '2') {
    // ผู้ใช้กดยืนยันว่าชำระแล้ว → บันทึกการสั่งซื้อ (ยังไม่เพิ่มแต้ม)
    if (!isset($_SESSION['buy_points'])) {
        $message = "ไม่พบข้อมูลการสั่งซื้อ กรุณาทำรายการใหม่";
        $step = 0;
    } else {
        $points = $_SESSION['buy_points']['points'];
        $amount = $_SESSION['buy_points']['amount'];
        $method = $_SESSION['buy_points']['method'];

        // ✅ บันทึกสถานะเป็น pending (รอแอดมินตรวจสอบ)
        $stmt = $conn->prepare("INSERT INTO payments (user_id, points_purchased, amount, payment_method, status, created_at)
                                VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iiis", $user_id, $points, $amount, $method);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['buy_points']);
        $message = "🕒 ชำระเงินแล้ว! กรุณารอการตรวจสอบจากผู้ดูแลระบบภายใน 24 ชม.";
        $step = 0;
    }
}
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ซื้อแต้ม | MangAnimeHub</title>

<!-- SweetAlert2 สำหรับ popup ยืนยัน -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root{
        --primary:#3498db; --accent:#2ecc71; --muted:#7f8c8d;
    }
    body{
        background: linear-gradient(135deg,#f3f6fb,#eef3fb);
        font-family: "Prompt", "Segoe UI", Arial, sans-serif;
        margin:0; padding:2rem; color:#222;
    }
    .card{
        max-width:760px; margin: auto; background:#fff; border-radius:14px;
        box-shadow:0 10px 30px rgba(0,0,0,0.08); padding:2rem; position:relative;
    }
    h2{text-align:center; margin:0 0 1rem; color:#223;}
    .notice{padding:12px;border-radius:10px;margin-bottom:1rem;}
    .success{background:#eaf8f1;border:1px solid #bfe9cd;color:#1b8a4a;}
    .grid{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
    .package{border-radius:10px;padding:12px;background:#fbfdff;border:1px solid #e6f0fb; display:flex;align-items:center;justify-content:space-between;}
    .package strong{color:var(--primary)}
    select, button{width:100%; padding:12px;border-radius:10px;border:1px solid #d6dbe8;font-size:16px}
    .controls{margin-top:1rem}
    .qr-wrap{text-align:center;padding:1rem 0}
    .qr-img{width:240px;height:240px;border-radius:12px; border:3px solid var(--primary); padding:10px;background:#fff; box-shadow:0 6px 18px rgba(0,0,0,0.06); display:inline-block}
    .btn-row{display:flex; gap:12px; margin-top:12px}
    .btn-row form{flex:1}
    .btn{background:var(--primary); color:#fff; border:none; padding:12px;border-radius:10px; font-weight:600; cursor:pointer}
    .btn.alt{background:#7f8c8d}
    .small{font-size:0.9rem;color:var(--muted)}
    .center{text-align:center}
    .logo{display:block;margin:10px auto 0;width:120px; opacity:0.95}
    @media (max-width:700px){ .grid{grid-template-columns:1fr} .qr-img{width:200px;height:200px} .btn-row{flex-direction:column} }
</style>
</head>
<body>
<div class="card">
    <h2> เติมแต้ม — MangAnimeHub</h2>

    <?php if ($message): ?>
        <div class="notice success"><?= htmlspecialchars($message) ?></div>
        <div class="center">
            <a href="buy_points.php" class="btn" style="display:inline-block;max-width:300px;text-decoration:none"> เติมอีกครั้ง</a>
        </div>
    <?php elseif ($step == 2 && isset($_SESSION['buy_points'])): ?>

        <?php
        // เลือกรูปตามช่องทาง
        $method = $_SESSION['buy_points']['method'];
        $amount = $_SESSION['buy_points']['amount'];
        $img = "../assets/images/fake_promptpay_qr1.png";
        if ($method === "Wallet") $img = "../assets/images/fake_promptpay_qr1.png";
        if ($method === "Credit Card") $img = "../assets/images/fake_promptpay_qr1.png";
        ?>

        <div class="center small">ช่องทางชำระ: <strong><?= htmlspecialchars($method) ?></strong></div>
        <div class="qr-wrap">
            <p class="small">ยอดชำระ: <strong style="font-size:1.25rem;"><?= number_format($amount) ?> บาท</strong></p>
            <img src="<?= $img ?>" alt="QR/Payment" class="qr-img">
            <div class="small" style="margin-top:8px">สแกน QR หรือใช้รายละเอียดชำระตามช่องทางที่เลือก</div>
        </div>

        <div class="btn-row">
            <form method="POST" onsubmit="return confirmPaid(event)">
                <input type="hidden" name="step" value="2">
                <button class="btn" type="submit"> ฉันชำระเงินแล้ว</button>
            </form>

            <form method="POST">
                <input type="hidden" name="step" value="back">
                <button class="btn alt" type="submit"> ย้อนกลับ</button>
            </form>
        </div>

    <?php else: ?>
        <!-- ฟอร์มเลือกแพ็กเกจ + ช่องทาง -->
        <form id="chooseForm" method="POST" onsubmit="return onChooseSubmit(event)">
            <input type="hidden" name="step" value="1">

            <div style="margin-bottom:8px" class="small">เลือกแพ็กเกจแต้ม (ราคาจริงจะแสดงด้านล่าง)</div>

            <div class="grid">
                <div class="package"><span> 10 แต้ม</span><strong>10 บาท</strong></div>
                <div class="package"><span> 50 แต้ม</span><strong>50 บาท</strong></div>
                <div class="package"><span> 100 แต้ม</span><strong>100 บาท</strong></div>
                <div class="package"><span> 300 แต้ม</span><strong>300 บาท</strong></div>
                <div class="package"><span> 500 แต้ม</span><strong>500 บาท</strong></div>
                <div class="package"><span> 1,000 แต้ม</span><strong>850 บาท</strong></div>
                <div class="package"><span> 1,500 แต้ม</span><strong>1200 บาท</strong></div>

            </div>

            <div style="margin-top:12px">
                <select name="points" id="points" required>
                    <option value="">-- เลือกแพ็กเกจ --</option>
                    <option value="10">10 แต้ม — 10 บาท</option>
                    <option value="50">50 แต้ม — 50 บาท</option>
                    <option value="100">100 แต้ม — 100 บาท</option>
                    <option value="300">300 แต้ม — 300 บาท</option>
                    <option value="500">500 แต้ม — 500 บาท</option>
                    <option value="1000">1,000 แต้ม — 850 บาท</option>
                    <option value="1500">1,500 แต้ม — 1200 บาท</option>

                </select>

                <select name="payment_method" id="payment_method" required>
                    <option value="">-- ช่องทางชำระ --</option>
                    <option value="PromptPay">PromptPay (QR)</option>
                    <option value="Wallet">Wallet (QR)</option>
                    <option value="Credit Card">Credit Card (Card / Gateway)</option>
                </select>

                <div class="controls">
                    <button type="submit" class="btn"> ดำเนินการต่อ</button>
                    <a href="../pages/Home.php">กลับไปหน้าแรก</a>
                </div>
                <p class="small center" style="margin-top:10px">หมายเหตุ: หน้านี้เป็นตัวอย่างการชำระ (ต้องผสานระบบ Payment Gateway จริงสำหรับการใช้งานจริง)</p>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// ฟังก์ชันเรียก popup ยืนยันก่อนไปหน้าจอชำระ
function onChooseSubmit(e) {
    e.preventDefault();
    const pts = document.getElementById('points').value;
    const method = document.getElementById('payment_method').value;
    if (!pts || !method) {
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกแพ็กเกจและช่องทางการชำระ' });
        return false;
    }
    // แสดง confirm + loading simulation
    Swal.fire({
        title: 'ยืนยันคำสั่งซื้อ',
        html: `คุณต้องการซื้อ <b>${pts} แต้ม</b> โดยชำระผ่าน <b>${method}</b> ?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันและไปชำระ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#3498db'
    }).then((res) => {
        if (res.isConfirmed) {
            // แสดงหน้าจอโหลดจำลองก่อน submit จริง (เพื่อความสมจริง)
            Swal.fire({
                title: 'กำลังเตรียมข้อมูลการชำระ...',
                html: 'กรุณารอสักครู่ ระบบจะพาคุณไปยังหน้าชำระ',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            setTimeout(() => {
                // submit ฟอร์มจริง
                document.getElementById('chooseForm').submit();
            }, 1200); // 1.2 วินาที เพื่อให้รู้สึกสมจริง
        }
    });
    return false;
}

// ฟังก์ชัน confirm เมื่อกด "ฉันชำระเงินแล้ว"
function confirmPaid(e) {
    e.preventDefault();
    Swal.fire({
        title: 'ยืนยันการชำระเงิน',
        text: 'คุณแน่ใจหรือว่าชำระเรียบร้อยแล้ว? (ตัวอย่าง: กดเพื่อยืนยัน)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ฉันชำระแล้ว',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#27ae60'
    }).then((r) => {
        if (r.isConfirmed) {
            // แสดง loading เล็กน้อยก่อน submit
            Swal.fire({
                title: 'ตรวจสอบข้อมูลการชำระ...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            setTimeout(() => {
                e.target.submit();
            }, 1200);
        }
    });
    return false;
}
</script>
</body>
</html>
