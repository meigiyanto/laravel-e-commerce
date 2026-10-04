import $ from 'jquery';

window.$ = $;
window.jQuery = $;

// Bootstrap 5 — satu-satunya Bootstrap di aplikasi admin.
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Chart.js
import Chart from 'chart.js/auto';

window.Chart = Chart;

// Load legacy jQuery/vendor plugins setelah jQuery tersedia.
await import('./admin-vendors.js');

// Load NioApp setelah Bootstrap dan seluruh vendor tersedia.
await import('./vendors/nioapp/nioapp.min.js');

// NioApp didefinisikan oleh file legacy sebagai `var NioApp`.
// Karena file tersebut dimuat sebagai ES module oleh Vite,
// expose NioApp ke global scope agar main.js dapat mengaksesnya.
if (typeof NioApp !== 'undefined') {
    window.NioApp = NioApp;
}

// Load aplikasi DashLite/NioApp.
await import('./main.js');
