/*************************************************
* Legacy vendor dependencies
* Bootstrap 5 TIDAK di-import di sini.
* Bootstrap hanya dikelola oleh resources/js/admin.js
**************************************************/

// jQuery plugins
await import('jquery-validation');
await import('jquery-form');

/**
 * Select 2
 *
 * Pastikan Select2 terpasang pada instance jQuery yang sama
 *
 * dengan yang digunakan oleh NioApp/DashLite.
 */
const Select2Module = await import('select2');

if (typeof window.jQuery.fn.select2 !== 'function') {
    const Select2Factory = Select2Module.default ?? Select2Module;

    if (typeof Select2Factory === 'function') {
        Select2Factory(window.jQuery);
    }
}

if (typeof window.jQuery.fn.select2 !== 'function') {
    throw new Error(
        'Select2 fail to initialize :$.fn.select2 unavailable.'
    );
}

// BS Date Picker
await import('bootstrap-datepicker');

// Slick Carousel
await import('slick-carousel');

// UI / utility plugins
await import('simplebar');

// Sweet Alert 2
await import('sweetalert2');

// Toastr
await import('toastr');

// ClipboardJS
const ClipboardModule = await import('clipboard');
const ClipboardJS = ClipboardModule.default;

if (typeof ClipboardJS !== 'function') {
    throw new Error('ClipboardJS constructor not found.');
}

window.ClipboardJS = ClipboardJS;

// NoUISlider
await import('nouislider');

// Magnific Popup
await import('magnific-popup');

// DataTables
await import('datatables.net');
await import('datatables.net-responsive');

// Dropzone
await import('dropzone');

// Local legacy vendors
await import('./vendors/knob/jquery.knob.min.js');
await import('./vendors/jquery-steps/jquery.steps.min.js');
await import('./vendors/prettify.js');
