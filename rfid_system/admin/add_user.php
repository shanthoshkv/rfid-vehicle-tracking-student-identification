<?php
include '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $usn = mysqli_real_escape_string($conn, $_POST['usn']);
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $license_number = mysqli_real_escape_string($conn, $_POST['license_number']);
    $aadhar_number = mysqli_real_escape_string($conn, $_POST['aadhar_number']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate Aadhar (must be 12 digits)
    if (strlen($aadhar_number) != 12 || !ctype_digit($aadhar_number)) {
        $error = 'Aadhar number must be exactly 12 digits!';
    } else {
        // Handle photo upload (REQUIRED) - Store as USN
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png');
            $filename = $_FILES['photo']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // Use USN as filename (will overwrite if exists)
                $newname = $usn . '.' . strtolower($filetype);
                $upload_path = '../uploads/photos/' . $newname;
                
                // Delete old photo with different extension if exists
                foreach ($allowed as $ext) {
                    $old_file = '../uploads/photos/' . $usn . '.' . $ext;
                    if (file_exists($old_file) && $old_file != $upload_path) {
                        unlink($old_file);
                    }
                }
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    $photo = $newname;
                } else {
                    $error = 'Error uploading photo!';
                }
            } else {
                $error = 'Invalid file format! Only JPG, JPEG, PNG allowed.';
            }
        } else {
            $error = 'User photo is required!';
        }
        
        if (!$error) {
            // Check if vehicle number already exists
            $check_sql = "SELECT * FROM users WHERE vehicle_number = '$vehicle_number'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Vehicle number already registered!';
            } else {
                // Check if this USN already exists to get the photo
                $check_usn_sql = "SELECT photo FROM users WHERE usn = '$usn' LIMIT 1";
                $usn_result = mysqli_query($conn, $check_usn_sql);
                if (mysqli_num_rows($usn_result) > 0) {
                    // USN exists, use existing photo if new one not uploaded
                    $existing_user = mysqli_fetch_assoc($usn_result);
                    if (!$photo) {
                        $photo = $existing_user['photo'];
                    }
                }
                
                $sql = "INSERT INTO users (uid, name, email, phone, usn, vehicle_type, vehicle_name, vehicle_number, license_number, aadhar_number, photo, status) 
                        VALUES ('$uid', '$name', '$email', '$phone', '$usn', '$vehicle_type', '$vehicle_name', '$vehicle_number', '$license_number', '$aadhar_number', '$photo', '$status')";
                
                if (mysqli_query($conn, $sql)) {
                    $success = 'User added successfully! Same RFID can be used for another vehicle.';
                    $_POST = array();
                } else {
                    $error = 'Error: ' . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h1 {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.2rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--text-secondary);
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 0.9rem;
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.9em;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-group input::placeholder {
            color: var(--text-secondary);
            opacity: 0.5;
        }
        
        .form-group input[type="file"] {
            padding: 0.5rem 0.9rem;
            cursor: pointer;
        }
        
        .form-help {
            font-size: 0.75em;
            color: var(--text-secondary);
            margin-top: 0.2rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            font-family: 'JetBrains Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            border-color: var(--primary);
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
                <a href="../security/" target="_blank">Security Gate</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>Add New User</h1>
            <p>Register a new vehicle and RFID tag (Same UID allowed for multiple vehicles, photo saved as USN)</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>RFID UID</label>
                        <input type="text" name="uid" placeholder="Scan RFID" required 
                               value="<?php echo isset($_POST['uid']) ? htmlspecialchars($_POST['uid']) : ''; ?>">
                        <div class="form-help">Same UID can register multiple vehicles</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="Student name" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Student USN</label>
                        <input type="text" name="usn" placeholder="e.g., 1RV23AS001" required
                               value="<?php echo isset($_POST['usn']) ? htmlspecialchars($_POST['usn']) : ''; ?>">
                        <div class="form-help">Photo will be saved as USN</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="user@example.com" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" placeholder="10-digit phone" maxlength="10" required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Aadhar Number</label>
                        <input type="text" name="aadhar_number" placeholder="12-digit Aadhar" maxlength="12" required
                               value="<?php echo isset($_POST['aadhar_number']) ? htmlspecialchars($_POST['aadhar_number']) : ''; ?>">
                        <div class="form-help">Must be exactly 12 digits</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Vehicle Type</label>
                        <select name="vehicle_type" required>
                            <option value="">-- Select --</option>
                            <option value="bike" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type']=='bike')?'selected':''; ?>>Bike</option>
                            <option value="car" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type']=='car')?'selected':''; ?>>Car</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <input type="text" name="vehicle_name" placeholder="e.g., Honda Activa, Civic" required
                               value="<?php echo isset($_POST['vehicle_name']) ? htmlspecialchars($_POST['vehicle_name']) : ''; ?>">
                        <div class="form-help">Brand and model name</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Vehicle Number</label>
                        <input type="text" name="vehicle_number" placeholder="e.g., KA01AB1234" required
                               value="<?php echo isset($_POST['vehicle_number']) ? htmlspecialchars($_POST['vehicle_number']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>License Number</label>
                        <input type="text" name="license_number" placeholder="DL number" required
                               value="<?php echo isset($_POST['license_number']) ? htmlspecialchars($_POST['license_number']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>User Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" required>
                        <div class="form-help">JPG, JPEG or PNG (saved as USN)</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add User</button>
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
