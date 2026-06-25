<?php
include_once '../includes/db_connect.php';

// รับข้อมูลจาก POST
$user_id = intval($_POST['user_id'] ?? 0);
$game_id = intval($_POST['game_id'] ?? 0);
$points_earned = intval($_POST['points_earned'] ?? 0);

if ($user_id <= 0 || $game_id <= 0 || $points_earned <= 0) {
    echo json_encode(['error' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

// บันทึกประวัติการเล่นเกม
$sql = "INSERT INTO game_play_history (user_id, game_id, points_earned) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $game_id, $points_earned);
$stmt->execute();

// อัปเดตแต้มสะสม
$sql_update = "INSERT INTO points (user_id, points) VALUES (?, ?)
               ON DUPLICATE KEY UPDATE points = points + VALUES(points)";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("ii", $user_id, $points_earned);
$stmt_update->execute();

echo json_encode(['success' => true, 'points_added' => $points_earned]);
?>
