/**
 * Mapa de Localização com Leaflet (OpenStreetMap)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.5.4 - 2026-03-11
 * @modified 1.9.2 - 2026-03-21 - Usar coordenadas salvas antes de geocoding, fallback robusto
 */
(function() {
	'use strict';

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initMaps);
	} else {
		initMaps();
	}

	function initMaps() {
		var mapContainers = document.querySelectorAll('[id^="gcep-map-"]');
		if (!mapContainers.length) return;

		loadLeaflet(function() {
			mapContainers.forEach(function(container) {
				var address = container.getAttribute('data-address');
				var lat = parseFloat(container.getAttribute('data-lat'));
				var lng = parseFloat(container.getAttribute('data-lng'));

				// Se tem coordenadas salvas, usar direto
				if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
					createMap(container, lat, lng, address || '');
					return;
				}

				// Fallback: geocodificar via Nominatim
				if (!address) {
					showError(container);
					return;
				}

				geocodeAddress(address, function(geoLat, geoLng) {
					if (geoLat && geoLng) {
						createMap(container, geoLat, geoLng, address);
					} else {
						showError(container);
					}
				});
			});
		});
	}

	function createMap(container, lat, lng, address) {
		var map = L.map(container, {
			center: [lat, lng],
			zoom: 16,
			scrollWheelZoom: false,
			zoomControl: true
		});

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			maxZoom: 19
		}).addTo(map);

		var marker = L.marker([lat, lng]).addTo(map);
		if (address) {
			marker.bindPopup('<strong>' + address + '</strong>').openPopup();
		}

		// Forçar resize após render (corrige tiles cinza)
		setTimeout(function() { map.invalidateSize(); }, 300);
	}

	function showError(container) {
		container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;gap:8px;"><span class="material-symbols-outlined">location_off</span>Não foi possível carregar o mapa</div>';
	}

	function loadLeaflet(callback) {
		if (window.L) {
			callback();
			return;
		}

		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
		document.head.appendChild(link);

		var script = document.createElement('script');
		script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
		script.onload = callback;
		script.onerror = function() {
			// Tentar CDN alternativo
			var fallback = document.createElement('script');
			fallback.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
			fallback.onload = callback;
			document.head.appendChild(fallback);

			var fallbackCss = document.createElement('link');
			fallbackCss.rel = 'stylesheet';
			fallbackCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
			document.head.appendChild(fallbackCss);
		};
		document.head.appendChild(script);
	}

	function geocodeAddress(address, callback) {
		var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1&countrycodes=br';

		fetch(url, {
			headers: { 'User-Agent': 'GuiaWP/1.0' }
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data && data.length > 0) {
				callback(parseFloat(data[0].lat), parseFloat(data[0].lon));
			} else {
				callback(null, null);
			}
		})
		.catch(function() {
			callback(null, null);
		});
	}
})();
