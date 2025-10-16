

<?php
require_once 'auth.php';
requireLogin();

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

// ดึงข้อมูลห้องประชุมทั้งหมดจากฐานข้อมูล (เหมือน form_config)
$rooms = [];
$sql_rooms = "SELECT * FROM rooms WHERE is_deleted = 0";
try {
    $stmt_rooms = $conn->prepare($sql_rooms);
    $stmt_rooms->execute();
    $rooms = $stmt_rooms->fetchAll();
} catch (PDOException $e) {
    die("Error fetching rooms: " . $e->getMessage());
}

// ไม่ต้องปิดการเชื่อมต่อด้วย $conn->close() เพราะ PDO จะจัดการให้เองเมื่อสคริปต์ทำงานเสร็จสิ้น
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เว็บแอปพลิเคชันการจองห้องประชุม</title>
    <link rel="stylesheet" href="main.css">
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
            <main class="content">
                <h2 class="text_center">ปฏิทินการจองห้องประชุม</h2>
                <div class="room-selection">
                    <?php foreach ($rooms as $room): ?>
                        <a href="room.php?room=<?php echo urlencode($room['room_name']); ?>" class="room-link">
                            <?php echo htmlspecialchars($room['room_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="text_pic_room">
                <h3>กรุณาเลือกห้องประชุม</h3>
                </div>
            </main>
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
            <main class="content">
                <h2>ปฏิทินการจองห้องประชุม</h2>

                <div class="room-selection">
                    <?php foreach ($rooms as $room): ?>
                        <a href="room.php?room=<?php echo urlencode($room['room_name']); ?>" class="room-link">
                            <?php echo htmlspecialchars($room['room_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="text_pic_room">
                <h3>กรุณาเลือกห้องประชุม</h3>
                </div>
            </main>
        </div>
    <?php endif; ?>
</body>
</html>