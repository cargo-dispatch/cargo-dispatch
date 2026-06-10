// assets/js/theme-toggle.js
// Ultra-fast and simple version

(function() {
    'use strict';
    
    const html = document.documentElement;
    
    // Apply theme instantly
    const storedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', storedTheme);
    localStorage.setItem('theme', storedTheme);
    
    // Wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        const btn = document.querySelector('.toggle-theme-btn');
        if (!btn) return;
        
        // Apply UI immediately
        updateUI(html.getAttribute('data-theme'));
        
        // Click handler
        btn.onclick = function(e) {
            e.preventDefault();
            
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            
            // Update everything instantly
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateUI(newTheme);
            
            // Save to server (don't wait)
            saveToServer(newTheme);
            
            // Event for charts
            document.dispatchEvent(new CustomEvent('themeChanged', { 
                detail: { theme: newTheme } 
            }));
        };
    }
    
    function updateUI(theme) {
        const container = document.querySelector('.theme-toggle-container');
        const text = document.querySelector('.theme-mode-text');
        const sun = document.querySelector('.sun-icon');
        const moon = document.querySelector('.moon-icon');
        const knob = document.querySelector('.theme-toggle-knob');
        const track = document.querySelector('.theme-toggle-track');
        
        if (!container) return;
        
        const isDark = theme === 'dark';
        
        // Update container
        if (isDark) {
            container.className = 'theme-toggle-container position-relative active-dark';
        } else {
            container.className = 'theme-toggle-container position-relative active-light';
        }
        
        // Update track
        if (track) {
            if (isDark) {
                track.className = 'theme-toggle-track position-absolute w-100 h-100 rounded-pill active-dark';
            } else {
                track.className = 'theme-toggle-track position-absolute w-100 h-100 rounded-pill active-light';
            }
        }
        
        // FIXED: Update text - show what you can switch TO
        // When in dark mode, show "Light Mode" (you can switch to light)
        // When in light mode, show "Dark Mode" (you can switch to dark)
        if (text) {
            text.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
        
        // Update knob - instant position change
        if (knob) {
            knob.style.left = isDark ? '22px' : '0px';
        }
        
        // Update icons
        // DARK MODE: Show SUN (you can switch TO light)
        // LIGHT MODE: Show MOON (you can switch TO dark)
        if (sun && moon) {
            if (isDark) {
                // Dark mode - show sun icon on right (switch to light)
                sun.style.cssText = 'width: 20px; height: 20px; left: 22px; top: 1px; opacity: 1; visibility: visible; transform: translateX(0) scale(1); transition: all 0.15s ease; z-index: 2; background-color: #f8c71f; border-radius: 50%; padding: 2px; position: absolute;';
                moon.style.cssText = 'width: 20px; height: 20px; left: 0px; top: 1px; opacity: 0; visibility: hidden; transform: translateX(0) scale(0.8); transition: all 0.15s ease; z-index: 2; background-color: #343a40; border-radius: 50%; padding: 2px; position: absolute;';
            } else {
                // Light mode - show moon icon on left (switch to dark)
                sun.style.cssText = 'width: 20px; height: 20px; left: 22px; top: 1px; opacity: 0; visibility: hidden; transform: translateX(0) scale(0.8); transition: all 0.15s ease; z-index: 2; background-color: #f8c71f; border-radius: 50%; padding: 2px; position: absolute;';
                moon.style.cssText = 'width: 20px; height: 20px; left: 0px; top: 1px; opacity: 1; visibility: visible; transform: translateX(0) scale(1); transition: all 0.15s ease; z-index: 2; background-color: #343a40; border-radius: 50%; padding: 2px; position: absolute;';
            }
        }
    }
    
    function saveToServer(theme) {
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (!csrf) return;
        
        const url = window.THEME_ROUTE || (window.APP_URL ? window.APP_URL.replace(/\/$/, '') + '/theme' : '/theme');
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf.getAttribute('content')
            },
            body: JSON.stringify({ theme: theme })
        }).catch(err => console.error('Theme save error:', err));
    }
    
})();