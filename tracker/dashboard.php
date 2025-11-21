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

// --- Fetch Total Waste Collected (from municipalities_record) ---
$totalWasteCollected = 0;
try {
    $stmt = $pdo->query("SELECT SUM(estimated_volume_per_truck_kg) AS total_volume FROM municipalities_record");
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

// --- [UPDATED] Fetch Collection Volume by Collector (LGU or Private) for Bar Chart ---
$collectorLabels = [];
$collectorData = [];
$collectorColors = []; // <-- NEW: Array to hold colors for each bar
try {
    // This query now includes a CASE statement to determine the collector_type
    $stmt = $pdo->query("
        SELECT 
            COALESCE(lgu_municipality, private_company) AS collector_name, 
            SUM(estimated_volume_per_truck_kg) AS total_volume,
            CASE 
                WHEN lgu_municipality IS NOT NULL AND lgu_municipality != '' THEN 'LGU'
                ELSE 'Private'
            END AS collector_type
        FROM municipalities_record 
        WHERE COALESCE(lgu_municipality, private_company) IS NOT NULL 
          AND COALESCE(lgu_municipality, private_company) != ''
        GROUP BY collector_name, collector_type
        ORDER BY total_volume DESC 
        LIMIT 5
    ");
    
    // Define the colors
    $lguColor = '#006400'; // Dark Green
    $privateColor = '#4682B4'; // Steel Blue

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $collectorLabels[] = $row['collector_name'];
        $collectorData[] = (float)$row['total_volume'];
        
        // Assign color based on the collector type
        if ($row['collector_type'] === 'Private') {
            $collectorColors[] = $privateColor;
        } else {
            $collectorColors[] = $lguColor;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching collection volume by collector: " . $e->getMessage());
    $collectorLabels = [];
    $collectorData = [];
    $collectorColors = [];
}


require_once 'templates/header.php';
require_once 'templates/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="content">
  <h2>Welcome to AMGCS Dashboard</h2>
  <div class="dashboard-cards">

    <a href="truck_list.php" class="card-link">
        <div class="card">
          <h3><?= htmlspecialchars($totalTrucks) ?></h3>
          <p>Total Trucks</p>
        </div>
    </a>

      <a href="dashboard_schedule.php" class="card-link">
        <div class="card">
          <h3><?= htmlspecialchars($totalScheduledTrips) ?></h3>
          <p>Scheduled Trips</p>
        </div>
      </a>

    <div class="card">
      <h3><?= htmlspecialchars(number_format($totalWasteCollected)) ?> kg</h3>
      <p>Total Waste Collected</p>
    </div>
      <a href="driver.php" class="card-link">
        <div class="card">
          <h3><?= htmlspecialchars($totalDrivers) ?></h3>
          <p>Drivers</p>
        </div>
      </a>

      <a href="employees.php" class="card-link">
        <div class="card">
          <h3><?= htmlspecialchars($totalEmployees) ?></h3>
          <p>Employees</p>
        </div>
      </a>

  </div>

  <div class="chart-section">
    <div class="chart-box">
      <h3>Waste Type Distribution</h3>
      <canvas id="wasteChart"></canvas>
    </div>
    <div class="chart-box">
      <h3>Collection Volume by Collector</h3>
      <canvas id="barChart"></canvas>
    </div>
  </div>
</div>

<script>
// Data for Pie Chart
const wasteLabels = <?= json_encode($wasteLabels) ?>;
const wasteData = <?= json_encode($wasteData) ?>;

// [UPDATED] Data for Bar Chart from PHP
const collectorLabels = <?= json_encode($collectorLabels) ?>;
const collectorData = <?= json_encode($collectorData) ?>;
const collectorColors = <?= json_encode($collectorColors) ?>; // <-- NEW: Color data from PHP

document.addEventListener('DOMContentLoaded', () => {
  // Pie Chart for Waste Type Distribution
  const wasteCtx = document.getElementById('wasteChart');
  if (wasteCtx) {
    new Chart(wasteCtx, {
      type: 'pie',
      data: {
        labels: wasteLabels,
        datasets: [{
          data: wasteData,
          backgroundColor: [
            'rgba(33, 173, 33, 0.71)', 
            'rgba(245, 55, 84, 0.88)', 
            'rgba(59, 195, 241, 0.86)', 
            'rgba(245, 144, 55, 0.88)', 
            'rgba(245, 59, 245, 0.83)'  
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // Bar Chart for Collection Volume by Collector
  const barCtx = document.getElementById('barChart');
  if (barCtx) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: collectorLabels,
        datasets: [{
          label: 'Volume (kg)',
          data: collectorData,
          // [UPDATED] Use the dynamic color array here
          backgroundColor: collectorColors 
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { 
                callback: function(value) {
                    return value.toLocaleString(); // Adds commas for thousands
                }
            }
          }
        }
      }
    });
  }
});
</script>

<?php require_once 'templates/footer.php'; ?>