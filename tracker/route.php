<?php
session_start(); 

$pageTitle = "Route Management";

require_once 'templates/header.php'; 
require_once 'templates/sidebar.php';

$pdo = null; 
$dbError = '';
try {
    $pdo = require_once("db_connect.php");
} catch (Exception $e) {
    $dbError = "Database Connection Error: " . $e->getMessage();
}

require_once("routemodel.php"); 

$routeData = [];
$fetchError = '';

if (empty($dbError) && $pdo) {
    try {
        $routeData = getAllRoutes($pdo);
    } catch (Exception $e) {
         $fetchError = 'Could not retrieve route data: ' . $e->getMessage();
    }
} else {
    $fetchError = $dbError;
}
?>

<html>
<head>
    <link rel="stylesheet" href="css/driver.css">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- CSS for the interactive map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- CSS for the map search box -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    
    <style>
        .dataTables_filter input[type="search"] {
            border-radius: 25px !important;
            border: 1px solid #ccc !important;
            padding: 8px 12px !important;
            outline: none !important;
        }
        .dataTables_length, .dataTables_filter {
            margin-bottom: 20px !important;
        }
        #map {
            height: 350px;
            width: 100%;
            margin-top: 15px;
            border: 1px solid #ccc;
            z-index: 1000;
        }

        #routeTable .action-buttons button { /* You can also add #assistantTable for consistency */
            margin: 0 3px;
            padding: 5px 8px;
            /* ... other styles */
            display: inline-flex; 
            /* ... other styles */
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Route Management</h2>
        <hr>

        <button class="add-user-btn" onclick="openAddRouteModal()">
            <i class="fa-solid fa-plus"></i> Add New Route
        </button>

        <?php if (!empty($fetchError)): ?>
            <div class='message error'><?php echo htmlspecialchars($fetchError); ?></div>
        <?php endif; ?>

        <table id="routeTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Dest. Coordinates</th>
                    <th>Waypoints / Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (is_array($routeData) && !empty($routeData)) {
                    foreach ($routeData as $route) {
                        $origin = htmlspecialchars($route['origin'] ?? '');
                        $destination = htmlspecialchars($route['destination'] ?? '');
                        $waypoints = htmlspecialchars($route['waypoints'] ?? 'N/A');
                        
                        $lat = htmlspecialchars($route['destination_lat'] ?? '');
                        $lon = htmlspecialchars($route['destination_lon'] ?? '');
                        $coords = (!empty($lat) && !empty($lon) && floatval($lat) != 0) ? "{$lat}, {$lon}" : 'Not Set';

                        $route_json = htmlspecialchars(json_encode($route), ENT_QUOTES, 'UTF-8');
                        $route_name_json = htmlspecialchars(json_encode($origin . " to " . $destination), ENT_QUOTES, 'UTF-8');

                        echo "<tr>";
                        echo "<td>" . $origin . "</td>";
                        echo "<td>" . $destination . "</td>";
                        echo "<td>" . $coords . "</td>";
                        echo "<td>" . $waypoints . "</td>";
                        echo "<td class='action-buttons'>
                                <button class='edit-btn' onclick='openEditRouteModal({$route_json})'><i class='fas fa-edit'></i></button>
                                <button class='delete-btn' onclick='openDeleteConfirmRouteModal(".(int)$route['route_id'].", {$route_name_json})'><i class='fas fa-trash'></i></button>
                              </td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Route Modal -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
            <h3 id="modalTitle">Add/Edit Route</h3>
            <form id="addEditForm" novalidate>
                <input type="hidden" id="routeId" name="id">
                <input type="hidden" id="actionType" name="action">
                <div class="form-group"><label for="origin">Origin Name:</label><input type="text" id="origin" name="origin" required><span class="error-message" id="originError"></span></div>
                <div class="form-group"><label for="destination">Destination Name:</label><input type="text" id="destination" name="destination" required><span class="error-message" id="destinationError"></span></div>
                
                <div class="form-group">
                    <label>Set Destination on Map (Search or click/drag pin):</label>
                    <div id="map"></div>
                    <span class="error-message" id="destination_latError"></span>
                </div>
                <input type="hidden" id="destination_lat" name="destination_lat">
                <input type="hidden" id="destination_lon" name="destination_lon">
                
                <div class="form-group"><label for="waypoints">Waypoints (comma-separated):</label><textarea id="waypoints" name="waypoints" rows="3"></textarea><span class="error-message" id="waypointsError"></span></div>

                <div class="modal-buttons"><button type="button" class="save-btn" onclick="submitRouteForm()">Save</button><button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
            <h3>Confirm Deletion</h3>
            <p id="deleteConfirmText">Are you sure you want to delete this route?</p>
            <input type="hidden" id="deleteRouteId"><button class="confirm-btn" onclick="confirmDeleteRoute()">Yes, Delete</button><button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
        </div>
    </div>

<!-- JavaScript libraries for the map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
    let map;
    let destinationMarker;
    const defaultCoords = [15.821998, 120.456722]; // Bayambang
    // --- THIS IS THE ONLY LINE THAT WAS CHANGED ---
    const searchBounds = L.latLngBounds(L.latLng(15.78, 120.40), L.latLng(16.00, 120.60)); // Tightly covers Bayambang and Urdaneta

    function initializeMap(initialCoords) {
        if (!map) { // Only create the map and its controls ONCE
            map = L.map('map').setView(initialCoords, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            
            const geocoder = L.Control.Geocoder.nominatim({
                geocodingQueryParams: {
                    viewbox: searchBounds.toBBoxString(),
                    bounded: 1
                }
            });

            new L.Control.Geocoder({
                geocoder: geocoder,
                placeholder: 'Search in Bayambang/Urdaneta...',
                defaultMarkGeocode: false,
                collapsed: false,
                suggestMinLength: 3, 
                suggestTimeout: 500
            }).on('markgeocode', function(e) {
                const latlng = e.geocode.center;
                map.setView(latlng, 17);
                destinationMarker.setLatLng(latlng);
                updateCoordinateInputs(latlng);
            }).addTo(map);

            destinationMarker = L.marker(initialCoords, { draggable: true }).addTo(map);

            map.on('click', e => {
                destinationMarker.setLatLng(e.latlng);
                updateCoordinateInputs(e.latlng);
            });
            destinationMarker.on('dragend', e => {
                updateCoordinateInputs(e.target.getLatLng());
            });
        }
        
        map.setView(initialCoords, 13);
        destinationMarker.setLatLng(initialCoords);
    }

    function updateCoordinateInputs(latlng) {
        $('#destination_lat').val(latlng.lat.toFixed(8));
        $('#destination_lon').val(latlng.lng.toFixed(8));
    }

    $(document).ready(function() {
        $('#routeTable').DataTable({
            "order": [[ 0, "asc" ]],
            "columnDefs": [ { "orderable": false, "targets": 4 } ],
            "language": { "emptyTable": "No routes found." }
        });
    });

    function clearFormErrors(formId) {
         $('#' + formId + ' .error-message').text('').hide();
         $('#' + formId + ' input, #' + formId + ' textarea').removeClass('error');
    }

    function openAddRouteModal() {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Add New Route');
        $('#actionType').val('add');
        $('#routeId').val('');
        openModal('addEditModal');
        $('#origin').focus();
        
        setTimeout(() => {
            initializeMap(defaultCoords);
            updateCoordinateInputs(L.latLng(defaultCoords));
            map.invalidateSize();
        }, 150);
    }
    
    function openEditRouteModal(routeData) {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Edit Route');
        $('#actionType').val('edit');
        $('#routeId').val(routeData.route_id);
        $('#origin').val(routeData.origin);
        $('#destination').val(routeData.destination);
        $('#waypoints').val(routeData.waypoints);
        $('#destination_lat').val(routeData.destination_lat);
        $('#destination_lon').val(routeData.destination_lon);
        
        openModal('addEditModal');
        
        setTimeout(() => {
            const hasValidCoords = routeData.destination_lat && parseFloat(routeData.destination_lat) != 0;
            const initialCoords = hasValidCoords ? [routeData.destination_lat, routeData.destination_lon] : defaultCoords;
            initializeMap(initialCoords);
            map.invalidateSize(); 
        }, 150);
    }

    function openDeleteConfirmRouteModal(id, routeName) {
        $('#deleteRouteId').val(id);
        $('#deleteConfirmText').text('Are you sure you want to delete route: ' + routeName + '?');
        openModal('deleteConfirmModal');
    }

    function confirmDeleteRoute() {
        const id = $('#deleteRouteId').val();
        if (!id) return;
        $.ajax({
            url: 'handle_route_actions.php', type: 'POST', data: { action: 'delete', id: id }, dataType: 'json',
            success: function(response) {
                alert(response.message || 'Operation failed.');
                if (response.success) location.reload();
            },
            error: (xhr) => alert('An AJAX error occurred: ' + xhr.responseText),
            complete: () => closeModal('deleteConfirmModal')
        });
    }
    
    function submitRouteForm() {
        clearFormErrors('addEditForm');
        let isValid = true;
        if (!$('#origin').val().trim()) { isValid = false; $('#originError').text('Origin is required.').show(); }
        if (!$('#destination').val().trim()) { isValid = false; $('#destinationError').text('Destination is required.').show(); }
        
        if (!$('#destination_lat').val().trim()) { isValid = false; $('#destination_latError').text('Please select a destination on the map.').show(); }
        
        if (!isValid) return;
        
        $('.save-btn').prop('disabled', true).text('Saving...');
        $.ajax({
            url: 'handle_route_actions.php', type: 'POST', data: $('#addEditForm').serialize(), dataType: 'json',
            success: function(response) {
                alert(response.message || 'Operation failed.');
                if (response.success) {
                    location.reload();
                } else if (response.errors) {
                    $.each(response.errors, (key, msg) => $('#' + key + 'Error').text(msg).show());
                }
            },
            error: (xhr) => alert('An AJAX error occurred: ' + xhr.responseText),
            complete: () => $('.save-btn').prop('disabled', false).text('Save')
        });
    }
</script>
<?php
require_once 'templates/footer.php';
if ($pdo) { $pdo = null; }
?>
</body>
</html>