/**
 * Upload AJAX de galeria de fotos com conversão WebP server-side
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.2.1 - 2026-03-11
 */
(function () {
	'use strict';

	var grid      = document.getElementById('gcep-gallery-grid');
	var input     = document.getElementById('gcep-gallery-input');
	var counter   = document.getElementById('gcep-gallery-count');
	var errorEl   = document.getElementById('gcep-gallery-error');
	var section   = document.getElementById('gcep-gallery-section');

	if (!grid || !input) return;

	var MAX_FILES  = 20;
	var MAX_SIZE   = 15 * 1024 * 1024;
	var ALLOWED    = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/heic', 'image/heif'];

	function getPostId() {
		var el = document.getElementById('gcep_anuncio_id');
		return el ? parseInt(el.value, 10) : 0;
	}

	function currentCount() {
		return grid.querySelectorAll('.gcep-gallery-item').length;
	}

	function updateCounter() {
		if (counter) {
			counter.textContent = currentCount() + ' / ' + MAX_FILES;
		}
	}

	function showError(msg) {
		if (!errorEl) return;
		errorEl.textContent = msg;
		errorEl.classList.remove('hidden');
		setTimeout(function () {
			errorEl.classList.add('hidden');
		}, 5000);
	}

	// Criar item de preview com loader
	function createPlaceholder() {
		var div = document.createElement('div');
		div.className = 'gcep-gallery-item relative group';
		div.innerHTML =
			'<div class="w-full aspect-square rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center">' +
				'<div class="gcep-gallery-loader animate-spin w-6 h-6 border-2 border-[#0052cc] border-t-transparent rounded-full"></div>' +
			'</div>';
		return div;
	}

	// Substituir placeholder por imagem real
	function replacePlaceholder(placeholder, attachmentId, thumbUrl) {
		placeholder.setAttribute('data-id', attachmentId);
		placeholder.innerHTML =
			'<img src="' + thumbUrl + '" alt="" class="w-full aspect-square object-cover rounded-xl border border-slate-200">' +
			'<button type="button" class="gcep-gallery-remove absolute top-1 right-1 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" title="Remover">' +
				'<span class="material-symbols-outlined text-sm">close</span>' +
			'</button>';
	}

	// Upload individual de arquivo
	function uploadFile(file) {
		var postId = getPostId();
		if (postId <= 0) {
			showError('Salve o tipo e plano primeiro (avance para o próximo step).');
			return;
		}

		if (currentCount() >= MAX_FILES) {
			showError('Limite de ' + MAX_FILES + ' fotos atingido.');
			return;
		}

		if (file.size > MAX_SIZE) {
			showError('Arquivo "' + file.name + '" excede 15 MB.');
			return;
		}

		if (ALLOWED.indexOf(file.type) === -1) {
			showError('Formato não suportado: ' + file.name);
			return;
		}

		var placeholder = createPlaceholder();
		grid.appendChild(placeholder);
		updateCounter();

		var formData = new FormData();
		formData.append('action', 'gcep_upload_gallery_photo');
		formData.append('nonce', gcepData.nonce);
		formData.append('post_id', postId);
		formData.append('file', file);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', gcepData.ajaxUrl, true);

		xhr.onload = function () {
			try {
				var res = JSON.parse(xhr.responseText);
				if (res.success && res.data) {
					replacePlaceholder(placeholder, res.data.attachment_id, res.data.thumb_url);
				} else {
					placeholder.remove();
					showError(res.data && res.data.message ? res.data.message : 'Erro no upload.');
				}
			} catch (e) {
				placeholder.remove();
				showError('Erro inesperado no upload.');
			}
			updateCounter();
		};

		xhr.onerror = function () {
			placeholder.remove();
			updateCounter();
			showError('Erro de rede no upload.');
		};

		xhr.send(formData);
	}

	// Listener de seleção de arquivos
	input.addEventListener('change', function () {
		var files = Array.from(this.files);
		files.forEach(function (file) {
			uploadFile(file);
		});
		// Limpar input para permitir re-seleção do mesmo arquivo
		input.value = '';
	});

	// Remover foto via delegação de eventos
	grid.addEventListener('click', function (e) {
		var btn = e.target.closest('.gcep-gallery-remove');
		if (!btn) return;

		var item = btn.closest('.gcep-gallery-item');
		if (!item) return;

		var attachmentId = item.getAttribute('data-id');
		var postId = getPostId();

		if (!attachmentId || !postId) {
			item.remove();
			updateCounter();
			return;
		}

		// Feedback visual imediato
		item.style.opacity = '0.4';
		item.style.pointerEvents = 'none';

		var formData = new FormData();
		formData.append('action', 'gcep_remove_gallery_photo');
		formData.append('nonce', gcepData.nonce);
		formData.append('post_id', postId);
		formData.append('attachment_id', attachmentId);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', gcepData.ajaxUrl, true);

		xhr.onload = function () {
			try {
				var res = JSON.parse(xhr.responseText);
				if (res.success) {
					item.remove();
				} else {
					item.style.opacity = '1';
					item.style.pointerEvents = '';
					showError(res.data && res.data.message ? res.data.message : 'Erro ao remover.');
				}
			} catch (e) {
				item.style.opacity = '1';
				item.style.pointerEvents = '';
			}
			updateCounter();
		};

		xhr.onerror = function () {
			item.style.opacity = '1';
			item.style.pointerEvents = '';
		};

		xhr.send(formData);
	});

	// Inicializar contador
	updateCounter();
})();
