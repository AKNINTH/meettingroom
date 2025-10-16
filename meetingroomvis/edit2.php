<?php
session_start();
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

// ฟังก์ชันสำหรับดึงข้อมูลจากฐานข้อมูล
function fetchOptions($conn, $table) {
    $options = [];
    $sql = "SELECT * FROM $table";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
    }
    return $options;
}

// ดึงข้อมูลตัวเลือกจากฐานข้อมูล
$departments = fetchOptions($conn, "departments");
$catering_options = fetchOptions($conn, "catering_options");
$layouts = fetchOptions($conn, "layouts");
$facilities = fetchOptions($conn, "facilities");
$rooms = fetchOptions($conn, "rooms"); // เพิ่มการดึงข้อมูลห้องประชุม

$booking_data = [];
$booking_id = '';

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    // ดึงข้อมูลการจองจากฐานข้อมูลตาม ID
    $sql = "SELECT * FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
    } else {
        echo "<script>alert('ไม่พบข้อมูลการจอง'); window.location.href='details.php';</script>";
        exit();
    }
    $stmt->close();
}

// ตรวจสอบว่ามีการส่งข้อมูลจากฟอร์มเพื่อแก้ไขหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $booking_id_to_update = $_POST['booking_id'];
    $title = $_POST['title'];
    $booker_name = $_POST['booker_name'];
    $department = $_POST['department'];
    $room = $_POST['room_name'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $food_beverage = $_POST['food_beverage'];
    $table_layout = $_POST['table_layout'];
    $facilities_str = isset($_POST['facilities']) ? implode(', ', $_POST['facilities']) : '';
    $other_requirements = $_POST['other_requirements'];

    // ตรวจสอบเวลาทับซ้อน
    $sql_conflict = "SELECT * FROM bookings WHERE room = ? AND date = ? AND id != ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))";
    $stmt_conflict = $conn->prepare($sql_conflict);
    // 7 ตัวแปร: room, date, id, end_time, start_time, start_time, end_time
    $stmt_conflict->bind_param("ssissss", $room, $date, $booking_id_to_update, $end_time, $start_time, $start_time, $end_time);
    $stmt_conflict->execute();
    $result_conflict = $stmt_conflict->get_result();
    if ($result_conflict->num_rows > 0) {
        $_SESSION['error_message'] = 'ไม่สามารถบันทึกได้ เนื่องจากช่วงเวลานี้มีการจองห้องนี้แล้ว';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        $stmt_conflict->close();
        $conn->close();
        exit();
    }
    $stmt_conflict->close();

    // อัปเดตข้อมูล
    $sql_update = "UPDATE bookings SET title = ?, booker_name = ?, department = ?, room = ?, date = ?, start_time = ?, end_time = ?, food_beverage = ?, table_layout = ?, facilities = ?, other_requirements = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update === false) {
        die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    $stmt_update->bind_param("sssssssssssi", $title, $booker_name, $department, $room, $date, $start_time, $end_time, $food_beverage, $table_layout, $facilities_str, $other_requirements, $booking_id_to_update);
    if ($stmt_update->execute()) {
        header('Location: details2.php');
        exit();
    } else {
        echo "<script>console.error('เกิดข้อผิดพลาดในการแก้ไขข้อมูล: " . $stmt_update->error . "');</script>";
    }
    $stmt_update->close();
    $conn->close();
    exit();
}

