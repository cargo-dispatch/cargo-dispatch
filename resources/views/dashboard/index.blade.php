@extends('layouts.app')
@section('title') {{ $name }} @endsection

@push('styles')
<link href="{{ asset('assets/css/dashboard.css') }}?v={{ filemtime(public_path('assets/css/dashboard.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="db-page">

    {{-- ── Stat Cards ── --}}
    <div class="db-stats">
        <a href="{{ route('dispatch.ai-board') }}" class="db-stat-card">
            <span class="db-stat-label">Live Load Board</span>
            <span class="db-stat-value" id="dbLiveLoadsCount2" style="color:var(--hover-color);">—</span>
            <span class="db-stat-change flat">FreightFinder · Click to view</span>
            <i class="bi bi-box-arrow-in-right db-stat-icon"></i>
        </a>

        <a href="{{ route('managedriver.index') }}" class="db-stat-card">
            <span class="db-stat-label">Total Drivers</span>
            <span class="db-stat-value">{{ $drivers }}</span>
            <span class="db-stat-change {{ $newDriversThisMonth > 0 ? 'up' : 'flat' }}">
                @if($newDriversThisMonth > 0)
                    +{{ $newDriversThisMonth }} this month &nbsp;·&nbsp; +{{ number_format($driverPercentageChange,1) }}%
                @else
                    No new drivers this month
                @endif
            </span>
            <i class="fa-solid fa-user-tie db-stat-icon"></i>
        </a>

        <a href="{{ route('vehicles.index') }}" class="db-stat-card">
            <span class="db-stat-label">Total Vehicles</span>
            <span class="db-stat-value">{{ $vehicles }}</span>
            <span class="db-stat-change {{ $newVehiclesThisMonth > 0 ? 'up' : 'flat' }}">
                @if($newVehiclesThisMonth > 0)
                    +{{ $newVehiclesThisMonth }} this month &nbsp;·&nbsp; +{{ number_format($vehiclePercentageChange,1) }}%
                @else
                    No new vehicles this month
                @endif
            </span>
            <i class="fa fa-truck db-stat-icon"></i>
        </a>

        <div class="db-stat-card">
            <span class="db-stat-label">Today's Shipments</span>
            <span class="db-stat-value">{{ $shipments }}</span>
            <span class="db-stat-change {{ $shipmentsYesterday > 0 ? ($shipments >= $shipmentsYesterday ? 'up' : 'down') : 'flat' }}">
                @if($shipmentsYesterday > 0)
                    {{ $shipmentsYesterday }} yesterday &nbsp;·&nbsp;
                    @if($shipmentPercentageChange >= 0)
                        +{{ number_format($shipmentPercentageChange,1) }}%
                    @else
                        {{ number_format($shipmentPercentageChange,1) }}%
                    @endif
                @else
                    No shipments yesterday
                @endif
            </span>
            <i class="fa-solid fa-truck-fast db-stat-icon"></i>
        </div>

    </div>

    {{-- ── Activity Chart + Live Map ── --}}
    <div class="db-mid-row">

        {{-- Activity Bar Chart --}}
        <div class="db-card">
            <div class="db-card-body">
                <div class="db-section-header">
                    <span class="db-section-title">Activity</span>
                    <select id="activityPeriodFilter" class="db-period-select">
                        <option value="week"    {{ request('period','week')=='week'    ? 'selected':'' }}>This week</option>
                        <option value="month"   {{ request('period')=='month'          ? 'selected':'' }}>This month</option>
                        <option value="6months" {{ request('period')=='6months'        ? 'selected':'' }}>Last 6 months</option>
                        <option value="year"    {{ request('period')=='year'           ? 'selected':'' }}>This year</option>
                    </select>
                </div>
                <div class="db-chart-wrap">
                    <canvas id="activityBarChart"></canvas>
                </div>
                <div class="db-chart-legend">
                    <div class="db-legend-item"><span class="db-legend-dot" style="background:rgba(120,120,120,.5)"></span>&lt;10 orders</div>
                    <div class="db-legend-item"><span class="db-legend-dot" style="background:rgba(180,180,180,.7)"></span>10–19 orders</div>
                    <div class="db-legend-item"><span class="db-legend-dot" style="background:var(--hover-color)"></span>20+ orders</div>
                </div>
            </div>
        </div>

        {{-- Live Driver Map --}}
        <div class="db-card" style="overflow:hidden;">
            <div class="db-card-body" style="padding-bottom:0;">
                <div class="db-section-header">
                    <span class="db-section-title"><i class="fas fa-map-marked-alt me-2" style="color:var(--hover-color)"></i>Live Driver Map</span>
                    <a href="{{ route('drivers.map') }}" class="pg-btn-secondary" style="font-size:11px;padding:4px 12px;">
                        <i class="bi bi-fullscreen"></i> Full View
                    </a>
                </div>
            </div>
            <div style="position:relative;height:320px;overflow:hidden;">
                <div id="dashboardMap" style="width:100%;height:320px;"></div>
                <div class="db-map-legend">
                    <div class="db-map-legend-item"><span class="db-map-legend-dot" style="background:#22c55e"></span>Driving</div>
                    <div class="db-map-legend-item"><span class="db-map-legend-dot" style="background:var(--hover-color)"></span>On Duty / Trucks</div>
                    <div class="db-map-legend-item"><span class="db-map-legend-dot" style="background:#9ca3af"></span>Off Duty</div>
                    <div class="db-map-legend-item"><span class="db-map-legend-dot" style="background:#818cf8"></span>Sleeper</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Orders + Side panels ── --}}
    <div class="db-orders-row">

        {{-- Orders Table --}}
        <div class="db-card">
            <div class="db-card-body">
                <div class="db-section-header">
                    <span class="db-section-title">Orders</span>
                    <select id="ordersPeriodFilter" class="db-period-select">
                        <option value="today" selected>Today</option>
                        <option value="week">This week</option>
                        <option value="month">This month</option>
                    </select>
                </div>

                <div class="db-order-tabs">
                    <div class="db-order-tab active" data-status="all">All <span class="tab-count">{{ $orderStatusCounts['total'] ?? 0 }}</span></div>
                    <div class="db-order-tab" data-status="assigned">Assigned <span class="tab-count">{{ $orderStatusCounts['assigned'] ?? 0 }}</span></div>
                    <div class="db-order-tab" data-status="unassigned">Unassigned <span class="tab-count">{{ $orderStatusCounts['unassigned'] ?? 0 }}</span></div>
                    <div class="db-order-tab" data-status="pending">Pending <span class="tab-count">{{ $orderStatusCounts['pending'] ?? 0 }}</span></div>
                    <div class="db-order-tab" data-status="complete">Complete <span class="tab-count">{{ $orderStatusCounts['complete'] ?? 0 }}</span></div>
                    <div class="db-order-tab" data-status="cancel">Cancel <span class="tab-count">{{ $orderStatusCounts['cancel'] ?? 0 }}</span></div>
                </div>

                <div class="db-orders-scroll">
                    <table class="db-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pickup</th>
                                <th>Drop</th>
                                <th>Driver</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            @forelse($ordersData as $order)
                            <tr onclick="window.location.href='{{ route('shipments.index') }}?order={{ $order['id'] }}'">
                                <td style="font-weight:600;color:var(--hover-color);">#{{ $order['id'] }}</td>
                                <td title="{{ $order['pickup_address'] ?? 'N/A' }}">{{ Str::limit($order['pickup_address'] ?? 'N/A', 22) }}</td>
                                <td title="{{ $order['drop_address'] ?? 'N/A' }}">{{ Str::limit($order['drop_address'] ?? 'N/A', 22) }}</td>
                                <td>
                                    @if(!empty($order['driver']))
                                        @php $dn = trim(($order['driver']['firstname'] ?? '') . ' ' . ($order['driver']['lastname'] ?? '')); @endphp
                                        <span title="{{ $dn }}">{{ Str::limit($dn, 16) }}</span>
                                    @else
                                        <span style="opacity:.4">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($order['vehicle']))
                                        {{ $order['vehicle']['vehicle_id'] ?? 'N/A' }}
                                    @else
                                        <span style="opacity:.4">Unassigned</span>
                                    @endif
                                </td>
                                <td><span class="db-badge {{ strtolower($order['status']) }}">{{ ucfirst($order['status']) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" style="text-align:center;padding:28px;opacity:.4;font-family:'Jost',sans-serif;">No orders found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right side: Top States + Top Customers --}}
        <div style="display:flex;flex-direction:column;gap:16px;min-width:0;">

            {{-- Top States --}}
            <div class="db-card">
                <div class="db-card-body">
                    <div class="db-section-header">
                        <span class="db-section-title">Top States</span>
                        <select id="topStatesFilter" class="db-period-select">
                            <option value="week" selected>This week</option>
                            <option value="month">This month</option>
                            <option value="year">This year</option>
                        </select>
                    </div>
                    <div class="db-mini-cards" id="topStatesContainer">
                        @if(count($topStates) > 0)
                            @foreach(array_slice($topStates, 0, 3) as $state)
                            <div class="db-mini-card">
                                <div class="db-mini-card-top">
                                    <span class="db-mini-card-avatar">{{ $state['avatar'] }}</span>
                                    <span class="db-mini-card-name">{{ $state['name'] }}</span>
                                </div>
                                <div class="db-mini-card-sub">United States</div>
                                <div class="db-mini-card-stats">
                                    <div>
                                        <div class="db-mini-card-stat-val">{{ $state['percentage'] }}%</div>
                                        <div class="db-mini-card-stat-lbl">Share</div>
                                    </div>
                                    <div>
                                        <div class="db-mini-card-stat-val">{{ $state['shipments'] }}</div>
                                        <div class="db-mini-card-stat-lbl">Orders</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div style="text-align:center;padding:20px;opacity:.4;font-size:13px;font-family:'Jost',sans-serif;">No state data for this period</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Top Customers --}}
            <div class="db-card">
                <div class="db-card-body">
                    <div class="db-section-header">
                        <span class="db-section-title">Top Customers</span>
                        <select id="topCustomersFilter" class="db-period-select">
                            <option value="week" selected>This week</option>
                            <option value="month">This month</option>
                            <option value="year">This year</option>
                        </select>
                    </div>
                    <div class="db-mini-cards" id="topCustomersContainer">
                        @if(count($topCustomers) > 0)
                            @foreach(array_slice($topCustomers, 0, 3) as $customer)
                            <div class="db-mini-card">
                                <div class="db-mini-card-top">
                                    <span class="db-mini-card-avatar">{{ $customer['avatar'] }}</span>
                                    <span class="db-mini-card-name">{{ $customer['name'] }}</span>
                                </div>
                                <div class="db-mini-card-sub">{{ Str::limit($customer['company'], 30) }}</div>
                                <div class="db-mini-card-stats">
                                    <div>
                                        <div class="db-mini-card-stat-val">{{ $customer['percentage'] }}%</div>
                                        <div class="db-mini-card-stat-lbl">Share</div>
                                    </div>
                                    <div>
                                        <div class="db-mini-card-stat-val">{{ $customer['orders'] }}</div>
                                        <div class="db-mini-card-stat-lbl">Orders</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div style="text-align:center;padding:20px;opacity:.4;font-size:13px;font-family:'Jost',sans-serif;">No customer data for this period</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Earnings Chart + Pie Chart ── --}}
    <div class="db-charts-row">
        <div class="db-card">
            <div class="db-card-body">
                <div class="db-section-header">
                    <span class="db-section-title">Monthly Earnings</span>
                </div>
                <div class="db-chart-wrap">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>
        </div>
        <div class="db-card">
            <div class="db-card-body">
                <div class="db-section-header">
                    <span class="db-section-title">Shipment Status</span>
                    <span style="font-size:11px;opacity:.45;font-family:'Jost',sans-serif;">Last 6 months</span>
                </div>
                <div class="db-chart-wrap-sm">
                    <canvas id="myPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Shipments By Region ── --}}
    <div class="db-card" style="margin-bottom:16px;">
        <div class="db-card-body">
            <div class="db-section-header">
                <span class="db-section-title">Monthly Shipments By Region</span>
            </div>
            @if(isset($shipmentsByRegion) && $shipmentsByRegion->isNotEmpty())
                @foreach($shipmentsByRegion as $regionData)
                <div class="db-region-row">
                    <img src="https://flagcdn.com/{{ $regionData['country_code'] }}.svg" class="db-region-flag" alt="{{ $regionData['region'] }}">
                    <span class="db-region-name">{{ ucwords($regionData['region']) }}</span>
                    <span class="db-region-pct">{{ $regionData['percentage'] }}%</span>
                    <span class="db-region-count">({{ $regionData['count'] }})</span>
                </div>
                <div class="db-progress">
                    <div class="db-progress-bar" style="width:{{ $regionData['percentage'] }}%"></div>
                </div>
                @endforeach
            @else
                <div style="text-align:center;padding:20px;opacity:.4;font-size:13px;font-family:'Jost',sans-serif;">No regional data available for this month</div>
            @endif
        </div>
    </div>

    {{-- ── Maintenance Tables ── --}}
    <div class="db-two-col">
        <div class="db-card">
            <div class="db-card-body">
                <div class="db-section-header">
                    <span class="db-section-title"><i class="fas fa-calendar-times me-2" style="color:var(--hover-color)"></i>Scheduled Maintenance Alert</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="db-maint-table">
                    <thead><tr><th>Vehicle</th><th>Type</th><th>Scheduled Date</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse($scheduledMaintenance as $maintenance)
                        <tr>
                            <td style="font-weight:600;color:var(--hover-color)">{{ $maintenance->vehicle->vehicle_id ?? 'N/A' }}</td>
                            <td>{{ $maintenance->maintenanceType->maintenance_types ?? 'N/A' }}</td>
                            <td>{{ $maintenance->maintenance_date ? date('M d, Y', strtotime($maintenance->maintenance_date)) : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('maintenance.edit', ['id' => $maintenance->id, 'redirect' => 'dashboard']) }}" class="pg-btn-view">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="db-maint-empty">No scheduled maintenance alerts</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-body">
                <div class="db-section-header">
                    <span class="db-section-title"><i class="fas fa-calendar-times me-2" style="color:var(--hover-color)"></i>Maintenance Expiry Alert</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="db-maint-table">
                    <thead><tr><th>Vehicle</th><th>Type</th><th>Next Service</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse($scheduledMaintenanceExpiry as $maintenance)
                        <tr>
                            <td style="font-weight:600;color:var(--hover-color)">{{ $maintenance->vehicle->vehicle_id ?? 'N/A' }}</td>
                            <td>{{ $maintenance->maintenanceType->maintenance_types ?? 'N/A' }}</td>
                            <td>{{ $maintenance->next_maintenance_date ? date('M d, Y', strtotime($maintenance->next_maintenance_date)) : 'N/A' }}</td>
                            <td>
                                <button type="button" class="pg-btn-icon danger disable-btn" data-id="{{ $maintenance->id }}" title="Disable Alert">
                                    <i class="bi bi-slash-circle"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="db-maint-empty">No maintenance expiry alerts</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ── Leaflet for Dashboard Map ── --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Theme helpers ──
