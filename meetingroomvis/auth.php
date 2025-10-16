<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login2.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: not_admin.php"); // หรือหน้าที่แจ้งว่าไม่มีสิทธิ์
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: login2.php");
    exit();
}
?>