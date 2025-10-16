    <script>
    // เมื่อคลิกชื่อห้องประชุม ให้แสดง popup ยืนยันการเปลี่ยนสี (Confirm -> form_config.php#rooms, Cancel -> ปิด popup)
    document.addEventListener('DOMContentLoaded', function() {
        // สร้าง modal element แบบไดนามิก ถ้ายังไม่มี
            // เมื่อคลิกชื่อห้องประชุม: แสดง modal ยืนยันการเปลี่ยนสี (ไม่มี alert/confirm ของ browser)
            if (!document.getElementById('changeColorConfirmModal')) {
                const modalHtml = `
                    <div id="changeColorConfirmModal" class="modal-bg">
                        <div class="modal-content">
                            <div class="modal-title">ยืนยันการเปลี่ยนสี</div>
                            <p id="changeColorMessage">คุณต้องการจะเปลี่ยนสีห้องประชุมนี้หรือไม่?</p>
                            <div style="text-align:right;margin-top:16px;">
                                <button id="changeColorCancel" class="modal-cancel">ยกเลิก</button>
                                <button id="changeColorConfirm" class="modal-confirm">ยืนยัน</button>
                            </div>
                        </div>
                    </div>`;
                document.body.insertAdjacentHTML('beforeend', modalHtml);
            }

            const modal = document.getElementById('changeColorConfirmModal');
            const msgEl = document.getElementById('changeColorMessage');
            const btnConfirm = document.getElementById('changeColorConfirm');
            const btnCancel = document.getElementById('changeColorCancel');

            // ปิด modal เมื่อกดยกเลิกหรือคลิกนอก
            if (btnCancel) btnCancel.addEventListener('click', () => modal && modal.classList.remove('active'));
            if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) modal.classList.remove('active'); });

            // ตั้ง listener สำหรับแต่ละ room-status
            document.querySelectorAll('.container_explanetion .room-status').forEach(function(div) {
                div.addEventListener('click', function(e) {
                    e.preventDefault();
                    const room = this.getAttribute('data-room') || '';
                    if (msgEl) msgEl.textContent = `ต้องการจะเปลี่ยนสีของ "${room}" หรือไม่?`;

                    // ตั้ง handler ยืนยันใหม่ (ลบ handler ก่อนหน้าโดยการแทนที่ปุ่ม)
                    if (btnConfirm) {
                        const newBtn = btnConfirm.cloneNode(true);
                        btnConfirm.parentNode.replaceChild(newBtn, btnConfirm);
                        newBtn.addEventListener('click', function() {
                            // ยืนยัน → ไปยัง form_config.php#rooms
                            window.location.href = 'form_config.php#rooms';
                        });
                    }

                    if (modal) modal.classList.add('active');
                });
            });
    });
    </script>
<?php
require_once 'auth.php';
requireLogin();

// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8"); 

// ดึงสีห้องประชุมทั้งหมด
$roomColors = [];
$sqlRooms = "SELECT room_name, color FROM rooms WHERE is_deleted = 0";
$resultRooms = $conn->query($sqlRooms);
if ($resultRooms && $resultRooms->num_rows > 0) {
    while ($row = $resultRooms->fetch_assoc()) {
        $roomColors[$row['room_name']] = $row['color'] ?: '#34c759';
    }
}