function getChartTextColor() { return (document.documentElement.getAttribute('data-theme')||'dark')==='light'?'#000':'#fff'; }
function getChartGridColor() { return (document.documentElement.getAttribute('data-theme')||'dark')==='light'?'rgba(0,0,0,0.1)':'rgba(255,255,255,0.1)'; }

// ── Dashboard Map (Leaflet — live truck icons) ──
let dashMap      = null;
let dashTileLayer = null;
const dashMarkers  = {};
const truckMarkers = {};
const DASH_MAP_POLL = 10000;

const dutyColors = {
    driving:             '#22c55e',
    on_duty_not_driving: '#F8C71F',
    off_duty:            '#9ca3af',
    sleeper:             '#818cf8',
};
function getDutyColor(s) { return dutyColors[s] || '#9ca3af'; }

// Build a truck SVG icon with the given color and heading rotation
function truckIcon(color, headingDeg) {
    // Wrap in a div so overflow:visible works and the drop-shadow isn't clipped
    const html = `<div style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;overflow:visible;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32"
          style="transform:rotate(${headingDeg || 0}deg);overflow:visible;filter:drop-shadow(0 2px 5px rgba(0,0,0,.6));">
        <!-- body/trailer -->
        <rect x="7" y="13" width="18" height="14" rx="2" fill="${color}"/>
        <rect x="7" y="13" width="18" height="14" rx="2" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="0.8"/>
        <!-- cab -->
        <rect x="9" y="4" width="14" height="10" rx="3" fill="${color}"/>
        <!-- windshield highlight -->
        <rect x="11" y="5.5" width="10" height="4.5" rx="1.5" fill="rgba(255,255,255,0.28)"/>
        <!-- grill -->
        <rect x="10" y="13" width="12" height="2" rx="1" fill="rgba(0,0,0,0.3)"/>
        <!-- wheels -->
        <circle cx="11" cy="28.5" r="2.5" fill="#222"/>
        <circle cx="11" cy="28.5" r="1"   fill="#666"/>
        <circle cx="21" cy="28.5" r="2.5" fill="#222"/>
        <circle cx="21" cy="28.5" r="1"   fill="#666"/>
        <!-- headlights -->
        <circle cx="11.5" cy="4.2" r="1.3" fill="#ffe566"/>
        <circle cx="20.5" cy="4.2" r="1.3" fill="#ffe566"/>
      </svg>
    </div>`;
    return L.divIcon({
        className:  '',
        html:       html,
        iconSize:   [36, 36],
        iconAnchor: [18, 18],
        popupAnchor:[0, -18],
    });
}

