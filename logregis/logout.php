<?php
session_start();            // เริ่ม session ก่อน
session_unset();            // ล้างตัวแปร session ทั้งหมด
session_destroy();          // ทำลาย session
header("Location: ../pages/Home.php"); // ย้ายกลับไปยังหน้า Home
exit();                     // ป้องกันโค้ดส่วนอื่นรันต่อ
