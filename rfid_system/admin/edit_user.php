<?php
include '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';
$user = null;

// Get user ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
    } else {
        header('Location: view_users.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $usn = mysqli_real_escape_string($conn, $_POST['usn']);
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $license_number = mysqli_real_escape_string($conn, $_POST['license_number']);
    $aadhar_number = mysqli_real_escape_string($conn, $_POST['aadhar_number']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $sql = "UPDATE users SET 
            uid='$uid', name='$name', email='$email', usn='$usn', 
            vehicle_type='$vehicle_type', vehicle_number='$vehicle_number', 
            license_number='$license_number', aadhar_number='$aadhar_number', status='$status'
            WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        $success = 'User updated successfully!';
        // Reload user data
        $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
        $user = mysqli_fetch_assoc($result);
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
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
        }
        
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9em;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.9rem 1rem;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.95em;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.9rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
            font-family: 'JetBrains Mono', monospace;
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
            background: var(--dark);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="navbar-brand">🚗 RFID Admin Panel</a>
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
            <h1>Edit User</h1>
            <p>Update user information</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($user): ?>
        <div class="card">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>RFID UID *</label>
                        <input type="text" name="uid" value="<?php echo htmlspecialchars($user['uid']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Student USN *</label>
                        <input type="text" name="usn" value="<?php echo htmlspecialchars($user['usn']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Vehicle Type *</label>
                        <select name="vehicle_type" required>
                            <option value="bike" <?php echo $user['vehicle_type']=='bike'?'selected':''; ?>>Bike</option>
                            <option value="car" <?php echo $user['vehicle_type']=='car'?'selected':''; ?>>Car</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Vehicle Number *</label>
                        <input type="text" name="vehicle_number" value="<?php echo htmlspecialchars($user['vehicle_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>License Number</label>
                        <input type="text" name="license_number" value="<?php echo htmlspecialchars($user['license_number']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Aadhar Number</label>
                        <input type="text" name="aadhar_number" value="<?php echo htmlspecialchars($user['aadhar_number']); ?>" maxlength="12">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="active" <?php echo $user['status']=='active'?'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status']=='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="view_users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
