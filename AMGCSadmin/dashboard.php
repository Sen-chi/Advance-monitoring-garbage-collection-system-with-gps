<?php
// Define the page title *before* including the header
$pageTitle = "Dashboard";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
require_once 'templates/footer.php';
?>
<html>
<!-- main content -->
<div class="content">
  <h2>Welcome to AMGCS Dashboard</h2>
  <div class="dashboard-cards">
    <div class="card">
      <h3>15</h3>
      <p>Total Trucks</p>
    </div>
    <div class="card">
      <h3>42</h3>
      <p>Scheduled Trips</p>
    </div>
    <div class="card">
      <h3>108,750 kg</h3>
      <p>Total Waste Collected</p>
    </div>
    <div class="card">
      <h3>8</h3>
      <p>Pending Requests</p>
    </div>
  </div>

  <div class="chart-section">
    <div class="chart-box">
      <h3>Waste Type Distribution</h3>
      <canvas id="wasteChart"></canvas>
    </div>
    <div class="chart-box">
      <h3>Collection Volume by Municipality</h3>
      <canvas id="barChart"></canvas>
    </div>
  </div>
</div>



  
<script>
function toggleDropdown(e) {
  const section = e.currentTarget;
  const dropdown = section.nextElementSibling;
  dropdown.classList.toggle('show');
  section.classList.toggle('active-dropdown');
}

document.getElementById('settings-button').addEventListener('click', function () {
  const menu = document.getElementById('settings-menu');
  menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
});

document.addEventListener('click', function (event) {
  const settingsBtn = document.getElementById('settings-button');
  const settingsMenu = document.getElementById('settings-menu');
  if (!settingsBtn.contains(event.target) && !settingsMenu.contains(event.target)) {
    settingsMenu.style.display = 'none';
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const wasteCtx = document.getElementById('wasteChart');
  if (wasteCtx) {
    new Chart(wasteCtx, {
      type: 'pie',
      data: {
        labels: ['Biodegradable', 'Non-Biodegradable', 'Recyclable'],
        datasets: [{
          data: [45, 30, 25],
          backgroundColor: ['green', 'red', 'blue']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  const barCtx = document.getElementById('barChart');
  if (barCtx) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Bayambang', 'San Carlos', 'Lingayen', 'Binalonan', 'Baguio'],
        datasets: [{
          label: 'Volume (kg)',
          data: [10000, 8500, 7000, 6300, 5900],
          backgroundColor: '#006400'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1000 }
          }
        }
      }
    });
  }
});
</script>
<script src="scripts/logout.js"></script>

</body>
</html>
