<?php
include 'config.php';

// Your desired credentials
$username = "admin";
$password = "CHANGE_ME";
$full_name = "System Administrator";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Delete existing admin with same username
$sql_delete = "DELETE FROM admins WHERE username = '$username'";
mysqli_query($conn, $sql_delete);

// Insert new admin with hashed password
$sql = "INSERT INTO admins (username, password, full_name) 
        VALUES ('$username', '$hashed_password', '$full_name')";

if (mysqli_query($conn, $sql)) {
    echo "✅ Admin created successfully!<br><br>";
    echo "Username: <strong>$username</strong><br>";
    echo "Password: <strong>$password</strong><br>";
    echo "Hashed: $hashed_password<br><br>";
    echo "You can now login at: <a href='admin/login.php'>Admin Login</a><br><br>";
    echo "⚠️ <strong>Delete this file (create_admin.php) after use for security!</strong>";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
