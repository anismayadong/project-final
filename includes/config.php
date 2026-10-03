<?php
//  ค่าตั้งต้นของระบบ
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "manganimehub";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}
// กำหนด charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");

// ราคาปลดล็อกต่อ 1 ตอน
$UNLOCK_COST = 50;

// จำนวนตอนฟรีเริ่มต้น
$FREE_CHAPTERS = 6;

// สัดส่วนรายได้
$AUTHOR_SHARE = 0.70;  // 70% นักเขียน
$ADMIN_SHARE  = 0.30;  // 30% แอดมิน
?>
