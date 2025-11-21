<?php
// for archiving

date_default_timezone_set('Asia/Manila');

$active_data_file = __DIR__ . '/truck_locations_data.json';
$archive_data_file = __DIR__ . '/truck_locations_archive.json';

define('OFFLINE_THRESHOLD', 1800);

if (!file_exists($active_data_file) || filesize($active_data_file) === 0) {
    echo "Active data file does not exist or is empty. Nothing to clean.";
    exit();
}

$current_locations = json_decode(file_get_contents($active_data_file), true);

if (!is_array($current_locations)) {
    echo "Invalid data format in the active JSON file. Cleanup aborted.";
    exit();
}

$archive_locations = file_exists($archive_data_file)
    ? json_decode(file_get_contents($archive_data_file), true)
    : [];

if (!is_array($archive_locations)) {
    $archive_locations = [];
}


$active_locations_to_keep = []; 
$locations_to_archive = []; 
$now = time(); 

foreach ($current_locations as $location) {

    if (isset($location['timestamp'])) {
        $location_time = strtotime($location['timestamp']);
        $time_difference = $now - $location_time;

        
        if ($time_difference <= OFFLINE_THRESHOLD) {
            $active_locations_to_keep[] = $location;
        } else {
           
            $locations_to_archive[] = $location;
        }
    }
}

if (!empty($locations_to_archive)) {
    
    $updated_archive = array_merge($archive_locations, $locations_to_archive);

    
    file_put_contents($archive_data_file, json_encode($updated_archive, JSON_PRETTY_PRINT));
}


file_put_contents($active_data_file, json_encode($active_locations_to_keep, JSON_PRETTY_PRINT));

echo "Cleanup and archiving complete. <br>";
echo "Kept: " . count($active_locations_to_keep) . " active truck(s).<br>";
echo "Archived: " . count($locations_to_archive) . " inactive truck(s).";

?>