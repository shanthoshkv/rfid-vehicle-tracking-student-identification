<?php
include '../config.php';

$today = date('Y-m-d');

$sql_entry = "SELECT COUNT(*) as count FROM access_logs WHERE access_type='entry' AND DATE(timestamp)='$today'";
$sql_exit = "SELECT COUNT(*) as count FROM access_logs WHERE access_type='exit' AND DATE(timestamp)='$today'";
$entry_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_entry))['count'];
$exit_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_exit))['count'];
$inside_count = $entry_count - $exit_count;

$sql_recent = "SELECT u.*, a.access_type, a.timestamp 
               FROM access_logs a 
               LEFT JOIN users u ON a.user_id = u.id 
               WHERE DATE(a.timestamp) = '$today' 
               ORDER BY a.timestamp DESC LIMIT 1";
$recent_result = mysqli_query($conn, $sql_recent);
$recent_access = mysqli_num_rows($recent_result) > 0 ? mysqli_fetch_assoc($recent_result) : null;

// Get all vehicles and photo for the same UID
$all_vehicles = array();
$user_photo = null;
if ($recent_access) {
    $uid = $recent_access['uid'];
    $sql_vehicles = "SELECT vehicle_name, vehicle_number, photo FROM users WHERE uid = '$uid' AND status='active'";
    $vehicles_result = mysqli_query($conn, $sql_vehicles);
    while ($vehicle = mysqli_fetch_assoc($vehicles_result)) {
        $all_vehicles[] = $vehicle;
        // Get photo from first available record
        if (!$user_photo && $vehicle['photo']) {
            $user_photo = $vehicle['photo'];
        }
    }
    // Use the photo from any vehicle record (all same USN = same photo)
    if ($user_photo) {
        $recent_access['photo'] = $user_photo;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Gate Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #6366f1;
            --dark: #000000;
            --card-bg: #0f0f0f;
            --text: #ffffff;
            --text-secondary: #a1a1aa;
            --border: #27272a;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        body {
            font-family: 'JetBrains Mono', sans-serif;
            background: #000000;
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .dashboard-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1rem;
            gap: 1rem;
            max-width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .header-left h1 {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        
        .header-left p {
            color: var(--text-secondary);
            font-size: 0.8em;
        }
        
        .time-display .time {
            font-size: 1.5em;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        
        .time-display .date {
            color: var(--text-secondary);
            font-size: 0.8em;
            text-align: right;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            flex-shrink: 0;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.2rem;
            text-align: center;
        }
        
        .stat-label {
            font-size: 0.75em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        
        .stat-card.entry .stat-value { color: var(--success); }
        .stat-card.exit .stat-value { color: var(--danger); }
        .stat-card.inside .stat-value { color: var(--primary); }
        
        .main-content {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 1rem;
            flex: 1;
            overflow: hidden;
        }
        
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        
        .card-header h2 {
            font-size: 1.2em;
            font-weight: 700;
        }
        
        .live-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 15px;
            font-size: 0.7em;
            font-weight: 600;
            color: var(--success);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pulse-dot {
            width: 6px;
            height: 6px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .user-display {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
            align-items: start;
        }
        
        .user-details h3 {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .user-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem;
            margin-bottom: 1rem;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.85em;
        }
        
        .info-label {
            color: var(--text-secondary);
            font-size: 0.9em;
            min-width: 70px;
            font-weight: 500;
        }
        
        .info-value {
            color: var(--text);
            font-weight: 600;
        }
        
        .vehicles-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .vehicles-section h4 {
            font-size: 0.9em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.8rem;
        }
        
        .vehicle-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .vehicle-item {
            padding: 0.6rem;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.85em;
        }
        
        .user-photo-container {
            position: relative;
        }
        
        .user-photo, .user-photo-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            border: 2px solid var(--border);
        }
        
        .user-photo {
            object-fit: cover;
        }
        
        .user-photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            font-size: 5em;
            font-weight: 700;
            color: white;
        }
        
        .access-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .access-badge.entry {
            background: var(--success);
            color: #000;
        }
        
        .access-badge.exit {
            background: var(--danger);
            color: #fff;
        }
        
        .waiting-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .waiting-state .icon {
            font-size: 3em;
            margin-bottom: 0.8rem;
            opacity: 0.3;
        }
        
        .waiting-state h3 {
            font-size: 1.2em;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .activity-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            padding-right: 0.3rem;
        }
        
        .activity-list::-webkit-scrollbar {
            width: 5px;
        }
        
        .activity-list::-webkit-scrollbar-track {
            background: var(--dark);
            border-radius: 10px;
        }
        
        .activity-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }
        
        .activity-item {
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.8rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        
        .activity-item:hover {
            border-color: var(--primary);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-name {
            font-size: 0.95em;
            font-weight: 700;
            color: var(--text);
        }
        
        .activity-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-size: 0.7em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .activity-badge.entry {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .activity-badge.exit {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .activity-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.4rem;
            font-size: 0.75em;
        }
        
        .activity-detail-item {
            color: var(--text-secondary);
        }
        
        .activity-detail-item strong {
            color: var(--text);
        }
        
        .activity-vehicles {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border);
        }
        
        .activity-vehicles-title {
            font-size: 0.7em;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }
        
        .activity-vehicle-item {
            font-size: 0.7em;
            color: var(--text);
            padding: 0.3rem 0;
        }
        
        .activity-time {
            font-size: 0.75em;
            color: var(--text-secondary);
            margin-top: 0.2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div class="header-left">
                <h1>Security Gate Dashboard</h1>
            </div>
            <div class="time-display">
                <div class="time" id="currentTime">--:--:--</div>
                <div class="date" id="currentDate">---</div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card entry">
                <div class="stat-label">Entries Today</div>
                <div class="stat-value" id="entryCount"><?php echo $entry_count; ?></div>
            </div>
            <div class="stat-card exit">
                <div class="stat-label">Exits Today</div>
                <div class="stat-value" id="exitCount"><?php echo $exit_count; ?></div>
            </div>
            <div class="stat-card inside">
                <div class="stat-label">Inside Campus</div>
                <div class="stat-value" id="insideCount"><?php echo $inside_count; ?></div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h2>Current Access</h2>
                    <div class="live-badge">
                        <span class="pulse-dot"></span>
                        LIVE
                    </div>
                </div>
                
                <div id="currentAccessDisplay">
                    <?php if ($recent_access): ?>
                        <div class="user-display">
                            <div class="user-details">
                                <h3><?php echo $recent_access['name']; ?></h3>
                                <div class="user-info">
                                    <div class="info-row">
                                        <span class="info-label">USN:</span>
                                        <span class="info-value"><?php echo $recent_access['usn']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value"><?php echo $recent_access['phone']; ?></span>
                                    </div>
                                </div>
                                
                                <?php if (count($all_vehicles) > 0): ?>
                                <div class="vehicles-section">
                                    <h4>📋 All Registered Vehicles (<?php echo count($all_vehicles); ?>)</h4>
                                    <div class="vehicle-list">
                                        <?php foreach ($all_vehicles as $vehicle): ?>
                                        <div class="vehicle-item">
                                            <strong><?php echo $vehicle['vehicle_name']; ?></strong> - <?php echo $vehicle['vehicle_number']; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="user-photo-container">
                                <?php if ($recent_access['photo']): ?>
                                    <img src="../uploads/photos/<?php echo $recent_access['photo']; ?>" class="user-photo" alt="Photo">
                                <?php else: ?>
                                    <div class="user-photo-placeholder">
                                        <?php echo strtoupper(substr($recent_access['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="access-badge <?php echo $recent_access['access_type']; ?>">
                                    <?php echo strtoupper($recent_access['access_type']); ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="waiting-state">
                            <div class="icon">⏳</div>
                            <h3>Waiting for card scan...</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Today's Activity</h2>
                </div>
                <div class="activity-list" id="activityList">
                    <!-- Loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-IN', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: false 
            });
            const dateStr = now.toLocaleDateString('en-IN', { 
                weekday: 'short',
                day: '2-digit', 
                month: 'short', 
                year: 'numeric' 
            });
            
            document.getElementById('currentTime').textContent = timeStr;
            document.getElementById('currentDate').textContent = dateStr;
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        function updateDashboard() {
            fetch('get_live_data.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('entryCount').textContent = data.entry_count;
                    document.getElementById('exitCount').textContent = data.exit_count;
                    document.getElementById('insideCount').textContent = data.inside_count;
                    
                    if (data.recent_access) {
                        updateCurrentAccess(data.recent_access, data.all_vehicles);
                    }
                    
                    if (data.activity_log) {
                        updateActivityLog(data.activity_log);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function updateCurrentAccess(access, allVehicles) {
            const photoHtml = access.photo 
                ? `<img src="../uploads/photos/${access.photo}" class="user-photo" alt="Photo">`
                : `<div class="user-photo-placeholder">${access.name.charAt(0).toUpperCase()}</div>`;
            
            let vehiclesHtml = '';
            if (allVehicles && allVehicles.length > 0) {
                vehiclesHtml = `
                    <div class="vehicles-section">
                        <h4>📋 All Registered Vehicles (${allVehicles.length})</h4>
                        <div class="vehicle-list">
                            ${allVehicles.map(v => `
                                <div class="vehicle-item">
                                    <strong>${v.vehicle_name}</strong> - ${v.vehicle_number}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            const html = `
                <div class="user-display">
                    <div class="user-details">
                        <h3>${access.name}</h3>
                        <div class="user-info">
                            <div class="info-row">
                                <span class="info-label">USN:</span>
                                <span class="info-value">${access.usn}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value">${access.phone}</span>
                            </div>
                        </div>
                        ${vehiclesHtml}
                    </div>
                    <div class="user-photo-container">
                        ${photoHtml}
                        <div class="access-badge ${access.access_type}">${access.access_type.toUpperCase()}</div>
                    </div>
                </div>
            `;
            
            document.getElementById('currentAccessDisplay').innerHTML = html;
        }
        
        function updateActivityLog(activities) {
            const html = activities.map(activity => {
                const time = new Date(activity.timestamp).toLocaleTimeString('en-IN', {
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false
                });
                
                let vehiclesHtml = '';
                if (activity.all_user_vehicles && activity.all_user_vehicles.length > 0) {
                    vehiclesHtml = `
                        <div class="activity-vehicles">
                            <div class="activity-vehicles-title">All Registered Vehicles</div>
                            ${activity.all_user_vehicles.map(v => `
                                <div class="activity-vehicle-item">
                                    <strong>${v.vehicle_name}</strong> - ${v.vehicle_number}
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                
                return `
                    <div class="activity-item">
                        <div class="activity-header">
                            <div class="activity-name">${activity.name}</div>
                            <div class="activity-badge ${activity.access_type}">${activity.access_type.toUpperCase()}</div>
                        </div>
                        <div class="activity-details">
                            <div class="activity-detail-item"><strong>USN:</strong> ${activity.usn}</div>
                            <div class="activity-detail-item"><strong>Phone:</strong> ${activity.phone}</div>
                        </div>
                        ${vehiclesHtml}
                        <div class="activity-time">⏱ ${time}</div>
                    </div>
                `;
            }).join('');
            
            document.getElementById('activityList').innerHTML = html;
        }
        
        setInterval(updateDashboard, 2000);
        updateDashboard();
    </script>
</body>
</html>
