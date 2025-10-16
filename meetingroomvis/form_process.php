<?php
require_once 'auth.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการจองห้องประชุม</title>
    <link rel="stylesheet" href="form_process.css">
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
        <div class="button-group">
            <input class="back-button" type="button" value="🏠 ไปหน้าแรก" onclick="window.location.href='main.php'">
            <input class="detail-button" type="button" value="📋 ดูรายการ" onclick="window.location.href='details2.php'">

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $meeting_title = $_POST['meeting_title'];
            $organizer = $_POST['organizer'];
            $department = $_POST['department'];
            $room_name = $_POST['room_name'];
            $booking_date = $_POST['booking_date'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $catering = $_POST['catering'];
            $layout = $_POST['layout'];
            $details2 = $_POST['details2'];
            $facilities = isset($_POST['facilities']) ? $_POST['facilities'] : [];

            // จัดเตรียม URL สำหรับปุ่ม "แก้ไข"
            $editParams = [
                'meeting_title' => $meeting_title,
                'organizer' => $organizer,
                'department' => $department,
                'room_name' => $room_name,
                'booking_date' => $booking_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'catering' => $catering,
                'layout' => $layout,
                'details2' => $details2,
                'facilities' => $facilities
            ];
            $editUrl = 'form.php?' . http_build_query($editParams);
        ?>

            <input class="edit-button" type="button" value="✏️ แก้ไข" onclick="window.location.href='<?= htmlspecialchars($editUrl) ?>'">
        </div>

        <?php
            // การเชื่อมต่อฐานข้อมูล
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "reservemeeting";

            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($start_time >= $end_time) {
                    echo "<h1 style='color:red'>เกิดข้อผิดพลาด</h1>";
                    echo "<p style='color:red'>เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด</p>";
                    exit;
                }

                // ตรวจสอบเวลาทับซ้อน
                $sql_check = "SELECT * FROM bookings WHERE room = :room AND date = :date AND is_deleted = 0
                    AND ((:start_time < end_time AND :end_time > start_time))";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bindParam(':room', $room_name);
                $stmt_check->bindParam(':date', $booking_date);
                $stmt_check->bindParam(':start_time', $start_time);
                $stmt_check->bindParam(':end_time', $end_time);
                $stmt_check->execute();

                if ($stmt_check->rowCount() > 0) {
                    echo "<h1 style='color:red'>เกิดข้อผิดพลาด</h1>";
                    echo "<p style='color:red'>ช่วงเวลานี้มีการจองห้องประชุมนี้แล้ว กรุณาเลือกเวลาใหม่</p>";
                    // แสดงข้อมูลที่กรอก
                    echo "<p><strong>หัวข้อ:</strong> " . htmlspecialchars($meeting_title) . "</p>";
                    echo "<p><strong>ผู้จัด:</strong> " . htmlspecialchars($organizer) . "</p>";
                    echo "<p><strong>แผนก:</strong> " . htmlspecialchars($department) . "</p>";
                    echo "<p><strong>ห้อง:</strong> " . htmlspecialchars($room_name) . "</p>";
                    echo "<p><strong>วันที่:</strong> " . htmlspecialchars($booking_date) . "</p>";
                    echo "<p style='color:red'><strong>เวลา:</strong>" . htmlspecialchars($start_time) . " - " . htmlspecialchars($end_time) . "</p>";
                    echo "<p><strong>อาหาร:</strong> " . htmlspecialchars($catering) . "</p>";
                    echo "<p><strong>โต๊ะ:</strong> " . htmlspecialchars($layout) . "</p>";
                    echo "<p><strong>อุปกรณ์:</strong> " . htmlspecialchars(implode(', ', $facilities)) . "</p>";
                    echo "<p><strong>รายละเอียด:</strong> " . htmlspecialchars($details2) . "</p>";
                    exit;
                }

                // บันทึกข้อมูล
                $sql = "INSERT INTO bookings (
                    title, booker_name, department, start_time, end_time, date,
                    food_beverage, table_layout, facilities, other_requirements, room
                ) VALUES (
                    :title, :booker_name, :department, :start_time, :end_time, :date,
                    :food_beverage, :table_layout, :facilities, :other_requirements, :room
                )";

                $stmt = $conn->prepare($sql);
                // แปลง facilities array เป็น string ก่อน
                $facilities_str = implode(', ', $facilities);

                $stmt->bindParam(':title', $meeting_title);
                $stmt->bindParam(':booker_name', $organizer);
                $stmt->bindParam(':department', $department);
                $stmt->bindParam(':start_time', $start_time);
                $stmt->bindParam(':end_time', $end_time);
                $stmt->bindParam(':date', $booking_date);
                $stmt->bindParam(':food_beverage', $catering);
                $stmt->bindParam(':table_layout', $layout);
                $stmt->bindParam(':facilities', $facilities_str);
                $stmt->bindParam(':other_requirements', $details2);
                $stmt->bindParam(':room', $room_name);

                $stmt->execute();

                echo '<div class="booking-success">';
                echo '<h1 style="color:green">🎉การจองห้องประชุมสำเร็จ! &#x2705;</h1>';
                echo '</div>';
                
                echo '<div class="booking-details">';
                
                // ข้อมูลการประชุม
                echo '<div class="detail-item">';
                echo '<div class="detail-label">หัวข้อ:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($meeting_title) . '</div>';
                echo '</div>';
                
                echo '<div class="detail-item">';
                echo '<div class="detail-label">ผู้จัด:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($organizer) . '</div>';
                echo '</div>';
                
                echo '<div class="detail-item">';
                echo '<div class="detail-label">แผนก:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($department) . '</div>';
                echo '</div>';
                
                // ข้อมูลห้องและเวลา
                echo '<div class="detail-item">';
                echo '<div class="detail-label">ห้อง:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($room_name) . '</div>';
                echo '</div>';
                
                echo '<div class="detail-item">';
                echo '<div class="detail-label">วันที่:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($booking_date) . '</div>';
                echo '</div>';
                
                echo '<div class="detail-item">';
                echo '<div class="detail-label">เวลา:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($start_time) . ' - ' . htmlspecialchars($end_time) . '</div>';
                echo '</div>';
                
                // ข้อมูลการจัดห้อง
                echo '<div class="detail-item">';
                echo '<div class="detail-label">อาหาร:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($catering) . '</div>';
                echo '</div>';
                
                echo '<div class="detail-item">';
                echo '<div class="detail-label">รูปแบบโต๊ะ:</div>';
                echo '<div class="detail-value">' . htmlspecialchars($layout) . '</div>';
                echo '</div>';
                
                echo '<div class="detail-item">';
                echo '<div class="detail-label">อุปกรณ์:</div>';
                echo '<div class="detail-value">' . htmlspecialchars(implode(', ', $facilities)) . '</div>';
                echo '</div>';
                
                if (!empty($details2)) {
                    echo '<div class="detail-item">';
                    echo '<div class="detail-label">รายละเอียดเพิ่มเติม:</div>';
                    echo '<div class="detail-value">' . htmlspecialchars($details2) . '</div>';
                    echo '</div>';
                }
                
                echo '</div>';

            } catch (PDOException $e) {
                echo "<h1 style='color:red'>เกิดข้อผิดพลาด</h1>";
                echo "<p>" . $e->getMessage() . "</p>";
                exit;
            } finally {
                $conn = null;
            }
        } else {
            header("Location: form.php");
            exit();
        }
        ?>
    </div>
</body>
</html>
