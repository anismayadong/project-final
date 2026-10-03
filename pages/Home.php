<?php
include_once '../includes/header.php';
require_once '../includes/db_connect.php';

// ดึงหนังสือ 4 เรื่องล่าสุดพร้อมกับข้อมูลผู้แต่ง
$sql = "
SELECT b.*, a.full_name AS author
FROM books b
LEFT JOIN authors a ON b.author_id = a.author_id
ORDER BY b.created_at DESC LIMIT 4
";
$result = $conn->query($sql);
?>

<main>
    <section class="hero-section2">
        <div class="hero-content">
            <h2>ยินดีต้อนรับสู่ MangAnime Hub</h2>
            <p>แพลตฟอร์มอ่านมังงะและอนิเมะพร้อมระบบเกม ที่รวบรวมการ์ตูนหลากหลายแนวให้คุณได้เพลิดเพลิน</p>
            <?php if (!isset($_SESSION["user_id"])): ?>
                <a href="../logregis/register.php" class="cta-button">เริ่มอ่านเลย!</a>
            <?php else: ?>
                <a href="../pages/manga.php" class="cta-button">ค้นหามังงะ</a>
            <?php endif; ?>
        </div>
    </section>
                
    <section class="featured-section">
        <h2 class="section-title">เรื่องยอดนิยม</h2>
        <div class="manga-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($book = $result->fetch_assoc()): ?>
                    <div class="manga-card">
                        <a href="../pages/book_detail.php?book_id=<?php echo $book['book_id']; ?>">
                            <div class="manga-thumbnail">
                                <img src="../uploads/<?php echo !empty($book['cover_image']) ? $book['cover_image'] : 'default_cover.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php if (isset($book['status'])): ?>
                                    <?php if ($book['status'] === 'new'): ?>
                                        <span class="manga-status new">ใหม่</span>
                                    <?php elseif ($book['status'] === 'updated'): ?>
                                        <span class="manga-status updated">อัปเดต</span>
                                    <?php elseif ($book['status'] === 'hot'): ?>
                                        <span class="manga-status hot">ฮิต</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </a>

                        <div class="manga-info">
                            <h3>
                                <a href="../pages/book_detail.php?book_id=<?php echo $book['book_id']; ?>">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                            </h3>
                            <p class="manga-author">ผู้แต่ง: <?php echo !empty($book['author']) ? htmlspecialchars($book['author']) : 'ไม่ระบุ'; ?></p>
                            <div class="manga-stats">
                                <span><i class="fas fa-eye"></i> <?php echo number_format($book['views'] ?? 0); ?></span>
                                <span><i class="fas fa-star"></i> <?php echo number_format($book['rating'] ?? 0.0, 1); ?></span>
                            </div>
                            <p class="manga-genre">แนว: <?php echo !empty($book['genre']) ? htmlspecialchars($book['genre']) : 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>ไม่พบหนังสือในขณะนี้</p>
            <?php endif; ?>
        </div>
        <a href="../pages/manga.php" class="view-all-btn">ดูทั้งหมด</a>
    </section>

<?php
$sql_games = "SELECT * FROM games ORDER BY created_at DESC LIMIT 3";
$result_games = $conn->query($sql_games);
?>

<section class="featured-section">
    <h2 class="section-title">เกมสะสมแต้ม</h2>
    <div class="games-container">
        <?php while($game = mysqli_fetch_assoc($result_games)): ?>
        <div class="game-card">
            <a href="../game/play_game.php?game_id=<?= $game['game_id'] ?>">
<img src="../uploads/<?= !empty($game['cover_image']) ? htmlspecialchars($game['cover_image']) : 'default_game_cover.jpg' ?>" 
     alt="<?= htmlspecialchars($game['game_name']) ?>">            </a>
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
            case 'easy':
                $reward = 5;
                break;
            case 'medium':
                $reward = 10;
                break;
            case 'hard':
                $reward = 15;
                break;
        }
    ?>
    <span><i class="fas fa-trophy"></i> รางวัล: <?= $reward ?> แต้ม (ระดับ: 
        <?= $game['difficulty'] == 'easy' ? 'ง่าย' : ($game['difficulty'] == 'medium' ? 'กลาง' : 'ยาก') ?>)
    </span>
</div>
                <a href="../game/play_game.php?game_id=<?= $game['game_id'] ?>" class="play-btn">เล่นเลย</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <a href="../pages/games.php" class="view-all-btn">ดูเกมทั้งหมด</a>
</section>
</main>

<?php include_once '../includes/footer.php'; ?>
