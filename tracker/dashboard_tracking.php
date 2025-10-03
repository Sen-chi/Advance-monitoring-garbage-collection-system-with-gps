<?php
$pageTitle = "Tracking";
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <style>
    #map {
      height: 400px; 
      width: 100%;
      border-radius: 8px;
    }
    .map-box {
      margin-bottom: 20px;
    }
    .truck-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .truck-table th, .truck-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    .truck-table th {
      background-color: #f2f2f2;
    }
    .status-tag {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.9em;
      color: white;
    }
    .status-active { background-color: #28a745; } 
    .status-idle { background-color: #ffc107; }    
    .status-maintenance { background-color: #dc3545; } 
  </style>
</head>
<body>

  <div class="content">
    <h2>Garbage Truck Tracking</h2>
    <div class="map-box">
      <div id="map"></div>
    </div>

    <table class="truck-table" id="trucksTable">
      <thead>
        <tr>
          <th>Truck #</th>
          <th>Plate Number</th>
          <th>Driver</th>
          <th>Capacity</th>
          <th>Status</th>
          <th>Last Update</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data is loaded by JavaScript -->
      </tbody>
    </table>
  </div>
  
  <script src="scripts/truck_locations.js"></script>

  <?php require_once 'templates/footer.php'; ?>
</body>
</html>