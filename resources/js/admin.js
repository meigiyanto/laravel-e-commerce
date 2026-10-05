// jQuery
import $ from 'jquery';
window.$ = $;
window.jQuery = $;

// Bootstrap 5
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Chart.js
import Chart from 'chart.js/auto';
window.Chart = Chart;

// Load legacy jQuery/vendor plugins setelah jQuery tersedia.
await import('./admin-vendors.js');

// Load NioApp setelah seluruh vendor tersedia.
await import('./vendors/nioapp/nioapp.min.js');

// NioApp didefinisikan oleh legacy script sebagai `var NioApp`.
// Expose ke global scope agar main.js dapat mengaksesnya.
if (typeof NioApp !== 'undefined') {
    window.NioApp = NioApp;
}

// Load aplikasi DashLite/NioApp.
await import('./main.js');

const NIO_DOC_READY_INIT_FLAG = '__nioDocReadyInitialized';

if (
    document.readyState !== 'loading' &&
    window.NioApp?.coms?.docReady &&
    !window[NIO_DOC_READY_INIT_FLAG]
) {
    window.NioApp.coms.docReady.forEach(function (callback) {
        callback();
    });

    window[NIO_DOC_READY_INIT_FLAG] = true;
}
