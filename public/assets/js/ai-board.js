/* AI Load Board — all logic lives here, config vars injected by blade */

/* global AI_BOARD_CONFIG */
const BASE       = AI_BOARD_CONFIG.base;
const CSRF_TOKEN = AI_BOARD_CONFIG.csrf;
const ROUTES     = AI_BOARD_CONFIG.routes;

let lbLoads      = [];
let lbFiltered   = [];
let lbPage       = 1;
let lbActiveMatch = null;
let lbPer        = 10;

function getLbPer() {
    return parseInt($('#lbPerPageSelect').val()) || 10;
}

$(document).ready(function () {

    $('#refreshLoadsBtn').on('click', function () {
        $(this).find('i').addClass('fa-spin');
        setTimeout(() => $(this).find('i').removeClass('fa-spin'), 600);
        loadFreightFinderLoads();
        loadRateIntelligence();
    });

    // FreightFinder AI Match is triggered via lbClickAiMatch() inline onclick (see lbRenderPage)

    // Assign internal load (existing shipment in DB)
    $(document).on('click', '.ai-assign-btn[data-shipment-id]:not([data-lb-idx]):not(:disabled)', function () {
        const $btn       = $(this);
        const shipmentId = $btn.data('shipment-id');
        const vehicleId  = $btn.data('vehicle-id');
        const driverId   = $btn.data('driver-id');
        const unit       = $btn.data('unit');
        const driverName = $btn.data('driver');

        if (!shipmentId || !vehicleId) { alert('Missing shipment or vehicle ID.'); return; }

        $btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-1"></i> Assigning...');

        $.ajax({
            url        : BASE + '/assign',
            method     : 'POST',
            contentType: 'application/json',
            headers    : { 'X-CSRF-TOKEN': CSRF_TOKEN },
            data       : JSON.stringify({ shipment_id: shipmentId, vehicle_id: vehicleId, driver_id: driverId || null }),
        })
        .done(function (res) {
            if (res.success) {
                $btn.addClass('assigned').html('<i class="fas fa-check me-1"></i> Assigned!');
                $btn.closest('.ai-results-wrap').find('.ai-assign-btn').not($btn).prop('disabled', true);
                showToast((driverName && driverName !== 'Unassigned') ? unit + ' assigned to ' + driverName : unit + ' assigned successfully');
            } else {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Assign This Load');
                alert('Assignment failed: ' + (res.message || 'Unknown error'));
            }
        })
        .fail(function (xhr) {
            $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Assign This Load');
            alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Server error'));
        });
    });

    // Assign external FreightFinder load (creates shipment + assigns)
    $(document).on('click', '.ai-assign-btn[data-lb-idx]:not(:disabled)', function () {
        const $btn     = $(this);
        const lbIdx    = parseInt($btn.data('lb-idx'), 10);
        const vehicleId = $btn.data('vehicle-id');
        const driverId  = $btn.data('driver-id');
        const unit      = $btn.data('unit');
        const driverName = $btn.data('driver');
        const load      = lbLoads[lbIdx];

        if (!load || !vehicleId) { alert('Missing load or vehicle data.'); return; }

        $btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-1"></i> Booking...');

        $.ajax({
            url        : BASE + '/assign-external',
            method     : 'POST',
            contentType: 'application/json',
            headers    : { 'X-CSRF-TOKEN': CSRF_TOKEN },
            data       : JSON.stringify({
                vehicle_id: vehicleId,
                driver_id : driverId || null,
                load: {
                    origin          : load.origin  || '',
                    destination     : load.destination || '',
                    equipment       : load.equipment || '',
                    date            : load.date || '',
                    company         : load.company || '',
                    external_load_id: load.id || '',
                },
            }),
        })
        .done(function (res) {
            if (res.success) {
                $btn.addClass('assigned').html('<i class="fas fa-check me-1"></i> Booked!');
                $btn.closest('.ai-results-wrap').find('.ai-assign-btn').not($btn).prop('disabled', true);
                showToast((driverName && driverName !== 'Unassigned') ? unit + ' booked → ' + driverName : unit + ' booked successfully');
            } else {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Book This Load');
                alert('Booking failed: ' + (res.message || 'Unknown error'));
            }
        })
        .fail(function (xhr) {
            $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Book This Load');
            alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Server error'));
        });
    });

    // FreightFinder controls
    $('#lbModalClose, #lbModal').on('click', function (e) { if (e.target === this) $('#lbModal').hide(); });
    $('#lbSearchBtn').on('click', loadFreightFinderLoads);
    $('#lbPagesSelect').on('change', loadFreightFinderLoads);
    $('#lbOriginInput').on('keypress', function (e) { if (e.which === 13) loadFreightFinderLoads(); });
    $('#lbPerPageSelect').on('change', function () { lbRenderPage(1); });
    $('#riRefreshBtn').on('click', loadRateIntelligence);

    // Filter by type + text (uses same origin input; Search button re-fetches)
    $('#lbOriginInput').on('input', lbApplyFilters);
    $('#lbFilterType').on('change', function () {
        $('#lbOriginInput').val('').attr('placeholder', 'Filter by ' + $(this).find('option:selected').text() + '...');
        lbFiltered = lbLoads;
        lbRenderPage(1);
    });

    loadFreightFinderLoads();
    loadRateIntelligence();
});

