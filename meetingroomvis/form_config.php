<?php
require_once 'auth.php';
requireLogin();
require_once 'db_connect.php'; // Assuming this file establishes your PDO connection correctly

$message = "";
// Get current section for redirect
$current_section = isset($_POST['section']) ? '#' . $_POST['section'] : '';

// ฟังก์ชันสำหรับดึงข้อมูลจากฐานข้อมูล
function fetchOptions($conn, $table) {
    $options = [];
    // Use PDO's prepare and execute for better security and compatibility
    $sql = "SELECT * FROM `$table` WHERE is_deleted = 0";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Log or display the PDO error
        error_log("PDO Prepare Error: " . $conn->errorInfo()[2]);
        $message = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->errorInfo()[2];
        return $options;
    }

    if (!$stmt->execute()) {
        // Log or display the execution error
        error_log("PDO Execute Error: " . $stmt->errorInfo()[2]);
        $message = "เกิดข้อผิดพลาดในการเรียกข้อมูล: " . $stmt->errorInfo()[2];
        return $options;
    }

    // Fetch all results into an array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // $stmt->rowCount() can be used for INSERT, UPDATE, DELETE to see affected rows.
    // For SELECT, fetching and using count() on the result array is the standard way.
    if (count($results) > 0) {
        $options = $results;
    }

    $stmt->closeCursor(); // Close the cursor to free up resources
    return $options;
}

# ฟังก์ชันสำหรับจัดการข้อมูล (เพิ่ม/ลบ)
function handleAction($conn, $table, $column_name, $id_column) {
    global $message, $current_section;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['add_submit'])) {
            $new_item = $_POST['new_item'];
            if (!empty($new_item)) {
                $sql = "INSERT INTO `$table` (`$column_name`) VALUES (?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    error_log("PDO Prepare Error (Add): " . $conn->errorInfo()[2]);
                    $message = "Error preparing statement: " . $conn->errorInfo()[2];
                    return;
                }

                // Use bindValue for PDO, or bindParam if variable scope is an issue
                // For simple cases, bindValue is often sufficient
                $stmt->bindValue(1, $new_item, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    // Use rowCount() to check if insertion was successful
                    if ($stmt->rowCount() > 0) {
                        $message = "เพิ่มรายการสำเร็จ!";
                        header("Location: form_config.php" . $current_section);
                        exit();
                    } else {
                        $message = "ไม่พบแถวที่ได้รับผลกระทบจากการเพิ่มรายการ (อาจเกิดจากข้อมูลซ้ำ)";
                    }
                } else {
                    error_log("PDO Execute Error (Add): " . $stmt->errorInfo()[2]);
                    $message = "Error executing query: " . $stmt->errorInfo()[2];
                }
                $stmt->closeCursor();
            } else {
                $message = "กรุณากรอกข้อมูลสำหรับรายการใหม่";
            }
        } elseif (isset($_POST['delete_submit'])) {
            $item_id_to_delete = $_POST['delete_id'];
            // Ensure delete_id is treated as an integer for safety
            $item_id_to_delete = filter_var($item_id_to_delete, FILTER_SANITIZE_NUMBER_INT);

            $sql = "UPDATE `$table` SET is_deleted = 1 WHERE `$id_column` = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                error_log("PDO Prepare Error (Delete): " . $conn->errorInfo()[2]);
                $message = "Error preparing statement: " . $conn->errorInfo()[2];
                return;
            }

            $stmt->bindValue(1, $item_id_to_delete, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = "ลบรายการสำเร็จ!";
                    header("Location: form_config.php" . $current_section);
                    exit();
                } else {
                    $message = "ไม่พบรายการที่ต้องการลบ";
                }
            } else {
                error_log("PDO Execute Error (Add): " . $stmt->errorInfo()[2]);
                $message = "Error executing query: " . $stmt->errorInfo()[2];
            }
            // Remove redundant header redirect as it's already handled in the success case
            $stmt->closeCursor();
        }
    }
}

