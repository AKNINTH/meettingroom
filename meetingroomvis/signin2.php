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
    <title>Sign Up</title>
    <link rel="stylesheet" href="signin2.css">
</head>
<body>
    <div class="signin-container">
        <h2>สมัครสมาชิก</h2>
        <?php
        if (isset($_SESSION['signin_error'])) {
            echo '<p class="error-message">' . $_SESSION['signin_error'] . '</p>';
            unset($_SESSION['signin_error']);
        }
        ?>
        <form action="process_signin.php" method="POST">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">สมัครสมาชิก</button>
            <p class="login-link">มีบัญชีอยู่แล้ว? <a href="login2.php">เข้าสู่ระบบ</a></p>
        </form>
    </div>
</body>
</html>