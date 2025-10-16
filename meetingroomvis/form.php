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

// ฟังก์ชันสำหรับดึงข้อมูลจากฐานข้อมูล
function fetchOptions($conn, $table) {
    $options = [];
    $sql = "SELECT * FROM $table WHERE is_deleted = 0";
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
$rooms = fetchOptions($conn, "rooms");
$catering_options = fetchOptions($conn, "catering_options");
$layouts = fetchOptions($conn, "layouts");
$facilities = fetchOptions($conn, "facilities");

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฟอร์มจองห้องประชุม</title>
    <link rel="stylesheet" href="form.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
            text-align: center;
        }

        .modal-title {
            margin-top: 0;
            color: #333;
        }

        .modal-buttons {
            margin-top: 20px;
            text-align: center;
        }

        .modal-ok {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
        }

        .modal-ok:hover {
            background-color: #0056b3;
        }

        /* สำหรับ alert แจ้งเตือนข้อผิดพลาด */
        .modal.error .modal-content {
            border-top: 4px solid #dc3545;
        }

        .modal.error .modal-ok {
            background-color: #dc3545;
        }

        .modal.error .modal-ok:hover {
            background-color: #c82333;
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
        <?php
            $meeting_title = $_GET['meeting_title'] ?? '';
            $organizer = $_GET['organizer'] ?? '';
            $department = $_GET['department'] ?? '';
            $room_name = $_GET['room_name'] ?? '';
            $booking_date = $_GET['booking_date'] ?? '';
            $start_time = $_GET['start_time'] ?? '';
            $end_time = $_GET['end_time'] ?? '';
            $catering = $_GET['catering'] ?? '';
            $layout = $_GET['layout'] ?? '';
            $details2 = $_GET['details2'] ?? '';
            $facilities_selected = $_GET['facilities'] ?? [];
        ?>
            <main class="content">
                <input class="back-button" type="button" value="ย้อนกลับ" onclick="window.location.href='main.php'">
                <h2>จองห้องประชุม</h2>
                <form action="form_process.php" method="POST">
                    <label for="meeting_title">หัวข้อการประชุม:</label><br>
                    <input type="text" id="meeting_title" name="meeting_title" required value="<?= htmlspecialchars($meeting_title) ?>"><br><br>

                    <label for="organizer">ชื่อผู้จอง:</label><br>
                    <input type="text" id="organizer" name="organizer" required value="<?= htmlspecialchars($organizer) ?>"><br><br>

                    <label for="department">แผนก(ผู้จอง):</label><br>
                    <select id="department" name="department" required>
                        <option value="">--เลือกแผนก--</option>
                        <?php foreach ($departments as $dep): ?>
                            <option value="<?= htmlspecialchars($dep['department_name']) ?>"
                                <?= ($dep['department_name'] == $department ? 'selected' : '') ?>>
                                <?= htmlspecialchars($dep['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label for="room_name">ห้องประชุม:</label><br>
                    <select id="room_name" name="room_name" required>
                        <option value="">-- กรุณาเลือกห้องประชุม --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= htmlspecialchars($room['room_name']) ?>"
                                <?= ($room['room_name'] == $room_name ? 'selected' : '') ?>>
                                <?= htmlspecialchars($room['room_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label for="booking_date">วันที่:</label><br>
                    <input type="date" id="booking_date" name="booking_date" required value="<?= htmlspecialchars($booking_date) ?>"><br><br>

                    <label for="start_time">เวลาเริ่มต้น:</label><br>
                    <input type="time" id="start_time" name="start_time" required value="<?= htmlspecialchars($start_time) ?>"><br><br>

                    <label for="end_time">เวลาสิ้นสุด:</label><br>
                    <input type="time" id="end_time" name="end_time" required value="<?= htmlspecialchars($end_time) ?>"><br><br>

                    <label for="catering">อาหารและเครื่องดื่ม:</label><br>
                    <select id="catering" name="catering">
                        <?php foreach ($catering_options as $option): ?>
                            <option value="<?= htmlspecialchars($option['option_name']) ?>"
                                <?= ($option['option_name'] == $catering ? 'selected' : '') ?>>
                                <?= htmlspecialchars($option['option_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label for="layout">รูปแบบการจัดโต๊ะประชุม:</label><br>
                    <select id="layout" name="layout">
                        <option value="">--เลือก--</option>
                        <?php foreach ($layouts as $lay): ?>
                            <option value="<?= htmlspecialchars($lay['layout_name']) ?>"
                                <?= ($lay['layout_name'] == $layout ? 'selected' : '') ?>>
                                <?= htmlspecialchars($lay['layout_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>อุปกรณ์และสิ่งอำนวยความสะดวก:</label><br>
                    <div class="checkbox-group">
                        <?php foreach ($facilities as $facility): ?>
                            <label>
                                <input type="checkbox" name="facilities[]" value="<?= htmlspecialchars($facility['facility_name']) ?>"
                                    <?= (in_array($facility['facility_name'], $facilities_selected) ? 'checked' : '') ?>>
                                <?= htmlspecialchars($facility['facility_name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div><br>

                    <label for="details2">รายละเอียดเพิ่มเติม:</label><br>
                    <textarea id="details2" name="details2" rows="4" cols="50"><?= htmlspecialchars($details2) ?></textarea><br><br>

                    <input type="submit" value="ยืนยันการจอง" id="submitBtn">
                </form>
            </main>

    </div>
<script>
// Autofill date/room from URL
const urlParams = new URLSearchParams(window.location.search);
const selectedDate = urlParams.get('date');
if (selectedDate) {
    const bookingDateField = document.getElementById('booking_date');
    if (bookingDateField) bookingDateField.value = selectedDate;
}
const selectedRoom = urlParams.get('room');
if (selectedRoom) {
    const roomNameField = document.getElementById('room_name');
    if (roomNameField) roomNameField.value = decodeURIComponent(selectedRoom);
}

// สร้าง modal element
const modalHtml = `
    <div id="alertModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title"></h3>
            <p class="modal-message"></p>
            <div class="modal-buttons">
                <button class="modal-ok">ตกลง</button>
            </div>
        </div>
    </div>`;
document.body.insertAdjacentHTML('beforeend', modalHtml);

// ฟังก์ชันแสดง modal alert
function showAlert(title, message, isError = false) {
    const modal = document.getElementById('alertModal');
    modal.className = 'modal' + (isError ? ' error' : '');
    modal.querySelector('.modal-title').textContent = title;
    modal.querySelector('.modal-message').textContent = message;
    modal.style.display = 'block';

    return new Promise((resolve) => {
        const okButton = modal.querySelector('.modal-ok');
        const handleClose = () => {
            modal.style.display = 'none';
            resolve();
            okButton.removeEventListener('click', handleClose);
        };
        okButton.addEventListener('click', handleClose);
    });
}

const bookingDateField = document.getElementById('booking_date');
const today = new Date();
today.setDate(today.getDate() + 1); // วันพรุ่งนี้
const yyyy = today.getFullYear();
const mm = String(today.getMonth() + 1).padStart(2, '0');
const dd = String(today.getDate()).padStart(2, '0');
const minDate = `${yyyy}-${mm}-${dd}`;
bookingDateField.min = minDate;

// เพิ่มแจ้งเตือนเมื่อเลือกวันที่ไม่ถูกต้อง
document.getElementById('submitBtn').addEventListener('click', async function(e) {
    e.preventDefault();

    const selectedDate = bookingDateField.value;
    if (selectedDate && selectedDate < minDate) {
        await showAlert(
            "❌ วันที่ไม่ถูกต้อง",
            `วันที่ต้องเป็นวันถัดไปหรือวันในอนาคตเท่านั้น (หลัง ${minDate})`,
            true
        );
        return;
    }

    // ตรวจสอบเวลาเริ่ม < เวลาสิ้นสุด
    const start = document.getElementById('start_time').value;
    const end = document.getElementById('end_time').value;
    if (start && end && start >= end) {
        await showAlert(
            "❌ เวลาไม่ถูกต้อง",
            "เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด",
            true
        );
        return;
    }

    // ตรวจสอบ checkbox facilities อย่างน้อย 1
    const facilities = document.querySelectorAll('input[name="facilities[]"]:checked');
    if (facilities.length === 0) {
        await showAlert(
            "❌ ไม่ได้เลือกอุปกรณ์",
            "กรุณาเลือกอุปกรณ์หรือสิ่งอำนวยความสะดวกอย่างน้อย 1 รายการ",
            true
        );
        return;
    }

    // ถ้าผ่านการตรวจสอบทั้งหมด
    document.querySelector('form').submit();
});

// ถ้ามี error=1 แปลว่ากลับมาจากการบันทึกล้มเหลว → เติมค่าที่กรอกไว้ก่อนหน้า
if (urlParams.get('error') === '1') {
    const fields = [
        'meeting_title', 'organizer', 'department', 'room_name',
        'booking_date', 'start_time', 'end_time',
        'catering', 'layout', 'details2'
    ];

    fields.forEach(id => {
        const field = document.getElementById(id);
        if (field && urlParams.get(id)) {
            field.value = decodeURIComponent(urlParams.get(id));
        }
    });

    // คืนค่า checkbox facilities
    const selectedFacilities = urlParams.getAll('facilities[]');
    if (selectedFacilities.length > 0) {
        document.querySelectorAll('input[name="facilities[]"]').forEach(cb => {
            if (selectedFacilities.includes(cb.value)) {
                cb.checked = true;
            }
        });
    }

    showAlert(
        "❌ การบันทึกล้มเหลว",
        "ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง",
        true
    );
}

</script>

</body>
</html>