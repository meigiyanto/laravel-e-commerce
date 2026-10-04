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

// jQuery plugins yang memang digunakan project
import 'jquery-validation';
import 'jquery-form';

// Sidebar
import './sidebar.js';

// DataTables
// import './datatables.js';