function getDashTileUrl() {
    const theme = document.documentElement.getAttribute('data-theme') || document.body.getAttribute('data-theme') || 'dark';
    return theme === 'light'
        ? 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
        : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
}

function updateDashMapTiles() {
    if (!dashMap) return;
    if (dashTileLayer) { dashMap.removeLayer(dashTileLayer); dashTileLayer = null; }
    dashTileLayer = L.tileLayer(getDashTileUrl(), { maxZoom: 18 }).addTo(dashMap);
}

function initDashboardMap() {
    const container = document.getElementById('dashboardMap');
    if (!container) return;
    container.style.height = '320px';

    // First call — create the map once
    if (!dashMap) {
        dashMap = L.map('dashboardMap', {
            zoomControl: true,
            attributionControl: false,
            preferCanvas: true,
        }).setView([39.5, -98.35], 4);
    }

    // Swap tile layer (works for both initial load and theme switches)
    updateDashMapTiles();

    setTimeout(() => {
        if (dashMap) {
            dashMap.invalidateSize();
            fetchDashMapData();
        }
    }, 150);
}

function fetchDashMapData() {
    if (!dashMap) return;

    // Fetch drivers + ELD truck snapshot in parallel
    Promise.all([
        fetch('{{ url("/api/drivers/locations") }}?_=' + Date.now()).then(r => r.json()).catch(() => []),
        fetch('{{ url("/api/mock/eld-snapshot") }}?_=' + Date.now()).then(r => r.json()).catch(() => ({ trucks: [] })),
    ]).then(([locData, eldData]) => {
        const drivers = Array.isArray(locData) ? locData : (locData.data || []);
        const trucks  = eldData.trucks || [];

        // ── Driver markers ──
        const seenDriverIds = new Set();
        drivers.forEach(driver => {
            const lat = parseFloat(driver.current_latitude  || driver.latitude);
            const lng = parseFloat(driver.current_longitude || driver.longitude);
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

            const id     = driver.id;
            const color  = getDutyColor(driver.current_duty_status);
            const name   = ((driver.firstname || '') + ' ' + (driver.lastname || '')).trim();
            const status = (driver.current_duty_status || 'off_duty').replace(/_/g, ' ');
            const popup  = `<div style="font-family:'Jost',sans-serif;min-width:140px;">
                <strong>${name || 'Driver'}</strong><br>
                <span style="text-transform:capitalize;font-size:11px;opacity:.7;">${status}</span>
            </div>`;

            seenDriverIds.add(String(id));

            if (dashMarkers[id]) {
                // Move smoothly + update icon if duty status changed
                dashMarkers[id].setLatLng([lat, lng]);
                dashMarkers[id].setIcon(truckIcon(color, 0));
            } else {
                dashMarkers[id] = L.marker([lat, lng], { icon: truckIcon(color, 0) })
                    .bindPopup(popup)
                    .addTo(dashMap);
            }
        });

        // Remove stale driver markers
        Object.keys(dashMarkers).forEach(id => {
            if (!seenDriverIds.has(String(id))) { dashMarkers[id].remove(); delete dashMarkers[id]; }
        });

        // ── Vehicle / ELD truck markers ──
        const seenTruckIds = new Set();
        trucks.forEach(truck => {
            const lat = parseFloat(truck.lat);
            const lng = parseFloat(truck.lng);
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

            const id      = 'v_' + truck.vehicle_id;
            const heading = parseFloat(truck.heading_deg || 0);
            const speed   = Math.round(truck.speed_mph || 0);
            const name    = truck.unit_number || ('Truck #' + truck.vehicle_id);
            const popup   = `<div style="font-family:'Jost',sans-serif;min-width:160px;">
                <strong>🚛 ${name}</strong><br>
                <span style="font-size:11px;opacity:.7;">Speed: ${speed} mph &nbsp;·&nbsp; Hdg: ${Math.round(heading)}°</span>
            </div>`;

            seenTruckIds.add(id);

            if (truckMarkers[id]) {
                truckMarkers[id].setLatLng([lat, lng]);
                truckMarkers[id].setIcon(truckIcon('#F8C71F', heading));
            } else {
                truckMarkers[id] = L.marker([lat, lng], { icon: truckIcon('#F8C71F', heading) })
                    .bindPopup(popup)
                    .addTo(dashMap);
            }
        });

        // Remove stale truck markers
        Object.keys(truckMarkers).forEach(id => {
            if (!seenTruckIds.has(id)) { truckMarkers[id].remove(); delete truckMarkers[id]; }
        });
    }).catch(() => {});
}

