<?php
include_once '../includes/header.php';
require_once "../includes/db_connect.php";

// รับค่าจาก GET ถ้ามี
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
// ดึง genre ทั้งหมดจากฐานข้อมูล
$genre_sql = "SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY LOWER(genre) ASC";
$genre_result = $conn->query($genre_sql);

// สร้าง SQL พื้นฐาน
$sql = "
    SELECT b.*, a.full_name AS author 
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.author_id 
    WHERE 1
";

// เพิ่มเงื่อนไขถ้ามีการค้นหา
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND b.title LIKE '%$search%'";
}

if (!empty($genre)) {
    $genre = $conn->real_escape_string($genre);
    $sql .= " AND b.genre LIKE '%$genre%'";
}

$sql .= " ORDER BY b.created_at DESC";

// รันคำสั่ง SQL
$result = $conn->query($sql);
?>

<!-- ===== FORM SEARCH & FILTER ===== -->
<form method="GET" action="../pages/manga.php" class="search-form">
    <input type="text" name="search" placeholder="ค้นหาชื่อเรื่อง..." 
        value="<?php echo htmlspecialchars($search); ?>" class="search-input">

    <select name="genre" class="genre-select">
        <option value="">-- เลือกแนว --</option>
        <?php if ($genre_result && $genre_result->num_rows > 0): ?>
            <?php while($row = $genre_result->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['genre']) ?>" 
                    <?= ($genre == $row['genre']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['genre']) ?>
                </option>
            <?php endwhile; ?>
        <?php else: ?>
            <option disabled>ไม่มีข้อมูลแนว</option>
        <?php endif; ?>
    </select>

    <button type="submit" class="search-button">ค้นหา</button>
</form>


<main>
    <section class="featured-section">
        <h2 class="section-title" style="text-align:center;">มังงะทั้งหมด</h2>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="manga-grid">
                <?php while ($book = $result->fetch_assoc()): ?>
                    <article class="manga-card">
                        <a href="book_detail.php?book_id=<?php echo $book['book_id']; ?>">
                            <div class="manga-thumbnail">
                        <img src="../uploads/<?php echo !empty($book['cover_image']) ? $book['cover_image'] : 'default_cover.jpg'; ?>" 
                        alt="<?php echo htmlspecialchars($book['title']); ?>">
                         <?php if (!empty($book['status'])): ?>
                        <span class="manga-status <?php echo $book['status']; ?>">
                    <?php
                        if ($book['status'] == 'new') echo 'ใหม่';
                            elseif ($book['status'] == 'hot') echo 'ฮิต';
                             elseif ($book['status'] == 'updated') echo 'อัปเดต';
                    ?>
                </span>
            <?php endif; ?>
        </div>
    </a>

    <div class="manga-info">
        <h3>
            <a href="../pages/book_detail.php?book_id=<?php echo $book['book_id']; ?>">
                <?php echo htmlspecialchars($book['title']); ?>
            </a>
        </h3>
        <p class="manga-author">ผู้แต่ง: <?php echo htmlspecialchars($book['author'] ?? 'ไม่ระบุ'); ?></p>
        <div class="manga-stats">
            <span><i class="fas fa-eye"></i> <?php echo number_format($book['views'] ?? 0); ?></span>
            <span><i class="fas fa-star"></i> <?php echo number_format($book['rating'] ?? 0.0, 1); ?></span>
        </div>
        <p class="manga-genre">แนว: <?php echo htmlspecialchars($book['genre']); ?></p>
    </div>
</article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center;">ไม่พบผลลัพธ์ที่ตรงกับคำค้นหา</p>
        <?php endif; ?>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>
