<?php
include '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Handle single log deletion
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM access_logs WHERE id = $id";
    if (mysqli_query($conn, $delete_sql)) {
        $success = 'Log deleted successfully!';
    } else {
        $error = 'Error deleting log!';
    }
}

// Handle bulk deletion
if (isset($_POST['bulk_delete']) && isset($_POST['selected_logs'])) {
    $selected_ids = array_map('intval', $_POST['selected_logs']);
    $ids_string = implode(',', $selected_ids);
    $bulk_delete_sql = "DELETE FROM access_logs WHERE id IN ($ids_string)";
    if (mysqli_query($conn, $bulk_delete_sql)) {
        $success = count($selected_ids) . ' log(s) deleted successfully!';
    } else {
        $error = 'Error deleting logs!';
    }
}

// Handle date range deletion
if (isset($_POST['delete_range'])) {
    $from_date = mysqli_real_escape_string($conn, $_POST['from_date']);
    $to_date = mysqli_real_escape_string($conn, $_POST['to_date']);
    
    if ($from_date && $to_date) {
        $range_delete_sql = "DELETE FROM access_logs WHERE DATE(timestamp) BETWEEN '$from_date' AND '$to_date'";
        $result = mysqli_query($conn, $range_delete_sql);
        if ($result) {
            $deleted_count = mysqli_affected_rows($conn);
            $success = "$deleted_count log(s) deleted from $from_date to $to_date!";
        } else {
            $error = 'Error deleting logs in date range!';
        }
    } else {
        $error = 'Please select both from and to dates!';
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Build SQL query
$sql = "SELECT * FROM access_logs WHERE 1=1";

if ($filter == 'entry') {
    $sql .= " AND access_type = 'entry'";
} elseif ($filter == 'exit') {
    $sql .= " AND access_type = 'exit'";
}

if ($date_filter) {
    $sql .= " AND DATE(timestamp) = '$date_filter'";
}

$sql .= " ORDER BY timestamp DESC LIMIT 500";
$logs = mysqli_query($conn, $sql);

// Get today's stats
$today = date('Y-m-d');
$sql_entry = "SELECT COUNT(*) as count FROM access_logs WHERE access_type='entry' AND DATE(timestamp)='$today'";
$sql_exit = "SELECT COUNT(*) as count FROM access_logs WHERE access_type='exit' AND DATE(timestamp)='$today'";
$entry_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_entry))['count'];
$exit_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_exit))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Logs - Admin</title>
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
            text-decoration: none;
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
            background: var(--dark);
            color: var(--text);
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--text-secondary);
        }
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-mini {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-mini h3 {
            font-size: 0.85em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.8rem;
        }
        
        .stat-mini .number {
            font-size: 2.5em;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        
        .stat-mini:nth-child(1) .number { color: var(--success); }
        .stat-mini:nth-child(2) .number { color: var(--danger); }
        .stat-mini:nth-child(3) .number { color: var(--primary); }
        
        .filters {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .filter-group label {
            color: var(--text-secondary);
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filters select,
        .filters input {
            padding: 0.6rem 1rem;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9em;
        }
        
        .filters select:focus,
        .filters input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 0.85em;
            font-family: 'JetBrains Mono', monospace;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-secondary:hover {
            background: var(--dark);
            border-color: var(--primary);
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
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
        }
        
        .bulk-actions {
            display: flex;
            gap: 0.8rem;
        }
        
        .delete-actions {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .delete-section h3 {
            font-size: 1em;
            margin-bottom: 1rem;
            color: var(--text);
        }
        
        .delete-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
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
            background: var(--dark);
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.75em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
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
        
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--primary);
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.75em;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="navbar-brand">RFID Admin Panel</a>
            <div class="navbar-menu">
                <a href="index.php">Dashboard</a>
                <a href="add_user.php">Add User</a>
                <a href="view_users.php">View Users</a>
                <a href="view_logs.php">View Logs</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>Access Logs</h1>
            <p>View and manage entry/exit records</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats-mini">
            <div class="stat-mini">
                <h3>Today's Entries</h3>
                <div class="number"><?php echo $entry_count; ?></div>
            </div>
            <div class="stat-mini">
                <h3>Today's Exits</h3>
                <div class="number"><?php echo $exit_count; ?></div>
            </div>
            <div class="stat-mini">
                <h3>Inside Campus</h3>
                <div class="number"><?php echo $entry_count - $exit_count; ?></div>
            </div>
        </div>
        
        <div class="filters">
            <form method="GET" style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <div class="filter-group">
                    <label>Filter:</label>
                    <select name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="entry" <?php echo $filter == 'entry' ? 'selected' : ''; ?>>Entry Only</option>
                        <option value="exit" <?php echo $filter == 'exit' ? 'selected' : ''; ?>>Exit Only</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
        
        <div class="delete-actions">
            <div class="delete-section">
                <h3>🗑️ Delete Logs by Date Range</h3>
                <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete all logs in this date range?');">
                    <div class="filter-group">
                        <label>From:</label>
                        <input type="date" name="from_date" required>
                    </div>
                    <div class="filter-group">
                        <label>To:</label>
                        <input type="date" name="to_date" required>
                    </div>
                    <button type="submit" name="delete_range" class="btn btn-danger">Delete Range</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Log Records</h2>
                <div class="bulk-actions">
                    <button type="button" class="btn btn-secondary btn-small" onclick="selectAll()">Select All</button>
                    <button type="button" class="btn btn-secondary btn-small" onclick="deselectAll()">Deselect All</button>
                    <button type="button" class="btn btn-danger btn-small" onclick="deleteSelected()">Delete Selected</button>
                </div>
            </div>
            
            <form method="POST" id="bulkDeleteForm">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCheckbox" onclick="toggleAll(this)"></th>
                            <th>UID</th>
                            <th>Name</th>
                            <th>Vehicle</th>
                            <th>Number</th>
                            <th>Type</th>
                            <th>Timestamp</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_logs[]" value="<?php echo $log['id']; ?>" class="log-checkbox"></td>
                            <td><strong><?php echo $log['uid']; ?></strong></td>
                            <td><?php echo $log['name']; ?></td>
                            <td><?php echo strtoupper($log['vehicle_type']); ?></td>
                            <td><?php echo $log['vehicle_number']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $log['access_type']; ?>">
                                    <?php echo $log['access_type'] == 'entry' ? '🟢 ENTRY' : '🔴 EXIT'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y, h:i:s A', strtotime($log['timestamp'])); ?></td>
                            <td>
                                <a href="?delete_id=<?php echo $log['id']; ?>&filter=<?php echo $filter; ?>&date=<?php echo $date_filter; ?>" 
                                   class="btn btn-danger btn-small" 
                                   onclick="return confirm('Delete this log?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    
    <script>
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.log-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('.log-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        }
        
        function deselectAll() {
            const checkboxes = document.querySelectorAll('.log-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        }
        
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.log-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one log to delete!');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${checkboxes.length} selected log(s)?`)) {
                const form = document.getElementById('bulkDeleteForm');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'bulk_delete';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        }
    </script>
</body>
</html>