// จัดการการกระทำสำหรับแต่ละตาราง
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['table_name'])) {
        $table = $_POST['table_name'];
        switch ($table) {
            case 'departments':
                handleAction($conn, 'departments', 'department_name', 'department_id');
                break;
            case 'rooms':
                handleAction($conn, 'rooms', 'room_name', 'room_id');
                // อัปเดตสีห้องประชุม
                if (isset($_POST['save_color']) && isset($_POST['room_id']) && isset($_POST['room_color'])) {
                    $room_id = intval($_POST['room_id']);
                    $room_color = $_POST['room_color'];
                    $sql = "UPDATE rooms SET color = ? WHERE room_id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bindValue(1, $room_color, PDO::PARAM_STR);
                        $stmt->bindValue(2, $room_id, PDO::PARAM_INT);
                        if ($stmt->execute()) {
                            $message = "บันทึกสีห้องประชุมเรียบร้อยแล้ว";
                            // อัพเดท current_section จาก POST data
                            $current_section = isset($_POST['section']) ? '#' . $_POST['section'] : '';
                            header("Location: form_config.php" . $current_section);
                            exit();
                        }
                        $stmt->closeCursor();
                    }
                }
                break;
            case 'catering_options':
                handleAction($conn, 'catering_options', 'option_name', 'option_id');
                break;
            case 'layouts':
                handleAction($conn, 'layouts', 'layout_name', 'layout_id');
                break;
            case 'facilities':
                handleAction($conn, 'facilities', 'facility_name', 'facility_id');
                break;
        }
    }
}

// ดึงข้อมูลสำหรับแสดงผล
$departments = fetchOptions($conn, "departments");
$rooms = fetchOptions($conn, "rooms");
$catering_options = fetchOptions($conn, "catering_options");
$layouts = fetchOptions($conn, "layouts");
$facilities = fetchOptions($conn, "facilities");