// ── Activity Bar Chart ──
let activityBarChart = null;
const initialActivityData = {
    labels: {!! json_encode($activityData['labels']) !!},
    data:   {!! json_encode($activityData['data']) !!}
};

function createActivityChart(labels, data) {
    const ctx = document.getElementById('activityBarChart').getContext('2d');
    const tc  = getChartTextColor(), gc = getChartGridColor();
    if (activityBarChart) activityBarChart.destroy();

    const maxValue = Math.max(...data, 1);
    let stepSize = maxValue > 100 ? 20 : maxValue > 50 ? 10 : 5;

    activityBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label:'<10',   data: data.map(v => v < 10 ? v : 0),            backgroundColor:'rgba(120,120,120,0.5)', borderColor:'rgba(120,120,120,0.8)', borderWidth:1 },
                { label:'10-19', data: data.map(v => v >= 10 && v < 20 ? v : 0), backgroundColor:'rgba(180,180,180,0.7)', borderColor:'rgba(180,180,180,0.9)', borderWidth:1 },
                { label:'20+',   data: data.map(v => v >= 20 ? v : 0),           backgroundColor:'#F8C71F',              borderColor:'#e6b800',                borderWidth:1 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: tc==='#000'?'rgba(239,239,239,0.9)':'rgba(43,47,59,0.9)', titleColor:tc, bodyColor:tc, titleFont:{family:'Jost',size:13}, bodyFont:{family:'Jost',size:13} }
            },
            scales: {
                x: { stacked:true, ticks:{ color:tc, font:{family:'Jost',size:11} }, grid:{display:false} },
                y: { stacked:true, beginAtZero:true, ticks:{ stepSize, color:tc, font:{family:'Jost',size:11} }, grid:{color:gc} }
            }
        }
    });
}
createActivityChart(initialActivityData.labels, initialActivityData.data);

