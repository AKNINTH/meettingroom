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

    // ตรวจสอบว่าชื่อผู้ใช้มีอยู่แล้วหรือไม่ (PDO)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $existing = $stmt->fetch();

    if ($existing) {
        $_SESSION['signin_error'] = "ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว.";
        header("Location: signin2.php");
        exit();
    }

    // เพิ่มผู้ใช้ใหม่ (PDO)
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'user')");
    $success = $stmt->execute([
        ':username' => $username,
        ':password' => $hashed_password
    ]);

    if ($success) {
        $_SESSION['registration_success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ.";
        header("Location: login2.php");
        exit();
    } else {
        // PDO: get error info
        $errorInfo = $stmt->errorInfo();
        $_SESSION['signin_error'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . ($errorInfo[2] ?? 'Unknown error');
        header("Location: signin2.php");
        exit();
    }

    // Close connection (optional with PDO)
    $conn = null;
} else {
    header("Location: signin2.php");
    exit();
}
?>