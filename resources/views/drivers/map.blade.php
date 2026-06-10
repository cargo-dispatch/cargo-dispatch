@extends('layouts.app')

@push('styles')
<link href="{{ asset('assets/css/drivers.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="map-section">
    <div class="map-sidebar">
        <div class="map-header sidebar-wrapper">
            <h5>Fleet Live Locations</h5>
            <h6>No of Units <span id="count" class="badge bg-primary">0</span></h6>
            <div id="lastUpdate" class="last-update">Loading data...</div>
            <div class="map-tools mt-2">
                <input type="text" class="form-control" id="searchBox" placeholder="Search driver...">
            </div>
        </div>
        <div class="driver-list sidebar-wrapper" id="driverList">
            <div class="text-center py-3">
                <span class="loading-spinner"></span> Loading drivers...
            </div>
        </div>
        <div class="sidebar-wrapper p-2">
            <div class="hos-section-title">Driver HOS Snapshot</div>
            <div id="hosList" class="hos-list">
                <div class="text-muted font-size-12">Loading HOS data...</div>
            </div>
        </div>
    </div>

    <div class="map-body m-2">
        <div id="map"></div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAZjPWGo5dmvczpw2tAw-to8vyr_IBlSfw"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// Global variables
let map;
let markers = {};
let vehicleMarkers = {};
let infoWindow;
let driversData = [];
let hosDrivers = [];
let pollingInterval;
let isFirstLoad = true;
const POLLING_INTERVAL = 10000; // 10 seconds
const LOCATIONS_URL = '{{ url("api/drivers/locations") }}';
const HOS_URL       = '{{ url("api/mock/eld-snapshot") }}';

// Initialize the map
function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 6,
        center: { lat: 37.0902, lng: -95.7129 },
        mapTypeControl: false,
        streetViewControl: false
    });

    infoWindow = new google.maps.InfoWindow();

    // Initial fetch
    fetchDrivers();

    // Start polling
    startPolling();
}

// Start the polling interval
function startPolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(fetchDrivers, POLLING_INTERVAL);
}

// Determine online/offline based on how recent the last update is (2-minute window)
function deriveStatus(lastUpdate) {
    if (!lastUpdate) return 'offline';
    const ageMs = Date.now() - new Date(lastUpdate).getTime();
    return ageMs < 2 * 60 * 1000 ? 'online' : 'offline';
}

// Fetch real driver GPS locations + HOS snapshot in parallel
function fetchDrivers() {
    if (isFirstLoad) {
        document.getElementById('driverList').innerHTML =
            '<div class="text-center py-3"><span class="loading-spinner"></span> Loading drivers...</div>';
    }

    const locFetch = fetch(LOCATIONS_URL + '?_=' + Date.now())
        .then(r => { if (!r.ok) throw new Error('locations ' + r.status); return r.json(); });

    const hosFetch = fetch(HOS_URL + '?_=' + Date.now())
        .then(r => r.ok ? r.json() : { drivers: [] })
        .catch(() => ({ drivers: [] }));   // HOS is optional — never block map

    Promise.all([locFetch, hosFetch])
        .then(([locations, hosData]) => {
            // locations is an array of real driver rows from the DB
            const normalized = locations.map(driver => ({
                id: driver.id,
                firstname: driver.firstname || 'Driver',
                lastname: driver.lastname || '',
                phoneno: driver.phoneno || 'N/A',
                emergencycontactno: driver.emergencycontactno || 'N/A',
                latitude: driver.latitude,
                longitude: driver.longitude,
                last_location_update: driver.last_location_update,
                status: deriveStatus(driver.last_location_update),
                photo_url: driver.photo_url || null,
            }));

            // HOS sidebar — keyed by driver name from ELD snapshot
            hosDrivers = (hosData.drivers || []).map(d => ({
                id: d.driver_id,
                name: d.name,
                status: d.current_status || d.current_duty_status || 'off_duty',
                hos: d.hos || {
                    drive_remaining_minutes: d.hos_drive_remaining_minutes || 0,
                    on_duty_remaining_minutes: d.hos_on_duty_remaining_minutes || 0,
                    cycle_remaining_minutes: d.hos_cycle_remaining_minutes || 0,
                },
            }));

            // Update moving vehicle markers from ELD snapshot trucks
            updateVehicleMarkers(hosData.trucks || []);

            processDriverData(normalized);
            updateUI();
            if (isFirstLoad) isFirstLoad = false;
        })
        .catch(error => {
            console.error('Error fetching driver data:', error);
            showError('Failed to load data. Retrying...');
            setTimeout(fetchDrivers, getBackoffDelay());
        });
}

