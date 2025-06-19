<?php
// We assume $currentPage, $isFileManagement, $isMunicipality
// have already been defined (e.g., in header.php)

// Example of how $isFileManagement and $isMunicipality might be determined:
/*
$isFileManagement = in_array($currentPage, ['garbage_type_add.php', 'truck_add.php', 'truck_list.php']);
$isMunicipality = in_array($currentPage, ['add_municipality.php', 'municipality_list.php']);
*/

?>
<div class="sidebar">
  <a href="dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-table-columns"></i> Dashboard</a>
  <a href="dashboard_tracking.php" class="sidebar-link <?= $currentPage === 'dashboard_tracking.php' ? 'active' : '' ?>"><i class="fas fa-truck"></i> Garbage Truck Tracking</a>
  <a href="dashboard_schedule.php" class="sidebar-link <?= $currentPage === 'dashboard_schedule.php' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
  <a href="reports_and_logs.php" class="sidebar-link <?= $currentPage === 'reports_and_logs.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reports</a>
  <a href="user_management.php" class="sidebar-link <?= $currentPage === 'user_management.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> User accounts Management</a>
   <a href="municipality_list.php" class="sidebar-link <?= $currentPage === 'municipality_list.php' ? 'active' : '' ?>"><i class="fas fa-city"></i>Municipalities Records</a>

  <!-- File Management Dropdown -->
  <!-- Changed <a> to <button> for better semantics -->
  <button class="sidebar-section <?= $isFileManagement ? 'active-dropdown' : '' ?>" type="button" onclick="toggleSidebarDropdown(event)">
    <span><i class="fas fa-database"></i> File Management</span>
    <!-- Chevron icon for dropdown indicator -->
    <i class="fas fa-chevron-down dropdown-arrow"></i>
  </button>
  <div class="sidebar-dropdown-content <?= $isFileManagement ? 'show' : '' ?>">
    <a href="driver.php" class="sidebar-link <?= $currentPage === 'driver.php' ? 'active' : '' ?>"><i class="fa-solid fa-user-group"></i> Truck Drivers</a>
    <a href="truck_list.php" class="sidebar-link <?= $currentPage === 'truck_list.php' ? 'active' : '' ?>"><i class="fas fa-truck-front"></i> Trucks</a>
    <a href="employees.php" class="sidebar-link <?= $currentPage === 'employees.php' ? 'active' : '' ?>"><i class="fa-solid fa-user-tie"></i> Employees</a>
  </div>

</div> <!-- End .sidebar -->

<script>
    function toggleSidebarDropdown(event) {
    // Get the clicked button/link
    const clickedElement = event.currentTarget; // Use currentTarget as the event listener is on the button/link

    // Find the next sibling element that is the dropdown content
    const dropdownContent = clickedElement.nextElementSibling;

    // Toggle the 'show' class on the dropdown content
    if (dropdownContent && dropdownContent.classList.contains('sidebar-dropdown-content')) {
        dropdownContent.classList.toggle('show');
        // Toggle the 'active-dropdown' class on the button/link itself
        clickedElement.classList.toggle('active-dropdown');

        // Optional: Rotate the arrow icon
        const arrowIcon = clickedElement.querySelector('.dropdown-arrow');
        if (arrowIcon) {
             arrowIcon.classList.toggle('rotated'); // Add a 'rotated' class
        }
    }
    // Prevent default action for any potential href="#" (though not applicable with <button type="button">)
    // event.preventDefault(); // Not needed with <button type="button">
}

// Optional: Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    // Check if the click is outside any .sidebar-section or .sidebar-dropdown-content
    const isClickInsideSidebar = event.target.closest('.sidebar');

    if (!isClickInsideSidebar) {
        // If clicking outside, close all open dropdowns
        document.querySelectorAll('.sidebar-dropdown-content.show').forEach(function(content) {
            content.classList.remove('show');
            // Also remove active class and reset arrow on the corresponding button
            const button = content.previousElementSibling;
            if (button && button.classList.contains('sidebar-section')) {
                 button.classList.remove('active-dropdown');
                 const arrowIcon = button.querySelector('.dropdown-arrow');
                 if (arrowIcon) {
                      arrowIcon.classList.remove('rotated');
                 }
            }
        });
    }
});
</script>