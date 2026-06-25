<?php
// เรียกใช้ไฟล์ header.php ที่มี session_start() และการเชื่อมต่อฐานข้อมูล
include_once '../includes/header.php';

// ตรวจสอบว่ามีการกรองระดับความยากหรือไม่
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';

if ($difficulty_filter !== '') {
    // ใช้ prepared statement เพื่อความปลอดภัย
    $sql_all_games = "SELECT * FROM games WHERE difficulty = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql_all_games);
    $stmt->bind_param("s", $difficulty_filter);
    $stmt->execute();
    $result_all_games = $stmt->get_result();
} else {
    // แสดงเกมทั้งหมดหากไม่มีการกรอง
    $sql_all_games = "SELECT * FROM games ORDER BY created_at DESC";
    $result_all_games = $conn->query($sql_all_games);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เกมทั้งหมด | ManganimeHub</title>
    <link rel="stylesheet" href="../assets/css/styles.css"> <!-- ลิงก์ไฟล์ CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<main class="main-content">
    <h1 class="section-title">เกมทั้งหมด</h1>

    <!-- ฟอร์มกรองระดับ -->
    <form method="GET" class="search-filter-form">
    <label for="difficulty">กรองตามระดับ:</label>
    <select name="difficulty" id="difficulty" onchange="this.form.submit()" class="filter-select">
        <option value="">ทั้งหมด</option>
        <option value="easy" <?= $difficulty_filter == 'easy' ? 'selected' : '' ?>>ง่าย</option>
        <option value="medium" <?= $difficulty_filter == 'medium' ? 'selected' : '' ?>>กลาง</option>
        <option value="hard" <?= $difficulty_filter == 'hard' ? 'selected' : '' ?>>ยาก</option>
    </select>
</form>



    <div class="games-container">
        <?php while($game = mysqli_fetch_assoc($result_all_games)): ?>
        <div class="game-card">
            <a href="../game/play_game.php?game_id=<?= $game['game_id'] ?>">
                <img src="../uploads/<?= !empty($game['cover_image']) ? htmlspecialchars($game['cover_image']) : 'default_game.jpg' ?>" 
                    alt="<?= htmlspecialchars($game['game_name']) ?>">
            </a>
            <div class="game-info">
                <h3>
                    <a href="../game/play_game.php?game_id=<?= $game['game_id'] ?>">
                        <?= htmlspecialchars($game['game_name']) ?>
                    </a>
                </h3>
                <p><?= htmlspecialchars($game['description']) ?></p>
                <div class="game-rewards">
                    <?php
                        $reward = 0;
                        switch ($game['difficulty']) {
                            case 'easy': $reward = 5; break;
                            case 'medium': $reward = 10; break;
                            case 'hard': $reward = 15; break;
                        }
                        $difficulty_text = $game['difficulty'] == 'easy' ? 'ง่าย' : ($game['difficulty'] == 'medium' ? 'กลาง' : 'ยาก');
                    ?>
                    <span><i class="fas fa-trophy"></i> รางวัล: <?= $reward ?> แต้ม (ระดับ: <?= $difficulty_text ?>)</span>
                </div>
                <a href="../game/play_game.php?game_id=<?= $game['game_id'] ?>" class="play-btn">เล่นเลย</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</main>

</body>
</html>

<?php
// เรียกใช้ไฟล์ footer.php
include_once '../includes/footer.php';
?>