// ── FreightFinder AI Match ────────────────────────────────────────────────────

function lbClickAiMatch(idx) {
    console.log('lbClickAiMatch fired, idx:', idx, 'lbLoads.length:', lbLoads.length);
    var load = lbLoads[idx];
    if (!load) { console.warn('No load at idx', idx); return; }

    var rowId       = 'lb-ai-row-' + idx;
    var alreadyOpen = lbActiveMatch === idx;

    $('.lb-ai-collapse-row').remove();
    $('.lb-ai-match-btn').removeClass('active');
    lbActiveMatch = null;
    if (alreadyOpen) return;

    lbActiveMatch = idx;
    $('[data-lb-idx="' + idx + '"] .lb-ai-match-btn').addClass('active');

    $('tr[data-lb-idx="' + idx + '"]').after(
        '<tr class="lb-ai-collapse-row" id="' + rowId + '">' +
        '<td colspan="10"><div class="ai-collapse-inner">' +
        '<div class="ai-collapse-header"><i class="fas fa-robot"></i> AI Fleet Match — ' +
        (load.origin || '?') + ' → ' + (load.destination || '?') +
        ' <span>' + (load.company || '') + '</span></div>' +
        '<div class="ai-loading"><i class="fas fa-circle-notch fa-spin"></i> Fetching fleet snapshot and running AI analysis...</div>' +
        '</div></td></tr>'
    );

    runLbAiMatch(load, idx, rowId);
}

function runLbAiMatch(load, lbIdx, rowId) {
    $.getJSON(BASE + '/eld-snapshot')
        .done(function (snapshot) {
            var trucks = (snapshot.trucks || []).slice(0, 10);
            if (!trucks.length) {
                setLbCollapseContent(rowId, '<div class="ai-empty-text"><i class="fas fa-truck me-1"></i> No trucks available in fleet.</div>');
                return;
            }
            var candidates = buildCandidates(trucks);
            var payload = {
                load: {
                    id              : lbIdx,
                    external_load_id: load.id || 'FF-' + lbIdx,
                    pickup_address  : load.origin || '',
                    drop_address    : load.destination || '',
                    distance_miles  : null,
                    rate_total_usd  : null,
                    weight_lbs      : null,
                    equipment       : load.equipment || '',
                    pickup_time     : load.date || null,
                    delivery_time   : null,
                },
                candidates: candidates,
            };
            postRank(payload, function (ranking) {
                setLbCollapseContent(rowId, renderAiCards(candidates, ranking, null, lbIdx));
            }, function () {
                setLbCollapseContent(rowId, '<div class="ai-error-text"><i class="fas fa-exclamation-circle me-1"></i> AI ranking failed.</div>');
            });
        })
        .fail(function () {
            setLbCollapseContent(rowId, '<div class="ai-error-text"><i class="fas fa-exclamation-circle me-1"></i> ELD snapshot failed.</div>');
        });
}

function buildCandidates(trucks) {
    return trucks.map(function (truck) {
        return {
            candidate_id                 : 'vehicle_' + truck.vehicle_id,
            unit_number                  : truck.unit_number,
            driver_name                  : truck.driver_name,
            driver_db_id                 : truck.driver_db_id,
            vehicle_db_id                : truck.vehicle_db_id,
            vehicle_type                 : truck.vehicle_type,
            vehicle_status               : truck.vehicle_status,
            license_plate                : truck.license_plate,
            equipment                    : truck.equipment,
            current_duty_status          : truck.current_duty_status,
            hos_drive_remaining_minutes  : truck.hos_drive_remaining_minutes,
            hos_on_duty_remaining_minutes: truck.hos_on_duty_remaining_minutes,
            current_lat                  : truck.lat  || null,
            current_lng                  : truck.lng  || null,
        };
    });
}

