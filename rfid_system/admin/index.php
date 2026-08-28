<?php
include '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get statistics
$stats = array();

$sql1 = "SELECT COUNT(*) as total FROM users WHERE status='active'";
$result1 = mysqli_query($conn, $sql1);
$stats['total_users'] = mysqli_fetch_assoc($result1)['total'];

$sql2 = "SELECT COUNT(*) as total FROM access_logs WHERE DATE(timestamp) = CURDATE()";
$result2 = mysqli_query($conn, $sql2);
$stats['today_access'] = mysqli_fetch_assoc($result2)['total'];

$sql3 = "SELECT COUNT(*) as total FROM access_logs WHERE access_type='entry' AND DATE(timestamp) = CURDATE()";
$result3 = mysqli_query($conn, $sql3);
$stats['today_entries'] = mysqli_fetch_assoc($result3)['total'];

$sql4 = "SELECT COUNT(*) as total FROM access_logs WHERE access_type='exit' AND DATE(timestamp) = CURDATE()";
$result4 = mysqli_query($conn, $sql4);
$stats['today_exits'] = mysqli_fetch_assoc($result4)['total'];

// Get recent users
$sql_users = "SELECT * FROM users ORDER BY created_at DESC LIMIT 10";
$recent_users = mysqli_query($conn, $sql_users);

// Get recent logs
$sql_logs = "SELECT * FROM access_logs ORDER BY timestamp DESC LIMIT 15";
$recent_logs = mysqli_query($conn, $sql_logs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RFID System</title>
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
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #ec4899;
            --dark: #000000;
            --dark-secondary: #0a0a0a;
            --card-bg: #0f0f0f;
            --text: #ffffff;
            --text-secondary: #a1a1aa;
            --border: #27272a;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        body {
            font-family: 'JetBrains Mono', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #000000;
            color: var(--text);
            line-height: 1.6;
        }
        
        /* Navigation */
        .navbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-container {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--text);
        }
        
        .navbar-menu {
            display: flex;
            gap: 0.5rem;
        }
        
        .navbar-menu a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .navbar-menu a:hover {
            background: var(--dark-secondary);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .navbar-menu a.active {
            background: var(--primary);
            color: white;
        }
        
        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 1em;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:nth-child(1)::before { background: var(--primary); }
        .stat-card:nth-child(2)::before { background: var(--success); }
        .stat-card:nth-child(3)::before { background: var(--accent); }
        .stat-card:nth-child(4)::before { background: var(--danger); }
        
        .stat-label {
            font-size: 0.85em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 3em;
            font-weight: 700;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        
        .stat-card:nth-child(1) .stat-value { color: var(--primary); }
        .stat-card:nth-child(2) .stat-value { color: var(--success); }
        .stat-card:nth-child(3) .stat-value { color: var(--accent); }
        .stat-card:nth-child(4) .stat-value { color: var(--danger); }
        
        /* Card */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .card-header h2 {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--text);
        }
        
        /* Button */
        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: white;
            color: black;
        }
        
        .btn-primary:hover {
            background: var(--text-secondary);
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-secondary:hover {
            border-color: var(--primary);
            background: var(--dark-secondary);
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            text-align: left;
            padding: 1rem;
            font-size: 0.85em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }
        
        table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        
        table tr:hover {
            background: var(--dark-secondary);
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        /* Badge */
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.75em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .badge-active {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .badge-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .badge-entry {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .badge-exit {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        /* User Photo */
        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
        }
        
        .user-photo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            font-weight: 700;
            font-size: 1.1em;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-menu {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.85em;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="navbar-brand">RFID Admin Panel</div>
            <div class="navbar-menu">
                <a href="index.php" class="active">Dashboard</a>
                <a href="add_user.php">Add User</a>
                <a href="view_users.php">View Users</a>
                <a href="view_logs.php">View Logs</a>
                <a href="../security/" target="_blank">Security Gate</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Today's Access</div>
                <div class="stat-value"><?php echo $stats['today_access']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Today's Entries</div>
                <div class="stat-value"><?php echo $stats['today_entries']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Today's Exits</div>
                <div class="stat-value"><?php echo $stats['today_exits']; ?></div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Users</h2>
                <a href="view_users.php" class="btn btn-primary">View All Users</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>USN</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($recent_users)): ?>
                    <tr>
                        <td>
                            <?php if ($user['photo']): ?>
                                <img src="../uploads/photos/<?php echo $user['photo']; ?>" class="user-photo" alt="Photo">
                            <?php else: ?>
                                <div class="user-photo-placeholder">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $user['name']; ?></strong></td>
                        <td><?php echo $user['usn']; ?></td>
                        <td><?php echo strtoupper($user['vehicle_type']); ?> - <?php echo $user['vehicle_number']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['status']; ?>">
                                <?php echo strtoupper($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Activity</h2>
                <a href="view_logs.php" class="btn btn-secondary">View All Logs</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>UID</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = mysqli_fetch_assoc($recent_logs)): ?>
                    <tr>
                        <td><strong><?php echo $log['name']; ?></strong></td>
                        <td><?php echo $log['uid']; ?></td>
                        <td><?php echo $log['vehicle_number']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $log['access_type']; ?>">
                                <?php echo strtoupper($log['access_type']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y, h:i A', strtotime($log['timestamp'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