$('#activityPeriodFilter').on('change', function() {
    $.get('{{ route("dashboard.activity.data") }}', { period: $(this).val() }, res => createActivityChart(res.labels, res.data));
});

// ── Area Chart ──
const earningsLabels = {!! json_encode($monthlyEarnings->pluck('month')) !!};
const earningsData   = {!! json_encode($monthlyEarnings->pluck('total')) !!};
let myChart = null;

function initializeChart() {
    const canvas = document.getElementById('myAreaChart');
    
    if (!canvas) return;
    if (myChart) myChart.destroy();
    const ctx = canvas.getContext('2d');
    const tc  = getChartTextColor(), gc = getChartGridColor();
    const ttBg = tc==='#fff'?'rgba(43,47,59,0.9)':'rgba(239,239,239,0.9)';
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, 'rgba(248,199,31,0.4)');
    gradient.addColorStop(1, 'rgba(248,199,31,0)');
    myChart = new Chart(ctx, {
        type: 'line',
        data: { labels: earningsLabels, datasets: [{ label:'Monthly Earnings', data: earningsData, backgroundColor: gradient, borderColor:'#F8C71F', borderWidth:2, fill:true, tension:0.4 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color:tc, font:{family:'Jost',size:13,weight:'600'} } },
                tooltip: {
                    titleColor:tc, bodyColor:tc, backgroundColor:ttBg,
                    titleFont:{family:'Jost',size:13}, bodyFont:{family:'Jost',size:13},
                    callbacks: {
                        label: ctx => ' $' + Number(ctx.parsed.y).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
                    }
                }
            },
            scales: {
                x: { ticks:{color:tc, font:{family:'Jost',size:12}}, grid:{color:gc} },
                y: {
                    beginAtZero:true,
                    ticks:{
                        color:tc, font:{family:'Jost',size:12},
                        callback: val => '$' + Number(val).toLocaleString('en-US')
                    },
                    grid:{color:gc}
                }
            }
        }
    });
}

