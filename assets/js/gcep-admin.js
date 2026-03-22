/**
 * Scripts do painel administrativo
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.2.2 - 2026-03-11
 */
(function () {
	'use strict';

	// Mapa de status → ícones/classes para reconstruir botões
	var statusMap = {
		publicado:             { icon: 'check_circle', hoverBg: 'hover:bg-emerald-50', hoverText: 'hover:text-emerald-600', title: 'Publicar' },
		rejeitado:             { icon: 'cancel',       hoverBg: 'hover:bg-rose-50',    hoverText: 'hover:text-rose-500',    title: 'Rejeitar' },
		aguardando_aprovacao:  { icon: 'hourglass_top',hoverBg: 'hover:bg-amber-50',   hoverText: 'hover:text-amber-600',   title: 'Pendente' }
	};

	// Mapa de cores para o badge
	var colorMap = {
		emerald: { bg: 'bg-emerald-100', text: 'text-emerald-700' },
		rose:    { bg: 'bg-rose-100',    text: 'text-rose-700' },
		amber:   { bg: 'bg-amber-100',   text: 'text-amber-700' },
		slate:   { bg: 'bg-slate-100',   text: 'text-slate-700' }
	};

	function changeStatus(btn) {
		var postId    = btn.getAttribute('data-id');
		var newStatus = btn.getAttribute('data-status');
		if (!postId || !newStatus) return;

		// Feedback visual
		var row = btn.closest('tr');
		if (row) row.style.opacity = '0.5';

		var formData = new FormData();
		formData.append('action', 'gcep_change_status');
		formData.append('nonce', gcepData.nonce);
		formData.append('post_id', postId);
		formData.append('status', newStatus);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', gcepData.ajaxUrl, true);
		xhr.onload = function () {
			if (row) row.style.opacity = '1';
			try {
				var res = JSON.parse(xhr.responseText);
				if (res.success && res.data) {
					updateRow(row, postId, res.data);
				} else {
					gcepToast(res.data && res.data.message ? res.data.message : 'Erro ao alterar status.', 'error');
				}
			} catch (e) {
				gcepToast('Erro inesperado.', 'error');
			}
		};
		xhr.onerror = function () {
			if (row) row.style.opacity = '1';
			gcepToast('Erro de rede.', 'error');
		};
		xhr.send(formData);
	}

	function updateRow(row, postId, data) {
		if (!row) return;

		// Atualizar badge de status
		var badge = row.querySelector('td:nth-child(5) span, td:nth-last-child(2) span');
		if (badge && badge.classList.contains('inline-flex')) {
			// Remover classes de cor anteriores
			badge.className = badge.className.replace(/bg-\w+-100/g, '').replace(/text-\w+-700/g, '').trim();
			var c = colorMap[data.color] || colorMap.slate;
			badge.classList.add(c.bg, c.text);
			badge.textContent = data.label;
		}

		// Reconstruir botões de ação
		var actionsDiv = row.querySelector('td:last-child .flex');
		if (!actionsDiv) return;

		// Preservar links e botao de deletar
		var preserved = [];
		actionsDiv.querySelectorAll('a[title]').forEach(function (a) { preserved.push(a.cloneNode(true)); });
		var deleteBtn = actionsDiv.querySelector('.gcep-delete-anuncio-btn');
		if (deleteBtn) preserved.push(deleteBtn.cloneNode(true));

		// Limpar e reconstruir
		actionsDiv.innerHTML = '';

		Object.keys(statusMap).forEach(function (st) {
			if (st === data.status) return;
			var s = statusMap[st];
			var b = document.createElement('button');
			b.className = 'gcep-status-btn p-1.5 rounded-lg ' + s.hoverBg + ' ' + s.hoverText + ' text-slate-400 transition-colors';
			b.setAttribute('data-id', postId);
			b.setAttribute('data-status', st);
			b.setAttribute('title', s.title);
			b.innerHTML = '<span class="material-symbols-outlined text-xl">' + s.icon + '</span>';
			actionsDiv.appendChild(b);
		});

		// Re-adicionar links e botao de deletar
		preserved.forEach(function (el) { actionsDiv.appendChild(el); });
	}

	function syncGatewayPanels() {
		var radios = document.querySelectorAll('input[name="gateway_ativo"]');
		var panels = document.querySelectorAll('[data-gateway-panel]');
		if (!radios.length || !panels.length) return;

		var activeGateway = '';
		radios.forEach(function (radio) {
			if (radio.checked) activeGateway = radio.value;
		});

		panels.forEach(function (panel) {
			var panelGateway = panel.getAttribute('data-gateway-panel') || '';
			panel.classList.toggle('hidden', panelGateway !== activeGateway);
		});
	}

	function syncCaptchaPanels() {
		var radios = document.querySelectorAll('input[name="auth_captcha_provider"]');
		var panels = document.querySelectorAll('[data-captcha-panel]');
		var cards = document.querySelectorAll('.gcep-captcha-provider-card');
		if (!radios.length || !panels.length) return;

		var activeProvider = 'none';
		radios.forEach(function (radio) {
			if (radio.checked) {
				activeProvider = radio.value;
			}
		});

		panels.forEach(function (panel) {
			panel.classList.toggle('hidden', panel.getAttribute('data-captcha-panel') !== activeProvider);
		});

		cards.forEach(function (card) {
			var radio = card.parentElement ? card.parentElement.querySelector('input[name="auth_captcha_provider"]') : null;
			var active = radio && radio.checked;

			card.classList.toggle('border-slate-900', !!active);
			card.classList.toggle('bg-slate-900', !!active);
			card.classList.toggle('text-white', !!active);
			card.classList.toggle('shadow-lg', !!active);
			card.classList.toggle('border-slate-200', !active);
			card.classList.toggle('bg-slate-50', !active);
			card.classList.toggle('text-slate-700', !active);
			card.classList.toggle('hover:border-slate-300', !active);

			var icon = card.querySelector('.material-symbols-outlined');
			var desc = card.querySelector('.mt-2');
			if (icon) {
				icon.classList.toggle('text-white', !!active);
				icon.classList.toggle('text-[#0052cc]', !active);
			}
			if (desc) {
				desc.classList.toggle('text-white/80', !!active);
				desc.classList.toggle('text-slate-500', !active);
			}
		});
	}

	function initSettingsTabs() {
		var tabs = document.querySelectorAll('[data-settings-tab]');
		var panels = document.querySelectorAll('[data-settings-panel]');
		if (!tabs.length || !panels.length) return;

		function setActiveTab(tabKey, updateHash) {
			var hasMatch = false;

			panels.forEach(function (panel) {
				var isActive = panel.getAttribute('data-settings-panel') === tabKey;
				panel.classList.toggle('hidden', !isActive);
				if (isActive) hasMatch = true;
			});

			if (!hasMatch) {
				tabKey = tabs[0].getAttribute('data-settings-tab') || 'plataforma';
				panels.forEach(function (panel) {
					panel.classList.toggle('hidden', panel.getAttribute('data-settings-panel') !== tabKey);
				});
			}

			tabs.forEach(function (tab) {
				var isActive = tab.getAttribute('data-settings-tab') === tabKey;
				tab.classList.toggle('bg-slate-900', isActive);
				tab.classList.toggle('text-white', isActive);
				tab.classList.toggle('shadow-sm', isActive);
				tab.classList.toggle('text-slate-500', !isActive);
				tab.classList.toggle('hover:bg-slate-50', !isActive);
				tab.classList.toggle('hover:text-slate-800', !isActive);
			});

			if (updateHash && window.history && window.history.replaceState) {
				window.history.replaceState(null, '', window.location.pathname + window.location.search + '#tab-' + tabKey);
			}
		}

		document.addEventListener('click', function (e) {
			var tab = e.target.closest('[data-settings-tab]');
			if (!tab) return;

			e.preventDefault();
			setActiveTab(tab.getAttribute('data-settings-tab') || 'plataforma', true);
		});

		var hash = (window.location.hash || '').replace('#tab-', '');
		setActiveTab(hash || tabs[0].getAttribute('data-settings-tab') || 'plataforma', false);
	}

	// Delegação de eventos
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.gcep-status-btn');
		if (btn) {
			e.preventDefault();
			changeStatus(btn);
		}
	});

	document.addEventListener('change', function (e) {
		if (e.target && e.target.matches('input[name="gateway_ativo"]')) {
			syncGatewayPanels();
		}

		if (e.target && e.target.matches('input[name="auth_captcha_provider"]')) {
			syncCaptchaPanels();
		}
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			syncGatewayPanels();
			syncCaptchaPanels();
			initSettingsTabs();
		});
	} else {
		syncGatewayPanels();
		syncCaptchaPanels();
		initSettingsTabs();
	}
})();
