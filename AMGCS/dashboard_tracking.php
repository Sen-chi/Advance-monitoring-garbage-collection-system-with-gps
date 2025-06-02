<?php
// Define the page title *before* including the header
$pageTitle = "Tracking";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
require_once 'templates/footer.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$isFileManagement = in_array($currentPage, [
  'add_municipality.php',
  'municipality_list.php',
  'garbage_type_add.php',
  'truck_add.php',
  'truck_list.php'
]);

$isMunicipality = in_array($currentPage, [
  'add_municipality.php',
  'municipality_list.php'
]);
?>
<html>

  <!-- MAIN CONTENT -->
  <div class="content">
    <h2>Garbage Truck Tracking</h2>
    <div class="map-box">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d24180.87113952668!2d120.9876323!3d14.6042006!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1616575037394!5m2!1sen!2sph"
        width="1000px" height="400px" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy">
      </iframe>
    </div>

    <table class="truck-table">
      <thead>
        <tr>
          <th>Truck #</th>
          <th>Plate Number</th>
          <th>Driver</th>
          <th>Capacity</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>ABC-1234</td>
          <td>Juan Dela Cruz</td>
          <td>5 tons</td>
          <td><span class="status-tag status-active">On Route</span></td>
        </tr>
        <tr>
          <td>2</td>
          <td>XYZ-5678</td>
          <td>Maria Santos</td>
          <td>4 tons</td>
          <td><span class="status-tag status-idle">Idle</span></td>
        </tr>
        <tr>
          <td>3</td>
          <td>LMN-9012</td>
          <td>Pedro Reyes</td>
          <td>6 tons</td>
          <td><span class="status-tag status-maintenance">Maintenance</span></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- JS: Dropdown toggle -->
  <script>
function toggleDropdown(e) {
    const dropdown = e.currentTarget.nextElementSibling;
    dropdown.classList.toggle('show');
    e.currentTarget.classList.toggle('active-dropdown');
  }
</script>

</body>
</html>
