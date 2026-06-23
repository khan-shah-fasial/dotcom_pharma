(function () {
    'use strict';

    var toggle = document.getElementById('auto-menu-close-toggle');
    var stateLabel = document.getElementById('auto-menu-close-state');

    if (!toggle || !stateLabel) {
        return;
    }

    var storageKey = toggle.getAttribute('data-storage-key') || 'backend_auto_menu_close';

    function isEnabled() {
        try {
            return window.localStorage.getItem(storageKey) === 'on';
        } catch (error) {
            return false;
        }
    }

    function setMenuClosed() {
        document.body.classList.add('side-menu-closed');
        document.body.classList.remove('side-menu-open');
    }

    function renderState() {
        var enabled = isEnabled();
        stateLabel.textContent = enabled
            ? toggle.getAttribute('data-on-label')
            : toggle.getAttribute('data-off-label');
        toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        toggle.classList.toggle('btn-success', enabled);
        toggle.classList.toggle('btn-soft-light', !enabled);

    }

    toggle.addEventListener('click', function () {
        try {
            window.localStorage.setItem(storageKey, isEnabled() ? 'off' : 'on');
        } catch (error) {
            return;
        }

        renderState();
    });

    document.addEventListener('click', function (event) {
        if (!isEnabled()) {
            return;
        }

        var link = event.target.closest('.aiz-sidebar a.aiz-side-nav-link');
        if (!link || !link.href || link.getAttribute('href') === '#') {
            return;
        }

        setMenuClosed();
    });

    renderState();

    if (isEnabled()) {
        setMenuClosed();
    }
})();