// Process incoming driver data
function processDriverData(newDrivers) {
    if (!newDrivers || newDrivers.length === 0) return;
    
    newDrivers.forEach(newDriver => {
        const existingIndex = driversData.findIndex(d => d.id === newDriver.id);
        
        if (existingIndex !== -1) {
            // Update existing driver data
            driversData[existingIndex] = newDriver;
        } else {
            // Add new driver
            driversData.push(newDriver);
        }
    });
    
    // Sort by last update time (newest first)
    driversData.sort((a, b) => 
        new Date(b.last_location_update) - new Date(a.last_location_update));
}

// Update all UI elements
function updateUI() {
    updateCount();
    updateMap();
    updateDriverList();
    updateLastUpdateTime();
    updateHosList();
}

// Update driver count display
function updateCount() {
    document.getElementById('count').innerText = driversData.length;
}

// Update the map with current driver data
function updateMap() {
    // First, remove markers for drivers that are no longer in the data
    Object.keys(markers).forEach(id => {
        if (!driversData.some(d => d.id == id)) {
            markers[id].setMap(null);
            delete markers[id];
        }
    });

    driversData.forEach(driver => {
        const id = driver.id;
        const pos = { 
            lat: parseFloat(driver.latitude), 
            lng: parseFloat(driver.longitude) 
        };
        const fullName = `${driver.firstname} ${driver.lastname}`;
        const infoContent = createInfoWindowContent(driver);

        if (markers[id]) {
            // Update existing marker position smoothly instead of teleporting
            const last = markerLastGPS[String(id)];
            if (!last || last.lat !== pos.lat || last.lng !== pos.lng) {
                const heading = last ? calcBearing(last.lat, last.lng, pos.lat, pos.lng) : 0;
                const fromLat = last ? last.lat : pos.lat;
                const fromLng = last ? last.lng : pos.lng;
                markerLastGPS[String(id)] = { lat: pos.lat, lng: pos.lng };
                markerPrevPos[String(id)] = { lat: pos.lat, lng: pos.lng };
                startContinuousMove(String(id), markers[id], fromLat, fromLng, pos.lat, pos.lng, heading, 'online');
            }

            // Update the info window content if it's currently open
            if (infoWindow.getAnchor() === markers[id]) {
                infoWindow.setContent(infoContent);
            }
        } else {
            // Create new marker — use photo if available, otherwise truck SVG
            const createMarker = (icon) => {
                markers[id] = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: fullName,
                    icon: icon
                });
                markers[id].addListener('click', () => {
                    infoWindow.setContent(infoContent);
                    infoWindow.open(map, markers[id]);
                });
            };

            if (driver.photo_url) {
                buildPhotoIcon(driver.photo_url, driver.firstname, driver.lastname, driver.status).then(createMarker);
            } else {
                buildInitialsIcon(driver.firstname, driver.lastname, driver.status).then(createMarker);
            }
        }
    });
}

// Calculate bearing in degrees between two lat/lng points
function calcBearing(lat1, lng1, lat2, lng2) {
    const toRad = d => d * Math.PI / 180;
    const toDeg = r => r * 180 / Math.PI;
    const dLng = toRad(lng2 - lng1);
    const rlat1 = toRad(lat1), rlat2 = toRad(lat2);
    const y = Math.sin(dLng) * Math.cos(rlat2);
    const x = Math.cos(rlat1) * Math.sin(rlat2) - Math.sin(rlat1) * Math.cos(rlat2) * Math.cos(dLng);
    return (toDeg(Math.atan2(y, x)) + 360) % 360;
}

