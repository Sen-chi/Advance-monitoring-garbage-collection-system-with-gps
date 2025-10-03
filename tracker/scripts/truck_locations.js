const map = L.map('map').setView([14.5995, 120.9842], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

let truckMarkers = {};
const trucksTableBody = document.querySelector('#trucksTable tbody');


const apiUrl = 'http://192.168.100.39/tracker/api/locations.php';

async function fetchTruckLocations() {
    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const locations = await response.json();

        trucksTableBody.innerHTML = '';
        const activeMarkers = {};

        if (locations && locations.length > 0) {
            let bounds = [];
            locations.forEach(truck => {
                const lat = parseFloat(truck.latitude);
                const lng = parseFloat(truck.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                const deviceId = truck.deviceId;
                const timestamp = new Date(truck.timestamp);
                const plateNumber = truck.plate_number;
                const driverName = truck.driver_name;
                const capacity = truck.capacity;
                const locationName = truck.locationName;

                let marker;
                if (truckMarkers[deviceId]) {
                    marker = truckMarkers[deviceId];
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng]).addTo(map);
                    truckMarkers[deviceId] = marker;
                }

                marker.bindPopup(`
                    <b>Truck #${deviceId} (${plateNumber})</b><br>
                    Driver: ${driverName}<br>
                    Location: ${locationName}<br>
                    Last Update: ${timestamp.toLocaleString()}
                `);
                activeMarkers[deviceId] = marker;
                bounds.push([lat, lng]);

                const row = trucksTableBody.insertRow();
                const now = new Date();
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
                    <td>${plateNumber}</td>
                    <td>${driverName}</td>
                    <td>${capacity}</td>
                    <td><span class="status-tag ${statusClass}">${statusText}</span></td>
                    <td>${timestamp.toLocaleString()}</td>
                `;
            });

            for (const deviceId in truckMarkers) {
                if (!activeMarkers[deviceId]) {
                    map.removeLayer(truckMarkers[deviceId]);
                    delete truckMarkers[deviceId];
                }
            }

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        } else {
            for (const deviceId in truckMarkers) {
                map.removeLayer(truckMarkers[deviceId]);
            }
            truckMarkers = {};
            trucksTableBody.innerHTML = `<tr><td colspan="6">No active trucks found. Waiting for a driver's app to send data...</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching truck locations:', error);
        trucksTableBody.innerHTML = `<tr><td colspan="6">Error loading data. Could not connect to the API. Is the server running at ${apiUrl}?</td></tr>`;
    }
}

setInterval(fetchTruckLocations, 5000);
fetchTruckLocations();