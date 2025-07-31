import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Tambahkan jQuery dan Bootstrap
import jQuery from 'jquery';
window.$ = jQuery;
window.jQuery = jQuery;

import 'bootstrap';