// Close the PDO connection by setting it to null
$conn = null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการตัวเลือก</title>
    <link rel="stylesheet" href="form_config.css">
    <style>
    .option-section {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .option-list {
      list-style-type: none;
      padding: 0;
    }
    .option-list li {
      padding: 5px 0;
      display: flex;
      align-items: center;
    }
    .option-list li button {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 5px 10px;
      cursor: pointer;
      border-radius: 3px;
    }
    .option-list li button:hover {
      background-color: #c82333;
    }

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
        position: relative;
    }

    .modal-title {
        margin-top: 0;
        color: #333;
    }

    .modal-buttons {
        margin-top: 20px;
        text-align: right;
    }

    .modal-confirm,
    .modal-cancel {
        padding: 8px 16px;
        margin-left: 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    /* สีปุ่มสำหรับ modal ลบ */
    #deleteConfirmModal .modal-confirm {
        background-color: #dc3545;
        color: white;
    }

    #deleteConfirmModal .modal-confirm:hover {
        background-color: #c82333;
    }

    /* สีปุ่มสำหรับ modal เพิ่มและบันทึกสี */
    #addConfirmModal .modal-confirm,
    #colorConfirmModal .modal-confirm {
        background-color: #28a745;
        color: white;
    }

    #addConfirmModal .modal-confirm:hover,
    #colorConfirmModal .modal-confirm:hover {
        background-color: #218838;
    }

    .modal-cancel {
        background-color: #6c757d;
        color: white;
    }

    .modal-cancel:hover {
        background-color: #5a6268;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to section if hash exists in URL
    if (window.location.hash) {
        const element = document.querySelector(window.location.hash);
        if (element) {
            setTimeout(function() {
                element.scrollIntoView({ behavior: 'smooth' });
            }, 100);
        }
    }

    // สร้าง modals
    const modalsHtml = `
        <div id="deleteConfirmModal" class="modal">
            <div class="modal-content">
                <h3 class="modal-title">ยืนยันการลบรายการ</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?</p>
                <div class="modal-buttons">
                    <button class="modal-confirm" id="confirmDelete">ยืนยัน</button>
                    <button class="modal-cancel" id="cancelDelete">ยกเลิก</button>
                </div>
            </div>
        </div>
        <div id="addConfirmModal" class="modal">
            <div class="modal-content">
                <h3 class="modal-title">ยืนยันการเพิ่มรายการ</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการเพิ่มรายการนี้?</p>
                <div class="modal-buttons">
                    <button class="modal-confirm" id="confirmAdd">ยืนยัน</button>
                    <button class="modal-cancel" id="cancelAdd">ยกเลิก</button>
                </div>
            </div>
        </div>
        <div id="colorConfirmModal" class="modal">
            <div class="modal-content">
                <h3 class="modal-title">ยืนยันการบันทึกสี</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการบันทึกสีนี้?</p>
                <div class="modal-buttons">
                    <button class="modal-confirm" id="confirmColor">ยืนยัน</button>
                    <button class="modal-cancel" id="cancelColor">ยกเลิก</button>
                </div>
            </div>
        </div>`;
    document.body.insertAdjacentHTML('beforeend', modalsHtml);

    // ฟังก์ชันสำหรับแสดง modal
    function showModal(modalId, form) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'block';
        return new Promise((resolve) => {
            const confirmBtn = modal.querySelector('.modal-confirm');
            const cancelBtn = modal.querySelector('.modal-cancel');

            function handleConfirm() {
                modal.style.display = 'none';
                resolve(true);
                cleanup();
            }

            function handleCancel() {
                modal.style.display = 'none';
                resolve(false);
                cleanup();
            }

            function handleOutsideClick(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                    resolve(false);
                    cleanup();
                }
            }

            function cleanup() {
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                window.removeEventListener('click', handleOutsideClick);
            }

            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
            window.addEventListener('click', handleOutsideClick);
        });
    }

    // จัดการปุ่มลบ
    document.querySelectorAll('button[name="delete_submit"]').forEach(btn => {
        btn.classList.add('delete-btn');
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const confirmed = await showModal('deleteConfirmModal');
            if (confirmed) {
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'delete_submit';
                submitInput.value = '1';
                form.appendChild(submitInput);
                form.submit();
            }
        });
    });

    // จัดการปุ่มเพิ่ม
    document.querySelectorAll('input[type="submit"][name="add_submit"]').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const confirmed = await showModal('addConfirmModal');
            if (confirmed) {
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'add_submit';
                submitInput.value = '1';
                form.appendChild(submitInput);
                form.submit();
            }
        });
    });

    // จัดการปุ่มบันทึกสี
    document.querySelectorAll('button[name="save_color"]').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const confirmed = await showModal('colorConfirmModal');
            if (confirmed) {
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'save_color';
                submitInput.value = '1';
                form.appendChild(submitInput);
                form.submit();
            }
        });
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

        <main class="content">
            <h2>จัดการตัวเลือกสำหรับระบบ</h2>
            <?php if (!empty($message)): ?>
                <p style="color: green; font-weight: bold;"><?php echo $message; ?></p>
            <?php endif; ?>
            <p>เพิ่มและลบรายการต่างๆ ที่แสดงในแบบฟอร์ม</p>

            <div class="option-section" id="departments">
                <h3>แผนก</h3>
                <form method="POST" action="form_config.php">
                    <input type="hidden" name="table_name" value="departments">
                    <input type="hidden" name="section" value="departments">
                    <input type="text" name="new_item" placeholder="ชื่อแผนกใหม่" required>
                    <input type="submit" name="add_submit" value="เพิ่มแผนก">
                </form>
                <ul class="option-list">
                    <?php foreach ($departments as $department): ?>
                        <li>
                            <form method="POST" action="form_config.php" style="display:inline;">
                                <input type="hidden" name="table_name" value="departments">
                                <input type="hidden" name="section" value="departments">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($department['department_id']); ?>">
                                <button type="submit" name="delete_submit">ลบ</button>
                            </form>
                            <span><?php echo htmlspecialchars($department['department_name']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="option-section" id="rooms">
                <h3>ห้องประชุม</h3>
                <form method="POST" action="form_config.php">
                    <input type="hidden" name="table_name" value="rooms">
                    <input type="hidden" name="section" value="rooms">
                    <input type="text" name="new_item" placeholder="ชื่อห้องประชุมใหม่" required>
                    <input type="submit" name="add_submit" value="เพิ่มห้อง">
                </form>
                <ul class="option-list">
                    <?php foreach ($rooms as $room): ?>
                        <li>
                            <form method="POST" action="form_config.php" style="display:inline;">
                                <input type="hidden" name="table_name" value="rooms">
                                <input type="hidden" name="section" value="rooms">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                <button type="submit" name="delete_submit">ลบ</button>
                            </form>
                            <span><?php echo htmlspecialchars($room['room_name']); ?></span>
                            <form method="POST" action="form_config.php" class="room-color-form">
                                <input type="hidden" name="table_name" value="rooms">
                                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                <input type="hidden" name="section" value="rooms">
                                <span style="display:inline-block;width:18px;height:18px;border-radius:4px;background:<?php echo htmlspecialchars($room['color'] ?? '#ffe0b2'); ?>;border:1px solid #ccc;"></span>
                                <input type="color" name="room_color" value="<?php echo htmlspecialchars($room['color'] ?? '#ffe0b2'); ?>">
                                <button type="submit" name="save_color" style="background:#4CAF50;color:#fff;border:none;padding:3px 10px;border-radius:3px;cursor:pointer;">บันทึกสี</button>
                            </form>

                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="option-section" id="catering">
                <h3>อาหารและเครื่องดื่ม</h3>
                <form method="POST" action="form_config.php">
                    <input type="hidden" name="table_name" value="catering_options">
                    <input type="hidden" name="section" value="catering">
                    <input type="text" name="new_item" placeholder="ชื่อรายการใหม่" required>
                    <input type="submit" name="add_submit" value="เพิ่มรายการ">
                </form>
                <ul class="option-list">
                    <?php foreach ($catering_options as $option): ?>
                        <li>
                            <form method="POST" action="form_config.php" style="display:inline;">
                                <input type="hidden" name="table_name" value="catering_options">
                                <input type="hidden" name="section" value="catering">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($option['option_id']); ?>">
                                <button type="submit" name="delete_submit">ลบ</button>
                            </form>
                            <span><?php echo htmlspecialchars($option['option_name']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="option-section" id="layouts">
                <h3>รูปแบบการจัดห้อง</h3>
                <form method="POST" action="form_config.php">
                    <input type="hidden" name="table_name" value="layouts">
                    <input type="hidden" name="section" value="layouts">
                    <input type="text" name="new_item" placeholder="ชื่อรูปแบบใหม่" required>
                    <input type="submit" name="add_submit" value="เพิ่มรูปแบบ">
                </form>
                <ul class="option-list">
                    <?php foreach ($layouts as $layout): ?>
                        <li>
                            <form method="POST" action="form_config.php" style="display:inline;">
                                <input type="hidden" name="table_name" value="layouts">
                                <input type="hidden" name="section" value="layouts">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($layout['layout_id']); ?>">
                                <button type="submit" name="delete_submit">ลบ</button>
                            </form>
                            <span><?php echo htmlspecialchars($layout['layout_name']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="option-section" id="facilities">
                <h3>อุปกรณ์และสิ่งอำนวยความสะดวก</h3>
                <form method="POST" action="form_config.php">
                    <input type="hidden" name="table_name" value="facilities">
                    <input type="hidden" name="section" value="facilities">
                    <input type="text" name="new_item" placeholder="อุปกรณ์ใหม่" required>
                    <input type="submit" name="add_submit" value="เพิ่มอุปกรณ์">
                </form>
                <ul class="option-list">
                    <?php foreach ($facilities as $facility): ?>
                        <li>
                            <form method="POST" action="form_config.php" style="display:inline;">
                                <input type="hidden" name="table_name" value="facilities">
                                <input type="hidden" name="section" value="facilities">
                                <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($facility['facility_id']); ?>">
                                <button type="submit" name="delete_submit">ลบ</button>
                            </form>
                            <span><?php echo htmlspecialchars($facility['facility_name']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </main>

    </div>

</body>
</html>