// ── Pie Chart ──
const shipmentStatusData = {!! json_encode($shipmentStatusData) !!};
const pieLabels = Object.keys(shipmentStatusData);
const pieCounts = Object.values(shipmentStatusData);
const colorFor      = s => s==='complete'||s==='delivered'?'#818cf8':s==='cancel'||s==='cancelled'?'#ef4444':s==='active'?'#22c55e':s==='assigned'?'#a855f7':'#F8C71F';
const hoverColorFor = s => s==='complete'||s==='delivered'?'#6366f1':s==='cancel'||s==='cancelled'?'#dc2626':s==='active'?'#16a34a':s==='assigned'?'#9333ea':'#e6b800';

let myPieChart = null;

function initializePieChart() {
    const canvas = document.getElementById('myPieChart');
    if (!canvas) return;
    if (!pieLabels.length) {
        const el = document.querySelector('.db-chart-wrap-sm');
        if (el) el.innerHTML = '<p style="text-align:center;opacity:.4;font-family:\'Jost\',sans-serif;padding-top:80px;">No shipment data available</p>';
        return;
    }
    if (myPieChart) myPieChart.destroy();
    const tc = getChartTextColor();
    myPieChart = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{ data: pieCounts, backgroundColor: pieLabels.map(colorFor), hoverBackgroundColor: pieLabels.map(hoverColorFor), borderWidth:2, borderColor:'transparent' }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display:true, position:'bottom', labels:{ color:tc, font:{family:'Jost',size:12} } },
                tooltip: { backgroundColor: tc==='#fff'?'rgba(43,47,59,0.9)':'rgba(239,239,239,0.9)', titleColor:tc, bodyColor:tc }
            },
            cutout: '62%'
        }
    });
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initializeChart,    100);
    setTimeout(initializePieChart, 100);
    setTimeout(initDashboardMap,   200);
    // Poll every 10s as fallback; Pusher handles instant duty status updates
    setInterval(fetchDashMapData, 10000);

    // Real-time location updates via Pusher
    const dashPrevPos = {}, dashLoops = {};
    function dashCalcBearing(lat1, lng1, lat2, lng2) {
        const toRad = d => d * Math.PI / 180, toDeg = r => r * 180 / Math.PI;
        const dLng = toRad(lng2 - lng1), rlat1 = toRad(lat1), rlat2 = toRad(lat2);
        const y = Math.sin(dLng) * Math.cos(rlat2);
        const x = Math.cos(rlat1) * Math.sin(rlat2) - Math.sin(rlat1) * Math.cos(rlat2) * Math.cos(dLng);
        return (toDeg(Math.atan2(y, x)) + 360) % 360;
    }
    function startDashMove(id, marker, fromLat, fromLng, toLat, toLng, heading, color) {
        if (dashLoops[id]) dashLoops[id].stop = true;
        const loop = { stop: false };
        dashLoops[id] = loop;
        const STEP_MS = 50, TRAVEL_MS = 5200, steps = TRAVEL_MS / STEP_MS;
        const dLat = (toLat - fromLat) / steps, dLng = (toLng - fromLng) / steps;
        let curLat = fromLat, curLng = fromLng;
        marker.setIcon(truckIcon(color, heading));
        function tick() {
            if (loop.stop) return;
            curLat += dLat; curLng += dLng;
            marker.setLatLng([curLat, curLng]);
            setTimeout(tick, STEP_MS);
        }
        setTimeout(tick, STEP_MS);
    }
    if (window.Echo) {
        window.Echo.channel('live.tracking').listen('.location.updated', function (data) {
            const toLat = parseFloat(data.lat), toLng = parseFloat(data.lng);
            if (isNaN(toLat) || isNaN(toLng)) return;
            if (data.type === 'driver') {
                const id = String(data.id);
                const color = getDutyColor(data.status || 'off_duty');
                let heading = 0, fromLat = toLat, fromLng = toLng;
                if (dashMarkers[id]) {
                    const cur = dashMarkers[id].getLatLng();
                    fromLat = cur.lat; fromLng = cur.lng;
                }
                if (dashPrevPos[id]) heading = dashCalcBearing(dashPrevPos[id].lat, dashPrevPos[id].lng, toLat, toLng);
                dashPrevPos[id] = { lat: toLat, lng: toLng };
                if (!dashMarkers[id]) {
                    dashMarkers[id] = L.marker([toLat, toLng], { icon: truckIcon(color, heading) })
                        .bindPopup(`<strong>${data.name}</strong><br>${(data.status||'off_duty').replace(/_/g,' ')}`)
                        .addTo(dashMap);
                }
                startDashMove(id, dashMarkers[id], fromLat, fromLng, toLat, toLng, heading, color);
            } else {
                const id = 'v_' + data.vehicle_id;
                if (!truckMarkers[id]) {
                    truckMarkers[id] = L.marker([toLat, toLng], { icon: truckIcon('#F8C71F', data.heading || 0) })
                        .bindPopup(`<strong>🚛 ${data.name}</strong><br>Speed: ${data.speed || 0} mph`)
                        .addTo(dashMap);
                } else {
                    truckMarkers[id].setLatLng([toLat, toLng]);
                }
            }
        });

        // Listen for driver duty status changes and update marker color immediately
        window.Echo.private('admin.notifications').listen('.driver.status.updated', function (data) {
            const id = String(data.driver_id);
            if (!dashMarkers[id]) return;
            const newColor = getDutyColor(data.current_duty_status || 'off_duty');
            const cur = dashMarkers[id].getLatLng();
            const name = ((data.firstname || '') + ' ' + (data.lastname || '')).trim();
            const statusLabel = (data.current_duty_status || 'off_duty').replace(/_/g, ' ');
            dashMarkers[id].setIcon(truckIcon(newColor, 0));
            dashMarkers[id].setPopupContent(`<div style="font-family:'Jost',sans-serif;min-width:140px;">
                <strong>${name}</strong><br>
                <span style="text-transform:capitalize;font-size:11px;opacity:.7;">${statusLabel}</span>
            </div>`);
        });
    }
});

