<?php
include '../config.php';

$today = date('Y-m-d');

$sql_entry = "SELECT COUNT(*) as count FROM access_logs WHERE access_type='entry' AND DATE(timestamp)='$today'";
$sql_exit = "SELECT COUNT(*) as count FROM access_logs WHERE access_type='exit' AND DATE(timestamp)='$today'";
$entry_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_entry))['count'];
$exit_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_exit))['count'];

$sql_recent = "SELECT u.*, a.access_type, a.timestamp 
               FROM access_logs a 
               LEFT JOIN users u ON a.user_id = u.id 
               WHERE DATE(a.timestamp) = '$today' 
               ORDER BY a.timestamp DESC LIMIT 1";
$recent_result = mysqli_query($conn, $sql_recent);
$recent_access = mysqli_num_rows($recent_result) > 0 ? mysqli_fetch_assoc($recent_result) : null;

// Get all vehicles and photo for the recent access UID (photo is same for all vehicles of same person)
$all_vehicles = array();
$user_photo = null;
if ($recent_access) {
    $uid = $recent_access['uid'];
    $sql_vehicles = "SELECT vehicle_name, vehicle_number, photo, usn FROM users WHERE uid = '$uid' AND status='active'";
    $vehicles_result = mysqli_query($conn, $sql_vehicles);
    while ($vehicle = mysqli_fetch_assoc($vehicles_result)) {
        $all_vehicles[] = $vehicle;
        // Get photo from first record (all have same photo based on USN)
        if (!$user_photo && $vehicle['photo']) {
            $user_photo = $vehicle['photo'];
        }
    }
    // Override recent_access photo with the correct one
    if ($user_photo) {
        $recent_access['photo'] = $user_photo;
    }
}

// Get activity log with all vehicles for each user
$sql_activity = "SELECT a.timestamp, a.access_type, u.* 
                 FROM access_logs a 
                 LEFT JOIN users u ON a.user_id = u.id 
                 WHERE DATE(a.timestamp) = '$today' 
                 ORDER BY a.timestamp DESC LIMIT 20";
$activity_result = mysqli_query($conn, $sql_activity);
$activity_log = array();

while ($activity = mysqli_fetch_assoc($activity_result)) {
    // Get all vehicles and photo for this user's UID
    $uid = $activity['uid'];
    $sql_user_vehicles = "SELECT vehicle_name, vehicle_number, photo FROM users WHERE uid = '$uid' AND status='active'";
    $user_vehicles_result = mysqli_query($conn, $sql_user_vehicles);
    $user_vehicles = array();
    $activity_photo = null;
    while ($vehicle = mysqli_fetch_assoc($user_vehicles_result)) {
        $user_vehicles[] = $vehicle;
        if (!$activity_photo && $vehicle['photo']) {
            $activity_photo = $vehicle['photo'];
        }
    }
    $activity['all_user_vehicles'] = $user_vehicles;
    // Override photo with correct one
    if ($activity_photo) {
        $activity['photo'] = $activity_photo;
    }
    $activity_log[] = $activity;
}

$data = array(
    'entry_count' => $entry_count,
    'exit_count' => $exit_count,
    'inside_count' => $entry_count - $exit_count,
    'recent_access' => $recent_access,
    'all_vehicles' => $all_vehicles,
    'activity_log' => $activity_log
);

header('Content-Type: application/json');
echo json_encode($data);

mysqli_close($conn);
?>
