<?php
session_start();
require_once 'auth.php';
requireLogin();

// กำหนดข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting"; // แก้ไขเป็นชื่อฐานข้อมูลของคุณ

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8"); 

// คำสั่ง SQL สำหรับดึงข้อมูลการจองทั้งหมดที่ยังไม่ถูกลบ

// รับค่าค้นหา
$title = isset($_GET['title']) ? trim($_GET['title']) : '';
$booker = isset($_GET['booker']) ? trim($_GET['booker']) : '';
$room = isset($_GET['room']) ? trim($_GET['room']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

// สร้าง WHERE ตามช่องค้นหา
$where = "is_deleted = FALSE";
if ($title !== '') {
    $title_sql = $conn->real_escape_string($title);
    $where .= " AND title LIKE '%$title_sql%'";
}
if ($booker !== '') {
    $booker_sql = $conn->real_escape_string($booker);
    $where .= " AND booker_name LIKE '%$booker_sql%'";
}
if ($room !== '') {
    $room_sql = $conn->real_escape_string($room);
    $where .= " AND room = '$room_sql'";
}
if ($date !== '') {
    $date_sql = $conn->real_escape_string($date);
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_sql)) {
        $date_sql = date('Y-m-d', strtotime(str_replace('-', '/', $date_sql)));
    }
    $where .= " AND date = '$date_sql'";
}