function postRank(payload, onDone, onFail) {
    $.ajax({
        url        : BASE + '/rank',
        method     : 'POST',
        contentType: 'application/json',
        headers    : { 'X-CSRF-TOKEN': CSRF_TOKEN },
        data       : JSON.stringify({ payload: payload }),
    })
    .done(function (res) { onDone(res.ranking || []); })
    .fail(onFail);
}

// ── AI cards renderer ─────────────────────────────────────────────────────────
// shipmentId: integer for internal loads, null for FreightFinder (uses lbIdx instead)

function renderAiCards(candidates, ranking, shipmentId, lbIdx) {
    if (!ranking.length) return '<div class="ai-empty-text">AI returned no ranking.</div>';

    var isExternal = (shipmentId == null);

    var byId = {};
    candidates.forEach(function (c) { byId[c.candidate_id] = c; });

    var html = '<div class="ai-cards-wrap">';

    ranking.forEach(function (row, idx) {
        var c      = byId[row.candidate_id] || {};
        var score  = row.score != null ? row.score : 0;
        var reason = row.reason || '';
        var isTop  = idx === 0;

        var badgeColor  = score >= 70 ? '#28a745' : score >= 40 ? '#F8C71F' : '#dc3545';
        var badgeText   = score >= 70 ? '#fff' : '#000';
        var statusColor = c.vehicle_status === 'available' ? '#28a745' : c.vehicle_status === 'busy' ? '#F8C71F' : '#dc3545';

        var driverLabel = (c.driver_name && c.driver_name !== 'Unassigned')
            ? '<span class="ai-pill"><i class="fas fa-user ai-pill-icon-accent"></i> ' + c.driver_name + '</span>'
            : '<span class="ai-pill ai-pill-danger"><i class="fas fa-user-slash"></i> No Driver</span>';

        var canAssign   = c.vehicle_status !== 'busy' && c.driver_db_id;
        var assignLabel = c.vehicle_status === 'busy'
            ? '<i class="fas fa-ban me-1"></i> Vehicle Busy'
            : !c.driver_db_id
                ? '<i class="fas fa-user-slash me-1"></i> No Driver'
                : isExternal
                    ? '<i class="fas fa-check me-1"></i> Book This Load'
                    : '<i class="fas fa-check me-1"></i> Assign This Load';

        var assignAttrs = isExternal
            ? 'data-lb-idx="' + lbIdx + '"'
            : 'data-shipment-id="' + shipmentId + '"';

        html += '<div class="ai-card-item' + (isTop ? ' ai-top-pick' : '') + '">' +
            (isTop ? '<span class="ai-top-badge">✦ AI TOP PICK</span>' : '') +
            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                '<span class="ai-card-title">' + (idx + 1) + '. ' + (c.unit_number || row.candidate_id) + '</span>' +
                '<span class="ai-score-badge" style="background:' + badgeColor + ';color:' + badgeText + ';">' + score + '</span>' +
            '</div>' +
            '<div class="ai-meta">' +
                '<i class="fas fa-truck me-1 ai-icon-accent"></i><strong>' + (c.vehicle_type || 'Unknown') + '</strong>' +
                ' &nbsp;·&nbsp; <span class="ai-status-dot" style="background:' + statusColor + ';"></span> ' + cap(c.vehicle_status || 'unknown') +
                ' &nbsp;·&nbsp; <i class="fas fa-tools" style="font-size:10px;"></i> ' + (c.equipment || '-') +
            '</div>' +
            '<div class="ai-pills-wrap">' +
                driverLabel +
                '<span class="ai-pill"><i class="fas fa-circle ai-pill-dot" style="color:' + statusColor + ';"></i> ' + cap(c.current_duty_status || 'unknown') + '</span>' +
                '<span class="ai-pill"><i class="fas fa-location-dot ai-pill-icon-accent"></i> ' + (c.distance_to_pickup_miles != null ? c.distance_to_pickup_miles : '-') + ' mi</span>' +
                '<span class="ai-pill"><i class="fas fa-clock ai-pill-icon-accent"></i> ' + hosLabel(c.hos_drive_remaining_minutes) + ' HOS</span>' +
                (c.license_plate ? '<span class="ai-pill"><i class="fas fa-id-card"></i> ' + c.license_plate + '</span>' : '') +
            '</div>' +
            '<div class="ai-reason"><i class="fas fa-robot ai-robot-icon"></i>' + reason + '</div>' +
            '<button class="ai-assign-btn" ' +
                assignAttrs + ' ' +
                'data-vehicle-id="' + (c.vehicle_db_id || '') + '" ' +
                'data-driver-id="' + (c.driver_db_id || '') + '" ' +
                'data-unit="' + (c.unit_number || '') + '" ' +
                'data-driver="' + (c.driver_name || '') + '" ' +
                (!canAssign ? 'disabled' : '') + '>' +
                assignLabel +
            '</button>' +
        '</div>';
    });

    return html + '</div>';
}

