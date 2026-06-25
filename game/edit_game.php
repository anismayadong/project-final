<?php
session_start();
require_once "../includes/db_connect.php";

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

if (!isset($_GET["id"])) {
    echo "ไม่พบเกมที่ต้องการแก้ไข";
    exit;
}

$game_id = (int)$_GET["id"];

// ดึงข้อมูลเกม
$stmt = $conn->prepare("SELECT * FROM games WHERE game_id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "ไม่พบเกมนี้ในระบบ";
    exit;
}

$game = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขเกม</title>
    <link rel="stylesheet" href="../assets/css/styles1.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            color: #333;
        }
        .container {
            background: #fff;
            width: 100%;
            max-width: 700px;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        form label {
            display: block;
            margin-top: 15px;
            margin-bottom: 8px;
            font-weight: bold;
            color: #34495e;
        }
        input[type="text"], textarea, select, input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }
        textarea { resize: vertical; }
        select { background: #fff; }
        img {
            margin-top: 10px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 6px;
            border: none;
            background: #3498db;
            color: #fff;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: #2980b9; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>🎮 แก้ไขข้อมูลเกม</h2>
    <form action="../game/edit_game_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">

        <label>ชื่อเกม:</label>
        <input type="text" name="game_name" value="<?php echo htmlspecialchars($game["game_name"]); ?>" required>

        <label>คำอธิบาย:</label>
        <textarea name="description" rows="5"><?php echo htmlspecialchars($game["description"]); ?></textarea>

        <label>ระดับความยาก:</label>
        <select name="difficulty">
            <option value="easy"   <?php if ($game["difficulty"] === "easy") echo "selected"; ?>>ง่าย</option>
            <option value="medium" <?php if ($game["difficulty"] === "medium") echo "selected"; ?>>ปานกลาง</option>
            <option value="hard"   <?php if ($game["difficulty"] === "hard") echo "selected"; ?>>ยาก</option>
        </select>

        <label>URL WebGL เกม:</label>
        <input type="text" name="game_url" value="<?php echo htmlspecialchars($game["game_url"]); ?>" required>

        <label>ภาพหน้าปก (หากต้องการเปลี่ยน):</label>
        <input type="file" name="cover_image" accept="image/*">

        <?php if ($game["cover_image"]): ?>
            <img src="../uploads/<?php echo htmlspecialchars($game["cover_image"]); ?>" width="150" alt="Game Cover">
        <?php endif; ?>

        <button type="submit">💾 บันทึกการแก้ไข</button>
    </form>
    <a href="../admin/admin_dashboard.php" class="back-link">⬅️ กลับหน้าจัดการ</a>
</div>
</body>
</html>

