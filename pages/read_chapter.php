<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isset($_GET['chapter_id'])) {
    echo "<p>ไม่พบตอนที่ต้องการ</p>";
    exit;
}

$chapter_id = intval($_GET['chapter_id']);
$user_id = $_SESSION['user_id'] ?? null;

// ✅ ตรวจสอบการเข้าสู่ระบบ
if (!$user_id) {
    header("Location: ../pages/Home.php");
    exit;
    //if (!$user_id) { echo "<p>กรุณาเข้าสู่ระบบก่อนอ่านตอน</p>"; 
    //echo "<a href='../pages/Home.php'>เข้าสู่ระบบ</a>"; exit;
}

// ✅ ดึงข้อมูลตอน
$sql = "SELECT c.*, b.title AS book_title, b.author_id 
        FROM chapters c 
        JOIN books b ON c.book_id = b.book_id 
        WHERE c.chapter_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chapter_id);
$stmt->execute();
$chapter = $stmt->get_result()->fetch_assoc();

if (!$chapter) {
    echo "<p>ไม่พบตอน</p>";
    exit;
}

// ✅ ตรวจสอบว่าปลดล็อกแล้วหรือยัง
$unlocked = false;

if ($chapter['chapter_number'] <= $FREE_CHAPTERS) {
    $unlocked = true;
} elseif (!$chapter['is_locked']) {
    $unlocked = true;
} else {
    $check = $conn->prepare("SELECT 1 FROM unlock_history WHERE user_id = ? AND chapter_id = ?");
    $check->bind_param("ii", $user_id, $chapter_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $unlocked = true;
    }
}

// ✅ บันทึกประวัติการอ่าน
if ($unlocked) {
    $log = $conn->prepare("INSERT INTO reading_history (user_id, chapter_id, read_time) VALUES (?, ?, NOW())");
    $log->bind_param("ii", $user_id, $chapter_id);
    $log->execute();
}

// ✅ หาตอนก่อนหน้า / ถัดไป
$prev = $conn->prepare("SELECT chapter_id FROM chapters WHERE book_id = ? AND chapter_number < ? ORDER BY chapter_number DESC LIMIT 1");
$prev->bind_param("ii", $chapter['book_id'], $chapter['chapter_number']);
$prev->execute();
$prev_id = $prev->get_result()->fetch_assoc()['chapter_id'] ?? null;

$next = $conn->prepare("SELECT chapter_id FROM chapters WHERE book_id = ? AND chapter_number > ? ORDER BY chapter_number ASC LIMIT 1");
$next->bind_param("ii", $chapter['book_id'], $chapter['chapter_number']);
$next->execute();
$next_id = $next->get_result()->fetch_assoc()['chapter_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($chapter['book_title']); ?> - ตอนที่ <?php echo $chapter['chapter_number']; ?></title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
    font-family: "Prompt", sans-serif;
    background: #f7f7f7;
    margin: 0; padding: 20px;
}
main.chapter-content {
    max-width: 900px; margin: auto;
    background: #fff; padding: 25px;
    border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
h2 { text-align: center; color: #333; margin-bottom: 20px; }
.content img {
    display: block; margin: 0 auto 30px auto;
    max-width: 100%; height: auto;
    border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
button {
    display: block; margin: 20px auto;
    padding: 12px 25px; background: #ff4b5c; color: #fff;
    font-size: 16px; border: none; border-radius: 8px; cursor: pointer;
}
button:hover { background: #e63946; }
.navigation { margin-top: 25px; text-align: center; }
.navigation a {
    margin: 0 15px; text-decoration: none;
    color: #0077cc; font-weight: bold;
}
.navigation a:hover { color: #ff4b5c; }
a.back-link {
    display: block; text-align: center;
    margin-top: 25px; color: #666; text-decoration: none;
}
a.back-link:hover { color: #000; }
</style>
</head>
<body>
<main class="chapter-content">
    <h2><?php echo htmlspecialchars($chapter['book_title']); ?> - ตอนที่ <?php echo $chapter['chapter_number']; ?>:
        <?php echo htmlspecialchars($chapter['title']); ?></h2>

    <div class="content">
    <?php if ($unlocked): ?>
        <?php
        $img_stmt = $conn->prepare("SELECT image_path FROM chapter_images WHERE chapter_id = ? ORDER BY page_number ASC");
        $img_stmt->bind_param("i", $chapter_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();

        if ($img_result->num_rows > 0):
            while ($img = $img_result->fetch_assoc()): ?>
                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Page">
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;">ไม่มีรูปภาพสำหรับตอนนี้</p>
        <?php endif; ?>
    <?php else: ?>
        <p style="text-align:center; font-size:18px;">🔒 ตอนนี้ถูกล็อก ต้องใช้ <?php echo $UNLOCK_COST; ?> แต้มในการปลดล็อก</p>
        <form id="unlockForm" method="post" action="../user/unlock_chapter.php">
            <input type="hidden" name="chapter_id" value="<?php echo $chapter['chapter_id']; ?>">
            <button type="submit">ปลดล็อกตอนนี้ (<?php echo $UNLOCK_COST; ?> แต้ม)</button>
        </form>
    <?php endif; ?>
    </div>

    <div class="navigation">
        <?php if ($prev_id): ?>
            <a href="read_chapter.php?chapter_id=<?php echo $prev_id; ?>">← ตอนก่อนหน้า</a>
        <?php endif; ?>
        <?php if ($next_id): ?>
            <a href="read_chapter.php?chapter_id=<?php echo $next_id; ?>">ตอนถัดไป →</a>
        <?php endif; ?>
    </div>

    <a class="back-link" href="../pages/book_detail.php?book_id=<?php echo $chapter['book_id']; ?>">← กลับไปที่หนังสือ</a>
</main>

<script>
// ✅ SweetAlert ก่อนปลดล็อก
document.getElementById('unlockForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'ยืนยันการปลดล็อก?',
        text: `คุณต้องการใช้ <?php echo $UNLOCK_COST; ?> แต้มเพื่อปลดล็อกตอนนี้หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ปลดล็อกเลย',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ff4b5c'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
});

// ✅ แสดงแจ้งเตือนเมื่อปลดล็อกสำเร็จ
<?php if (isset($_GET['unlocked']) && $_GET['unlocked'] === 'success'): ?>
Swal.fire({
    title: ' ปลดล็อกสำเร็จ!',
    text: 'คุณสามารถอ่านตอนนี้ได้แล้ว',
    icon: 'success',
    confirmButtonText: 'ตกลง',
    confirmButtonColor: '#27ae60'
});
<?php endif; ?>
</script>
</body>
</html>
