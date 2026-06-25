<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $game_id = (int)$_POST["game_id"];
    $game_name = trim($_POST["game_name"]);
    $description = trim($_POST["description"]);
    $difficulty = $_POST["difficulty"];
    $game_url = trim($_POST["game_url"]);

    $cover_image = null;

    // ถ้ามีอัปโหลดรูปใหม่
    if (!empty($_FILES["cover_image"]["name"])) {
        $targetDir = "../uploads/";
        $fileName = basename($_FILES["cover_image"]["name"]);
        $targetFile = $targetDir . uniqid() . "_" . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $targetFile)) {
                $cover_image = basename($targetFile);
            }
        }
    }

    // อัปเดตฐานข้อมูล
    if ($cover_image) {
        $stmt = $conn->prepare("UPDATE games SET game_name = ?, description = ?, difficulty = ?, game_url = ?, cover_image = ? WHERE game_id = ?");
        $stmt->bind_param("sssssi", $game_name, $description, $difficulty, $game_url, $cover_image, $game_id);
    } else {
        $stmt = $conn->prepare("UPDATE games SET game_name = ?, description = ?, difficulty = ?, game_url = ? WHERE game_id = ?");
        $stmt->bind_param("ssssi", $game_name, $description, $difficulty, $game_url, $game_id);
    }

    if ($stmt->execute()) {
        header("Location: ../admin/admin_dashboard.php?edit_success=1");
        exit;
    } else {
        echo "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
