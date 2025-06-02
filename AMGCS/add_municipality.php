<?php
// Define the page title *before* including the header
$pageTitle = "Dashboard";

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
<!-- CONTENT -->
<div class="content">
  <h2>Add Municipality</h2>
  <div class="box-container" style="max-width:600px;">
    <form action="process_add_municipality.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">

      <div>
        <label for="date">Date:</label><br>
        <input type="date" id="date" name="date" required style="width: 100%;">
      </div>

      <div>
        <label for="time">Time:</label><br>
        <input type="time" id="time" name="time" required style="width: 100%;">
      </div>

      <div>
        <label for="municipality_type">Type of Municipality:</label><br>
        <select id="municipality_type" name="municipality_type" required style="width: 100%;">
          <option value="">-- Select Type --</option>
          <option value="Public (LGU)">Public (LGU)</option>
          <option value="Private Company">Private Company</option>
        </select>
      </div>

      <div>
        <label for="lgu_municipality">LGU Municipality:</label><br>
        <select id="lgu_municipality" name="lgu_municipality" required style="width: 100%;">
          <option value="">-- Select LGU Municipality --</option>
          <option value="Baguio City">Baguio City</option>
          <option value="Bayamabang">Bayambang</option>
          <option value="Urdaneta City">Urdaneta City</option>
          <option value="San Carlos City">San Carlos City</option>
          <!-- Add more LGUs as needed -->
        </select>
      </div>

      <div>
        <label for="truck_count">Plate Number:</label><br>
        <input type="number" id="truck_count" name="truck_count" min="1" required style="width: 100%;">
      </div>

      <div>
        <label for="estimated_volume">Estimated Volume per Truck (in kg):</label><br>
        <input type="number" id="estimated_volume" name="estimated_volume" min="0" step="0.1" required style="width: 100%;">
      </div>

      <div>
        <label for="remarks">Remarks:</label><br>
        <textarea id="remarks" name="remarks" rows="3" style="width: 100%; resize: none;"></textarea>
      </div>

      <div style="display: flex; gap: 10px;">
        <button type="submit" class="btn-save">Save Schedule</button>
        <a href="municipality_list.php" class="btn-cancel">Cancel</a>
      </div>
    </form>
  </div>
</div>



<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to logout?</p>
    <button class="yes-btn" onclick="logout()">Yes</button>
    <button class="cancel-btn" onclick="closeModal()">Cancel</button>
  </div>
</div>

<!-- JS -->
<script>
  function toggleDropdown(e) {
    const dropdown = e.currentTarget.nextElementSibling;
    dropdown.classList.toggle('show');
    e.currentTarget.classList.toggle('active-dropdown');
  }

  function toggleMunicipalityDropdown() {
    const type = document.getElementById("name").value; // Now reading from the name dropdown
    const lguMunicipality = document.getElementById("lguMunicipality");
    const privateMunicipality = document.getElementById("privateMunicipality");

    // Hide both dropdowns initially
    lguMunicipality.style.display = "none";
    privateMunicipality.style.display = "none";

    // Show the correct dropdown based on type
    if (type === "LGU") {
      lguMunicipality.style.display = "block";
    } else if (type === "Private") {
      privateMunicipality.style.display = "block";
    }
  }

  const notifButton = document.getElementById("notif-button");
  const notifMenu = document.getElementById("notif-menu");
  const settingsButton = document.getElementById("settings-button");
  const settingsMenu = document.getElementById("settings-menu");

  function openLogoutModal() {
    document.getElementById("logoutModal").style.display = "flex";
  }

  function closeModal() {
    document.getElementById("logoutModal").style.display = "none";
  }

  function logout() {
    window.location.href = "sign_in.php";
  }

  notifButton?.addEventListener("click", function (e) {
    e.stopPropagation();
    notifMenu.classList.toggle("show");
    settingsMenu.classList.remove("show");
  });

  settingsButton?.addEventListener("click", function (e) {
    e.stopPropagation();
    settingsMenu.classList.toggle("show");
    notifMenu.classList.remove("show");
  });

  window.addEventListener("click", function (event) {
    if (!notifButton.contains(event.target)) notifMenu.classList.remove("show");
    if (!settingsButton.contains(event.target)) settingsMenu.classList.remove("show");
  });

  window.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      notifMenu.classList.remove("show");
      settingsMenu.classList.remove("show");
    }
  });
</script>
</body>
</html>