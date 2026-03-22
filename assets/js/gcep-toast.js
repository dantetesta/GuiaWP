/**
 * Sistema global de notificações Toast — estilo SweetAlert
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.8.2 - 2026-03-20
 *
 * Uso:
 *   gcepToast('Mensagem aqui', 'success')   // success | error | warning | info
 *   gcepToast('Mensagem aqui', 'error', 6000) // duração customizada em ms
 */

(function () {
	'use strict';

	var DURATION_DEFAULT = 4000;
	var ANIMATION_OUT    = 350;

	var icons = {
		success: 'check_circle',
		error:   'cancel',
		warning: 'warning',
		info:    'info',
	};

	var colors = {
		success: { bg: '#f0fdf4', border: '#bbf7d0', icon: '#16a34a', bar: '#16a34a' },
		error:   { bg: '#fff1f2', border: '#fecdd3', icon: '#e11d48', bar: '#e11d48' },
		warning: { bg: '#fffbeb', border: '#fde68a', icon: '#d97706', bar: '#d97706' },
		info:    { bg: '#eff6ff', border: '#bfdbfe', icon: '#2563eb', bar: '#2563eb' },
	};

	// Injetar keyframes uma única vez
	function injectStyles() {
		if (document.getElementById('gcep-toast-styles')) return;
		var style = document.createElement('style');
		style.id  = 'gcep-toast-styles';
		style.textContent = [
			'@keyframes gcepToastIn{from{opacity:0;transform:translate(-50%,-50%) scale(.7)}to{opacity:1;transform:translate(-50%,-50%) scale(1)}}',
			'@keyframes gcepToastOut{from{opacity:1;transform:translate(-50%,-50%) scale(1)}to{opacity:0;transform:translate(-50%,-50%) scale(.7)}}',
			'@keyframes gcepToastBar{from{width:100%}to{width:0%}}',
		].join('');
		document.head.appendChild(style);
	}

	function removeToast(el, overlay) {
		el.style.animation = 'gcepToastOut ' + ANIMATION_OUT + 'ms cubic-bezier(.55,.06,.68,.19) forwards';
		setTimeout(function () {
			if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
		}, ANIMATION_OUT);
	}

	/**
	 * Exibe um toast centrado na tela.
	 *
	 * @param {string} message  - Texto da notificação
	 * @param {string} type     - 'success' | 'error' | 'warning' | 'info'
	 * @param {number} duration - Duração em ms (padrão: 4000)
	 */
	function gcepToast(message, type, duration) {
		injectStyles();

		type     = type     || 'success';
		duration = duration || DURATION_DEFAULT;

		var c    = colors[type]  || colors.info;
		var icon = icons[type]   || 'info';

		// Overlay de fundo (click para fechar)
		var overlay      = document.createElement('div');
		overlay.style.cssText = [
			'position:fixed',
			'inset:0',
			'display:flex',
			'align-items:center',
			'justify-content:center',
			'z-index:99999',
			'pointer-events:none',
		].join(';');

		// Card principal
		var card       = document.createElement('div');
		card.style.cssText = [
			'background:' + c.bg,
			'border:1.5px solid ' + c.border,
			'border-radius:20px',
			'padding:32px 36px 24px',
			'min-width:300px',
			'max-width:420px',
			'width:calc(100vw - 48px)',
			'text-align:center',
			'box-shadow:0 20px 60px rgba(0,0,0,.15),0 4px 16px rgba(0,0,0,.08)',
			'position:fixed',
			'top:50%',
			'left:50%',
			'transform:translate(-50%,-50%)',
			'pointer-events:all',
			'animation:gcepToastIn .4s cubic-bezier(.34,1.56,.64,1) forwards',
			'z-index:99999',
			'overflow:hidden',
		].join(';');

		// Ícone
		var iconWrap   = document.createElement('div');
		iconWrap.style.cssText = [
			'width:64px',
			'height:64px',
			'border-radius:50%',
			'background:' + c.icon + '1a',
			'display:flex',
			'align-items:center',
			'justify-content:center',
			'margin:0 auto 16px',
		].join(';');

		var iconEl     = document.createElement('span');
		iconEl.className     = 'material-symbols-outlined';
		iconEl.style.cssText = 'font-size:36px;color:' + c.icon + ';font-variation-settings:"FILL" 1,"wght" 400';
		iconEl.textContent   = icon;
		iconWrap.appendChild(iconEl);

		// Mensagem
		var msgEl     = document.createElement('p');
		msgEl.style.cssText = [
			'font-size:15px',
			'font-weight:500',
			'color:#1e293b',
			'line-height:1.5',
			'margin:0 0 20px',
			'font-family:inherit',
		].join(';');
		msgEl.textContent = message;

		// Botão fechar
		var closeBtn   = document.createElement('button');
		closeBtn.style.cssText = [
			'position:absolute',
			'top:12px',
			'right:12px',
			'background:none',
			'border:none',
			'cursor:pointer',
			'padding:4px',
			'color:#94a3b8',
			'line-height:1',
			'display:flex',
		].join(';');
		closeBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:20px">close</span>';
		closeBtn.addEventListener('click', function () { removeToast(card, overlay); });

		// Barra de progresso
		var barTrack   = document.createElement('div');
		barTrack.style.cssText = [
			'position:absolute',
			'bottom:0',
			'left:0',
			'right:0',
			'height:4px',
			'background:' + c.border,
			'border-radius:0 0 20px 20px',
			'overflow:hidden',
		].join(';');

		var bar        = document.createElement('div');
		bar.style.cssText = [
			'height:100%',
			'background:' + c.bar,
			'animation:gcepToastBar ' + duration + 'ms linear forwards',
			'transform-origin:left',
		].join(';');
		barTrack.appendChild(bar);

		// Montar card
		card.appendChild(closeBtn);
		card.appendChild(iconWrap);
		card.appendChild(msgEl);
		card.appendChild(barTrack);

		overlay.appendChild(card);
		document.body.appendChild(overlay);

		// Auto-dismiss
		var timer = setTimeout(function () { removeToast(card, overlay); }, duration);

		// Pausar barra ao hover
		card.addEventListener('mouseenter', function () {
			bar.style.animationPlayState = 'paused';
			clearTimeout(timer);
		});
		card.addEventListener('mouseleave', function () {
			bar.style.animationPlayState = 'running';
			timer = setTimeout(function () { removeToast(card, overlay); }, 800);
		});
	}

	// Expor globalmente
	window.gcepToast = gcepToast;

})();
