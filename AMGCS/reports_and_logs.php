<?php
// Define the page title *before* including the header
$pageTitle = "Reports";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
require_once 'templates/footer.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$isMasterData = in_array($currentPage, [
  'add_municipality.php',
  'municipality_list.php',
  'garbage_type_add.php',
  'truck_add.php',
  'truck_list.php'
]);

$isMunicipalityManagement = in_array($currentPage, [
  'add_municipality.php',
  'municipality_list.php'
]);
?>
<html>


<div class="content">
  <h2>Reports & Logs</h2>
  <div class="box-container">


    <!-- Table -->
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Route</th>
          <th>Driver</th>
          <th>Truck</th>
          <th>Waste Type</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>April 16, 2025</td>
          <td>7:00 AM - 11:00 AM</td>
          <td>Brgy. Malasiqui</td>
          <td>Juan Dela Cruz</td>
          <td>ABC-1234</td>
          <td>Biodegradable</td>
          <td>Completed</td>
        </tr>
        <tr>
          <td>April 15, 2025</td>
          <td>1:00 PM - 5:00 PM</td>
          <td>Brgy. Lingayen</td>
          <td>Maria Santos</td>
          <td>XYZ-5678</td>
          <td>Non-Biodegradable</td>
          <td>Pending</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<script>
function toggleDropdown(event) {
  const section = event.currentTarget;
  const dropdown = section.nextElementSibling;
  dropdown.classList.toggle("show");
  section.classList.toggle("active-dropdown");
}
</script>

</body>
</html>
