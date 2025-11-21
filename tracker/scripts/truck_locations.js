// --- Map Initialization ---
const map = L.map('map').setView([14.5995, 120.9842], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// --- Global Variables ---
let truckMarkers = {}; // Stores the marker layers for each truck
const trucksTableBody = document.querySelector('#trucksTable tbody');
const apiUrl = 'http://192.168.100.39/tracker/api/locations.php';

// --- NEW: Variables to store trail data and layers ---
let truckTrails = {}; // This will store the Leaflet Polyline layers.
let trailData = {};   // This will store the coordinate history for each truck.

async function fetchTruckLocations() {
    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const locations = await response.json();

        trucksTableBody.innerHTML = ''; // Clear the table for fresh data
        const activeMarkers = {};       // Keep track of trucks in the current API response

        if (locations && locations.length > 0) {
            let bounds = []; // Used to auto-zoom the map

            locations.forEach(truck => {
                const lat = parseFloat(truck.latitude);
                const lng = parseFloat(truck.longitude);
                if (isNaN(lat) || isNaN(lng)) return; // Skip if coordinates are invalid

                const deviceId = truck.deviceId;
                const latLng = [lat, lng]; // Store coordinates in an array

                // --- 1. UPDATE MARKER ---
                let marker;
                if (truckMarkers[deviceId]) {
                    marker = truckMarkers[deviceId];
                    marker.setLatLng(latLng);
                } else {
                    marker = L.marker(latLng).addTo(map);
                    truckMarkers[deviceId] = marker;
                }

                marker.bindPopup(`
                    <b>Truck #${deviceId} (${truck.plate_number})</b><br>
                    Driver: ${truck.driver_name}<br>
                    Location: ${truck.locationName}<br>
                    Last Update: ${new Date(truck.timestamp).toLocaleString()}
                `);
                
                activeMarkers[deviceId] = marker; // Mark this truck as active
                bounds.push(latLng);

                // --- 2. NEW: UPDATE TRAIL ---
                // Initialize trail data if it's the first time we see this truck
                if (!trailData[deviceId]) {
                    trailData[deviceId] = [];
                }

                // Add the new point to the trail, but only if it's different from the last one
                const lastPoint = trailData[deviceId][trailData[deviceId].length - 1];
                if (!lastPoint || lastPoint[0] !== lat || lastPoint[1] !== lng) {
                    trailData[deviceId].push(latLng);
                }

                // Update or create the trail polyline on the map
                if (truckTrails[deviceId]) {
                    // If the trail layer exists, update its path
                    truckTrails[deviceId].setLatLngs(trailData[deviceId]);
                } else {
                    // Otherwise, create a new trail layer and add it to the map
                    truckTrails[deviceId] = L.polyline(trailData[deviceId], {
                        color: 'purple', // Trail color
                        weight: 4        // Trail thickness
                    }).addTo(map);
                }

                // --- 3. UPDATE TABLE (Your existing logic) ---
                const row = trucksTableBody.insertRow();
                const now = new Date();
                const timestamp = new Date(truck.timestamp);
                const minutesSinceLastUpdate = (now.getTime() - timestamp.getTime()) / (1000 * 60);
                
                let statusClass = 'status-active';
                let statusText = 'On Route';
                if (minutesSinceLastUpdate > 5) {
                    statusClass = 'status-idle';
                    statusText = 'Idle';
                }
                if (minutesSinceLastUpdate > 30) {
                    statusClass = 'status-maintenance';
                    statusText = 'Offline';
                }

                row.innerHTML = `
                    <td>${deviceId}</td>
                    <td>${truck.plate_number}</td>
                    <td>${truck.driver_name}</td>
                    <td>${truck.capacity}</td>
                    <td><span class="status-tag ${statusClass}">${statusText}</span></td>
                    <td>${timestamp.toLocaleString()}</td>
                `;
            });

            // --- 4. CLEAN UP INACTIVE TRUCKS ---
            // Remove markers and trails for trucks that are no longer in the API response
            for (const deviceId in truckMarkers) {
                if (!activeMarkers[deviceId]) {
                    map.removeLayer(truckMarkers[deviceId]);
                    delete truckMarkers[deviceId];
                    
                    // NEW: Also remove the trail from the map and clear its data
                    if (truckTrails[deviceId]) {
                        map.removeLayer(truckTrails[deviceId]);
                        delete truckTrails[deviceId];
                        delete trailData[deviceId];
                    }
                }
            }

            // Auto-zoom map to fit all active markers
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }

        } else {
            // If no trucks are found, clear everything from the map
            for (const deviceId in truckMarkers) {
                map.removeLayer(truckMarkers[deviceId]);
                if (truckTrails[deviceId]) {
                    map.removeLayer(truckTrails[deviceId]);
                }
            }
            truckMarkers = {};
            truckTrails = {};
            trailData = {};
            trucksTableBody.innerHTML = `<tr><td colspan="6">No active trucks found. Waiting for data...</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching truck locations:', error);
        trucksTableBody.innerHTML = `<tr><td colspan="6">Error loading data. Could not connect to the API.</td></tr>`;
    }
}

// --- Start the process ---
// Fetch data every 5 seconds
setInterval(fetchTruckLocations, 5000);
// Fetch immediately on page load
fetchTruckLocations();