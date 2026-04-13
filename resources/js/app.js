import './bootstrap';
import './echo';
import TomSelect from 'tom-select';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.TomSelect = TomSelect;

import Swal from 'sweetalert2';
window.Swal = Swal;

Alpine.start();