$sql = "SELECT * FROM bookings WHERE $where ORDER BY date DESC, start_time DESC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจอง</title>
    <link rel="stylesheet" href="details2.css">
    <style>
        /* Zoom Settings Modal Styles */
        .modal-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-bg.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            right: 10px;
            top: 10px;
            border: none;
            background: none;
            font-size: 24px;
            cursor: pointer;
        }
        .zoom-settings-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-buttons button {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .save-btn {
            background: #2196F3;
            color: white;
        }
        .cancel-btn {
            background: #6c757d;
            color: white;
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
        <div class="main-content">
            <div class="details-container">
                <h2>รายละเอียดการจองทั้งหมด</h2>
                <?php
                // ดึงรายชื่อห้องประชุมสำหรับ dropdown
                // ดึงรายชื่อห้องประชุมจาก rooms สำหรับ dropdown
                // ดึงรายชื่อห้องประชุมจาก rooms สำหรับ dropdown
                $rooms_result_user = $conn->query("SELECT room_name FROM rooms WHERE is_deleted = 0 ORDER BY room_name ASC");
                $room_options_user = [];
                if ($rooms_result_user) {
                    while ($r = $rooms_result_user->fetch_assoc()) {
                        $room_options_user[] = $r['room_name'];
                    }
                } else {
                    echo '<div style="color:red">เกิดข้อผิดพลาดในการดึงข้อมูลห้องประชุม (user): ' . $conn->error . '</div>';
                }

                // รับค่าค้นหา
                $title = isset($_GET['title']) ? trim($_GET['title']) : '';
                $booker = isset($_GET['booker']) ? trim($_GET['booker']) : '';
                $room = isset($_GET['room']) ? trim($_GET['room']) : '';
                $date = isset($_GET['date']) ? trim($_GET['date']) : '';
                ?>
                <form method="get" style="margin-bottom: 1rem; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="title" placeholder="หัวข้อ" value="<?php echo htmlspecialchars($title); ?>" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                    <input type="text" name="booker" placeholder="ชื่อผู้จอง" value="<?php echo htmlspecialchars($booker); ?>" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                    <select name="room" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                        <option value="">-- ห้องประชุม --</option>
                        <?php foreach ($room_options_user as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($room === $opt) echo 'selected'; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                    <button type="submit" style="padding:8px 16px; border-radius:5px; background:#2196F3; color:#fff; border:none; font-weight:bold;">ค้นหา</button>
                    <?php if ($title || $booker || $room || $date): ?>
                        <a href="details2.php" style="padding:8px 16px; border-radius:5px; background:#6c757d; color:#fff; text-decoration:none;">ล้างค้นหา</a>
                    <?php endif; ?>
                </form>
                <div class="text-right">
                    <a href="main.php" class="back-button">ย้อนกลับ</a>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                    <div>
                        <table>
                            <thead>
                                <tr>
                                    <th>วัน/เดือน/ปี</th>
                                    <th>ห้องประชุม</th>
                                    <th>เวลา</th>
                                    <th>หัวข้อ</th>
                                    <th>ผู้จอง</th>
                                    <th>แผนก</th>
                                    <th>การจัดโต๊ะ</th>
                                    <th>อาหาร</th>
                                    <th>อุปกรณ์</th>
                                    <th>ความต้องการอื่น ๆ</th>
                                    <th>ใบรายการอาหาร</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['room']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_time']) . ' - ' . htmlspecialchars($row['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['booker_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td><?php echo htmlspecialchars($row['table_layout']); ?></td>
                                    <td><?php echo htmlspecialchars($row['food_beverage']); ?></td>
                                    <td><?php echo htmlspecialchars($row['facilities']); ?></td>
                                    <td style="max-width: 200px; word-break: break-word;"><?php echo nl2br(htmlspecialchars($row['other_requirements'])); ?></td>
                                        <td>
                                            <button class="food-pdf-btn">ใบรายการอาหาร</button>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if (!empty($row['id_zoom'])): ?>
                                                    <a href="https://zoom.us/j/<?php echo htmlspecialchars($row['id_zoom']); ?>" target="_blank" class="zoom-button">ไปยัง Zoom</a>
                                                <?php else: ?>
                                                    <span class="zoom-button disabled" title="ไม่พบการตั้งค่า Zoom">ไปยัง Zoom</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Modal for PDF preview -->
                    <div id="foodPdfModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
                        <div style="background:#fff;padding:20px;max-width:90vw;max-height:90vh;overflow:auto;position:relative;">
                            <button onclick="document.getElementById('foodPdfModal').style.display='none'" style="position:absolute;top:10px;right:10px;font-size:1.5em;">&times;</button>
                            <iframe id="foodPdfFrame" src="" style="width:80vw;height:80vh;border:none;"></iframe>
                            <div style="text-align:center;margin-top:10px;">
                                <button onclick="document.getElementById('foodPdfFrame').contentWindow.print()">พิมพ์ PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                // Handle food PDF preview/print (PDF only, no form)
                document.querySelectorAll('.food-pdf-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.getElementById('foodPdfFrame').src = 'FM-SV-007_ใบรายการอาหารที่ใช้ในการประชุม.pdf';
                        document.getElementById('foodPdfModal').style.display = 'flex';
                    });
                });
                </script>
                <?php else: ?>
                    <h3 class="ไม่พบรายการจอง">ไม่พบรายการจอง</h3>
                <?php endif; ?>
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
            <div class="details-container">
                <h2>รายละเอียดการจองทั้งหมด</h2>
                <?php
                // ดึงรายชื่อห้องประชุมสำหรับ dropdown
                // ดึงรายชื่อห้องประชุมจาก rooms สำหรับ dropdown
                // ดึงรายชื่อห้องประชุมจาก rooms สำหรับ dropdown
                $rooms_result = $conn->query("SELECT room_name FROM rooms WHERE is_deleted = 0 ORDER BY room_name ASC");
                $room_options = [];
                if ($rooms_result) {
                    while ($r = $rooms_result->fetch_assoc()) {
                        $room_options[] = $r['room_name'];
                    }
                } else {
                    echo '<div style="color:red">เกิดข้อผิดพลาดในการดึงข้อมูลห้องประชุม (admin): ' . $conn->error . '</div>';
                }

                // รับค่าค้นหา
                $title = isset($_GET['title']) ? trim($_GET['title']) : '';
                $booker = isset($_GET['booker']) ? trim($_GET['booker']) : '';
                $room = isset($_GET['room']) ? trim($_GET['room']) : '';
                $date = isset($_GET['date']) ? trim($_GET['date']) : '';
                ?>
                <form method="get" style="margin-bottom: 1rem; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="title" placeholder="หัวข้อ" value="<?php echo htmlspecialchars($title); ?>" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                    <input type="text" name="booker" placeholder="ชื่อผู้จอง" value="<?php echo htmlspecialchars($booker); ?>" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                    <select name="room" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                        <option value="">-- ห้องประชุม --</option>
                        <?php foreach ($room_options as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php if ($room === $opt) echo 'selected'; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" style="padding:8px; border-radius:5px; border:1px solid #ccc; flex:1;">
                    <button type="submit" style="padding:8px 16px; border-radius:5px; background:#2196F3; color:#fff; border:none; font-weight:bold;">ค้นหา</button>
                    <?php if ($title || $booker || $room || $date): ?>
                        <a href="details2.php" style="padding:8px 16px; border-radius:5px; background:#6c757d; color:#fff; text-decoration:none;">ล้างค้นหา</a>
                    <?php endif; ?>
                </form>
                <div class="text-right">
                    <a href="trash2.php" class="back-button">ถังขยะ</a>
                    <a href="main.php" class="back-button">ย้อนกลับ</a>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                    <div>
                        <table>
                            <thead>
                                <tr>
                                    <th>วัน/เดือน/ปี</th>
                                    <th>ห้องประชุม</th>
                                    <th>เวลา</th>
                                    <th>หัวข้อ</th>
                                    <th>ผู้จอง</th>
                                    <th>แผนก</th>
                                    <th>การจัดโต๊ะ</th>
                                    <th>อาหาร</th>
                                    <th>อุปกรณ์</th>
                                    <th>ความต้องการอื่น ๆ</th>
                                    <th>ใบรายการอาหาร</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['room']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_time']) . ' - ' . htmlspecialchars($row['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['booker_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td><?php echo htmlspecialchars($row['table_layout']); ?></td>
                                    <td><?php echo htmlspecialchars($row['food_beverage']); ?></td>
                                    <td><?php echo htmlspecialchars($row['facilities']); ?></td>
                                    <td style="max-width: 200px; word-break: break-word;"><?php echo nl2br(htmlspecialchars($row['other_requirements'])); ?></td>
                                        <td>
                                            <button class="food-pdf-btn">ใบรายการอาหาร</button>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit2.php?id=<?php echo $row['id']; ?>" class="edit-button">แก้ไข/ลบ</a>
                                                <?php if (!empty($row['id_zoom'])): ?>
                                                    <a href="https://zoom.us/j/<?php echo htmlspecialchars($row['id_zoom']); ?>" 
                                                       target="_blank" 
                                                       class="zoom-button" 
                                                       title="Meeting ID: <?php echo htmlspecialchars($row['id_zoom']); ?><?php echo !empty($row['password_zoom']) ? ' | Password: ' . htmlspecialchars($row['password_zoom']) : ''; ?>">
                                                        ไปยัง Zoom
                                                    </a>
                                                <?php else: ?>
                                                    <span class="zoom-button disabled" title="ไม่พบการตั้งค่า Zoom">ไปยัง Zoom</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                            <!-- Modal for PDF preview (shared) -->
                                            <div id="foodPdfModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
                                                <div style="background:#fff;padding:20px;max-width:90vw;max-height:90vh;overflow:auto;position:relative;">
                                                    <button onclick="document.getElementById('foodPdfModal').style.display='none'" style="position:absolute;top:10px;right:10px;font-size:1.5em;">&times;</button>
                                                    <iframe id="foodPdfFrame" src="" style="width:80vw;height:80vh;border:none;"></iframe>
                                                    <div style="text-align:center;margin-top:10px;">
                                                        <button onclick="document.getElementById('foodPdfFrame').contentWindow.print()">พิมพ์ PDF</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                        // Handle food PDF preview/print (shared for both user/admin tables)
                                        document.querySelectorAll('.food-pdf-btn').forEach(btn => {
                                            btn.addEventListener('click', function() {
                                                document.getElementById('foodPdfFrame').src = 'FM-SV-007_ใบรายการอาหารที่ใช้ในการประชุม.pdf';
                                                document.getElementById('foodPdfModal').style.display = 'flex';
                                            });
                                        });
                                        </script>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <h3 class="ไม่พบรายการจอง">ไม่พบรายการจอง</h3>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal for Zoom Settings -->
    <div id="zoomSettingsModal" class="modal-bg">
        <div class="modal-content">
            <button class="modal-close" id="closeZoomSettingsBtn">&times;</button>
            <h3>ตั้งค่า Zoom Meeting</h3>
            <form id="zoomSettingsForm" class="zoom-settings-form">
                <input type="hidden" id="bookingId" name="bookingId">
                <div class="form-group">
                    <label for="zoomId">Zoom Meeting ID:</label>
                    <input type="text" id="zoomId" name="zoomId" required>
                </div>
                <div class="form-group">
                    <label for="zoomPassword">Zoom Password:</label>
                    <input type="text" id="zoomPassword" name="zoomPassword" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" id="cancelZoomSettings">ยกเลิก</button>
                    <button type="submit" class="save-btn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-bg.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            right: 10px;
            top: 10px;
            border: none;
            background: none;
            font-size: 24px;
            cursor: pointer;
        }
        .zoom-settings-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-buttons button {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .save-btn {
            background: #2196F3;
            color: white;
        }
        .cancel-btn {
            background: #6c757d;
            color: white;
        }
        .zoom-settings-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .zoom-button.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>

    <script>
    function openZoomSettings(bookingId, zoomId, zoomPassword) {
        document.getElementById('bookingId').value = bookingId;
        document.getElementById('zoomId').value = zoomId;
        document.getElementById('zoomPassword').value = zoomPassword;
        document.getElementById('zoomSettingsModal').classList.add('active');
    }

    document.getElementById('closeZoomSettingsBtn').addEventListener('click', function() {
        document.getElementById('zoomSettingsModal').classList.remove('active');
    });

    document.getElementById('cancelZoomSettings').addEventListener('click', function() {
        document.getElementById('zoomSettingsModal').classList.remove('active');
    });

    document.getElementById('zoomSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('save_zoom_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('บันทึกการตั้งค่า Zoom สำเร็จ');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>