// ── Re-init on theme change ──
function reinitCharts() {
    setTimeout(initializeChart,    100);
    setTimeout(initializePieChart, 100);
    setTimeout(updateDashMapTiles, 50);  // just swap tiles, keep markers alive
    if (activityBarChart) {
        const lbl = activityBarChart.data.labels;
        const orig = lbl.map((_, i) => activityBarChart.data.datasets.reduce((s, ds) => s + (ds.data[i]||0), 0));
        setTimeout(() => createActivityChart(lbl, orig), 200);
    }
}
document.addEventListener('themeChanged', reinitCharts);
new MutationObserver(mutations => {
    mutations.forEach(m => { if (m.attributeName === 'data-theme') reinitCharts(); });
}).observe(document.documentElement, { attributes:true, attributeFilter:['data-theme'] });

// ── Maintenance Disable ──
$(document).on('click', '.disable-btn', function() {
    const id = $(this).data('id');
    Swal.fire({ title:'Are you sure?', text:'Disable this maintenance alert?', icon:'warning', showCancelButton:true, confirmButtonColor:'#F8C71F', cancelButtonColor:'#d33', confirmButtonText:'Yes, disable it' })
    .then(r => {
        if (r.isConfirmed) {
            $.post(`{{ url('admin/maintenance/disable') }}/${id}`, { _token:'{{ csrf_token() }}' })
            .done(res => { Swal.fire({ title:'Disabled!', text:res.message, icon:'success', timer:2000, showConfirmButton:false }); setTimeout(()=>location.reload(),2000); })
            .fail(xhr => { Swal.fire({ title:'Error!', text:xhr.responseJSON?.error||'Something went wrong.', icon:'error' }); });
        }
    });
});

// ── Orders filtering ──
let currentOrderStatus = null, currentOrderPeriod = 'today';

$('.db-order-tab').on('click', function() {
    currentOrderStatus = $(this).data('status') === 'all' ? null : $(this).data('status');
    $('.db-order-tab').removeClass('active');
    $(this).addClass('active');
    loadOrders();
});

$('#ordersPeriodFilter').on('change', function() {
    currentOrderPeriod = $(this).val();
    loadOrders();
});

function loadOrders() {
    $.get('{{ route("dashboard.orders.data") }}', { period: currentOrderPeriod, status: currentOrderStatus })
    .done(res => { updateOrdersTable(res.orders); updateTabCounts(res.statusCounts); });
}

window.addEventListener('shipmentRealtimeUpdated', function () {
    loadOrders();
});

