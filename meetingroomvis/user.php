<?php
require_once 'auth.php';
requireLogin();
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

// เพิ่มคอลัมน์ is_deleted ถ้ายังไม่มี
$sql_check = "SHOW COLUMNS FROM users LIKE 'is_deleted'";
$result_check = $conn->query($sql_check);
if ($result_check->num_rows == 0) {
    $sql_alter = "ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0";
    $conn->query($sql_alter);
}

// ลบผู้ใช้ (Soft Delete)
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $sql_delete = "UPDATE users SET is_deleted = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        header("Location: user.php?message=ลบผู้ใช้สำเร็จ"); // Redirect พร้อมข้อความแจ้งเตือน
        exit();
    } else {
        header("Location: user.php?message=เกิดข้อผิดพลาดในการลบผู้ใช้&error=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการผู้ใช้</title>
    <link rel="stylesheet" href="user.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // สร้าง modal element
            const modalHtml = `
                <div id="deleteConfirmModal" class="modal">
                    <div class="modal-content">
                        <h3 class="modal-title">ยืนยันการลบผู้ใช้</h3>
                        <p>คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?</p>
                        <div class="modal-buttons">
                            <button class="modal-confirm" id="confirmDelete">ยืนยัน</button>
                            <button class="modal-cancel" id="cancelDelete">ยกเลิก</button>
                        </div>
                    </div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            const modal = document.getElementById('deleteConfirmModal');
            let deleteUrl = '';

            // เมื่อคลิกปุ่มลบ
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    deleteUrl = this.getAttribute('href');
                    modal.style.display = 'block';
                });
            });

            // ปุ่มยกเลิกใน modal
            document.getElementById('cancelDelete').addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // ปุ่มยืนยันใน modal
            document.getElementById('confirmDelete').addEventListener('click', function() {
                if (deleteUrl) {
                    window.location.href = deleteUrl;
                }
            });

            // ปิด modal เมื่อคลิกพื้นหลัง
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
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
            <div class="details-container">
                <?php if (isUser()): ?>
                    <h2>รายละเอียดผู้ใช้</h2>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ชื่อผู้ใช้</th>
                                <th>ตำแหน่ง</th>
                                <th>สร้างเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // ดึงข้อมูลผู้ใช้จากฐานข้อมูล (เฉพาะที่ยังไม่ถูกลบ)
                            $sql = "SELECT * FROM users WHERE is_deleted = 0";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td data-label='ชื่อผู้ใช้'>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td data-label='ตำแหน่ง'>" . htmlspecialchars($row['role']) . "</td>";
                                    echo "<td data-label='สร้างเมื่อ'>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>ไม่พบข้อมูลผู้ใช้</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <h2>รายละเอียดผู้ใช้</h2>
                    <a href="add_user.php" class="add-user-btn">เพิ่มผู้ใช้</a>
                    <?php if (isset($_GET['message'])): ?>
                        <div class="message <?php echo isset($_GET['error']) ? 'error' : 'success'; ?>">
                            <?php echo htmlspecialchars($_GET['message']); ?>
                        </div>
                    <?php endif; ?>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ชื่อผู้ใช้</th>
                                <th>ตำแหน่ง</th>
                                <th>สร้างเมื่อ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // ดึงข้อมูลผู้ใช้จากฐานข้อมูล (เฉพาะที่ยังไม่ถูกลบ)
                            $sql = "SELECT * FROM users WHERE is_deleted = 0";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td data-label='ชื่อผู้ใช้'>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td data-label='ตำแหน่ง'>" . htmlspecialchars($row['role']) . "</td>";
                                    echo "<td data-label='สร้างเมื่อ'>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "<td data-label='จัดการ' class='user-actions'>";
                                    echo "<a href='edit_user.php?id=" . htmlspecialchars($row['id']) . "' class='edit-btn'>แก้ไข</a>";
                                    echo "<a href='user.php?delete=" . htmlspecialchars($row['id']) . "' class='delete-btn'>ลบ</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>ไม่พบข้อมูลผู้ใช้</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>