<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $booking_id_to_delete = $_POST['booking_id'];

    // เปลี่ยนจาก DELETE เป็น UPDATE เพื่อทำ Soft Delete
    $sql_soft_delete = "UPDATE bookings SET is_deleted = TRUE WHERE id = ?";
    
    $stmt_soft_delete = $conn->prepare($sql_soft_delete);

    if ($stmt_soft_delete === false) {
        die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    
    $stmt_soft_delete->bind_param("i", $booking_id_to_delete);
    
    if ($stmt_soft_delete->execute()) {
        header('Location: details2.php');
        exit();
    } else {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการย้ายข้อมูล: ' . $stmt_soft_delete->error;
        header('Location: edit2.php?id=' . $booking_id_to_delete);
        exit();
    }
    
    $stmt_soft_delete->close();
    $conn->close();
    exit();
} else {
    $_SESSION['error_message'] = 'คำขอไม่ถูกต้อง';
    header('Location: details2.php');
    exit();
}
?>