// ── Rate Intelligence ─────────────────────────────────────────────────────────

function loadRateIntelligence() {
    var origin = $('#lbOriginInput').val().trim() || 'Dallas, TX';
    $('#riStatus').text('Analysing...');
    $('#riContent').html('<div class="text-center p-3 text-muted"><span class="loading-spinner"></span> Fetching lane data &amp; running Groq analysis...</div>');

    $.get(ROUTES.rateIntelligence, { origin: origin, pages: 4 })
        .done(function (res) {
            $('#riStatus').text('');
            if (!res.success || !res.lanes || !res.lanes.length) {
                $('#riContent').html('<div class="ai-empty-text p-2">No lane data available.</div>');
                return;
            }
            renderRateIntelligence(res.lanes);
        })
        .fail(function () {
            $('#riStatus').text('');
            $('#riContent').html('<div class="ai-error-text p-2"><i class="fas fa-exclamation-circle me-1"></i> Failed to load rate intelligence.</div>');
        });
}

function renderRateIntelligence(lanes) {
    var html = '<div class="table-responsive"><table class="custom-table sidebar-wrapper mb-0 ri-table">' +
        '<thead><tr>' +
        '<th>Lane</th><th class="text-center">Loads</th>' +
        '<th>AI Recommendation</th>' +
        '</tr></thead><tbody>';

    lanes.forEach(function (lane) {
        var text = lane.insight || '<span class="text-muted" style="font-size:11px;">No AI insight available</span>';
        var tl   = (lane.insight || '').toLowerCase();
        var badgeClass = 'ri-badge ri-badge-negotiate';
        var badgeLabel = 'NEGOTIATE';
        if (tl.includes('take') || tl.includes('good') || tl.includes('great') || tl.includes('accept') || tl.includes('strong')) {
            badgeClass = 'ri-badge ri-badge-take'; badgeLabel = 'TAKE IT';
        } else if (tl.includes('wait') || tl.includes('hold') || tl.includes('avoid') || tl.includes('low demand')) {
            badgeClass = 'ri-badge ri-badge-hold'; badgeLabel = 'HOLD';
        }
        var badge = lane.insight ? '<span class="' + badgeClass + '">' + badgeLabel + '</span> ' : '';

        html += '<tr>' +
            '<td class="ri-lane-cell">' + lane.lane + '</td>' +
            '<td class="text-center"><span class="region-badge">' + lane.load_count + '</span></td>' +
            '<td>' + badge + '<span class="ri-insight-text">' + text + '</span></td>' +
            '</tr>';
    });

    $('#riContent').html(html + '</tbody></table></div>');
}

// ── FreightFinder load board ──────────────────────────────────────────────────

