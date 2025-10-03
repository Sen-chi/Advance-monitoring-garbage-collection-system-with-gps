<?php
session_start();
$pageTitle = "Dashboard";

require_once 'db_connect.php'; 

if (!isset($pdo) || !$pdo) {
    die("Error: PDO connection failed. Check db_connect.php.");
}

// --- Fetch Total Trucks ---
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM truck_info");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalTrucks = $row ? (int)$row['count'] : 0;
} catch (PDOException $e) {
    error_log("Error fetching truck count: " . $e->getMessage());
    $totalTrucks = "Error";
}

// --- Fetch Total Scheduled Trips ---
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM schedules");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalScheduledTrips = $row ? (int)$row['count'] : 0;
} catch (PDOException $e) {
    error_log("Error fetching scheduled trips count: " . $e->getMessage());
    $totalScheduledTrips = "Error";
}

// --- Fetch Total Waste Collected ---
$totalWasteCollected = 0;
try {
    $stmt = $pdo->query("SELECT SUM(estimated_volume_per_truck_kg) AS total_volume FROM mucipalities_record");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalWasteCollected = $row['total_volume'] ?? 0;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// --- Fetch Total Drivers (from truck_driver table) ---
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM truck_driver");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalDrivers = $row ? (int)$row['count'] : 0;
} catch (PDOException $e) {
    error_log("Error fetching total drivers: " . $e->getMessage());
    $totalDrivers = "Error";
}

// --- Fetch Total Employees (from employee table) ---
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM employee");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalEmployees = $row ? (int)$row['count'] : 0;
} catch (PDOException $e) {
    error_log("Error fetching total employees: " . $e->getMessage());
    $totalEmployees = "Error";
}

// --- Fetch Waste Type Distribution from schedules ---
$wasteData = [];
$wasteLabels = [];
try {
    $stmt = $pdo->query("
        SELECT waste_type, COUNT(*) AS count 
        FROM schedules 
        GROUP BY waste_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $wasteLabels[] = $row['waste_type'];
        $wasteData[] = (int)$row['count'];
    }
} catch (PDOException $e) {
    error_log("Error fetching waste distribution: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="styles/home.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="content">
  <h2>Welcome to AMGCS Dashboard</h2>
  <div class="dashboard-cards">
    <div class="card">
      <h3><?= htmlspecialchars($totalTrucks) ?></h3>
      <p>Total Trucks</p>
    </div>
    <div class="card">
      <h3><?= htmlspecialchars($totalScheduledTrips) ?></h3>
      <p>Scheduled Trips</p>
    </div>
    <div class="card">
      <h3><?= htmlspecialchars(number_format($totalWasteCollected)) ?> kg</h3>
      <p>Total Waste Collected</p>
    </div>
    <div class="card">
      <h3>8</h3>
      <p>Pending Requests</p>
    </div>
    <div class="card">
      <h3><?= htmlspecialchars($totalDrivers) ?></h3>
      <p>Drivers</p>
    </div>
    <div class="card">
      <h3><?= htmlspecialchars($totalEmployees) ?></h3>
      <p>Employees</p>
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
const wasteLabels = <?= json_encode($wasteLabels) ?>;
const wasteData = <?= json_encode($wasteData) ?>;

document.addEventListener('DOMContentLoaded', () => {
  const wasteCtx = document.getElementById('wasteChart');
  if (wasteCtx) {
    new Chart(wasteCtx, {
      type: 'pie',
      data: {
        labels: wasteLabels,
        datasets: [{
          data: wasteData,
          backgroundColor: ['green', 'red', 'blue', 'orange', 'purple']
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
<?php require_once 'templates/footer.php'; ?>
</body>
</html>
