<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['signin_error'] = "รหัสผ่านไม่ตรงกัน.";
        header("Location: signin2.php");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ตรวจสอบว่าชื่อผู้ใช้มีอยู่แล้วหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['signin_error'] = "ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว.";
        header("Location: signin2.php");
        exit();
    }

    $stmt->close();

    // เพิ่มผู้ใช้ใหม่
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['registration_success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ.";
        header("Location: login2.php");
        exit();
    } else {
        $_SESSION['signin_error'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $stmt->error;
        header("Location: signin2.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: signin2.php");
    exit();
}
?>