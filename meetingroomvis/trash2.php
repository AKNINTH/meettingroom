<?php
require_once 'auth.php';
requireLogin();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8"); 

// โค้ดสำหรับกู้คืนข้อมูล
if (isset($_GET['restore_id'])) {
    $restore_id = $_GET['restore_id'];
    $sql_restore = "UPDATE bookings SET is_deleted = FALSE WHERE id = ?";
    $stmt_restore = $conn->prepare($sql_restore);
    $stmt_restore->bind_param("i", $restore_id);
    if ($stmt_restore->execute()) {
        $_SESSION['success_message'] = 'กู้คืนข้อมูลสำเร็จ';
        header('Location: trash2.php');
    } else {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการกู้คืน';
        header('Location: trash2.php');
    }
    $stmt_restore->close();
    exit();
}

// โค้ดสำหรับลบข้อมูลถาวร
if (isset($_GET['hard_delete_id'])) {
    $hard_delete_id = $_GET['hard_delete_id'];
    $sql_hard_delete = "DELETE FROM bookings WHERE id = ?";
    $stmt_hard_delete = $conn->prepare($sql_hard_delete);
    $stmt_hard_delete->bind_param("i", $hard_delete_id);
    if ($stmt_hard_delete->execute()) {
        $_SESSION['success_message'] = 'ลบข้อมูลถาวรแล้ว';
        header('Location: trash2.php');
    } else {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการลบ';
        header('Location: trash2.php');
    }
    $stmt_hard_delete->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลการจอง</title>
    <link rel="stylesheet" href="trash2.css">
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

        <div class="main-content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            <div>
                <h2>ถังขยะ</h2>
                <div class="text-right">
                    <a href="details2.php" class="back-button">ย้อนกลับ</a>
                </div>
                    <table>
                        <thead>
                            <tr>
                                <th>หัวข้อ</th>
                                <th>ชื่อผู้จอง</th>
                                <th>วันที่</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM bookings WHERE is_deleted = TRUE";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['booker_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                                echo "<td>";
                                echo " | ";
                                echo "<a href='#' onclick='showRestoreModal(" . $row['id'] . ")' class='btn btn-restore'>กู้คืน</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>ไม่มีข้อมูลในถังขยะ</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Restore Modal -->
    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">ยืนยันการกู้คืน</h3>
            <p>คุณต้องการกู้คืนข้อมูลนี้หรือไม่?</p>
            <div class="modal-buttons">
                <button class="modal-confirm" id="confirmRestore" onclick="restoreBooking()">ยืนยัน</button>
                <button class="modal-cancel" onclick="hideRestoreModal()">ยกเลิก</button>
            </div>
        </div>
    </div>

    <script>
    let currentBookingId = null;

    function showRestoreModal(bookingId) {
        currentBookingId = bookingId;
        document.getElementById('restoreModal').style.display = 'block';
    }

    function hideRestoreModal() {
        document.getElementById('restoreModal').style.display = 'none';
    }

    function restoreBooking() {
        if (currentBookingId) {
            window.location.href = 'trash2.php?restore_id=' + currentBookingId;
        }
    }

    // ปิด modal เมื่อคลิกพื้นหลัง
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('restoreModal');
        if (event.target == modal) {
            hideRestoreModal();
        }
    });

    // ปิด modal เมื่อกด ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideRestoreModal();
        }
    });
    </script>
</body>
</html>