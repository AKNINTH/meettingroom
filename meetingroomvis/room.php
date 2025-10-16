<?php
require_once 'auth.php';
requireLogin();
require_once 'db_connect.php';


// ดึงข้อมูลห้องประชุมทั้งหมดจากฐานข้อมูล (เหมือน main.php)
$rooms = [];
$sql_rooms = "SELECT * FROM rooms WHERE is_deleted = 0";
try {
    $stmt_rooms = $conn->prepare($sql_rooms);
    $stmt_rooms->execute();
    $rooms = $stmt_rooms->fetchAll();
} catch (PDOException $e) {
    die("Error fetching rooms: " . $e->getMessage());
}

// รับชื่อห้องประชุมจาก query string ถ้าไม่มีให้ใช้ 'ห้องประชุม1'
$roomName = isset($_GET['room']) && $_GET['room'] !== '' ? $_GET['room'] : (isset($rooms[0]['room_name']) ? $rooms[0]['room_name'] : 'ห้องประชุม1');

function textColorForBg($hex) {
    $hex = trim($hex);
    if ($hex === '') return '#000';
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6) return '#000';
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $yiq = (($r*299)+($g*587)+($b*114))/1000;
    return ($yiq >= 128) ? '#000' : '#fff';

}

// เพิ่มเวลาใน SQL
$sql = "SELECT date, title, room, start_time, end_time FROM bookings WHERE room = :room AND is_deleted = 0";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':room', $roomName, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll();

    $bookings = [];
    if ($result) {
        foreach ($result as $row) {
            // จัดเก็บตามวันที่ YYYY-MM-DD
            $bookings[$row['date']][] = [
                'title' => $row['title'],
                'room'  => $row['room'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }
    }
} catch (PDOException $e) {
    die("Error fetching bookings: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินการจอง - <?php echo htmlspecialchars($roomName); ?></title>
    <link rel="stylesheet" href="room.css">
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
    <!-- Modal for booking details -->
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
</head>
<body>
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
                    <?php if (isAdmin()): ?>
                        <li><a href="form_config.php">ตั้งค่าการจอง</a></li>
                        <li><a href="user.php">จัดการผู้ใช้</a></li>
                        <li><a href="setting.php">ปฏิทินการจองห้องประชุมทั้งหมด</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        <main class="content">
            <h2>ปฏิทินการจอง <?php echo htmlspecialchars($roomName); ?></h2>
            <div class="room-selection">
                <?php foreach ($rooms as $room): ?>
                    <?php
                        $rName = $room['room_name'];
                        $rColor = isset($room['color']) && $room['color'] !== '' ? $room['color'] : '#e9ecef';
                        $isActive = ($roomName == $rName);
                        $linkStyle = '';
                        if ($isActive) {
                            $textC = textColorForBg($rColor);
                            $linkStyle = "background: {$rColor}; color: {$textC};";
                        }
                    ?>
                    <a href="room.php?room=<?php echo urlencode($rName); ?>" class="room-link<?php echo $isActive ? ' active-link' : ''; ?>" style="<?php echo $linkStyle; ?>">
                        <?php echo htmlspecialchars($rName); ?>
                    </a>
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
    <script>
        const bookings = <?php echo json_encode($bookings); ?>;
        const roomName = <?php echo json_encode($roomName); ?>;
        // Map room name => color from PHP
        const roomColors = <?php echo json_encode(array_column($rooms, 'color', 'room_name')); ?> || {};

        function getContrastColor(hex) {
            if (!hex) return '#000';
            let h = String(hex).replace('#','').trim();
            if (h.length === 3) h = h.split('').map(c => c+c).join('');
            if (h.length !== 6) return '#000';
            const r = parseInt(h.substr(0,2),16);
            const g = parseInt(h.substr(2,2),16);
            const b = parseInt(h.substr(4,2),16);
            const yiq = ((r*299)+(g*587)+(b*114))/1000;
            return (yiq >= 128) ? '#000' : '#fff';
        }

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
        closeYearModalBtn.onclick = () => {
            document.getElementById('yearSelectorModal').style.display = 'none';
        };

        const yearSelectorModal = document.getElementById('yearSelectorModal');
        yearSelectorModal.onclick = (e) => {
            if (e.target === yearSelectorModal) {
                yearSelectorModal.style.display = 'none';
            }
        };

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
                        const roomClass = `room-${booking.room.replace(/\s/g, '')}`;
                        const meetingDiv = document.createElement('div');
                        meetingDiv.classList.add('meeting-title', roomClass);
                        meetingDiv.title = booking.title;

                        // Apply room color if available
                        const bgColor = roomColors[booking.room] || '#ffe0b2';
                        meetingDiv.style.background = bgColor;
                        meetingDiv.style.color = getContrastColor(bgColor);

                        const maxLength = 10;
                        meetingDiv.textContent = booking.title.length > maxLength
                            ? booking.title.substring(0, maxLength) + '...'
                            : booking.title;

                        meetingDiv.addEventListener('click', (e) => {
                            e.stopPropagation();
                            // แสดง modal พร้อมรายละเอียด
                            document.getElementById('modalTitle').textContent = booking.title;
                            document.getElementById('modalRoom').textContent = booking.room;
                            // วันที่ไทย
                            const [y, m, d] = fullDate.split('-');
                            const thaiDate = `${parseInt(d)} ${thaiMonths[parseInt(m)-1]} ${toThaiYear(parseInt(y))}`;
                            document.getElementById('modalDate').textContent = thaiDate;
                            // เวลา
                            let timeStr = '';
                            if (booking.start_time && booking.end_time) {
                                timeStr = booking.start_time + ' - ' + booking.end_time;
                            }
                            document.getElementById('modalTime').textContent = timeStr;
                            document.getElementById('bookingModal').style.display = 'flex';
                        });
                    // Modal close
                    document.getElementById('closeModalBtn').onclick = function() {
                        document.getElementById('bookingModal').style.display = 'none';
                    };
                    document.getElementById('bookingModal').onclick = function(e) {
                        if (e.target === this) this.style.display = 'none';
                    };
                        meetingsContainer.appendChild(meetingDiv);
                    });
                }

                dayCell.addEventListener('click', () => {
                    window.location.href = `form.php?date=${fullDate}&room=${encodeURIComponent(roomName)}`;
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
    </script>
</body>
</html>
