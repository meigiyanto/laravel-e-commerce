/************************************************* * Legacy vendor dependencies
* Bootstrap 5 TIDAK di-import di sini.
* Bootstrap hanya dikelola oleh resources/js/admin.js
**************************************************/

// jQuery plugins
await import('jquery-validation');
await import('jquery-form');
await import('select2');
await import('bootstrap-datepicker');
await import('slick-carousel');

// UI / utility plugins
await import('simplebar');
await import('sweetalert2');
await import('toastr');
// await import('clipboard');
const ClipboardJS = (await import('clipboard')).default;
window.ClipboardJS = ClipboardJS;

await import('nouislider');
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
