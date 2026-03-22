/**
 * Integração intl-tel-input — bandeiras DDI nos campos de telefone/WhatsApp
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.9.8 - 2026-03-21
 */
(function () {
	'use strict';

	if (typeof intlTelInput === 'undefined') return;

	// Selecionar todos os inputs marcados com data-intl-tel
	var inputs = document.querySelectorAll('input[data-intl-tel]');
	if (!inputs.length) return;

	// Configuração base (v25+ API: countryOrder substitui preferredCountries, loadUtils substitui utilsScript)
	var baseConfig = {
		initialCountry: 'br',
		countryOrder: ['br', 'us', 'pt', 'ar', 'py', 'uy', 'cl', 'co'],
		separateDialCode: true,
		nationalMode: true,
		autoPlaceholder: 'aggressive',
		loadUtils: function () {
			return import('https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/utils.js');
		}
	};

	// Geo-detection para definir país automaticamente
	var geoConfig = Object.assign({}, baseConfig, {
		initialCountry: 'auto',
		geoIpLookup: function (success, failure) {
			fetch('https://ipapi.co/json')
				.then(function (res) { return res.json(); })
				.then(function (data) {
					var code = (data && data.country_code) ? data.country_code : 'br';
					success(code.toLowerCase());
				})
				.catch(function () {
					success('br');
				});
		}
	});

	// Instâncias para acesso posterior
	var instances = [];

	inputs.forEach(function (input) {
		// Verificar se já foi inicializado
		if (input.dataset.intlTelInitialized) return;
		input.dataset.intlTelInitialized = '1';

		// Usar geo-detection apenas no primeiro input, os demais herdam o mesmo país
		var config = instances.length === 0 ? geoConfig : baseConfig;

		var iti = intlTelInput(input, config);
		instances.push({ input: input, iti: iti });

		// Quando o primeiro input resolve geo, aplicar nos demais
		if (instances.length === 1) {
			input.addEventListener('countrychange', function () {
				var country = iti.getSelectedCountryData();
				if (country && country.iso2) {
					instances.forEach(function (inst, idx) {
						if (idx > 0 && !inst.input.dataset.intlTelManual) {
							inst.iti.setCountry(country.iso2);
						}
					});
				}
			});
		}

		// Marcar como manual quando o usuário muda o país manualmente
		var flagContainer = input.closest('.iti');
		if (flagContainer) {
			var flagBtn = flagContainer.querySelector('.iti__selected-country');
			if (flagBtn) {
				flagBtn.addEventListener('click', function () {
					input.dataset.intlTelManual = '1';
				});
			}
		}
	});

	// Antes do submit, enviar somente dígitos nacionais (sem DDI) para manter compatibilidade com o backend
	var forms = [];
	instances.forEach(function (inst) {
		var form = inst.input.closest('form');
		if (form && forms.indexOf(form) === -1) {
			forms.push(form);
			form.addEventListener('submit', function () {
				instances.forEach(function (i) {
					if (i.input.closest('form') === form) {
						var num = i.iti.getNumber();
						if (num) {
							// Remover DDI para salvar no formato nacional
							var countryData = i.iti.getSelectedCountryData();
							var dialCode = countryData ? countryData.dialCode : '';
							var national = num.replace('+' + dialCode, '').replace(/\D/g, '');
							i.input.value = national;
						}
					}
				});
			});
		}
	});

})();
