<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ไม่มีสิทธิ์เข้าถึง - เว็บแอปพลิเคชันการจองห้องประชุม</title>
    <link rel="stylesheet" href="not_admin.css">
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
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button id="prev-month-btn">◀</button>
                        <div id="month-year-display" class="month-year"></div>
                        <button id="next-month-btn">▶</button>
                    </div>
                    <div class="calendar-grid-container">
                        <div class="calendar-grid">
                            </div>
                    </div>
                </div>
            </main>
        </div>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
        <header class="navbar">
            <a href="main.php" class="logo">ปฏิทินการจองห้องประชุม</a>
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

        <div class="content">
            <h1>🚫 ไม่มีสิทธิ์เข้าถึง</h1>
            <p>คุณไม่มีสิทธิ์ในการเข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ (Admin) เพื่อใช้งานในส่วนนี้</p>
            <p>หรือกลับไปหน้าหลักเพื่อใช้งานในส่วนอื่น ๆ</p>
            <a href='main.php' class='delete-btn' onclick='return confirm(\"คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?\")'>ลบ</a>
            <a href="logout2.php">log out</a>
        </div>
    </div>
</body>
</html>