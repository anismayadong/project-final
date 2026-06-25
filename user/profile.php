<?php
session_start();
include_once '../includes/db_connect.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/Home.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$username = htmlspecialchars($_SESSION["username"] ?? 'N/A');
$full_name = htmlspecialchars($_SESSION["full_name"] ?? 'N/A');
$phone_number = htmlspecialchars($_SESSION["phone_number"] ?? 'N/A');
$email = htmlspecialchars($_SESSION["email"] ?? 'N/A');

// ดึงแต้ม
$current_points = 0;
$stmt = $conn->prepare("SELECT points FROM points WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($points_result);
if ($stmt->fetch()) {
    $current_points = $points_result;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ผู้ใช้ | MangAnimeHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            color: #333;
        }

        .profile-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 700px;
        }

        h2, h3 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .section-toggle {
            margin-bottom: 20px;
        }

        .section-toggle button {
            padding: 8px 16px;
            margin: 5px;
            border: none;
            background-color: #3498db;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        .section-toggle button:hover {
            background-color: #2980b9;
        }

        .toggle-section {
            display: none;
            margin-top: 15px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            background-color: #ecf0f1;
            margin-bottom: 8px;
            padding: 12px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        li strong { color: #2980b9; }
        li small { color: #7f8c8d; font-size: 0.9em; }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-top: 25px;
        }

        .buttons a {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .buttons a.logout { background-color: #e74c3c; }
        .buttons a:hover { background-color: #2980b9; }
        .buttons a.logout:hover { background-color: #c0392b; }
    </style>
</head>
<body>
<div class="profile-container">
    <h2>ข้อมูลโปรไฟล์ผู้ใช้</h2>
    <p><strong>ชื่อผู้ใช้:</strong> <?= $username ?></p>
    <p><strong>ชื่อ-นามสกุล:</strong> <?= $full_name ?></p>
    <p><strong>เบอร์โทร:</strong> <?= $phone_number ?></p>
    <p><strong>อีเมล:</strong> <?= $email ?></p>
    <p><strong>แต้มปัจจุบัน:</strong> <?= number_format($current_points) ?> แต้ม</p>

    <div class="section-toggle">
        <button onclick="toggleSection('unlock-history')"> ประวัติการปลดล็อกตอน</button>
        <button onclick="toggleSection('purchase-history')"> ประวัติการซื้อแต้ม</button>
        <button onclick="toggleSection('game-history')"> ประวัติการเล่นเกม</button> <!-- ✅ เพิ่ม -->
    </div>

    <div id="unlock-history" class="toggle-section">
        <h3> ประวัติการปลดล็อกตอน</h3>
        <ul>
        <?php
        $stmt = $conn->prepare("SELECT uh.chapter_id, c.title AS chapter_title, b.title AS book_title, uh.unlocked_at
                                FROM unlock_history uh
                                JOIN chapters c ON uh.chapter_id = c.chapter_id
                                JOIN books b ON c.book_id = b.book_id
                                WHERE uh.user_id = ? ORDER BY uh.unlocked_at DESC");
        $total_unlock = 0;

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $total_unlock++;
                    echo "<li><strong>" . htmlspecialchars($row["chapter_title"]) . "</strong> <em>จาก</em> " .
                         htmlspecialchars($row["book_title"]) .
                         "<small> ปลดเมื่อ " . date("d/m/Y H:i", strtotime($row["unlocked_at"])) . "</small></li>";
                }
            } else {
                echo "<li>ยังไม่มีประวัติการปลดล็อกตอน</li>";
            }
            $stmt->close();
        }
        ?>
        </ul>
        <p><strong>จำนวนตอนที่ปลดล็อก:</strong> <?= $total_unlock ?> ตอน</p>
    </div>

    <div id="purchase-history" class="toggle-section">
    <h3>💳 ประวัติการซื้อแต้ม</h3>
    <ul>
    <?php
// ✅ ดึงเฉพาะ 10 รายการล่าสุด พร้อมช่องทางชำระเงิน
$stmt = $conn->prepare("SELECT points_purchased AS points, amount, payment_method, status, created_at
                        FROM payments
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                        LIMIT 10");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($purchase = $result->fetch_assoc()) {
            $status_color = match ($purchase['status']) {
                'success' => '#2ecc71',  // เขียว
                'pending' => '#f1c40f',  // เหลือง
                'failed'  => '#e74c3c',  // แดง
                default   => '#7f8c8d'
            };

            echo "<li>
                    <strong>ซื้อ:</strong> " . htmlspecialchars($purchase['points']) . " แต้ม 
                    <em>(" . number_format($purchase['amount'], 2) . " บาท)</em><br>
                    <small>จ่ายผ่าน: " . htmlspecialchars($purchase['payment_method']) . " | 
                    " . date('d/m/Y H:i', strtotime($purchase['created_at'])) . "</small><br>
                    <small style='color: $status_color;'>สถานะ: " . htmlspecialchars($purchase['status']) . "</small>
                  </li>";
        }
    } else {
        echo "<li>ยังไม่มีประวัติการซื้อแต้ม</li>";
    }
    $stmt->close();
}
?>

    </ul>
</div>

<div id="game-history" class="toggle-section">
    <h3> ประวัติการเล่นเกม</h3>
    <ul>
    <?php
    // ✅ ดึงข้อมูลการเล่น 10 ครั้งล่าสุด
    $stmt = $conn->prepare("SELECT g.game_name, g.difficulty, h.points_earned, h.played_at
                            FROM game_play_history h
                            JOIN games g ON h.game_id = g.game_id
                            WHERE h.user_id = ?
                            ORDER BY h.played_at DESC
                            LIMIT 10");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $difficulty_label = match ($row['difficulty']) {
                    'easy'   => 'ง่าย',
                    'medium' => 'ปานกลาง',
                    'hard'   => 'ยาก',
                    default  => '-'
                };

                echo "<li>
                        <strong style='font-size:1.1rem; color:#2980b9;'>" . htmlspecialchars($row['game_name']) . "</strong> 
                        <em>ระดับ:</em> $difficulty_label <br>
                        <span style='display:block; font-size:1.05rem; color:#2c3e50; margin-top:6px;'>
     ได้รับแต้ม: <strong style='color:#27ae60;'>+{$row['points_earned']}</strong> |
    " . date('d/m/Y H:i', strtotime($row['played_at'])) . "
</span>

                      </li>";
            }
        } else {
            echo "<li>ยังไม่มีประวัติการเล่นเกม</li>";
        }
        $stmt->close();
    }
    ?>
    </ul>
</div>

    <div class="buttons">
        <a href="../pages/Home.php"> หน้าหลัก</a>
        <a href="../pages/buy_points.php"> ซื้อแต้มเพิ่มเติม</a>
        <a href="../pages/profile_edit.php">แก้ไขโปรไฟล์</a>
        <a href="../logregis/logout.php" class="logout"onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?');"> ออกจากระบบ</a>
    </div>
</div>

<script>
    function toggleSection(id) {
        const section = document.getElementById(id);
        section.style.display = section.style.display === 'none' || section.style.display === '' ? 'block' : 'none';
    }
</script>
</body>
</html>