<?php
include_once '../includes/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/config.php'; // ✅ เรียก config

$user_id = $_SESSION['user_id'] ?? 0;
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// 🔹 ใช้ค่าจาก config แทนการเขียนคงที่
$unlock_cost = $UNLOCK_COST;

if ($book_id <= 0) {
    echo "<main class='book-detail' style='padding:50px 20px; text-align:center;'>";
    echo "<h2>⚠️ ข้อมูลหนังสือไม่ถูกต้อง</h2>";
    echo "<p><a href='../pages/Home.php' class='btn-read' style='display:inline-block; margin-top:20px;'>← กลับหน้าหลัก</a></p>";
    echo "</main>";
    include_once '../includes/footer.php';
    exit;
}

// เพิ่ม views ทีละ 1
$conn->query("UPDATE books SET views = views + 1 WHERE book_id = $book_id");

// ดึงข้อมูลหนังสือ
$sql = "SELECT b.*, a.full_name AS author FROM books b 
        LEFT JOIN authors a ON b.author_id = a.author_id 
        WHERE b.book_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
$stmt->close();

if (!$book) {
    echo "<main class='book-detail' style='padding:50px 20px; text-align:center;'>";
    echo "<h2>⚠️ ไม่พบข้อมูลหนังสือเรื่องนี้</h2>";
    echo "<p><a href='../pages/manga.php' class='btn-read' style='display:inline-block; margin-top:20px;'>← ค้นหามังงะเรื่องอื่น</a></p>";
    echo "</main>";
    include_once '../includes/footer.php';
    exit;
}

// ดึงตอนที่ user เคยปลดล็อกไว้
$unlocked_chapter_ids = [];
if ($user_id) {
    $sql = "SELECT chapter_id FROM unlock_history WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unlocked_chapter_ids[] = $row['chapter_id'];
    }
}

// ดึงตอนทั้งหมดของหนังสือ
$chapter_sql = "SELECT * FROM chapters WHERE book_id = $book_id ORDER BY chapter_number ASC";
$chapter_result = $conn->query($chapter_sql);

// 🔹 ดึงค่าเฉลี่ยเรทติ้ง
$avg_rating = 0;
$rating_count = 0;

$rating_sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total 
               FROM reviews WHERE book_id = ?";
$stmt = $conn->prepare($rating_sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $avg_rating = round($row['avg_rating'], 1);
    $rating_count = $row['total'];
}
$stmt->close();

?>


<main class="book-detail">
    <section class="book-header">
        <img src="../uploads/<?php echo $book['cover_image']; ?>" 
             alt="<?php echo htmlspecialchars($book['title']); ?>" style="max-width:200px;">
        <div class="book-info">
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <p><strong>ผู้แต่ง:</strong> <?php echo htmlspecialchars($book['author'] ?? 'ไม่ระบุ'); ?></p>
            <p><strong>แนว:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
            <p><strong>คำอธิบาย:</strong> <?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
           <p><strong>เรทติ่ง:</strong>
    <?php if ($rating_count > 0): ?>
        <span style="color:#f39c12; font-size:1.2rem;">
            <?php echo str_repeat("⭐", floor($avg_rating)); ?>
            <?php if ($avg_rating - floor($avg_rating) >= 0.5) echo "⭐"; ?>
        </span>
        (<?php echo $avg_rating; ?> / 5 จาก <?php echo $rating_count; ?> รีวิว)
    <?php else: ?>
        <span>ยังไม่มีคะแนน</span>
    <?php endif; ?>
</p>

        </div>
    </section>
    
    <section class="chapter-list">
    <h3>ตอนทั้งหมด</h3>
    <?php if ($chapter_result->num_rows > 0): ?>
      <ul>
        <?php while ($chapter = $chapter_result->fetch_assoc()): ?>
          <li>
            <div class="chapter-item">
              <span>ตอนที่ <?php echo $chapter['chapter_number']; ?>: 
                <?php echo htmlspecialchars($chapter['title']); ?></span>

              <div class="chapter-actions">
                <?php if ($chapter['chapter_number'] <= $FREE_CHAPTERS || in_array($chapter['chapter_id'], $unlocked_chapter_ids)): ?>
                  <a class="btn-read" href="../pages/read_chapter.php?chapter_id=<?php echo $chapter['chapter_id']; ?>"> อ่าน</a>
                <?php elseif ($user_id): ?>
                  <form action="../user/unlock_chapter.php" method="POST" onsubmit="return confirmUnlock(<?php echo $unlock_cost; ?>);">
                    <input type="hidden" name="chapter_id" value="<?php echo $chapter['chapter_id']; ?>">
                    <button class="btn-unlock" type="submit"> ปลดล็อก (<?php echo $unlock_cost; ?> แต้ม)</button>
                  </form>
                <?php else: ?>
                  <a class="btn-login" href="../pages/Home.php">🔒 เข้าสู่ระบบเพื่อปลดล็อก</a>
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>ยังไม่มีตอนในขณะนี้</p>
    <?php endif; ?>
  </section>
