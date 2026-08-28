<?php
include '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get user photo to delete
    $sql_get = "SELECT photo FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql_get);
    $user = mysqli_fetch_assoc($result);
    
    // Delete photo file
    if ($user && $user['photo']) {
        $photo_path = '../uploads/photos/' . $user['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    // Delete user
    $sql = "DELETE FROM users WHERE id = $id";
    mysqli_query($conn, $sql);
}

header('Location: view_users.php');
exit();
?>
