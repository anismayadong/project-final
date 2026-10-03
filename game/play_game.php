<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['game_id'])) {
    header("Location: ../pages/Home.php");
    exit;
}

$game_id = $_GET['game_id'];
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลเกม
$sql = "SELECT * FROM games WHERE game_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    echo "ไม่พบข้อมูลเกม";
    exit;
}

// ตรวจสอบจำนวนครั้งที่เล่นวันนี้
$check_play_sql = "
    SELECT COUNT(*) AS play_count 
    FROM game_play_history 
    WHERE user_id = ? AND DATE(played_at) = CURDATE()
";
$check_stmt = $conn->prepare($check_play_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$play_data = $check_result->fetch_assoc();

$today_play_count = $play_data['play_count'];

// เริ่มต้นค่า
$can_earn_points = true;
$message = "";
$points = 0;

// ถ้าเล่นเกิน 6 ครั้งแล้ว
if ($today_play_count >= 6) {
    $can_earn_points = false;
    $message = "คุณเล่นครบ 6 ครั้งแล้ววันนี้ 🎮 แต้มจะไม่ถูกเพิ่ม";
} else {
    // คำนวณแต้มตามความยาก
    switch ($game['difficulty']) {
        case 'easy': $points = 5; break;
        case 'medium': $points = 10; break;
        case 'hard': $points = 15; break;
    }

    // ✅ เพิ่มแต้ม (ถ้ายังไม่มีแถวของ user ให้สร้างใหม่)
$update = $conn->prepare("UPDATE points SET points = points + ? WHERE user_id = ?");
$update->bind_param("ii", $points, $user_id);
$update->execute();

if ($update->affected_rows === 0) {
    // ถ้ายังไม่มี record ให้เพิ่มแถวใหม่
    $insertPoints = $conn->prepare("INSERT INTO points (user_id, points) VALUES (?, ?)");
    $insertPoints->bind_param("ii", $user_id, $points);
    $insertPoints->execute();
    $insertPoints->close();
}
$update->close();

// ✅ บันทึกประวัติการเล่น
$insert_sql = "INSERT INTO game_play_history (user_id, game_id, points_earned, played_at)
               VALUES (?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iii", $user_id, $game_id, $points);
$insert_stmt->execute();

$message = "+$points แต้ม!";

}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($game['game_name']) ?> | MangAnime Hub</title>
    <style>
        body {
            margin: 0;
            background-color: #000;
            font-family: 'Segoe UI', sans-serif;
        }

        #unity-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        .exit-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: #ff4d4d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            z-index: 1000;
            text-decoration: none;
        }

        .exit-button:hover {
            background-color: #cc0000;
        }

        .game-header {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: rgba(255,255,255,0.9);
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 16px;
            color: #000;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .points-popup {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            background-color: <?= $can_earn_points ? '#ffe600' : '#ffcccc' ?>;
            color: #000;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 20px;
            font-weight: bold;
            z-index: 1000;
            animation: fadeInOut 4s ease forwards;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; bottom: 20px; }
            10% { opacity: 1; bottom: 40px; }
            90% { opacity: 1; }
            100% { opacity: 0; bottom: 60px; }
        }
    </style>
</head>
<body>

<div id="unity-container">
    <a href="../pages/games.php" class="exit-button">← ออกจากเกม</a>

    <div class="game-header">
        🎮 <?= htmlspecialchars($game['game_name']) ?> | 
        ระดับ: <?= $game['difficulty'] === 'easy' ? 'ง่าย' : ($game['difficulty'] === 'medium' ? 'กลาง' : 'ยาก') ?>
    </div>

    <div class="points-popup"><?= $message ?></div>

    <iframe src="<?php echo htmlspecialchars($game['game_url']); ?>" width="100%" height="100%" frameborder="0"></iframe>
</div>

</body>
</html>
