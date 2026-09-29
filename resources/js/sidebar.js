"use strict";

/*
|------------------------------------------------------| Sidebar Controller
|-----------------------------------------------------
|
| Controller khusus untuk sidebar Laravel E-Commerce.
|
| Mendukung:
|
| - Mobile sidebar open / close
| - Desktop compact sidebar
| - Submenu open / close
| - Overlay
| - ESC untuk menutup sidebar
|
*/

document.addEventListener("DOMContentLoaded", () => {

    /*
    |--------------------------------------------------    | Helper
    |--------------------------------------------------    */

    const getSidebar = (target) => {
        if (!target) {
            return null;
        }

        return document.querySelector(
            `[data-content="${target}"]`
        );
    };

    /*
    |--------------------------------------------------    | Open / Close Mobile Sidebar
    |--------------------------------------------------    */

    document
        .querySelectorAll(".nk-nav-toggle[data-target]")
        .forEach((toggle) => {

            toggle.addEventListener("click", (event) => {

                event.preventDefault();

                const target = toggle.dataset.target;

                const sidebar = getSidebar(target);

                if (!sidebar) {
                    console.warn(
                        `Sidebar dengan target "${target}" tidak ditemukan.`
                    );

                    return;
                }

                const isOpen =
                    sidebar.classList.contains(
                        "nk-sidebar-active"
                    );

                if (isOpen) {

                    closeSidebar(
                        sidebar,
                        toggle
                    );

                } else {

                    openSidebar(
                        sidebar,
                        toggle
                    );

                }

            });

        });


    /*
    |--------------------------------------------------    | Open Sidebar
    |--------------------------------------------------    */

    function openSidebar(sidebar, toggle) {

        sidebar.classList.add(
            "nk-sidebar-active"
        );

        toggle.classList.add(
            "toggle-active"
        );

        document.body.classList.add(
            "nav-shown"
        );

        createOverlay();

    }


    /*
    |--------------------------------------------------    | Close Sidebar
    |--------------------------------------------------    */

    function closeSidebar(sidebar, toggle = null) {

        sidebar.classList.remove(
            "nk-sidebar-active"
        );

        if (toggle) {

            toggle.classList.remove(
                "toggle-active"
            );

        } else {

            document
                .querySelectorAll(
                    ".nk-nav-toggle"
                )
                .forEach((button) => {

                    button.classList.remove(
                        "toggle-active"
                    );

                });

        }

        document.body.classList.remove(
            "nav-shown"
        );

        removeOverlay();

    }


    /*
    |--------------------------------------------------    | Overlay
    |--------------------------------------------------    */

    function createOverlay() {

        let overlay = document.querySelector(
            ".nk-sidebar-overlay"
        );

        if (overlay) {
            return;
        }

        overlay = document.createElement("div");

        overlay.className =
            "nk-sidebar-overlay";

        document.body.appendChild(
            overlay
        );

        overlay.addEventListener(
            "click",
            () => {

                document
                    .querySelectorAll(
                        ".nk-sidebar"
                    )
                    .forEach((sidebar) => {

                        sidebar.classList.remove(
                            "nk-sidebar-active"
                        );

                    });

                document.body.classList.remove(
                    "nav-shown"
                );

                document
                    .querySelectorAll(
                        ".nk-nav-toggle"
                    )
                    .forEach((button) => {

                        button.classList.remove(
                            "toggle-active"
                        );

                    });

                removeOverlay();

            }
        );

    }


    /*
    |--------------------------------------------------    | Remove Overlay
    |--------------------------------------------------    */

    function removeOverlay() {

        const overlay =
            document.querySelector(
                ".nk-sidebar-overlay"
            );

        if (overlay) {
            overlay.remove();
        }

    }


    /*
    |--------------------------------------------------    | Compact Sidebar
    |--------------------------------------------------    */

    document
        .querySelectorAll(
            ".nk-nav-compact[data-target]"
        )
        .forEach((toggle) => {

            toggle.addEventListener(
                "click",
                (event) => {

                    event.preventDefault();

                    const target =
                        toggle.dataset.target;

                    const sidebar =
                        getSidebar(target);

                    if (!sidebar) {

                        console.warn(
                            `Sidebar dengan target "${target}" tidak ditemukan.`
                        );

                        return;

                    }

                    toggle.classList.toggle(
                        "compact-active"
                    );

                    sidebar.classList.toggle(
                        "is-compact"
                    );

                }
            );

        });


    /*
    |--------------------------------------------------    | Sidebar Submenu
    |--------------------------------------------------    */

    document
        .querySelectorAll(
            ".nk-menu-toggle"
        )
        .forEach((toggle) => {

            toggle.addEventListener(
                "click",
                (event) => {

                    event.preventDefault();

                    const parent =
                        toggle.closest(
                            ".nk-menu-item"
                        );

                    if (!parent) {
                        return;
                    }

                    const submenu =
                        parent.querySelector(
                            ":scope > .nk-menu-sub"
                        );

                    if (!submenu) {
                        return;
                    }

                    const isOpen =
                        parent.classList.contains(
                            "active"
                        );

                    /*
                    |----------------------------------                    | Close other submenus
                    |----------------------------------                    */

                    document
                        .querySelectorAll(
                            ".nk-menu-item.has-sub"
                        )
                        .forEach((item) => {

                            if (item === parent) {
                                return;
                            }

                            item.classList.remove(
                                "active"
                            );

                            const child =
                                item.querySelector(
                                    ":scope > .nk-menu-sub"
                                );

                            if (child) {

                                child.style.display =
                                    "none";

                            }

                        });


                    /*
                    |----------------------------------                    | Toggle current submenu
                    |----------------------------------                    */

                    if (isOpen) {

                        parent.classList.remove(
                            "active"
                        );

                        submenu.style.display =
                            "none";

                    } else {

                        parent.classList.add(
                            "active"
                        );

                        submenu.style.display =
                            "block";

                    }

                }
            );

        });


    /*
    |--------------------------------------------------    | Close Sidebar with ESC
    |--------------------------------------------------    */

    document.addEventListener(
        "keydown",
        (event) => {

            if (event.key !== "Escape") {
                return;
            }

            document
                .querySelectorAll(
                    ".nk-sidebar"
                )
                .forEach((sidebar) => {

                    sidebar.classList.remove(
                        "nk-sidebar-active"
                    );

                });

            document.body.classList.remove(
                "nav-shown"
            );

            document
                .querySelectorAll(
                    ".nk-nav-toggle"
                )
                .forEach((button) => {

                    button.classList.remove(
                        "toggle-active"
                    );

                });

            removeOverlay();

        }
    );


    /*
    |--------------------------------------------------    | Close mobile sidebar after clicking menu link
    |--------------------------------------------------    */

    document
        .querySelectorAll(
            ".nk-sidebar .nk-menu-link"
        )
        .forEach((link) => {

            link.addEventListener(
                "click",
                () => {

                    /*
                    | Jangan menutup jika link tersebut
                    | adalah tombol submenu.
                    */

                    if (
                        link.classList.contains(
                            "nk-menu-toggle"
                        )
                    ) {
                        return;
                    }

                    if (
                        window.innerWidth < 1200
                    ) {

                        const sidebar =
                            link.closest(
                                ".nk-sidebar"
                            );

                        if (sidebar) {

                            closeSidebar(
                                sidebar
                            );

                        }

                    }

                }
            );

        });


    /*
    |--------------------------------------------------    | Responsive handling
    |--------------------------------------------------    */

    window.addEventListener(
        "resize",
        () => {

            /*
            | Desktop
            */

            if (
                window.innerWidth >= 1200
            ) {

                document.body.classList.remove(
                    "nav-shown"
                );

                removeOverlay();

                document
                    .querySelectorAll(
                        ".nk-sidebar"
                    )
                    .forEach((sidebar) => {

                        sidebar.classList.remove(
                            "nk-sidebar-active"
                        );

                    });

                document
                    .querySelectorAll(
                        ".nk-nav-toggle"
                    )
                    .forEach((button) => {

                        button.classList.remove(
                            "toggle-active"
                        );

                    });

            }

        }
    );

});