</main>
        <!-- รีวิวและให้คะแนน -->
    <section class="book-reviews">
        <h3> รีวิวและคะแนน</h3>

        <?php if ($user_id): ?>
            <form action="../user/submit_review.php" method="POST" class="review-form">
                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">

                <div class="form-group">
                    <label>ให้คะแนน:</label>
                    <select name="rating" required>
                        <option value="">-- เลือกดาว --</option>
                        <option value="5">⭐⭐⭐⭐⭐ (5 ดาว)</option>
                        <option value="4">⭐⭐⭐⭐ (4 ดาว)</option>
                        <option value="3">⭐⭐⭐ (3 ดาว)</option>
                        <option value="2">⭐⭐ (2 ดาว)</option>
                        <option value="1">⭐ (1 ดาว)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>เขียนรีวิว:</label>
                    <textarea name="review_text" rows="3" placeholder="เขียนความเห็นของคุณ..." required></textarea>
                </div>

                <button type="submit" class="btn-submit"> ส่งรีวิว</button>
            </form>
        <?php else: ?>
            <p class="login-alert">🔒 กรุณา <a href="../pages/Home.php">เข้าสู่ระบบ</a> เพื่อรีวิวหนังสือ</p>
        <?php endif; ?>

        <h4> รีวิวล่าสุด</h4>
        <div class="reviews-list">
            <?php
            $review_sql = "SELECT r.review_id, r.rating, r.review_text, r.created_at, u.username 
               FROM reviews r 
               JOIN users u ON r.user_id = u.user_id 
               WHERE r.book_id = ? 
               ORDER BY r.created_at DESC";

            $stmt = $conn->prepare($review_sql);
            if ($stmt) {
                $stmt->bind_param("i", $book_id);
                $stmt->execute();
                $reviews = $stmt->get_result();

                if ($reviews && $reviews->num_rows > 0) {
                    while ($rev = $reviews->fetch_assoc()) {
                        // ⭐ แปลงตัวเลข rating เป็นดาว
                        $stars = str_repeat("⭐", $rev['rating']) . str_repeat("☆", 5 - $rev['rating']);

                        echo "<div class='review-box'>
                                <p class='review-user'><strong>" . htmlspecialchars($rev['username']) . "</strong></p>
                                <p class='review-rating'>$stars</p>
                                <p class='review-text'>" . nl2br(htmlspecialchars($rev['review_text'])) . "</p>
                                <p class='review-date'><small> " . date("d/m/Y H:i", strtotime($rev['created_at'])) . "</small></p>
                              </div>";
                    }
                } else {
                    echo "<p class='no-review'>ยังไม่มีรีวิว</p>";
                }
                $stmt->close();
            } else {
                echo "<p class='error'>เกิดข้อผิดพลาด: " . $conn->error . "</p>";
            }
            ?>
        </div>
    </section>

    <style>
        .book-reviews {
            margin-top: 30px;
            padding: 20px;
            background: #fdfdfd;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .review-form {
            margin-bottom: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .form-group label { font-weight: bold; color: #333; }
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .btn-submit {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover { background: #2980b9; }
        .reviews-list { margin-top: 15px; }
        .review-box {
            background: #fff;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .review-user { font-weight: bold; color: #2c3e50; }
        .review-rating { color: #f39c12; font-size: 18px; margin: 5px 0; }
        .review-text { margin: 8px 0; color: #444; }
        .review-date { font-size: 0.85em; color: #888; }
        .login-alert { color: #e74c3c; font-weight: bold; }

        .chapter-list {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}
.chapter-list ul {
  list-style: none; 
  padding: 0;
  margin: 0;
}
.chapter-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding: 10px 0;
}
.btn-read,.btn-login {
  background: #4b01d1;
  color: white;
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.9rem;
  border: none;
  transition: 0.3s;
}
.btn-read:hover, .btn-unlock:hover, .btn-login:hover {
  background: #9615ecff;
}
.btn-unlock{
 background: #db3b3bff;
  color: white;
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.9rem;
  border: none;
  transition: 0.3s;
}


    </style>


        
        </ul>
</section>

<script>
function confirmUnlock(cost) {
    return confirm(`คุณต้องการใช้ ${cost} แต้มเพื่อปลดล็อกตอนนี้หรือไม่?`);
}
</script>

<?php include '../includes/footer.php'; ?>
