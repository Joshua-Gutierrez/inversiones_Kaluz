import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.L = L;

if (typeof window.initViajeMapa === 'function') {
    window.initViajeMapa();
}
