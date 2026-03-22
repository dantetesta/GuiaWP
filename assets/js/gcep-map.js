/**
 * Mapa público de anúncios — Leaflet/OSM com pins, filtros e modal
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.7.0 - 2026-03-11
 * @modified 1.9.4 - 2026-03-21 - Modal redesenhado: capa + foto perfil sobreposta
 * @modified 1.9.7 - 2026-03-21 - Endereco completo e icones contato no modal
 */
(function () {
	'use strict';

	var mapContainer = document.getElementById('gcep-map-fullscreen');
	if (!mapContainer || !window.L) return;

	var BRASIL_CENTER = [-15.78, -47.93];
	var DEFAULT_ZOOM  = 5;
	var ajaxUrl       = (window.gcepData && window.gcepData.ajaxUrl) || '/wp-admin/admin-ajax.php';

	// Inicializar mapa
	var map = L.map(mapContainer, {
		center: BRASIL_CENTER,
		zoom: DEFAULT_ZOOM,
		scrollWheelZoom: true,
		zoomControl: true
	});

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
		maxZoom: 19
	}).addTo(map);

	var markersLayer = L.layerGroup().addTo(map);
	var anunciosCache = [];
	var viewedIds = {};

	// Icone personalizado
	function criarIcone(plano) {
		var cor = plano === 'premium' ? '#0052cc' : '#64748b';
		return L.divIcon({
			className: 'gcep-map-pin',
			html: '<div style="background:' + cor + ';width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;">' +
				'<span style="color:white;font-size:16px;font-family:\'Material Symbols Outlined\';font-weight:400;">location_on</span>' +
				'</div>',
			iconSize: [32, 32],
			iconAnchor: [16, 32],
			popupAnchor: [0, -32]
		});
	}

	// Carregar anuncios via AJAX
	function carregarAnuncios() {
		var search = document.getElementById('gcep-map-search');
		var category = document.getElementById('gcep-map-category');

		var params = new URLSearchParams();
		params.append('action', 'gcep_map_anuncios');
		if (search && search.value.trim()) params.append('s', search.value.trim());
		if (category && category.value) params.append('gcep_cat', category.value);

		fetch(ajaxUrl + '?' + params.toString())
			.then(function (r) { return r.json(); })
			.then(function (resp) {
				if (!resp.success) return;
				anunciosCache = resp.data;
				renderPins(resp.data);
			})
			.catch(function (err) {
				console.error('Erro ao carregar anúncios:', err);
			});
	}

	// Renderizar pins no mapa
	function renderPins(anuncios) {
		markersLayer.clearLayers();

		var bounds = [];
		var count = 0;

		anuncios.forEach(function (a) {
			if (!a.lat || !a.lng) return;

			var marker = L.marker([a.lat, a.lng], {
				icon: criarIcone(a.plano),
				title: a.titulo
			});

			marker.on('click', function () {
				abrirModal(a);
			});

			markersLayer.addLayer(marker);
			bounds.push([a.lat, a.lng]);
			count++;
		});

		// Atualizar contagem
		var countEl = document.getElementById('gcep-map-count-text');
		if (countEl) {
			countEl.textContent = count + (count === 1 ? ' anúncio no mapa' : ' anúncios no mapa');
		}

		// Ajustar zoom para mostrar todos os pins
		if (bounds.length > 0) {
			map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
		}
	}

	// Abrir modal com dados do anuncio
	function abrirModal(a) {
		var overlay = document.getElementById('gcep-map-modal-overlay');
		var modal   = document.getElementById('gcep-map-modal');
		if (!overlay || !modal) return;

		// Foto de capa
		var capaEl = document.getElementById('gcep-modal-capa');
		var capaPlaceholder = document.getElementById('gcep-modal-capa-placeholder');
		if (capaEl) {
			if (a.capa) {
				capaEl.style.backgroundImage = 'url(' + a.capa + ')';
				capaEl.style.backgroundSize = 'cover';
				capaEl.style.backgroundPosition = 'center';
				if (capaPlaceholder) capaPlaceholder.style.display = 'none';
			} else {
				capaEl.style.backgroundImage = 'none';
				if (capaPlaceholder) capaPlaceholder.style.display = 'flex';
			}
		}

		// Foto de perfil
		var logoEl = document.getElementById('gcep-modal-logo');
		if (logoEl) {
			if (a.logo) {
				logoEl.innerHTML = '<img src="' + a.logo + '" alt="" style="width:100%;height:100%;object-fit:cover;">';
			} else {
				logoEl.innerHTML = '<span class="material-symbols-outlined" style="font-size:32px;color:#cbd5e1;">person</span>';
			}
		}

		// Titulo
		var tituloEl = document.getElementById('gcep-modal-titulo');
		if (tituloEl) tituloEl.textContent = a.titulo || '';

		// Categoria
		var catEl = document.getElementById('gcep-modal-cat');
		if (catEl) {
			catEl.textContent = a.categoria || '';
			catEl.style.display = a.categoria ? 'inline-block' : 'none';
		}

		// Endereço completo
		var endEl = document.getElementById('gcep-modal-endereco');
		if (endEl) {
			if (a.endereco) {
				endEl.textContent = a.endereco;
				endEl.style.display = 'block';
			} else {
				endEl.style.display = 'none';
			}
		}

		// Ícones de contato
		var contatosEl = document.getElementById('gcep-modal-contatos');
		var telEl   = document.getElementById('gcep-modal-tel');
		var wppEl   = document.getElementById('gcep-modal-wpp');
		var emailEl = document.getElementById('gcep-modal-email');
		var siteEl  = document.getElementById('gcep-modal-site');
		var temContato = false;

		if (telEl) {
			if (a.telefone) {
				var telDigits = a.telefone.replace(/\D/g, '');
				telEl.href = 'tel:' + (telDigits.length >= 10 ? '+55' : '') + telDigits;
				telEl.style.display = 'inline-flex';
				temContato = true;
			} else {
				telEl.style.display = 'none';
			}
		}

		if (wppEl) {
			if (a.whatsapp) {
				var wppDigits = a.whatsapp.replace(/\D/g, '');
				var wppNum = wppDigits.length >= 10 ? '55' + wppDigits : wppDigits;
				wppEl.href = 'https://wa.me/' + wppNum;
				wppEl.style.display = 'inline-flex';
				temContato = true;
			} else {
				wppEl.style.display = 'none';
			}
		}

		if (emailEl) {
			if (a.email) {
				emailEl.href = 'mailto:' + a.email;
				emailEl.style.display = 'inline-flex';
				temContato = true;
			} else {
				emailEl.style.display = 'none';
			}
		}

		if (siteEl) {
			if (a.site) {
				siteEl.href = a.site;
				siteEl.style.display = 'inline-flex';
				temContato = true;
			} else {
				siteEl.style.display = 'none';
			}
		}

		if (contatosEl) {
			contatosEl.style.display = temContato ? 'flex' : 'none';
		}

		// Link "Saiba mais"
		var linkEl = document.getElementById('gcep-modal-link');
		if (linkEl) linkEl.href = a.url || '#';

		// Mostrar modal
		overlay.style.display = 'block';
		modal.style.display = 'block';

		// Registrar visita (1 vez por anuncio por sessao)
		if (!viewedIds[a.id]) {
			viewedIds[a.id] = true;
			registrarVisita(a.id);
		}
	}

	// Fechar modal
	function fecharModal() {
		var overlay = document.getElementById('gcep-map-modal-overlay');
		var modal   = document.getElementById('gcep-map-modal');
		if (overlay) overlay.style.display = 'none';
		if (modal) modal.style.display = 'none';
	}

	var closeBtn = document.getElementById('gcep-modal-close');
	var overlayEl = document.getElementById('gcep-map-modal-overlay');
	if (closeBtn) closeBtn.addEventListener('click', fecharModal);
	if (overlayEl) overlayEl.addEventListener('click', fecharModal);

	// ESC para fechar
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') fecharModal();
	});

	// Registrar visita via AJAX
	function registrarVisita(postId) {
		var formData = new FormData();
		formData.append('action', 'gcep_map_view');
		formData.append('post_id', postId);

		fetch(ajaxUrl, { method: 'POST', body: formData })
			.catch(function () {});
	}

	// Filtrar
	var filterBtn = document.getElementById('gcep-map-filter-btn');
	if (filterBtn) {
		filterBtn.addEventListener('click', function () {
			carregarAnuncios();
		});
	}

	// Enter no campo de busca
	var searchInput = document.getElementById('gcep-map-search');
	if (searchInput) {
		searchInput.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				carregarAnuncios();
			}
		});
	}

	// Geolocalização do usuário
	var geoBtn = document.getElementById('gcep-map-geoloc-btn');
	if (geoBtn) {
		geoBtn.addEventListener('click', function () {
			if (!navigator.geolocation) {
				gcepToast('Seu navegador não suporta geolocalização.', 'warning');
				return;
			}

			geoBtn.disabled = true;
			var iconSpan = geoBtn.querySelector('.material-symbols-outlined');
			if (iconSpan) iconSpan.textContent = 'hourglass_empty';

			navigator.geolocation.getCurrentPosition(
				function (pos) {
					var lat = pos.coords.latitude;
					var lng = pos.coords.longitude;

					map.setView([lat, lng], 13);

					// Pin do usuario
					L.circleMarker([lat, lng], {
						radius: 10,
						fillColor: '#0052cc',
						fillOpacity: 0.8,
						color: 'white',
						weight: 3
					}).addTo(map).bindPopup('<strong>Você está aqui</strong>');

					geoBtn.disabled = false;
					if (iconSpan) iconSpan.textContent = 'my_location';
				},
				function () {
					gcepToast('Não foi possível obter sua localização.', 'error');
					geoBtn.disabled = false;
					if (iconSpan) iconSpan.textContent = 'my_location';
				},
				{ enableHighAccuracy: true, timeout: 10000 }
			);
		});
	}

	// Carregar anuncios ao iniciar
	carregarAnuncios();
})();