// Truck SVG icon — green when online, red when offline, rotated by heading
function getMarkerIcon(status, headingDeg) {
    const color  = status === 'online' ? '#22c55e' : '#ef4444';
    const border = status === 'online' ? '#15803d' : '#b91c1c';
    const rotation = (((headingDeg || 0) - 90) + 360) % 360;
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="40" viewBox="0 0 48 40"
             style="transform:rotate(${rotation}deg);transform-origin:center center;">
            <rect x="2" y="10" width="28" height="18" rx="3" fill="${color}" stroke="${border}" stroke-width="1.5"/>
            <rect x="30" y="14" width="15" height="14" rx="3" fill="${color}" stroke="${border}" stroke-width="1.5"/>
            <rect x="32" y="16" width="9" height="7" rx="1.5" fill="white" opacity="0.85"/>
            <rect x="42" y="9" width="2.5" height="7" rx="1" fill="${border}"/>
            <circle cx="10" cy="29" r="4.5" fill="#1e293b" stroke="${border}" stroke-width="1.2"/>
            <circle cx="10" cy="29" r="2" fill="#64748b"/>
            <circle cx="23" cy="29" r="4.5" fill="#1e293b" stroke="${border}" stroke-width="1.2"/>
            <circle cx="23" cy="29" r="2" fill="#64748b"/>
            <circle cx="37" cy="29" r="4" fill="#1e293b" stroke="${border}" stroke-width="1.2"/>
            <circle cx="37" cy="29" r="1.8" fill="#64748b"/>
            <circle cx="44" cy="23" r="2" fill="#fde68a" stroke="${border}" stroke-width="0.8"/>
        </svg>`;
    return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        scaledSize: new google.maps.Size(48, 40),
        anchor: new google.maps.Point(24, 20),
    };
}

// Continuous movement system — truck moves at constant speed, no jumps
const markerLoops = {}; // active animation loop per driver id

function startContinuousMove(markerId, marker, fromLat, fromLng, toLat, toLng, heading, status) {
    if (markerLoops[markerId]) markerLoops[markerId].stop = true;

    const loop = { stop: false };
    markerLoops[markerId] = loop;

    const STEP_MS = 50;
    const TRAVEL_MS = 5200;
    const steps = TRAVEL_MS / STEP_MS;
    const dLat = (toLat - fromLat) / steps;
    const dLng = (toLng - fromLng) / steps;

    let curLat = fromLat, curLng = fromLng, stepCount = 0;
    // Don't overwrite circular driver icon — only set truck icon for vehicle markers

    function tick() {
        if (loop.stop) return;
        stepCount++;
        // Stop at target — do NOT extrapolate past it
        if (stepCount >= steps) {
            marker.setPosition({ lat: toLat, lng: toLng });
            return;
        }
        curLat += dLat;
        curLng += dLng;
        marker.setPosition({ lat: curLat, lng: curLng });
        setTimeout(tick, STEP_MS);
    }
    setTimeout(tick, STEP_MS);
}

// Build a circular initials marker with pin tail — returns a Promise<icon object>
function buildInitialsIcon(firstname, lastname, status) {
    return new Promise((resolve) => {
        const size = 36;
        const totalH = size + 10; // extra height for pin tail
        const borderColor = status === 'online' ? '#22c55e' : '#ef4444';
        const initials = ((firstname || '?')[0] + (lastname || '?')[0]).toUpperCase();
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = totalH;
        const ctx = canvas.getContext('2d');

        // Pin tail
        ctx.beginPath();
        ctx.moveTo(size / 2 - 6, size - 4);
        ctx.lineTo(size / 2, totalH);
        ctx.lineTo(size / 2 + 6, size - 4);
        ctx.fillStyle = borderColor;
        ctx.fill();

        // Background circle
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, size / 2 - 3, 0, Math.PI * 2);
        ctx.fillStyle = '#1D4ED8';
        ctx.fill();

        // Initials text
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 12px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(initials, size / 2, size / 2);

        // Status ring
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, size / 2 - 1.5, 0, Math.PI * 2);
        ctx.strokeStyle = borderColor;
        ctx.lineWidth = 3;
        ctx.stroke();

        resolve({
            url: canvas.toDataURL('image/png'),
            scaledSize: new google.maps.Size(size, totalH),
            anchor: new google.maps.Point(size / 2, totalH), // anchor at tip of pin
        });
    });
}

// Build a circular photo marker with pin tail — returns a Promise<icon object>
function buildPhotoIcon(photoUrl, firstname, lastname, status) {
    return new Promise((resolve) => {
        const size = 36;
        const totalH = size + 10;
        const borderColor = status === 'online' ? '#22c55e' : '#ef4444';
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = totalH;
        const ctx = canvas.getContext('2d');

        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            // Pin tail
            ctx.beginPath();
            ctx.moveTo(size / 2 - 6, size - 4);
            ctx.lineTo(size / 2, totalH);
            ctx.lineTo(size / 2 + 6, size - 4);
            ctx.fillStyle = borderColor;
            ctx.fill();

            // Clip to circle
            ctx.beginPath();
            ctx.arc(size / 2, size / 2, size / 2 - 3, 0, Math.PI * 2);
            ctx.closePath();
            ctx.clip();
            ctx.drawImage(img, 3, 3, size - 6, size - 6);

            // Status ring
            ctx.beginPath();
            ctx.arc(size / 2, size / 2, size / 2 - 1.5, 0, Math.PI * 2);
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 3;
            ctx.stroke();

            resolve({
                url: canvas.toDataURL('image/png'),
                scaledSize: new google.maps.Size(size, totalH),
                anchor: new google.maps.Point(size / 2, totalH),
            });
        };
        img.onerror = () => buildInitialsIcon(firstname, lastname, status).then(resolve);
        img.src = photoUrl;
    });
}

// Create info window content
function createInfoWindowContent(driver) {
    const photoHtml = driver.photo_url
        ? `<img src="${driver.photo_url}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-right:10px;border:2px solid #e5e7eb;">`
        : `<span style="width:48px;height:48px;border-radius:50%;background:#e5e7eb;display:inline-flex;align-items:center;justify-content:center;margin-right:10px;font-size:18px;">🧑‍✈️</span>`;
    return `
        <div class="custom-infowindow" style="display:flex;align-items:center;padding:4px 2px;">
            ${photoHtml}
            <div>
                <strong>${driver.firstname} ${driver.lastname}</strong>
                <div>Phone: ${driver.phoneno}</div>
                <div>Emergency: ${driver.emergencycontactno}</div>
                <div>Last update: ${formatDateTime(driver.last_location_update)}</div>
            </div>
        </div>`;
}

// Update the driver list
function updateDriverList() {
    const search = document.getElementById('searchBox').value.toLowerCase();
    const list = document.getElementById('driverList');
    
    // Filter drivers based on search
    const filteredDrivers = driversData.filter(d => 
        `${d.firstname} ${d.lastname}`.toLowerCase().includes(search));
    
    // If no results
    if (filteredDrivers.length === 0) {
        list.innerHTML = '<div class="text-center py-3">No drivers found</div>';
        return;
    }
    
    // Create new list
    let html = '';
    filteredDrivers.forEach(driver => {
        const ring = driver.status === 'online' ? '#22c55e' : '#ef4444';
        const avatar = driver.photo_url
            ? `<img src="${driver.photo_url}" style="width:32px;height:32px;border-radius:50%;border:2px solid ${ring};object-fit:cover;margin-right:8px;">`
            : `<span class="driver-status-dot ${driver.status}" style="margin-right:8px;"></span>`;
        html += `
            <div class="driver-card" data-id="${driver.id}">
                <div class="d-flex align-items-center">${avatar}<strong class="text-dark">${driver.firstname} ${driver.lastname}</strong></div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted">${formatTime(driver.last_location_update)}</small>
                </div>
            </div>`;
    });
    
    list.innerHTML = html;
    
    // Add click handlers
    document.querySelectorAll('.driver-card').forEach(card => {
        const driverId = card.getAttribute('data-id');
        const driver = driversData.find(d => d.id == driverId);
        
        if (driver) {
            card.addEventListener('click', () => {
                const latLng = new google.maps.LatLng(driver.latitude, driver.longitude);
                map.panTo(latLng);
                map.setZoom(12);
                if (markers[driverId]) {
                    google.maps.event.trigger(markers[driverId], 'click');
                }
            });
        }
    });
}

// Update the last update time display
function updateLastUpdateTime() {
    const now = new Date();
    document.getElementById('lastUpdate').textContent = 
        `Last updated: ${now.toLocaleTimeString()}`;
}

// Format date time for display
function formatDateTime(dateString) {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    return date.toLocaleString();
}

// Format time for display
function formatTime(dateString) {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    return date.toLocaleTimeString();
}

// Calculate backoff delay for retries
function getBackoffDelay(attempt = 1) {
    const maxDelay = 60000; // 1 minute max
    const baseDelay = 5000; // 5 seconds base
    return Math.min(baseDelay * Math.pow(2, attempt), maxDelay);
}

// Show error message
function showError(message) {
    const lastUpdate = document.getElementById('lastUpdate');
    lastUpdate.textContent = message;
    lastUpdate.style.color = 'red';
    
    // Reset color after delay
    setTimeout(() => {
        lastUpdate.style.color = '';
    }, 3000);
}

// Update vehicle markers (moving trucks)
function updateVehicleMarkers(trucks) {
    if (!trucks || !trucks.length) return;

    const truckSvg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="40" viewBox="0 0 48 40">
            <rect x="2" y="10" width="28" height="18" rx="3" fill="#F8C71F" stroke="#b45309" stroke-width="1.5"/>
            <rect x="30" y="14" width="15" height="14" rx="3" fill="#F8C71F" stroke="#b45309" stroke-width="1.5"/>
            <rect x="32" y="16" width="9" height="7" rx="1.5" fill="white" opacity="0.85"/>
            <rect x="42" y="9" width="2.5" height="7" rx="1" fill="#b45309"/>
            <circle cx="10" cy="29" r="4.5" fill="#1e293b" stroke="#b45309" stroke-width="1.2"/>
            <circle cx="10" cy="29" r="2" fill="#64748b"/>
            <circle cx="23" cy="29" r="4.5" fill="#1e293b" stroke="#b45309" stroke-width="1.2"/>
            <circle cx="23" cy="29" r="2" fill="#64748b"/>
            <circle cx="37" cy="29" r="4" fill="#1e293b" stroke="#b45309" stroke-width="1.2"/>
            <circle cx="37" cy="29" r="1.8" fill="#64748b"/>
            <circle cx="44" cy="23" r="2" fill="#fde68a" stroke="#b45309" stroke-width="0.8"/>
        </svg>`;
    const truckIcon = {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(truckSvg),
        scaledSize: new google.maps.Size(48, 40),
        anchor: new google.maps.Point(24, 34),
    };

    trucks.forEach(truck => {
        const lat = parseFloat(truck.lat);
        const lng = parseFloat(truck.lng);
        if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

        const id   = 'v_' + truck.vehicle_id;
        const pos  = { lat, lng };
        const name = truck.unit_number || ('Truck #' + truck.vehicle_id);
        const info = `
            <div style="padding:8px;min-width:160px">
                <strong>🚛 ${name}</strong><br>
                <span>Speed: ${Math.round(truck.speed_mph || 0)} mph</span><br>
                <span>Heading: ${Math.round(truck.heading_deg || 0)}°</span><br>
                <small style="color:#888">Updated: ${truck.last_update_utc ? new Date(truck.last_update_utc).toLocaleTimeString() : 'N/A'}</small>
            </div>`;

        if (vehicleMarkers[id]) {
            vehicleMarkers[id].setPosition(pos);
            vehicleMarkers[id]._infoContent = info;
        } else {
            const marker = new google.maps.Marker({
                position: pos,
                map: map,
                title: name,
                icon: truckIcon,
                label: { text: '🚛', fontSize: '18px' },
            });
            marker._infoContent = info;
            marker.addListener('click', () => {
                infoWindow.setContent(marker._infoContent);
                infoWindow.open(map, marker);
            });
            vehicleMarkers[id] = marker;
        }
    });
}

