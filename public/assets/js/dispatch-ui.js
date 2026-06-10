// Badge count update with animation
function updateBadgeCounts(counts) {
    const badges = {
        'pending':  document.querySelector('.pending-count'),
        'active':   document.querySelector('.active-count'),
        'complete': document.querySelector('.complete-count'),
        'cancel':   document.querySelector('.cancel-count')
    };

    for (const [status, badge] of Object.entries(badges)) {
        if (badge && counts[status] !== undefined) {
            badge.classList.add('updating');
            badge.textContent = counts[status];
            setTimeout(() => badge.classList.remove('updating'), 300);
        }
    }
}

// Tab click handlers
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('#shipmentTabs .nav-link');

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const status = this.dataset.status;
            if (typeof loadDataForStatus === 'function') loadDataForStatus(status);
        });
    });
});

// Local date/time display
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('localDateTime');
    if (!el) return;
    const options = {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: true
    };
    el.textContent = new Date().toLocaleString(undefined, options);
});