function lbRenderPage(page) {
    lbPage = page;
    var perPage  = getLbPer();
    var total    = lbFiltered.length;
    var lastPage = Math.ceil(total / perPage);
    var start    = (page - 1) * perPage;
    var slice    = lbFiltered.slice(start, start + perPage);

    var html = '';
    slice.forEach(function (l, j) {
        var i     = start + j;
        var phone = l.phone
            ? '<a href="tel:' + l.phone + '" class="lb-phone" onclick="event.stopPropagation();">' + l.phone + '</a>'
            : '<span class="lb-empty">—</span>';
        var equip = l.equipment
            ? '<span class="region-badge">' + l.equipment + '</span>'
            : '<span class="lb-empty">—</span>';

        var origParts  = (l.origin      || '').split(',');
        var destParts  = (l.destination || '').split(',');
        var origCity   = origParts[0] ? origParts[0].trim() : '—';
        var origState  = origParts[1] ? origParts[1].trim() : '—';
        var destCity   = destParts[0] ? destParts[0].trim() : '—';
        var destState  = destParts[1] ? destParts[1].trim() : '—';

        html += '<tr class="lb-row" onclick="lbShowDetail(' + i + ')" data-lb-idx="' + i + '">' +
            '<td class="lb-date-cell">' + (l.date || '—') + '</td>' +
            '<td class="lb-bold">' + origCity + '</td>' +
            '<td><span class="region-badge">' + origState + '</span></td>' +
            '<td class="lb-company" title="' + (l.destination || '') + '">' + destCity + '</td>' +
            '<td><span class="region-badge">' + destState + '</span></td>' +
            '<td class="lb-company" title="' + (l.company || '') + '">' + (l.company || '—') + '</td>' +
            '<td>' + phone + '</td>' +
            '<td>' + equip + '</td>' +
            '<td onclick="event.stopPropagation();"><button class="lb-ai-match-btn" data-lb-idx="' + i + '" onclick="event.stopPropagation(); lbClickAiMatch(' + i + ');"><i class="fas fa-robot me-1"></i> AI Match</button></td>' +
            '<td class="lb-eye"><i class="bi bi-eye"></i></td>' +
            '</tr>';
    });

    $('#lbBody').html(html || '<tr><td colspan="10" class="text-center p-3 text-muted">No loads found.</td></tr>');

    var from = start + 1, to = Math.min(start + perPage, total);
    $('#lbPageInfo').text('Showing ' + from + '–' + to + ' of ' + total + ' loads');

    var btn = function (label, onclick, disabled) {
        return '<button class="btn btn-sm btn-outline-secondary" onclick="' + onclick + '" ' + (disabled ? 'disabled' : '') + '>' + label + '</button>';
    };
    var abtn = function (label, onclick) {
        return '<button class="btn btn-sm theme-btn" onclick="' + onclick + '">' + label + '</button>';
    };

    var p = btn('«', 'lbRenderPage(1)', page === 1) + btn('‹', 'lbRenderPage(' + (page - 1) + ')', page === 1);
    var delta = 2;
    for (var pg = Math.max(1, page - delta); pg <= Math.min(lastPage, page + delta); pg++) {
        p += (pg === page ? abtn(pg, 'lbRenderPage(' + pg + ')') : btn(pg, 'lbRenderPage(' + pg + ')', false));
    }
    p += btn('›', 'lbRenderPage(' + (page + 1) + ')', page === lastPage) +
         btn('»', 'lbRenderPage(' + lastPage + ')', page === lastPage);
    $('#lbPagination').html(p);
}

function lbBuildFilters() {
    $('#lbFilterType').val('all');
    $('#lbOriginInput').val('').attr('placeholder', 'Filter loaded results...');
    $('#lbFilterType').show();
}

function lbApplyFilters() {
    if (!lbLoads.length) return;
    var type = $('#lbFilterType').val();
    var q    = $('#lbOriginInput').val().trim().toLowerCase();

    if (!q) { lbFiltered = lbLoads; lbRenderPage(1); return; }

    lbFiltered = lbLoads.filter(function (l) {
        var oState = (l.origin      || '').split(',')[1] ? l.origin.split(',')[1].trim().toLowerCase()      : '';
        var dState = (l.destination || '').split(',')[1] ? l.destination.split(',')[1].trim().toLowerCase() : '';
        switch (type) {
            case 'originState': return oState.includes(q);
            case 'destState':   return dState.includes(q);
            case 'company':     return (l.company   || '').toLowerCase().includes(q);
            case 'equipment':   return (l.equipment || '').toLowerCase().includes(q);
            case 'phone':       return (l.phone     || '').toLowerCase().includes(q);
            default:
                return (l.origin      || '').toLowerCase().includes(q) ||
                       (l.destination || '').toLowerCase().includes(q) ||
                       (l.company     || '').toLowerCase().includes(q) ||
                       (l.equipment   || '').toLowerCase().includes(q) ||
                       (l.phone       || '').toLowerCase().includes(q);
        }
    });

    lbRenderPage(1);
}

