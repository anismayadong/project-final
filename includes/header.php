<?php
//เริ่มต้นการทำงานของเซสชัน ใช้เก็บข้อมูลของผู้ใช้ไว้ชั่วคราวในระหว่างการเข้าชมเว็บไซต์
session_start();
include_once '../includes/db_connect.php';

$userPoints = 0;

//ตรวจสอบว่ามีตัวแปร user_id ในเซสชันหรือไม่ ถ้ามีแสดงว่าผู้ใช้ได้เข้าสู่ระบบแล้ว
if (isset($_SESSION["user_id"])) {
    //$user_id = $_SESSION["user_id"];: ถ้าผู้ใช้เข้าสู่ระบบแล้ว ให้นำค่า user_id จากเซสชันมาเก็บไว้ในตัวแปร $user_id
    $user_id = $_SESSION["user_id"];

    // ดึงแต้มจากฐานข้อมูลใหม่ทุกครั้งที่โหลด header
    //: เตรียมคำสั่ง SQL สำหรับดึงข้อมูลแต้มจากตาราง points โดยใช้ user_id เป็นเงื่อนไขเพื่อป้องกัน SQL Injection
    //: ผูกค่าตัวแปร $user_id เข้ากับคำสั่ง SQL ที่เตรียมไว้ โดย "i" หมายถึงตัวแปรนี้เป็นจำนวนเต็ม (integer)

    //: รับผลลัพธ์ที่ได้จากการประมวลผลคำสั่ง SQL มาเก็บไว้ในตัวแปร $result
    $stmt = $conn->prepare("SELECT points FROM points WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    //: ตรวจสอบว่ามีข้อมูลจากผลลัพธ์หรือไม่ ถ้ามีจะดึงข้อมูลมาทีละแถวและเก็บไว้ในตัวแปร $row
    //: กำหนดค่าแต้มของผู้ใช้ที่ได้จากฐานข้อมูลมาเก็บไว้ในตัวแปร $userPoints
    if ($row = $result->fetch_assoc()) {
        $userPoints = $row['points'];

        // อัปเดตแต้มใน SESSION ด้วย (เพื่อให้หน้าอื่นใช้ได้ด้วยถ้ามี)
        $_SESSION['points'] = $userPoints;
    }
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MangAnime Hub | แพลตฟอร์มอ่านการ์ตูนมังงะและอนิเมะ</title>
    <link rel="stylesheet" href="../assets/css/styles1.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body>
    <header>
        <nav class="main-nav">
            <div class="logo">
                <a href="../pages/Home.php"><h1 >MangAnime Hub</h1></a>
            </div>
            <div class="nav-links">
                <a href="../pages/Home.php" <?php echo (basename($_SERVER['PHP_SELF']) == '../pages/Home.php') ? 'class="active"' : ''; ?>>หน้าหลัก</a>
                <a href="../pages/manga.php" <?php echo (basename($_SERVER['PHP_SELF']) == '../pages/manga.php') ? 'class="active"' : ''; ?>>มังงะ</a>
                <a href="../pages/games.php" <?php echo (basename($_SERVER['PHP_SELF']) == '../pages/games.php') ? 'class="active"' : ''; ?>>เกม</a>
                <a href="../pages/ranking.php" <?php echo (basename($_SERVER['PHP_SELF']) == '..pages/ranking.php') ? 'class="active"' : ''; ?>>อันดับ</a>
            </div>
            <div class="user-controls">
                <div class="search-bar">
                    
                </div>
                <div class="user-menu">
                    <?php if (isset($_SESSION["user_id"])): ?>
                        <span class="points-display">
    แต้ม: <?php echo number_format($userPoints); ?> <i class="fas fa-coins"></i>
</span>

                        <?php if (isset($_SESSION['user_id'])): ?>
    <a href="../pages/buy_points.php">🪙 ซื้อแต้ม</a>
<?php endif; ?>
                        <a href="../user/profile.php" class="profile-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
                        <a href="../logregis/logout.php"  class="register-btn" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?');">ออกจากระบบ</a>
                    <?php else: ?>
                        <form method="post" action="../logregis/process_login.php" class="header-login-form">
                            <input type="text" name="login" class="login-btn" placeholder="ชื่อผู้ใช้หรืออีเมล" required>
                            <input type="password" name="password" class="login-btn" placeholder="รหัสผ่าน" required>
                            <button type="submit" class="login-btn">เข้าสู่ระบบ</button>
                            <a href="../logregis/register.php" class="register-btn">สมัครสมาชิก</a>
                            <a href="../author/author_login.php" class="register-btn">เข้าสู่ระบบผู้แต่ง</a>

                        </form>
                        <?php if (isset($_SESSION["login_error"])): ?>
                            <p style="color:red;"><?php echo $_SESSION["login_error"]; unset($_SESSION["login_error"]); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
