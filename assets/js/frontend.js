/**
 * GuiaWP - JavaScript front-end
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

(function () {
	'use strict';

	function formatPhoneValue(value) {
		var digits = String(value || '').replace(/\D/g, '');
		var local;

		if (digits.length > 11 && digits.indexOf('55') === 0) {
			digits = digits.substring(2);
		}

		if (digits.length > 11) {
			digits = digits.substring(0, 11);
		}

		if (!digits.length) {
			return '';
		}

		if (digits.length < 3) {
			return '(' + digits;
		}

		local = digits.substring(2);

		if (digits.length <= 10) {
			if (local.length <= 4) {
				return '(' + digits.substring(0, 2) + ') ' + local;
			}

			return '(' + digits.substring(0, 2) + ') ' + local.substring(0, 4) + '-' + local.substring(4, 8);
		}

		if (local.length <= 1) {
			return '(' + digits.substring(0, 2) + ') ' + local;
		}

		if (local.length <= 5) {
			return '(' + digits.substring(0, 2) + ') ' + local.substring(0, 1) + ' ' + local.substring(1);
		}

		return '(' + digits.substring(0, 2) + ') ' + local.substring(0, 1) + ' ' + local.substring(1, 5) + '-' + local.substring(5, 9);
	}

	function bindPhoneMask(input) {
		if (!input) return;

		function applyMask() {
			input.value = formatPhoneValue(input.value);
		}

		input.addEventListener('input', applyMask);
		applyMask();
	}

	// Máscara de telefone
	document.querySelectorAll('input[type="tel"], input[name="telefone_principal"], input[name="whatsapp_principal"], input[name="whatsapp_comprovante"]').forEach(function (input) {
		bindPhoneMask(input);
	});

	// Auto-dismiss de mensagens flash (apenas divs com border)
	document.querySelectorAll('div[class*="bg-emerald-50"][class*="border"], div[class*="bg-rose-50"][class*="border"]').forEach(function (el) {
		if (el.closest('form')) return;
		if (el.querySelector('p, span') && el.textContent.trim().length > 0) {
			setTimeout(function () {
				el.style.transition = 'opacity 0.5s';
				el.style.opacity = '0';
				setTimeout(function () { el.remove(); }, 500);
			}, 5000);
		}
	});

	// Deletar anuncio via AJAX (dono ou admin)
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.gcep-delete-anuncio-btn');
		if (!btn) return;
		e.preventDefault();

		var postId = btn.getAttribute('data-id');
		var title  = btn.getAttribute('data-title') || '';
		if (!postId) return;

		var msg = 'Tem certeza que deseja remover o anúncio "' + title + '"?\n\nTodas as imagens e dados serão excluídos permanentemente.';
		if (!confirm(msg)) return;

		var row = btn.closest('tr');
		if (row) row.style.opacity = '0.4';
		btn.disabled = true;

		var fd = new FormData();
		fd.append('action', 'gcep_delete_anuncio');
		fd.append('nonce', (window.gcepData && gcepData.nonce) || '');
		fd.append('post_id', postId);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', (window.gcepData && gcepData.ajaxUrl) || '/wp-admin/admin-ajax.php', true);
		xhr.onload = function () {
			try {
				var res = JSON.parse(xhr.responseText);
				if (res.success) {
					if (row) {
						row.style.transition = 'opacity 0.3s';
						row.style.opacity = '0';
						setTimeout(function () { row.remove(); }, 300);
					}
				} else {
					if (row) row.style.opacity = '1';
					btn.disabled = false;
					gcepToast(res.data && res.data.message ? res.data.message : 'Erro ao remover anúncio.', 'error');
				}
			} catch (err) {
				if (row) row.style.opacity = '1';
				btn.disabled = false;
				gcepToast('Erro inesperado.', 'error');
			}
		};
		xhr.onerror = function () {
			if (row) row.style.opacity = '1';
			btn.disabled = false;
			gcepToast('Erro de rede.', 'error');
		};
		xhr.send(fd);
	});

})();
