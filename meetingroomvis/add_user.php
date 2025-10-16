<?php
require_once 'auth.php';
?>
<?php
// กำหนดข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting";

$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8"); 

$message = '';
$error = '';

// ตรวจสอบว่ามีการส่งข้อมูลจากฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = htmlspecialchars(trim($_POST['username']));
    $new_password = trim($_POST['password']);
    $new_role = htmlspecialchars(trim($_POST['role']));

    // ตรวจสอบข้อมูลไม่ให้ว่างเปล่า
    if (empty($new_username) || empty($new_password) || empty($new_role)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // เข้ารหัสรหัสผ่านก่อนบันทึกลงฐานข้อมูล
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // เตรียมคำสั่ง SQL สำหรับเพิ่มผู้ใช้ใหม่
        $sql_insert = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sss", $new_username, $hashed_password, $new_role);
        
        if ($stmt_insert->execute()) {
            $message = "เพิ่มผู้ใช้ใหม่สำเร็จ!";
            header("Location: user.php?message=add_success"); // Redirect กลับไปหน้า user.php
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ใช้ใหม่</title>
    <link rel="stylesheet" href="details2.css">
    <style>
        .add-form-container {
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-cancel {
            background-color: #6c757d;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php if (isUser()): ?>
            <header class="navbar">
            <a href="manufrom.php" class="logo"><img src="VCV_SK2_0.jpg" alt="logo" style="height:40px;vertical-align:middle;"></a>
            <nav class="top-menu">
                <ul>
                    <li><a href="logout2.php">logout</a></li>
                </ul>
            </nav>
        </header>
        <div class="container">
            <aside class="sidebar">
                <nav class="side-menu">
                    <h5>เมนูหลัก</h5>
                    <ul>
                        <li><a href="manufrom.php">ปฏิทินการจองห้องประชุม</a></li>
                        <li><a href="form.php">จองห้องประชุม</a></li>
                        <li><a href="details2.php">ดูตารางการจอง หรือลิ้งค์ZOOM</a></li>
                    </ul>
                </nav>
            </aside>
        </div>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
        <header class="navbar">
            <a href="manufrom.php" class="logo">ปฏิทินการจองห้องประชุม</a>
            <nav class="top-menu">
                <ul>
                    <li><a href="setting.php">ตั้งค่า</a></li>
                    <li><a href="logout2.php">logout</a></li>
                </ul>
            </nav>
        </header>
        <div class="container">
            <aside class="sidebar">
                <nav class="side-menu">
                    <h5>เมนูหลัก</h5>
                    <ul>
                        <li><a href="manufrom.php">ปฏิทินการจองห้องประชุม</a></li>
                        <li><a href="form.php">จองห้องประชุม</a></li>
                        <li><a href="details2.php">ดูตารางการจอง หรือลิ้งค์ZOOM</a></li>
                        <li><a href="form_config.php">ตั้งค่าการจอง</a></li>
                        <li><a href="user.php">จัดการผู้ใช้</a></li>
                        <li><a href="setting.php">ปฏิทินการจองห้องประชุมทั้งหมด</a></li>
                    </ul>
                </nav>
            </aside>
    <?php endif; ?>

        <div class="main-content">
            <div class="add-form-container">
                <h2>เพิ่มผู้ใช้ใหม่</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="add_user.php" method="POST">
                    
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">รหัสผ่าน:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">ตำแหน่ง:</label>
                        <select id="role" name="role" required>
                            <option value="user">ผู้ใช้งานทั่วไป</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="user.php" class="btn btn-cancel">ยกเลิก</a>
                        <button type="submit" class="btn btn-success">เพิ่มผู้ใช้</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>