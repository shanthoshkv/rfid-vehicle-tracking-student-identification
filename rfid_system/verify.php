<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    
    // Check if UID exists and is active
    $sql = "SELECT * FROM users WHERE uid = '$uid' AND status = 'active' ORDER BY created_at DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $name = $user['name'];
        $vehicle_type = $user['vehicle_type'];
        $vehicle_name = $user['vehicle_name'];
        $vehicle_number = $user['vehicle_number'];
        $user_id = $user['id'];
        $photo = $user['photo'];
        
        // Check last access type for this specific vehicle
        $sql_last = "SELECT access_type FROM access_logs WHERE vehicle_number = '$vehicle_number' ORDER BY timestamp DESC LIMIT 1";
        $result_last = mysqli_query($conn, $sql_last);
        
        $access_type = 'entry';
        if (mysqli_num_rows($result_last) > 0) {
            $last_access = mysqli_fetch_assoc($result_last);
            $access_type = ($last_access['access_type'] == 'entry') ? 'exit' : 'entry';
        }
        
        // Log access
        $log_sql = "INSERT INTO access_logs (uid, user_id, name, vehicle_type, vehicle_number, access_type) 
                    VALUES ('$uid', $user_id, '$name', '$vehicle_type', '$vehicle_number', '$access_type')";
        mysqli_query($conn, $log_sql);
        
        echo "GRANTED|$name|$access_type|$vehicle_type|$vehicle_number|$photo";
        
    } else {
        echo "DENIED|Unknown Card";
    }
}

mysqli_close($conn);
?>
