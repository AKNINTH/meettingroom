<?php
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการดำเนินการนี้']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$bookingId = $_POST['bookingId'] ?? '';
$zoomId = $_POST['zoomId'] ?? '';
$zoomPassword = $_POST['zoomPassword'] ?? '';

if (empty($bookingId) || empty($zoomId) || empty($zoomPassword)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");

    // อัพเดทข้อมูล Zoom
    $stmt = $conn->prepare("UPDATE bookings SET id_zoom = :zoomId, password_zoom = :zoomPassword WHERE id = :bookingId");
    $stmt->execute([
        ':zoomId' => $zoomId,
        ':zoomPassword' => $zoomPassword,
        ':bookingId' => $bookingId
    ]);

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>