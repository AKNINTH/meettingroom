<?php
require_once 'auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: main.php');
    exit();
}

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

$id = null;
$user = null;
$message = '';
$error = '';

// ดึงข้อมูลผู้ใช้ปัจจุบันจากฐานข้อมูล
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql_select = "SELECT id, username, role FROM users WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        $error = "ไม่พบข้อมูลผู้ใช้ที่ต้องการแก้ไข";
    }
} else {
    $error = "ไม่พบรหัสผู้ใช้ที่ระบุ";
}

// ถ้ามีการส่งข้อมูลจากฟอร์มเพื่ออัปเดต
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id_to_update = intval($_POST['id']);
    $new_username = htmlspecialchars(trim($_POST['username']));
    $new_role = htmlspecialchars(trim($_POST['role']));
    $new_password = trim($_POST['password']);

    if (empty($new_username) || empty($new_role)) {
        $error = "กรุณากรอกชื่อผู้ใช้และตำแหน่งให้ครบถ้วน";
    } else {
        // เตรียมคำสั่ง SQL สำหรับอัปเดต
        $sql_update = "UPDATE users SET username = ?, role = ? ";
        $params = array("ss", $new_username, $new_role);
        
        // ถ้ามีการกรอกรหัสผ่านใหม่ ให้เพิ่มการอัปเดตรหัสผ่าน
        if (!empty($new_password)) {
            // ใช้ password_hash() เพื่อเข้ารหัสรหัสผ่าน
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update .= ", password = ? ";
            $params[0] .= "s"; // เพิ่มประเภทตัวแปรสำหรับรหัสผ่าน
            $params[] = $hashed_password;
        }

        $sql_update .= "WHERE id = ?";
        $params[0] .= "i"; // เพิ่มประเภทตัวแปรสำหรับ id
        $params[] = $id_to_update;

        // อัปเดตข้อมูลผู้ใช้ในฐานข้อมูล
        $stmt_update = $conn->prepare($sql_update);

        // ใช้ call_user_func_array เพื่อ bind_param แบบไดนามิก
        call_user_func_array(array($stmt_update, 'bind_param'), refValues($params));
        
        if ($stmt_update->execute()) {
            $message = "อัปเดตข้อมูลผู้ใช้สำเร็จ!";
            header("Location: user.php?message=edit_success");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
        }
    }
}

// ฟังก์ชันช่วยในการส่งอาร์เรย์เป็น reference ให้กับ bind_param
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) // PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้</title>
    <link rel="stylesheet" href="details2.css">
    <style>
        .edit-form-container {
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
            <a href="main.php" class="logo"><img src="VCV_SK2_0.jpg" alt="logo" style="height:40px;vertical-align:middle;"></a>
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
                        <li><a href="main.php">ปฏิทินการจองห้องประชุม</a></li>
                        <li><a href="form.php">จองห้องประชุม</a></li>
                        <li><a href="details2.php">ดูตารางการจอง หรือลิ้งค์ZOOM</a></li>
                    </ul>
                </nav>
            </aside>
        </div>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
        <header class="navbar">
            <a href="main.php" class="logo"><img src="VCV_SK2_0.jpg" alt="logo" style="height:40px;vertical-align:middle;"></a>
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
                        <li><a href="main.php">ปฏิทินการจองห้องประชุม</a></li>
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
            <div class="edit-form-container">
                <h2>แก้ไขผู้ใช้</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($user): ?>
                    <form action="edit_user.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                        
                        <div class="form-group">
                            <label for="username">ชื่อผู้ใช้:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">ตำแหน่ง:</label>
                            <select id="role" name="role" required>
                                <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>ผู้ใช้งานทั่วไป</option>
                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">รหัสผ่านใหม่ (ไม่ต้องกรอกหากไม่ต้องการเปลี่ยน):</label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <div class="form-actions">
                            <a href="user.php" class="btn btn-cancel">ยกเลิก</a>
                            <button type="submit" class="btn btn-success">บันทึก</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>