<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: manufrom.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login2.css">
</head>
<body>
    <div class="login-container">
        <h2>เข้าสู่ระบบ</h2>
        <?php
        if (isset($_SESSION['login_error'])) {
            echo '<p class="error-message">' . $_SESSION['login_error'] . '</p>';
            unset($_SESSION['login_error']);
        }
        if (isset($_SESSION['registration_success'])) {
            echo '<p class="success-message">' . $_SESSION['registration_success'] . '</p>';
            unset($_SESSION['registration_success']);
        }
        ?>
        <form action="process_login.php" method="POST">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
            <p class="register-link">ยังไม่มีบัญชี? <a href="signin2.php">สมัครสมาชิก</a></p>
        </form>
    </div>
</body>
</html>