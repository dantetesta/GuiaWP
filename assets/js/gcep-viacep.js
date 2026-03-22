/**
 * Integração ViaCEP + Leaflet/OSM - Endereço com mapa interativo
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.1.0 - 2026-03-11
 * @modified 1.7.0 - 2026-03-11 - Mapa Leaflet com geocodificação Nominatim e pin arrastável
 */
(function () {
	'use strict';

	var cepInput = document.getElementById('gcep_cep');
	if (!cepInput) return;

	var fields = {
		logradouro: document.getElementById('gcep_logradouro'),
		bairro:     document.getElementById('gcep_bairro'),
		cidade:     document.getElementById('gcep_cidade'),
		estado:     document.getElementById('gcep_estado'),
	};

	var latInput  = document.getElementById('gcep_latitude');
	var lngInput  = document.getElementById('gcep_longitude');
	var feedbackEl = document.getElementById('gcep-cep-feedback');
	var mapContainer = document.getElementById('gcep-address-map');

	var map = null;
	var marker = null;
	var BRASIL_CENTER = [-15.78, -47.93];
	var DEFAULT_ZOOM  = 15;

	// Inicializar mapa se Leaflet disponivel e container existir
	function initMap(lat, lng) {
		if (!window.L || !mapContainer) return;

		if (map) {
			map.setView([lat, lng], DEFAULT_ZOOM);
			if (marker) {
				marker.setLatLng([lat, lng]);
			}
			updateLatLng(lat, lng);
			return;
		}

		mapContainer.classList.remove('hidden');
		mapContainer.style.height = '300px';

		map = L.map(mapContainer, { scrollWheelZoom: true }).setView([lat, lng], DEFAULT_ZOOM);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			maxZoom: 19,
		}).addTo(map);

		marker = L.marker([lat, lng], { draggable: true }).addTo(map);

		marker.on('dragend', function () {
			var pos = marker.getLatLng();
			updateLatLng(pos.lat, pos.lng);
		});

		map.on('click', function (e) {
			marker.setLatLng(e.latlng);
			updateLatLng(e.latlng.lat, e.latlng.lng);
		});

		// Forcar recalculo de tiles apos render
		setTimeout(function () { map.invalidateSize(); }, 200);

		updateLatLng(lat, lng);
	}

	function updateLatLng(lat, lng) {
		if (latInput) latInput.value = parseFloat(lat).toFixed(7);
		if (lngInput) lngInput.value = parseFloat(lng).toFixed(7);
	}

	// Geocodificar endereco via Nominatim (OSM)
	function geocodeAddress(logradouro, cidade, estado, cep) {
		var query = [logradouro, cidade, estado, cep].filter(Boolean).join(', ') + ', Brasil';
		var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query);

		return fetch(url, {
			headers: { 'Accept-Language': 'pt-BR' }
		})
		.then(function (r) { return r.json(); })
		.then(function (results) {
			if (results && results.length > 0) {
				return { lat: parseFloat(results[0].lat), lng: parseFloat(results[0].lon) };
			}
			// Fallback: buscar apenas por cidade + estado
			if (cidade && estado) {
				var fallbackUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(cidade + ', ' + estado + ', Brasil');
				return fetch(fallbackUrl, { headers: { 'Accept-Language': 'pt-BR' } })
					.then(function (r2) { return r2.json(); })
					.then(function (r2) {
						if (r2 && r2.length > 0) {
							return { lat: parseFloat(r2[0].lat), lng: parseFloat(r2[0].lon) };
						}
						return null;
					});
			}
			return null;
		})
		.catch(function () { return null; });
	}

	function limparCamposEndereco() {
		Object.values(fields).forEach(function (el) {
			if (el) el.value = '';
		});
	}

	function setFeedback(msg, tipo) {
		if (!feedbackEl) return;
		feedbackEl.textContent = msg;
		feedbackEl.className = 'text-xs mt-1 font-medium ' + (tipo === 'erro' ? 'text-rose-500' : 'text-emerald-600');
	}

	function buscarCep(cep) {
		cep = cep.replace(/\D/g, '');
		if (cep.length !== 8) {
			setFeedback('CEP deve ter 8 dígitos.', 'erro');
			return;
		}

		setFeedback('Buscando...', 'ok');

		fetch('https://viacep.com.br/ws/' + cep + '/json/')
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.erro) {
					setFeedback('CEP não encontrado.', 'erro');
					limparCamposEndereco();
					return;
				}

				if (fields.logradouro) fields.logradouro.value = data.logradouro || '';
				if (fields.bairro)     fields.bairro.value     = data.bairro || '';
				if (fields.cidade)     fields.cidade.value     = data.localidade || '';
				if (fields.estado)     fields.estado.value     = data.uf || '';

				setFeedback('Endereço encontrado! Ajuste o pin no mapa se necessário.', 'ok');

				// Foco no campo numero
				var numInput = document.getElementById('gcep_numero');
				if (numInput) numInput.focus();

				// Geocodificar e mostrar mapa
				geocodeAddress(
					data.logradouro || '',
					data.localidade || '',
					data.uf || '',
					cep
				).then(function (coords) {
					if (coords) {
						initMap(coords.lat, coords.lng);
					} else {
						// Fallback: centro do Brasil
						initMap(BRASIL_CENTER[0], BRASIL_CENTER[1]);
					}
				});
			})
			.catch(function () {
				setFeedback('Erro ao consultar ViaCEP.', 'erro');
			});
	}

	// Mascara simples de CEP: 00000-000
	cepInput.addEventListener('input', function () {
		var v = this.value.replace(/\D/g, '');
		if (v.length > 5) {
			v = v.substring(0, 5) + '-' + v.substring(5, 8);
		}
		this.value = v;
	});

	// Buscar ao sair do campo ou ao completar 9 caracteres (com traco)
	cepInput.addEventListener('blur', function () {
		var cep = this.value.replace(/\D/g, '');
		if (cep.length === 8) buscarCep(cep);
	});

	cepInput.addEventListener('input', function () {
		var cep = this.value.replace(/\D/g, '');
		if (cep.length === 8) buscarCep(cep);
	});

	// Se ja tem lat/lng salvos (edicao), inicializar mapa
	if (latInput && lngInput && latInput.value && lngInput.value) {
		var savedLat = parseFloat(latInput.value);
		var savedLng = parseFloat(lngInput.value);
		if (!isNaN(savedLat) && !isNaN(savedLng) && savedLat !== 0 && savedLng !== 0) {
			// Aguardar DOM completo para Leaflet
			if (document.readyState === 'complete') {
				initMap(savedLat, savedLng);
			} else {
				window.addEventListener('load', function () {
					initMap(savedLat, savedLng);
				});
			}
		}
	}
})();
