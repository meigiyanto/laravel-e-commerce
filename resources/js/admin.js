import $ from 'jquery';
window.$ = $;
window.jQuery = $;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import Chart from 'chart.js/auto';
window.Chart = Chart;

await import('./bundle.js');
await import('./main.js');