// ตรวจสอบว่ามีข้อมูลการจองหรือไม่
if (empty($booking_data)) {
    echo "<script>alert('ไม่พบข้อมูลการจอง'); window.location.href='details2.php';</script>";
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลการจอง</title>
    <link rel="stylesheet" href="edit2.css">
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
        <div class="main-content">
            <div class="form-container">
                <div class="ha23">
                    <h2>แก้ไขข้อมูลการจอง</h2>
                </div>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="error-message">
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']);
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>
                <form id="editForm" method="POST" action="edit2.php">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                    <div class="form-grid">
                        <div class="section-title">ข้อมูลการจอง</div>
                        <div class="full">
                            <label for="title">หัวข้อเรื่อง:</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($booking_data['title'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="booker_name">ชื่อผู้จอง:</label>
                            <input type="text" id="booker_name" name="booker_name" value="<?php echo htmlspecialchars($booking_data['booker_name'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="department">แผนก:</label>
                            <select id="department" name="department" required>
                                <option value="">--เลือกแผนก--</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department['department_name']); ?>" <?php echo (isset($booking_data['department']) && $booking_data['department'] == $department['department_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="room_name">ห้องประชุม:</label>
                            <select id="room_name" name="room_name" required>
                                <option value="">-- กรุณาเลือกห้องประชุม --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['room_name']); ?>" <?php echo (isset($booking_data['room']) && trim($booking_data['room']) === trim($room['room_name']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($room['room_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="section-title">วันที่และเวลา</div>
                        <div>
                            <label for="date">วันที่:</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($booking_data['date'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="start_time">เวลาเริ่มต้น:</label>
                            <input type="time" id="start_time" name="start_time" value="<?php echo htmlspecialchars($booking_data['start_time'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="end_time">เวลาสิ้นสุด:</label>
                            <input type="time" id="end_time" name="end_time" value="<?php echo htmlspecialchars($booking_data['end_time'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="food_beverage">อาหารและเครื่องดื่ม:</label>
                            <select id="food_beverage" name="food_beverage">
                                <option value="">--เลือก--</option>
                                <?php foreach ($catering_options as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option['option_name']); ?>" <?php echo (isset($booking_data['food_beverage']) && trim($booking_data['food_beverage']) === trim($option['option_name']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($option['option_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="table_layout">รูปแบบการจัดโต๊ะประชุม:</label>
                            <select id="table_layout" name="table_layout">
                                <option value="">--เลือก--</option>
                                <?php foreach ($layouts as $layout): ?>
                                    <option value="<?php echo htmlspecialchars($layout['layout_name']); ?>" <?php echo (isset($booking_data['table_layout']) && trim($booking_data['table_layout']) === trim($layout['layout_name']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($layout['layout_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="section-title">อุปกรณ์และความต้องการ</div>
                        <div class="full">
                            <label>อุปกรณ์และสิ่งอำนวยความสะดวก:</label>
                            <div class="checkbox-group">
                                <?php $facilities_array = explode(', ', $booking_data['facilities'] ?? ''); ?>
                                <?php foreach ($facilities as $facility): ?>
                                    <label>
                                        <input type="checkbox" name="facilities[]" value="<?php echo htmlspecialchars($facility['facility_name']); ?>" <?php echo in_array($facility['facility_name'], $facilities_array) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($facility['facility_name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="full">
                            <label for="other_requirements">ความต้องการอื่นๆ:</label>
                            <textarea id="other_requirements" name="other_requirements" rows="4"><?php echo htmlspecialchars($booking_data['other_requirements'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save">บันทึกการแก้ไข</button>
                        <button type="submit" formaction="soft_delete2.php" formnovalidate class="danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการย้ายข้อมูลนี้ไปที่ถังขยะ?');">ลบข้อมูล</button>
                        <div class="links">
                            <a href="main.php" class="button_back">กลับไปหน้าแรก</a>
                            <a href="details2.php" class="button_back">ย้อนกลับ</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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
        <div class="main-content">
            <div class="form-container">
                <div class="ha23">
                    <h2>แก้ไขข้อมูลการจอง</h2>
                </div>
                <form id="editForm" method="POST" action="edit2.php">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                    <div class="form-grid">
                        <div class="full">
                            <label for="title">หัวข้อเรื่อง:</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($booking_data['title'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="booker_name">ชื่อผู้จอง:</label>
                            <input type="text" id="booker_name" name="booker_name" value="<?php echo htmlspecialchars($booking_data['booker_name'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="department">แผนก:</label>
                            <select id="department" name="department" required>
                                <option value="">--เลือกแผนก--</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department['department_name']); ?>" <?php echo (isset($booking_data['department']) && $booking_data['department'] == $department['department_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="room_name">ห้องประชุม:</label>
                            <select id="room_name" name="room_name" required>
                                <option value="">-- กรุณาเลือกห้องประชุม --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['room_name']); ?>" <?php echo (isset($booking_data['room']) && trim($booking_data['room']) === trim($room['room_name']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($room['room_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="date">วันที่:</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($booking_data['date'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="start_time">เวลาเริ่มต้น:</label>
                            <input type="time" id="start_time" name="start_time" value="<?php echo htmlspecialchars($booking_data['start_time'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="end_time">เวลาสิ้นสุด:</label>
                            <input type="time" id="end_time" name="end_time" value="<?php echo htmlspecialchars($booking_data['end_time'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label for="food_beverage">อาหารและเครื่องดื่ม:</label>
                            <select id="food_beverage" name="food_beverage">
                                <option value="">--เลือก--</option>
                                <?php foreach ($catering_options as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option['option_name']); ?>" <?php echo (isset($booking_data['food_beverage']) && trim($booking_data['food_beverage']) === trim($option['option_name']) ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($option['option_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="table_layout">รูปแบบการจัดโต๊ะประชุม:</label>
                            <select id="table_layout" name="table_layout">
                                <option value="">--เลือก--</option>
                                <?php foreach ($layouts as $layout): ?>
                                    <option value="<?php echo htmlspecialchars($layout['layout_name']); ?>" <?php echo (isset($booking_data['table_layout']) && $booking_data['table_layout'] == $layout['layout_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($layout['layout_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="full">
                            <label>อุปกรณ์และสิ่งอำนวยความสะดวก:</label>
                            <div class="checkbox-group">
                                <?php $facilities_array = explode(', ', $booking_data['facilities'] ?? ''); ?>
                                <?php foreach ($facilities as $facility): ?>
                                    <label>
                                        <input type="checkbox" name="facilities[]" value="<?php echo htmlspecialchars($facility['facility_name']); ?>" <?php echo in_array($facility['facility_name'], $facilities_array) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($facility['facility_name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="full">
                            <label for="other_requirements">ความต้องการอื่นๆ:</label>
                            <textarea id="other_requirements" name="other_requirements" rows="4"><?php echo htmlspecialchars($booking_data['other_requirements'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="save" id="saveBtn">บันทึกการแก้ไข</button>
                        <button type="button" class="danger" id="deleteBtn">ลบข้อมูล</button>
                        <div class="links">
                            <a href="main.php" class="button_back">กลับไปหน้าแรก</a>
                            <a href="details2.php" class="button_back">ย้อนกลับ</a>
                        </div>
                    </div>
                </form>

                <!-- Modal Popup สำหรับการลบ -->
                <div id="deleteConfirmModal" class="modal">
                    <div class="modal-content">
                        <h3 class="modal-title">ยืนยันการลบข้อมูล</h3>
                        <p>คุณแน่ใจหรือไม่ว่าต้องการย้ายข้อมูลนี้ไปที่ถังขยะ?</p>
                        <div class="modal-buttons">
                            <button class="modal-confirm" id="confirmDelete">ยืนยัน</button>
                            <button class="modal-cancel" id="cancelDelete">ยกเลิก</button>
                        </div>
                    </div>
                </div>

                <!-- Modal Popup สำหรับการบันทึก -->
                <div id="saveConfirmModal" class="modal">
                    <div class="modal-content">
                        <h3 class="modal-title">ยืนยันการบันทึก</h3>
                        <p>คุณแน่ใจหรือไม่ว่าต้องการบันทึกการเปลี่ยนแปลงนี้?</p>
                        <div class="modal-buttons">
                            <button class="modal-confirm save-confirm" id="confirmSave">ยืนยัน</button>
                            <button class="modal-cancel" id="cancelSave">ยกเลิก</button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const deleteModal = document.getElementById('deleteConfirmModal');
                        const saveModal = document.getElementById('saveConfirmModal');
                        const deleteBtn = document.getElementById('deleteBtn');
                        const saveBtn = document.getElementById('saveBtn');
                        const confirmDeleteBtn = document.getElementById('confirmDelete');
                        const cancelDeleteBtn = document.getElementById('cancelDelete');
                        const confirmSaveBtn = document.getElementById('confirmSave');
                        const cancelSaveBtn = document.getElementById('cancelSave');
                        const form = document.getElementById('editForm');

                        // เมื่อคลิกปุ่มลบ
                        deleteBtn.addEventListener('click', function() {
                            deleteModal.style.display = 'block';
                        });

                        // เมื่อคลิกปุ่มบันทึก
                        saveBtn.addEventListener('click', function() {
                            saveModal.style.display = 'block';
                        });

                        // เมื่อคลิกปุ่มยืนยันการลบ
                        confirmDeleteBtn.addEventListener('click', function() {
                            form.action = 'soft_delete2.php';
                            form.submit();
                        });

                        // เมื่อคลิกปุ่มยืนยันการบันทึก
                        confirmSaveBtn.addEventListener('click', function() {
                            form.action = 'edit2.php';
                            form.submit();
                        });

                        // เมื่อคลิกปุ่มยกเลิกการลบ
                        cancelDeleteBtn.addEventListener('click', function() {
                            deleteModal.style.display = 'none';
                        });

                        // เมื่อคลิกปุ่มยกเลิกการบันทึก
                        cancelSaveBtn.addEventListener('click', function() {
                            saveModal.style.display = 'none';
                        });

                        // ปิด modal เมื่อคลิกพื้นหลัง
                        window.addEventListener('click', function(event) {
                            if (event.target == deleteModal) {
                                deleteModal.style.display = 'none';
                            }
                            if (event.target == saveModal) {
                                saveModal.style.display = 'none';
                            }
                        });

                        // ปิด modal เมื่อกด ESC
                        document.addEventListener('keydown', function(event) {
                            if (event.key === 'Escape') {
                                deleteModal.style.display = 'none';
                                saveModal.style.display = 'none';
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>