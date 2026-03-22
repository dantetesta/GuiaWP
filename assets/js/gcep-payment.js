/**
 * Frontend de pagamento — PIX (QR Code + polling) e Cartao
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */
(function () {
	'use strict';

	const POLLING_INTERVAL = 4000;
	const POLLING_TIMEOUT  = 30 * 60 * 1000;

	let pollingTimer   = null;
	let pollingTimeout = null;
	let processando    = false;

	/* ── Elementos ───────────────────────────────────────── */

	const els = {
		formPix:       () => document.getElementById('gcep-pay-form-pix'),
		formCartao:    () => document.getElementById('gcep-pay-form-cartao'),
		tabPix:        () => document.getElementById('gcep-tab-pix'),
		tabCartao:     () => document.getElementById('gcep-tab-cartao'),
		panelPix:      () => document.getElementById('gcep-panel-pix'),
		panelCartao:   () => document.getElementById('gcep-panel-cartao'),
		panelManual:   () => document.getElementById('gcep-panel-manual'),
		qrSection:     () => document.getElementById('gcep-qr-section'),
		qrImg:         () => document.getElementById('gcep-qr-img'),
		qrCode:        () => document.getElementById('gcep-qr-code'),
		pixStatus:     () => document.getElementById('gcep-pix-status'),
		pixTimer:      () => document.getElementById('gcep-pix-timer'),
		btnCopiar:     () => document.getElementById('gcep-btn-copiar'),
		msgSucesso:    () => document.getElementById('gcep-msg-sucesso'),
		msgErro:       () => document.getElementById('gcep-msg-erro'),
		msgErroTexto:  () => document.getElementById('gcep-msg-erro-texto'),
		cartaoErro:    () => document.getElementById('gcep-cartao-erro'),
	};

	/* ── Abas ────────────────────────────────────────────── */

	function initTabs() {
		const tabPix    = els.tabPix();
		const tabCartao = els.tabCartao();

		if (!tabPix) return;

		tabPix.addEventListener('click', function () {
			setActiveTab('pix');
		});

		if (tabCartao) {
			tabCartao.addEventListener('click', function () {
				setActiveTab('cartao');
			});
		}
	}

	function setActiveTab(tab) {
		const tabPix      = els.tabPix();
		const tabCartao   = els.tabCartao();
		const panelPix    = els.panelPix();
		const panelCartao = els.panelCartao();
		const panelManual = els.panelManual();

		const activeClass   = 'bg-white text-slate-900 shadow-sm font-bold';
		const inactiveClass = 'text-slate-500 hover:text-slate-700';

		if (tabPix) {
			tabPix.className = tabPix.className.replace(/bg-white text-slate-900 shadow-sm font-bold|text-slate-500 hover:text-slate-700/g, '').trim();
			tabPix.classList.add(...(tab === 'pix' ? activeClass : inactiveClass).split(' '));
		}
		if (tabCartao) {
			tabCartao.className = tabCartao.className.replace(/bg-white text-slate-900 shadow-sm font-bold|text-slate-500 hover:text-slate-700/g, '').trim();
			tabCartao.classList.add(...(tab === 'cartao' ? activeClass : inactiveClass).split(' '));
		}

		if (panelPix)    panelPix.style.display    = tab === 'pix' ? 'block' : 'none';
		if (panelCartao) panelCartao.style.display  = tab === 'cartao' ? 'block' : 'none';
		if (panelManual) panelManual.style.display  = 'none';
	}

	/* ── PIX ─────────────────────────────────────────────── */

	function initPix() {
		const form = els.formPix();
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (processando) return;
			processando = true;

			hideMessages();
			const btn = form.querySelector('button[type="submit"]');
			const originalText = btn.textContent;
			btn.disabled = true;
			btn.textContent = 'Gerando PIX...';

			const anuncioId = form.querySelector('[name="anuncio_id"]').value;
			const cpf       = form.querySelector('[name="cpf"]').value;

			const fd = new FormData();
			fd.append('action', 'gcep_criar_cobranca');
			fd.append('nonce', gcepData.nonce);
			fd.append('anuncio_id', anuncioId);
			fd.append('metodo', 'pix');
			fd.append('cpf', cpf);

			fetch(gcepData.ajaxUrl, { method: 'POST', body: fd })
				.then(r => r.json())
				.then(function (res) {
					processando = false;
					btn.disabled = false;
					btn.textContent = originalText;

					if (!res.success) {
						showError(res.data?.message || 'Erro ao gerar PIX.');
						return;
					}

					showQrCode(res.data, anuncioId);
				})
				.catch(function () {
					processando = false;
					btn.disabled = false;
					btn.textContent = originalText;
					showError('Erro de conexao. Tente novamente.');
				});
		});

		// Copiar codigo PIX
		const btnCopiar = els.btnCopiar();
		if (btnCopiar) {
			btnCopiar.addEventListener('click', function () {
				const code = els.qrCode();
				if (code) {
					navigator.clipboard.writeText(code.textContent).then(function () {
						btnCopiar.textContent = 'Copiado!';
						setTimeout(function () { btnCopiar.textContent = 'Copiar PIX'; }, 2000);
					});
				}
			});
		}
	}

	function showQrCode(data, anuncioId) {
		const formPix   = els.formPix();
		const qrSection = els.qrSection();
		const qrImg     = els.qrImg();
		const qrCode    = els.qrCode();

		if (formPix)   formPix.style.display   = 'none';
		if (qrSection) qrSection.style.display = 'block';
		if (qrImg && data.pix_qr_image) qrImg.src = data.pix_qr_image;
		if (qrCode && data.pix_codigo)  qrCode.textContent = data.pix_codigo;

		startPolling(anuncioId);
		startTimer();
	}

	/* ── CARTAO ──────────────────────────────────────────── */

	function initCartao() {
		const form = els.formCartao();
		if (!form) return;

		// Mascara numero do cartao (espacos a cada 4 digitos)
		const numInput = form.querySelector('[name="cartao_numero"]');
		if (numInput) {
			numInput.addEventListener('input', function () {
				let v = this.value.replace(/\D/g, '').substring(0, 16);
				this.value = v.replace(/(.{4})/g, '$1 ').trim();
			});
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (processando) return;
			processando = true;

			hideMessages();
			const cartaoErro = els.cartaoErro();
			if (cartaoErro) cartaoErro.style.display = 'none';

			const btn = form.querySelector('button[type="submit"]');
			const originalText = btn.textContent;
			btn.disabled = true;
			btn.textContent = 'Processando...';

			const fd = new FormData();
			fd.append('action', 'gcep_criar_cobranca');
			fd.append('nonce', gcepData.nonce);
			fd.append('anuncio_id', form.querySelector('[name="anuncio_id"]').value);
			fd.append('metodo', 'credit_card');
			fd.append('cpf', form.querySelector('[name="cpf"]').value);
			fd.append('cartao_numero', form.querySelector('[name="cartao_numero"]').value.replace(/\D/g, ''));
			fd.append('cartao_mes', form.querySelector('[name="cartao_mes"]').value);
			fd.append('cartao_ano', form.querySelector('[name="cartao_ano"]').value);
			fd.append('cartao_cvv', form.querySelector('[name="cartao_cvv"]').value);
			fd.append('cartao_titular', form.querySelector('[name="cartao_titular"]').value);

			fetch(gcepData.ajaxUrl, { method: 'POST', body: fd })
				.then(r => r.json())
				.then(function (res) {
					processando = false;
					btn.disabled = false;
					btn.textContent = originalText;

					if (!res.success) {
						if (cartaoErro) {
							cartaoErro.textContent = res.data?.message || 'Pagamento recusado.';
							cartaoErro.style.display = 'block';
						}
						return;
					}

					if (res.data.aprovado) {
						showSuccess();
					} else {
						if (cartaoErro) {
							cartaoErro.textContent = res.data.erro || 'Pagamento nao aprovado. Tente outro cartao.';
							cartaoErro.style.display = 'block';
						}
					}
				})
				.catch(function () {
					processando = false;
					btn.disabled = false;
					btn.textContent = originalText;
					if (cartaoErro) {
						cartaoErro.textContent = 'Erro de conexao. Tente novamente.';
						cartaoErro.style.display = 'block';
					}
				});
		});
	}

	/* ── POLLING ─────────────────────────────────────────── */

	function startPolling(anuncioId) {
		stopPolling();

		pollingTimer = setInterval(function () {
			verificarPagamento(anuncioId);
		}, POLLING_INTERVAL);

		pollingTimeout = setTimeout(function () {
			stopPolling();
			const pixStatus = els.pixStatus();
			if (pixStatus) {
				pixStatus.textContent = 'PIX expirado. Gere um novo codigo.';
				pixStatus.classList.remove('text-amber-600');
				pixStatus.classList.add('text-red-600');
			}
		}, POLLING_TIMEOUT);
	}

	function stopPolling() {
		if (pollingTimer)   clearInterval(pollingTimer);
		if (pollingTimeout) clearTimeout(pollingTimeout);
		pollingTimer   = null;
		pollingTimeout = null;
	}

	function verificarPagamento(anuncioId) {
		const fd = new FormData();
		fd.append('action', 'gcep_verificar_pagamento');
		fd.append('nonce', gcepData.nonce);
		fd.append('anuncio_id', anuncioId);

		fetch(gcepData.ajaxUrl, { method: 'POST', body: fd })
			.then(r => r.json())
			.then(function (res) {
				if (res.success && res.data.pago) {
					stopPolling();
					showSuccess();
				}
			})
			.catch(function () {});
	}

	/* ── TIMER ───────────────────────────────────────────── */

	function startTimer() {
		let seconds = 30 * 60;
		const timerEl = els.pixTimer();
		if (!timerEl) return;

		const tick = setInterval(function () {
			seconds--;
			if (seconds <= 0) {
				clearInterval(tick);
				timerEl.textContent = '00:00';
				return;
			}
			const m = Math.floor(seconds / 60).toString().padStart(2, '0');
			const s = (seconds % 60).toString().padStart(2, '0');
			timerEl.textContent = m + ':' + s;
		}, 1000);
	}

	/* ── MENSAGENS ───────────────────────────────────────── */

	function showSuccess() {
		const el = els.msgSucesso();
		if (el) el.style.display = 'flex';

		// Esconder tudo
		const formPix    = els.formPix();
		const qrSection  = els.qrSection();
		const panelPix   = els.panelPix();
		const panelCartao = els.panelCartao();
		if (formPix)    formPix.style.display    = 'none';
		if (qrSection)  qrSection.style.display  = 'none';

		setTimeout(function () {
			window.location.href = gcepData.homeUrl + '/painel/anuncios?gcep_msg=' + encodeURIComponent('Pagamento confirmado! Seu anuncio esta ativo.') + '&gcep_type=success';
		}, 3000);
	}

	function showError(msg) {
		const el     = els.msgErro();
		const textoEl = els.msgErroTexto();
		if (el)     el.style.display = 'block';
		if (textoEl) textoEl.textContent = msg;
	}

	function hideMessages() {
		const erro    = els.msgErro();
		const sucesso = els.msgSucesso();
		if (erro)    erro.style.display    = 'none';
		if (sucesso) sucesso.style.display = 'none';
	}

	/* ── INIT ────────────────────────────────────────────── */

	document.addEventListener('DOMContentLoaded', function () {
		initTabs();
		initPix();
		initCartao();
	});
})();