// ดึงข้อมูลการจอง (รวมเวลาเริ่มต้นและสิ้นสุดเพื่อแสดงรายละเอียด)
$bookings = [];
$sql = "SELECT date, title, room, start_time, end_time FROM bookings WHERE is_deleted = 0";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[$row['date']][] = [
            'title' => $row['title'],
            'room'  => $row['room'],
            'start_time' => isset($row['start_time']) ? $row['start_time'] : '',
            'end_time' => isset($row['end_time']) ? $row['end_time'] : ''
        ];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เว็บแอปพลิเคชันการจองห้องประชุม</title>
    <link rel="stylesheet" href="setting.css">
    <style>
        /* Year selector modal styles */
        .year-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: 300px;
            justify-content: center;
            margin-top: 12px;
        }
        .year-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            min-width: 80px;
            text-align: center;
        }
        .year-btn:hover {
            background: #f0f0f0;
        }
        .year-btn.selected {
            background: #007bff;
            color: white;
            border-color: #0056b3;
        }
        #month-year-display {
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }
        #month-year-display:hover {
            background: rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Modal for booking details (moved here to be available site-wide) -->
    <div class="modal-bg" id="bookingModal">
        <div class="modal-content">
            <button class="modal-close" id="closeModalBtn">&times;</button>
            <div class="modal-title" id="modalTitle"></div>
            <div class="modal-row"><span class="modal-label">ห้อง:</span> <span id="modalRoom"></span></div>
            <div class="modal-row"><span class="modal-label">วันที่:</span> <span id="modalDate"></span></div>
            <div class="modal-row"><span class="modal-label">เวลา:</span> <span id="modalTime"></span></div>
        </div>
    </div>
    <!-- Year selector modal -->
    <div class="modal-bg" id="yearSelectorModal">
        <div class="modal-content">
            <button class="modal-close" id="closeYearModalBtn">&times;</button>
            <div class="modal-title">เลือกปี</div>
            <div class="year-selector" id="yearButtons">
                <!-- Years will be added here by JS -->
            </div>
        </div>
    </div>
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

                <div class="container_explanetion" style="justify-content: end; gap:12px;">
                    <?php foreach ($roomColors as $roomName => $color): ?>
                        <div class="room-status" 
                            data-room="<?php echo htmlspecialchars($roomName); ?>" 
                            data-color="<?php echo htmlspecialchars($color); ?>" 
                            style="background: <?php echo htmlspecialchars($color); ?>; color: <?php echo (in_array(strtolower($color), ['#fff','#ffffff','#ffe0b2','#ffd180','#ffc107']) ? '#222' : '#fff'); ?>; cursor:pointer;">
                            <?php echo htmlspecialchars($roomName); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
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
                        <li><a href="form_config.php">ตั้งค่าการจอง</a></li>
                        <li><a href="user.php">จัดการผู้ใช้</a></li>
                        <li><a href="setting.php">ปฏิทินการจองห้องประชุมทั้งหมด</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="content">

                <div class="container_explanetion" style="justify-content: end; gap:12px;">
                    <?php foreach ($roomColors as $roomName => $color): ?>
                        <div class="room-status" 
                            data-room="<?php echo htmlspecialchars($roomName); ?>" 
                            data-color="<?php echo htmlspecialchars($color); ?>" 
                            style="background: <?php echo htmlspecialchars($color); ?>; color: <?php echo (in_array(strtolower($color), ['#fff','#ffffff','#ffe0b2','#ffd180','#ffc107']) ? '#222' : '#fff'); ?>; cursor:pointer;">
                            <?php echo htmlspecialchars($roomName); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

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

    <script>
    const bookings = <?php echo json_encode($bookings); ?>;
    const roomColors = <?php echo json_encode($roomColors); ?>;

        const monthYearDisplay = document.getElementById('month-year-display');
        const prevMonthBtn = document.getElementById('prev-month-btn');
        const nextMonthBtn = document.getElementById('next-month-btn');
        const calendarGrid = document.querySelector('.calendar-grid');

        const thaiMonths = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน',
            'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม',
            'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        const weekdays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        function toThaiYear(westernYear) {
            return westernYear + 543;
        }

        function generateYearButtons() {
            const yearSelectorContainer = document.getElementById('yearButtons');
            yearSelectorContainer.innerHTML = '';
            const currentWesternYear = new Date().getFullYear();
            // Show 5 years before and 5 years after current year
            for (let year = currentWesternYear - 5; year <= currentWesternYear + 5; year++) {
                const yearBtn = document.createElement('button');
                yearBtn.classList.add('year-btn');
                if (year === currentYear) {
                    yearBtn.classList.add('selected');
                }
                yearBtn.textContent = toThaiYear(year);
                yearBtn.onclick = () => {
                    currentYear = year;
                    document.getElementById('yearSelectorModal').style.display = 'none';
                    renderCalendar();
                };
                yearSelectorContainer.appendChild(yearBtn);
            }
        }

        // Initialize year selector
        monthYearDisplay.onclick = () => {
            generateYearButtons();
            document.getElementById('yearSelectorModal').style.display = 'flex';
        };

        const closeYearModalBtn = document.getElementById('closeYearModalBtn');
        if (closeYearModalBtn) {
            closeYearModalBtn.onclick = () => {
                document.getElementById('yearSelectorModal').style.display = 'none';
            };
        }

        const yearSelectorModal = document.getElementById('yearSelectorModal');
        if (yearSelectorModal) {
            yearSelectorModal.onclick = (e) => {
                if (e.target === yearSelectorModal) {
                    yearSelectorModal.style.display = 'none';
                }
            };
        }

        function renderCalendar() {
            calendarGrid.innerHTML = '';

            // Add weekday headers
            weekdays.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.classList.add('day-header');
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });

            const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            // Add empty cells for the days before the first day of the month
            for (let i = 0; i < firstDayOfMonth; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.classList.add('calendar-day', 'empty');
                calendarGrid.appendChild(emptyCell);
            }

            // Add the days of the month
            for (let i = 1; i <= daysInMonth; i++) {
                const dayCell = document.createElement('div');
                dayCell.classList.add('calendar-day');

                const dateNumber = document.createElement('span');
                dateNumber.classList.add('date-number');
                dateNumber.textContent = i;
                dayCell.appendChild(dateNumber);

                const month = (currentMonth + 1).toString().padStart(2, '0');
                const day = i.toString().padStart(2, '0');
                const fullDate = `${currentYear}-${month}-${day}`;

                const meetingsContainer = document.createElement('div');
                meetingsContainer.classList.add('meetings-container');
                dayCell.appendChild(meetingsContainer);

                if (bookings[fullDate]) {
                    bookings[fullDate].forEach(booking => {
                        const meetingDiv = document.createElement('div');
                        meetingDiv.classList.add('meeting-title');
                        meetingDiv.title = booking.title;
                        // ใช้สีจากฐานข้อมูล
                        const color = roomColors[booking.room] || '#bebebeff';
                        meetingDiv.style.backgroundColor = color;
                        meetingDiv.style.color = '#fff';
                        // ปรับสีตัวอักษรถ้าพื้นหลังอ่อน
                        if (['#fff','#ffffff','#ffe0b2','#ffd180','#ffc107'].includes(color.toLowerCase())) {
                            meetingDiv.style.color = '#222';
                        }
                        const maxLength = 10;
                        meetingDiv.textContent = booking.title.length > maxLength
                            ? booking.title.substring(0, maxLength) + '...'
                            : booking.title;
                        meetingDiv.addEventListener('click', (e) => {
                            e.stopPropagation();
                            // populate modal fields
                            document.getElementById('modalTitle').textContent = booking.title;
                            document.getElementById('modalRoom').textContent = booking.room;
                            // convert fullDate (YYYY-MM-DD) to Thai format
                            const [y, m, d] = fullDate.split('-');
                            const thaiDate = `${parseInt(d)} ${thaiMonths[parseInt(m)-1]} ${toThaiYear(parseInt(y))}`;
                            document.getElementById('modalDate').textContent = thaiDate;
                            // time
                            let timeStr = '';
                            if (booking.start_time && booking.end_time) {
                                timeStr = booking.start_time + ' - ' + booking.end_time;
                            }
                            document.getElementById('modalTime').textContent = timeStr;
                            // show modal
                            const modal = document.getElementById('bookingModal');
                            if (modal) modal.classList.add('active');
                        });
                        meetingsContainer.appendChild(meetingDiv);
                    });
                }

                dayCell.addEventListener('click', () => {
                    window.location.href = `form.php?date=${fullDate}`;
                });

                calendarGrid.appendChild(dayCell);
            }

            const thaiMonth = thaiMonths[currentMonth];
            const thaiYear = toThaiYear(currentYear);
            monthYearDisplay.textContent = `${thaiMonth} ${thaiYear}`;
        }

        prevMonthBtn.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar();
        });

        nextMonthBtn.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        });

        renderCalendar();

        // ฟีเจอร์เปลี่ยนสีห้องประชุม
        document.querySelectorAll('.room-status').forEach(function(div) {
            div.addEventListener('click', function() {
                const room = div.getAttribute('data-room');
                const oldColor = div.getAttribute('data-color');
                if (confirm('ต้องการจะเปลี่ยนสีห้องประชุม ' + room + ' หรือไม่?')) {
                    // สร้าง input[type=color] ชั่วคราว
                    const colorInput = document.createElement('input');
                    colorInput.type = 'color';
                    colorInput.value = oldColor;
                    colorInput.style.position = 'fixed';
                    colorInput.style.left = '-9999px';
                    document.body.appendChild(colorInput);
                    colorInput.click();
                    colorInput.addEventListener('input', function() {
                        // ส่งไปหน้า form_config.php เพื่อบันทึกสีใหม่ (ใช้ form POST แบบ hidden)
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'form_config.php';
                        form.style.display = 'none';
                        form.innerHTML = `
                            <input type="hidden" name="table_name" value="rooms">
                            <input type="hidden" name="room_id" value="${encodeURIComponent(room)}">
                            <input type="color" name="room_color" value="${colorInput.value}">
                            <input type="submit" name="save_color" value="บันทึกสี">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }, { once: true });
                }
            });
        });

        // Modal close handlers (close button and click outside)
        document.addEventListener('DOMContentLoaded', function() {
            const bookingModal = document.getElementById('bookingModal');
            const closeBtn = document.getElementById('closeModalBtn');
            if (closeBtn) closeBtn.addEventListener('click', function() {
                if (bookingModal) bookingModal.classList.remove('active');
            });
            if (bookingModal) bookingModal.addEventListener('click', function(e) {
                if (e.target === bookingModal) bookingModal.classList.remove('active');
            });
        });
    </script>
    
</body>
</html>