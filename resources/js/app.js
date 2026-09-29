import $ from 'jquery';

window.$ = $;
window.jQuery = $;

// Bootstrap
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Alpine
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ====================================================// jQuery Plugins
// ====================================================
import 'jquery-validation';
import 'jquery-form';

// ====================================================// NioApp
// ====================================================//
// DashLite menggunakan NioApp sebagai global object.
// Kita membuat object ini terlebih dahulu agar main.js// tidak gagal dengan error "NioApp is not defined".
//

window.NioApp = window.NioApp || {
    Package: {},
    TGL: {},
    Ani: {},
    State: {},
    Break: {},
    Win: {},
    coms: {
        docReady: []
    }
};

// ====================================================// Basic NioApp configuration
// ====================================================

window.NioApp.Package = window.NioApp.Package || {};

window.NioApp.Break = {
    xs: 0,
    sm: 576,
    md: 768,
    lg: 992,
    xl: 1200,
    xxl: 1400
};

window.NioApp.Win = {
    width: window.innerWidth,
    height: window.innerHeight
};

window.NioApp.State = {
    isMobile: window.innerWidth < 992,
    isRTL: document.documentElement.dir === 'rtl',
    asMobile: window.innerWidth < 992
};

window.NioApp.coms = window.NioApp.coms || {
    docReady: []
};

// ===================================================
// Utility methods required by DashLite
// ===================================================

if (!$.fn.exists) {
    $.fn.exists = function () {
        return this.length > 0;
    };
}

window.NioApp.AddInBody = function (className) {
    document.body.classList.add(className);
};

window.NioApp.BreakClass = function (selector, breakpoint, options = {}) {
    const elements = document.querySelectorAll(selector);

    elements.forEach((element) => {
        const shouldAdd = window.innerWidth < breakpoint;

        if (shouldAdd) {
            if (options.classAdd) {
                element.classList.add(options.classAdd);
            }
        } else {
            if (options.classAdd) {
                element.classList.remove(options.classAdd);
            }
        }
    });
};

// ===================================================
// Simple Toggle implementation
// ===================================================

window.NioApp.Toggle = window.NioApp.Toggle || {

    trigger(target, options = {}) {
        const content = document.querySelector(
            `[data-content="${target}"]`
        );

        const toggle = document.querySelector(
            `[data-target="${target}"]`
        );

        if (!content) {
            return;
        }

        if (options.content) {
            content.classList.toggle(options.content);
        }

        if (toggle && options.active) {
            toggle.classList.toggle(options.active);
        }

        if (options.body) {
            document.body.classList.toggle(options.body);
        }

        if (options.overlay) {
            let overlay = document.querySelector(
                `.${options.overlay}`
            );

            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = options.overlay;

                document.body.appendChild(overlay);

                overlay.addEventListener('click', () => {
                    window.NioApp.Toggle.removed(
                        target,
                        options
                    );
                });
            }
        }
    },

    removed(target, options = {}) {
        const content = document.querySelector(
            `[data-content="${target}"]`
        );

        const toggle = document.querySelector(
            `[data-target="${target}"]`
        );

        if (content && options.content) {
            content.classList.remove(options.content);
        }

        if (toggle && options.active) {
            toggle.classList.remove(options.active);
        }

        if (options.body) {
            document.body.classList.remove(options.body);
        }

        if (options.overlay) {
            const overlay = document.querySelector(
                `.${options.overlay}`
            );

            if (overlay) {
                overlay.remove();
            }
        }
    },

    dropMenu(toggle, options = {}) {
        const parent = toggle.closest('.nk-menu-item');

        if (!parent) {
            return;
        }

        const submenu = parent.querySelector(':scope > .nk-menu-sub');

        if (!submenu) {
            return;
        }

        const isOpen = parent.classList.contains(options.active);

        document
            .querySelectorAll('.nk-menu-item.has-sub')
            .forEach((item) => {
                if (item !== parent) {
                    item.classList.remove(options.active);

                    const child = item.querySelector(
                        ':scope > .nk-menu-sub'
                    );

                    if (child) {
                        child.style.display = 'none';
                    }
                }
            });

        if (isOpen) {
            parent.classList.remove(options.active);
            submenu.style.display = 'none';
        } else {
            parent.classList.add(options.active);
            submenu.style.display = 'block';
        }
    }
};

// ===================================================
// Load DashLite main.js
// ===================================================

import './main.js';

// ===================================================
// Sidebar fallback
// ===================================================
//
// Ini adalah fallback khusus sidebar.
// Jika JavaScript DashLite gagal menjalankan toggle,
// sidebar tetap dapat digunakan.
//

document.addEventListener('DOMContentLoaded', () => {

    // ------------------------------------------------
    // Sidebar mobile / open-close
    // ------------------------------------------------

    document
        .querySelectorAll('.nk-nav-toggle[data-target]')
        .forEach((button) => {

            button.addEventListener('click', (event) => {

                event.preventDefault();

                const target = button.dataset.target;

                const sidebar = document.querySelector(
                    `[data-content="${target}"]`
                );

                if (!sidebar) {
                    return;
                }

                sidebar.classList.toggle('nk-sidebar-active');
                button.classList.toggle('toggle-active');

                document.body.classList.toggle('nav-shown');

            });

        });

    // ------------------------------------------------    // Sidebar compact
    // ------------------------------------------------

    document
        .querySelectorAll('.nk-nav-compact[data-target]')
        .forEach((button) => {

            button.addEventListener('click', (event) => {

                event.preventDefault();

                const target = button.dataset.target;

                const sidebar = document.querySelector(
                    `[data-content="${target}"]`
                );

                if (!sidebar) {
                    return;
                }

                button.classList.toggle('compact-active');
                sidebar.classList.toggle('is-compact');

            });

        });

    // ------------------------------------------------    // Sidebar submenu
    // ------------------------------------------------
    document
        .querySelectorAll('.nk-menu-toggle')
        .forEach((toggle) => {

            toggle.addEventListener('click', (event) => {

                event.preventDefault();

                const parent = toggle.closest('.nk-menu-item');

                if (!parent) {
                    return;
                }

                const submenu = parent.querySelector(
                    ':scope > .nk-menu-sub'
                );

                if (!submenu) {
                    return;
                }

                const isOpen =
                    parent.classList.contains('active');

                // Tutup submenu lain
                document
                    .querySelectorAll('.nk-menu-item.has-sub')
                    .forEach((item) => {

                        if (item !== parent) {

                            item.classList.remove('active');

                            const child =
                                item.querySelector(
                                    ':scope > .nk-menu-sub'
                                );

                            if (child) {
                                child.style.display = 'none';
                            }

                        }

                    });

                if (isOpen) {

                    parent.classList.remove('active');

                    submenu.style.display = 'none';

                } else {

                    parent.classList.add('active');

                    submenu.style.display = 'block';

                }

            });

        });

    // ------------------------------------------------    // Close sidebar when clicking overlay
    // ------------------------------------------------
    document.addEventListener('click', (event) => {

        if (
            event.target.classList.contains(
                'nk-sidebar-overlay'
            )
        ) {

            document.body.classList.remove(
                'nav-shown'
            );

            document
                .querySelectorAll('.nk-sidebar')
                .forEach((sidebar) => {
                    sidebar.classList.remove(
                        'nk-sidebar-active'
                    );
                });

        }

    });

});