// Initialize when page loads
window.onload = initMap;

// Search functionality
document.getElementById('searchBox').addEventListener('input', () => {
    updateDriverList();
});

// Render HOS / duty status list
function updateHosList() {
    const container = document.getElementById('hosList');
    if (!container) return;

    if (!hosDrivers.length) {
        container.innerHTML = '<div class="text-muted font-size-12">No HOS data available</div>';
        return;
    }

    let html = '';
    hosDrivers.forEach(d => {
        const driveHrs = minutesToHoursLabel(d.hos.drive_remaining_minutes || 0);
        const dutyHrs  = minutesToHoursLabel(d.hos.on_duty_remaining_minutes || 0);
        html += `
            <div class="hos-item">
                <div>
                    <span class="hos-status-pill bg-primary text-white text-capitalize">${d.status.replace(/_/g, ' ')}</span>
                    <strong>${d.name || 'Driver '+d.id}</strong>
                </div>
                <div class="mt-1 text-muted">
                    Drive left: ${driveHrs} &bull; On-duty left: ${dutyHrs}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function minutesToHoursLabel(minutes) {
    const m = parseInt(minutes || 0, 10);
    const h = Math.floor(m / 60);
    const rem = m % 60;
    if (h <= 0) return `${rem}m`;
    return `${h}h ${rem}m`;
}

// ─── Real-time via Pusher ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        forceTLS: true,
    });

    pusher.connection.bind('connected', function () {
        document.getElementById('lastUpdate').textContent = '🟢 Pusher Connected';
    });

    pusher.connection.bind('error', function (err) {
        console.error('[Map Pusher] connection error', err);
    });

    const channel = pusher.subscribe('live.tracking');
    channel.bind('location.updated', function (data) {
        if (data.type === 'driver') {
            realtimeUpdateDriverMarker(data);
        } else {
            realtimeUpdateVehicleMarker(data);
        }
        document.getElementById('lastUpdate').textContent =
            '🟢 Live — ' + new Date().toLocaleTimeString();
    });
});

const markerPrevPos = {}; // last confirmed GPS point per driver
const markerLastGPS = {};  // same — used as fromLat/fromLng so we never read animated position

function realtimeUpdateDriverMarker(data) {
    const id  = String(data.id);
    const toLat = parseFloat(data.lat), toLng = parseFloat(data.lng);
    if (isNaN(toLat) || isNaN(toLng)) return;

    // If same GPS point as last time, driver hasn't moved — don't restart animation
    const last = markerLastGPS[id];
    if (last && last.lat === toLat && last.lng === toLng) return;

    let heading = 0;
    let fromLat = toLat, fromLng = toLng;

    if (last) {
        fromLat = last.lat; fromLng = last.lng;
        heading = calcBearing(last.lat, last.lng, toLat, toLng);
    }

    markerLastGPS[id] = { lat: toLat, lng: toLng };
    markerPrevPos[id] = { lat: toLat, lng: toLng };

    if (!markers[id]) {
        const nameParts = (data.name || '').split(' ');
        buildInitialsIcon(nameParts[0] || '?', nameParts[1] || '?', 'online').then(icon => {
            markers[id] = new google.maps.Marker({ position: { lat: fromLat, lng: fromLng }, map, title: data.name, icon });
            markers[id].addListener('click', () => {
                infoWindow.setContent(`<strong>${data.name}</strong><br>${(data.status||'').replace(/_/g,' ')}`);
                infoWindow.open(map, markers[id]);
            });
            startContinuousMove(id, markers[id], fromLat, fromLng, toLat, toLng, heading, 'online');
        });
        return;
    }

    startContinuousMove(id, markers[id], fromLat, fromLng, toLat, toLng, heading, 'online');

    const existing = driversData.find(d => String(d.id) === id);
    if (existing) { existing.latitude = data.lat; existing.longitude = data.lng; }
}

function realtimeUpdateVehicleMarker(data) {
    const id  = 'v_' + data.vehicle_id;
    const pos = { lat: parseFloat(data.lat), lng: parseFloat(data.lng) };
    if (isNaN(pos.lat) || isNaN(pos.lng)) return;

    const rtSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="48" height="40" viewBox="0 0 48 40"><rect x="2" y="10" width="28" height="18" rx="3" fill="#F8C71F" stroke="#b45309" stroke-width="1.5"/><rect x="30" y="14" width="15" height="14" rx="3" fill="#F8C71F" stroke="#b45309" stroke-width="1.5"/><rect x="32" y="16" width="9" height="7" rx="1.5" fill="white" opacity="0.85"/><rect x="42" y="9" width="2.5" height="7" rx="1" fill="#b45309"/><circle cx="10" cy="29" r="4.5" fill="#1e293b" stroke="#b45309" stroke-width="1.2"/><circle cx="10" cy="29" r="2" fill="#64748b"/><circle cx="23" cy="29" r="4.5" fill="#1e293b" stroke="#b45309" stroke-width="1.2"/><circle cx="23" cy="29" r="2" fill="#64748b"/><circle cx="37" cy="29" r="4" fill="#1e293b" stroke="#b45309" stroke-width="1.2"/><circle cx="37" cy="29" r="1.8" fill="#64748b"/><circle cx="44" cy="23" r="2" fill="#fde68a" stroke="#b45309" stroke-width="0.8"/></svg>`;
    const truckIcon = { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(rtSvg), scaledSize: new google.maps.Size(48, 40), anchor: new google.maps.Point(24, 34) };
    const info = `<div style="padding:8px"><strong>🚛 ${data.name}</strong><br>Speed: ${data.speed || 0} mph<br>Heading: ${data.heading || 0}°</div>`;

    if (vehicleMarkers[id]) {
        vehicleMarkers[id].setPosition(pos);
        vehicleMarkers[id]._infoContent = info;
    } else {
        const marker = new google.maps.Marker({ position: pos, map: map, title: data.name, icon: truckIcon });
        marker._infoContent = info;
        marker.addListener('click', () => { infoWindow.setContent(marker._infoContent); infoWindow.open(map, marker); });
        vehicleMarkers[id] = marker;
    }
}

</script>
@endsection