function updateOrdersTable(orders) {
    const tbody = $('#ordersTableBody');
    tbody.empty();
    if (!orders.length) {
        tbody.append('<tr><td colspan="6" style="text-align:center;padding:28px;opacity:.4;font-family:\'Jost\',sans-serif;">No orders found</td></tr>');
        return;
    }
    orders.forEach(order => {
        const pu  = order.pickup_address || 'N/A';
        const dr  = order.drop_address   || 'N/A';
        const tpu = pu.length > 22 ? pu.substring(0, 22) + '…' : pu;
        const tdr = dr.length > 22 ? dr.substring(0, 22) + '…' : dr;

        let driverInfo = '<span style="opacity:.4">Unassigned</span>';
        if (order.driver) {
            const dn = ((order.driver.firstname||'') + ' ' + (order.driver.lastname||'')).trim();
            driverInfo = `<span title="${dn}">${dn.length > 16 ? dn.substring(0,16)+'…' : dn}</span>`;
        }
        let vehicleInfo = '<span style="opacity:.4">Unassigned</span>';
        if (order.vehicle) vehicleInfo = `<span>${order.vehicle.vehicle_id||'N/A'}</span>`;

        tbody.append(`<tr onclick="window.location.href='{{ route('shipments.index') }}?order=${order.id}'">
            <td style="font-weight:600;color:var(--hover-color);">#${order.id}</td>
            <td title="${pu}">${tpu}</td>
            <td title="${dr}">${tdr}</td>
            <td>${driverInfo}</td>
            <td>${vehicleInfo}</td>
            <td><span class="db-badge ${order.status.toLowerCase()}">${order.status.charAt(0).toUpperCase()+order.status.slice(1)}</span></td>
        </tr>`);
    });
}

function updateTabCounts(c) {
    $('.db-order-tab[data-status="all"]        .tab-count').text(c.total      || 0);
    $('.db-order-tab[data-status="assigned"]   .tab-count').text(c.assigned   || 0);
    $('.db-order-tab[data-status="unassigned"] .tab-count').text(c.unassigned || 0);
    $('.db-order-tab[data-status="pending"]    .tab-count').text(c.pending    || 0);
    $('.db-order-tab[data-status="complete"]   .tab-count').text(c.complete   || 0);
    $('.db-order-tab[data-status="cancel"]     .tab-count').text(c.cancel     || 0);
}

// ── Top States AJAX ──
function buildMiniCard(item, type) {
    const sub  = type === 'state' ? 'United States' : (item.company || item.name);
    const val1 = item.percentage + '%';
    const val2 = type === 'state' ? item.shipments : item.orders;
    const lbl2 = 'Orders';
    return `<div class="db-mini-card">
        <div class="db-mini-card-top">
            <span class="db-mini-card-avatar">${item.avatar}</span>
            <span class="db-mini-card-name">${item.name}</span>
        </div>
        <div class="db-mini-card-sub">${sub}</div>
        <div class="db-mini-card-stats">
            <div><div class="db-mini-card-stat-val">${val1}</div><div class="db-mini-card-stat-lbl">Share</div></div>
            <div><div class="db-mini-card-stat-val">${val2}</div><div class="db-mini-card-stat-lbl">${lbl2}</div></div>
        </div>
    </div>`;
}

$('#topStatesFilter').on('change', function() {
    const c = $('#topStatesContainer');
    c.html('<div class="db-skeleton mb-2"></div><div class="db-skeleton mb-2"></div><div class="db-skeleton"></div>');
    $.get('{{ route("dashboard.topStates.data") }}', { period: $(this).val() })
    .done(res => {
        c.empty();
        if (res.topStates && res.topStates.length) {
            res.topStates.forEach(s => c.append(buildMiniCard(s, 'state')));
        } else {
            c.html('<div style="text-align:center;padding:20px;opacity:.4;font-size:13px;font-family:\'Jost\',sans-serif;">No state data for this period</div>');
        }
    });
});

$('#topCustomersFilter').on('change', function() {
    const c = $('#topCustomersContainer');
    c.html('<div class="db-skeleton mb-2"></div><div class="db-skeleton mb-2"></div><div class="db-skeleton"></div>');
    $.get('{{ route("dashboard.topCustomers.data") }}', { period: $(this).val() })
    .done(res => {
        c.empty();
        if (res.topCustomers && res.topCustomers.length) {
            res.topCustomers.forEach(cu => c.append(buildMiniCard(cu, 'customer')));
        } else {
            c.html('<div style="text-align:center;padding:20px;opacity:.4;font-size:13px;font-family:\'Jost\',sans-serif;">No customer data for this period</div>');
        }
    });
});

// ── Live Loads Count (stat card) ─────────────────────────────────────────────
$.get('{{ route("dashboard.loadboard.loads") }}', { origin: 'Dallas, TX', pages: 2 })
    .done(function(res) {
        if (res.success) { $('#dbLiveLoadsCount').text(res.total); $('#dbLiveLoadsCount2').text(res.total); }
    });
</script>

@endsection
