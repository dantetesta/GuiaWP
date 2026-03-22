/**
 * Formulário de Anúncio - Campos condicionais, repeater de vídeos, máscara CNPJ
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.1.0 - 2026-03-11
 */
(function () {
	'use strict';

	// --- Campos condicionais por tipo de anúncio (empresa/profissional) ---
	var tipoSelect = document.getElementById('gcep_tipo_anuncio');
	var cnpjSection = document.getElementById('gcep-section-cnpj');

	function toggleTipoFields() {
		if (!tipoSelect) return;
		var isEmpresa = tipoSelect.value === 'empresa';
		if (cnpjSection) cnpjSection.style.display = isEmpresa ? '' : 'none';
	}

	if (tipoSelect) {
		tipoSelect.addEventListener('change', toggleTipoFields);
		toggleTipoFields();
	}

	// --- Campos condicionais por plano (gratis/premium) ---
	var planoSelect = document.getElementById('gcep_tipo_plano');
	var premiumSections = document.querySelectorAll('[data-premium-only]');

	function togglePlanoFields() {
		if (!planoSelect) return;
		var isPremium = planoSelect.value === 'premium';
		premiumSections.forEach(function (el) {
			el.style.display = isPremium ? '' : 'none';
		});
	}

	if (planoSelect) {
		planoSelect.addEventListener('change', togglePlanoFields);
		togglePlanoFields();
	}

	// --- Máscara de CNPJ: 00.000.000/0000-00 ---
	var cnpjInput = document.getElementById('gcep_cnpj');
	if (cnpjInput) {
		cnpjInput.addEventListener('input', function () {
			var v = this.value.replace(/\D/g, '');
			if (v.length > 14) v = v.substring(0, 14);
			if (v.length > 12) {
				v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
			} else if (v.length > 8) {
				v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{1,4})/, '$1.$2.$3/$4');
			} else if (v.length > 5) {
				v = v.replace(/^(\d{2})(\d{3})(\d{1,3})/, '$1.$2.$3');
			} else if (v.length > 2) {
				v = v.replace(/^(\d{2})(\d{1,3})/, '$1.$2');
			}
			this.value = v;
		});
	}

	// --- Repeater de Vídeos ---
	var videoContainer = document.getElementById('gcep-videos-container');
	var addVideoBtn = document.getElementById('gcep-add-video');

	function criarVideoItem(titulo, url) {
		var div = document.createElement('div');
		div.className = 'gcep-video-item flex flex-col sm:flex-row gap-3 items-start bg-slate-50 p-4 rounded-xl border border-slate-200 group';

		div.innerHTML =
			'<div class="flex-1 w-full space-y-3">' +
				'<input type="text" name="gcep_video_titulo[]" value="' + (titulo || '') + '" placeholder="Título do vídeo" class="w-full rounded-lg border-slate-200 bg-white p-3 text-sm focus:ring-2 focus:ring-[#0052cc]/20 outline-none">' +
				'<input type="url" name="gcep_video_url[]" value="' + (url || '') + '" placeholder="https://youtube.com/watch?v=..." class="w-full rounded-lg border-slate-200 bg-white p-3 text-sm focus:ring-2 focus:ring-[#0052cc]/20 outline-none">' +
			'</div>' +
			'<button type="button" class="gcep-remove-video p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Remover">' +
				'<span class="material-symbols-outlined text-xl">delete</span>' +
			'</button>';

		return div;
	}

	if (addVideoBtn && videoContainer) {
		addVideoBtn.addEventListener('click', function () {
			var items = videoContainer.querySelectorAll('.gcep-video-item');
			if (items.length >= 10) {
				gcepToast('Máximo de 10 vídeos permitidos.', 'warning');
				return;
			}
			videoContainer.appendChild(criarVideoItem('', ''));
		});

		videoContainer.addEventListener('click', function (e) {
			var btn = e.target.closest('.gcep-remove-video');
			if (btn) {
				btn.closest('.gcep-video-item').remove();
			}
		});
	}

	// --- Máscara de telefone ---
	function formatPhoneValue(value) {
		var digits = String(value || '').replace(/\D/g, '');
		var local;

		if (digits.length > 11 && digits.indexOf('55') === 0) {
			digits = digits.substring(2);
		}

		if (digits.length > 11) digits = digits.substring(0, 11);
		if (!digits.length) return '';
		if (digits.length < 3) return '(' + digits;

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

	var telInputs = document.querySelectorAll('input[name="gcep_telefone"], input[name="gcep_whatsapp"]');
	telInputs.forEach(function (input) {
		var applyMask = function () {
			input.value = formatPhoneValue(input.value);
		};

		input.addEventListener('input', applyMask);
		applyMask();
	});
})();
