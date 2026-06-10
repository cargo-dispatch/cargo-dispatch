// Page Loader
(function () {
    function hidePageLoader() {
        const pageLoader = document.getElementById('pageLoader');
        if (pageLoader && !pageLoader.classList.contains('hidden')) {
            pageLoader.classList.add('hidden');
            setTimeout(() => { pageLoader.style.display = 'none'; }, 300);
        }
    }
    document.addEventListener('DOMContentLoaded', hidePageLoader);
    window.addEventListener('load', hidePageLoader);
    setTimeout(hidePageLoader, 5000);
})();

// Custom Select Dropdown
document.addEventListener('DOMContentLoaded', function () {
    const selects = document.querySelectorAll('select.form-control');

    selects.forEach(select => {
        if (select.closest('.custom-select-overlay')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-overlay';
        wrapper.style.cssText = 'position: relative; display: block; width: 100% !important;';

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        select.style.cssText += 'appearance: none; -webkit-appearance: none; -moz-appearance: none; cursor: pointer; width: 100% !important; padding-right: 35px !important; box-sizing: border-box !important;';

        const arrow = document.createElement('div');
        arrow.className = 'custom-select-arrow';
        arrow.style.cssText = 'position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-color); z-index: 1;';
        arrow.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>';
        wrapper.appendChild(arrow);

        const customOptions = document.createElement('div');
        customOptions.className = 'custom-options-overlay';
        customOptions.style.cssText = 'position: absolute; top: 100%; left: 0; right: 0; background: var(--main-wrapper-bg); border: 1px solid var(--chart-grid); border-radius: 4px; display: none; z-index: 9999; width: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 200px; overflow-y: auto; overflow-x: hidden;';

        Array.from(select.options).forEach(option => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'custom-option';
            optionDiv.textContent = option.text;
            optionDiv.dataset.value = option.value;
            optionDiv.style.cssText = 'padding: 10px 15px; cursor: pointer; transition: all 0.2s; background: var(--main-wrapper-bg); color: var(--text-color); border-bottom: 1px solid var(--chart-grid); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';

            optionDiv.addEventListener('mouseenter', function () { this.style.backgroundColor = '#f1c04e'; this.style.color = 'black'; });
            optionDiv.addEventListener('mouseleave', function () { this.style.backgroundColor = 'var(--main-wrapper-bg)'; this.style.color = 'var(--text-color)'; });
            optionDiv.addEventListener('click', function () {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                customOptions.style.display = 'none';
            });

            customOptions.appendChild(optionDiv);
        });

        wrapper.appendChild(customOptions);

        select.addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();

            document.querySelectorAll('.custom-options-overlay').forEach(d => {
                if (d !== customOptions) d.style.display = 'none';
            });

            const isVisible = customOptions.style.display === 'block';
            customOptions.style.display = isVisible ? 'none' : 'block';

            if (!isVisible) {
                const selectRect = select.getBoundingClientRect();
                customOptions.style.width = select.offsetWidth + 'px';
                const spaceBelow = window.innerHeight - selectRect.bottom;
                if (spaceBelow < 200 && spaceBelow < selectRect.top) {
                    customOptions.style.top = 'auto';
                    customOptions.style.bottom = '100%';
                    customOptions.style.maxHeight = Math.min(200, selectRect.top - 10) + 'px';
                } else {
                    customOptions.style.top = '100%';
                    customOptions.style.bottom = 'auto';
                    customOptions.style.maxHeight = Math.min(200, spaceBelow - 10) + 'px';
                }
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) customOptions.style.display = 'none';
        });

        select.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') customOptions.style.display = 'none';
        });
    });
});

// Hamburger Menu Toggle
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggleTop');
    if (!sidebarToggle) return;

    sidebarToggle.innerHTML = '';
    const hamburger = document.createElement('div');
    hamburger.className = 'hamburger';
    hamburger.innerHTML = '<span></span><span></span><span></span><span></span>';
    sidebarToggle.appendChild(hamburger);

    function updateHamburgerState() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        if (sidebar.classList.contains('toggled')) {
            hamburger.classList.remove('active');
        } else {
            hamburger.classList.add('active');
        }
    }

    updateHamburgerState();

    sidebarToggle.addEventListener('click', function (e) {
        e.preventDefault();
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.toggle('toggled');
        document.body.classList.toggle('sidebar-toggled');
        updateHamburgerState();
    });

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth < 768 && sidebar && !sidebar.classList.contains('toggled')) {
                sidebar.classList.add('toggled');
                document.body.classList.add('sidebar-toggled');
            }
            updateHamburgerState();
        }, 250);
    });

    const desktopToggle = document.getElementById('sidebarToggle');
    if (desktopToggle) {
        desktopToggle.addEventListener('click', function () {
            setTimeout(updateHamburgerState, 100);
        });
    }
});

// Navbar Dropdown Positioning
document.addEventListener('DOMContentLoaded', function () {
    const dropdownButton = document.getElementById('dropdownMenuButton');
    if (!dropdownButton) return;

    dropdownButton.removeAttribute('data-bs-toggle');
    const dropdownMenu = dropdownButton.nextElementSibling;
    let isOpen = false;

    dropdownButton.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (isOpen) {
            dropdownMenu.classList.remove('show');
            dropdownButton.setAttribute('aria-expanded', 'false');
            isOpen = false;
            return;
        }

        const buttonRect = this.getBoundingClientRect();
        const spaceBelow = window.innerHeight - buttonRect.bottom;

        dropdownMenu.style.position = 'absolute';
        dropdownMenu.style.top = '';
        dropdownMenu.style.bottom = '';
        dropdownMenu.style.left = '0';
        dropdownMenu.style.transform = '';

        if (spaceBelow < 100) {
            dropdownMenu.style.bottom = '100%';
            dropdownMenu.style.marginBottom = '8px';
        } else {
            dropdownMenu.style.top = '100%';
            dropdownMenu.style.marginTop = '8px';
        }

        dropdownMenu.classList.add('show');
        dropdownButton.setAttribute('aria-expanded', 'true');
        isOpen = true;
    });

    document.addEventListener('click', function (e) {
        if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.remove('show');
            dropdownButton.setAttribute('aria-expanded', 'false');
            isOpen = false;
        }
    });
});