function loadFreightFinderLoads() {
    $('#lbOriginInput').attr('placeholder', 'Origin city, state');
    $('#lbFilterType').hide().val('all');
    var origin = $('#lbOriginInput').val().trim() || 'Dallas, TX';
    var pages  = $('#lbPagesSelect').val();
    $('#lbStatus').text('Fetching...');
    $('#lbPageInfo').text('');
    $('#lbPagination').html('');
    $('#lbBody').html('<tr><td colspan="10" class="text-center p-4"><span class="loading-spinner"></span> Scraping up to ' + (parseInt(pages) * 25) + ' live loads...</td></tr>');

    $.get(ROUTES.loadboardLoads, { origin: origin, pages: pages })
        .done(function (res) {
            if (!res.success || !res.loads.length) {
                $('#lbBody').html('<tr><td colspan="10" class="text-center p-3 text-muted">No loads found. Try a different origin.</td></tr>');
                $('#lbStatus').text('');
                return;
            }
            lbLoads    = res.loads;
            lbFiltered = res.loads;
            $('#lbStatus').text(res.total + ' loads');
            lbBuildFilters();
            lbRenderPage(1);
        })
        .fail(function () {
            $('#lbBody').html('<tr><td colspan="10" class="text-center p-3 text-danger">Failed to fetch loads.</td></tr>');
            $('#lbStatus').text('');
        });
}

function lbShowDetail(i) {
    var l = lbLoads[i];
    if (!l) return;

    var renderModal = function (data) {
        var phone = data.phone
            ? '<a href="tel:' + data.phone + '" class="lb-modal-phone">' + data.phone + '</a>' : null;
        var rate = (data.rate && data.rate !== '$0.00') ? data.rate : null;

        var detailRow = function (label, val, accent) {
            if (!val) return '';
            return '<div class="lb-detail-row">' +
                '<span class="lb-detail-label">' + label + '</span>' +
                '<span class="' + (accent ? 'lb-detail-value-accent' : 'lb-detail-value') + '">' + val + '</span>' +
                '</div>';
        };

        $('#lbModalBody').html(
            '<div class="mb-3">' +
                '<div class="lb-modal-company">' + (data.company || l.company || 'Unknown Company') + '</div>' +
                '<div class="lb-modal-address">' + (data.address || '') + '</div>' +
            '</div>' +
            '<div class="lb-modal-route">' +
                '<div><div class="lb-modal-route-label">Origin</div><div class="lb-modal-route-value">' + (l.origin || '—') + '</div></div>' +
                '<div class="lb-modal-arrow">→</div>' +
                '<div><div class="lb-modal-route-label">Destination</div><div class="lb-modal-route-value">' + (l.destination || '—') + '</div></div>' +
            '</div>' +
            '<div>' +
                detailRow('Date', l.date) +
                detailRow('Phone', phone) +
                detailRow('Equipment', data.equipment || l.equipment) +
                detailRow('Weight', data.weight ? data.weight + ' lbs' : null) +
                detailRow('Rate', rate, true) +
                detailRow('Address', data.address) +
            '</div>' +
            (data.phone ? '<div class="mt-3"><a href="tel:' + data.phone + '" class="btn btn-sm theme-btn"><i class="bi bi-telephone-fill me-1"></i> Call Now</a></div>' : '')
        );
    };

    $('#lbModalBody').html('<div class="text-center p-4"><span class="loading-spinner"></span> Loading details...</div>');
    $('#lbModal').css('display', 'flex');

    $.get(ROUTES.loadboardDetail, { id: l.id })
        .done(function (res) { res.success ? renderModal(res.detail) : renderModal(l); })
        .fail(function () { renderModal(l); });
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function setLbCollapseContent(rowId, html) {
    var $inner = $('#' + rowId + ' .ai-collapse-inner');
    $inner.find('.ai-loading').remove();
    $inner.find('.ai-results-wrap').remove();
    $inner.append('<div class="ai-results-wrap">' + html + '</div>');
}

function hosLabel(minutes) {
    var m = parseInt(minutes || 0, 10);
    var h = Math.floor(m / 60);
    var r = m % 60;
    return h <= 0 ? r + 'm' : h + 'h ' + r + 'm';
}

function cap(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
}

function showToast(msg) {
    $('#assignToastMsg').text(msg);
    $('#assignToast').fadeIn(200);
    setTimeout(function () { $('#assignToast').fadeOut(400); }, 4000);
}
