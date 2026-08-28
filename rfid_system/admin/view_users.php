<?php
include '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$users = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Admin</title>
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
        
        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.5em;
            font-weight: 700;
        }
        
        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: white;
            color: black;
        }
        
        .btn-primary:hover {
            background: var(--text-secondary);
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.8em;
            border-radius: 6px;
        }
        
        .btn-edit {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--primary);
        }
        
        .btn-edit:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-delete {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--danger);
        }
        
        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }
        
        /* Card */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            overflow-x: auto;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
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
            position: sticky;
            top: 0;
            background: var(--card-bg);
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
        
        .user-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
        }
        
        .user-photo-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            font-weight: 700;
            font-size: 1.3em;
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
        
        .actions {
            display: flex;
            gap: 0.5rem;
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
            <div>
                <h1>All Users</h1>
                <p style="color: var(--text-secondary);">Manage registered users</p>
            </div>
            <a href="add_user.php" class="btn btn-primary">+ Add New User</a>
        </div>
        
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>UID</th>
                        <th>Name</th>
                        <th>USN</th>
                        <th>Email</th>
                        <th>Vehicle</th>
                        <th>License</th>
                        <th>Aadhar</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
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
                        <td><strong><?php echo $user['uid']; ?></strong></td>
                        <td><?php echo $user['name']; ?></td>
                        <td><?php echo $user['usn']; ?></td>
                        <td><?php echo $user['email'] ?: 'N/A'; ?></td>
                        <td>
                            <?php echo strtoupper($user['vehicle_type']); ?><br>
                            <small style="color: var(--text-secondary);"><?php echo $user['vehicle_number']; ?></small>
                        </td>
                        <td><?php echo $user['license_number'] ?: 'N/A'; ?></td>
                        <td><?php echo $user['aadhar_number'] ?: 'N/A'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['status']; ?>">
                                <?php echo strtoupper($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="actions">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-small btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
