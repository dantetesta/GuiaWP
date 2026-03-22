/**
 * Sistema de Crop de Imagens - Logo (1:1 500x500), Capa (3.5:1 1400x400), Hero (2.4:1 1920x800)
 * Gera imagem final em WebP com qualidade otimizada por tipo
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.1.0 - 2026-03-11
 * @modified 1.5.4 - 2026-03-11 - Adicionado crop hero 1920x800 com qualidade 85%
 * @modified 1.9.8 - 2026-03-22 - Adicionado crop CTA 800x600 para imagem do bloco CTA na página inicial
 */
(function () {
	'use strict';

	// Configurações de crop por tipo
	var CROP_CONFIG = {
		logo: { width: 500, height: 500, ratio: 1, label: 'Logo / Foto Principal', quality: 0.9 },
		capa: { width: 1400, height: 400, ratio: 3.5, label: 'Foto de Capa', quality: 0.9 },
		hero: { width: 1920, height: 800, ratio: 2.4, label: 'Imagem do Hero', quality: 0.85 },
		categoria: { width: 400, height: 400, ratio: 1, label: 'Imagem da Categoria', quality: 0.9 },
		cta: { width: 800, height: 600, ratio: 4 / 3, label: 'Imagem CTA', quality: 0.9 }
	};

	/**
	 * Inicializa o crop para um input de arquivo
	 */
	function initCrop(inputId, type, hiddenInputName) {
		var fileInput = document.getElementById(inputId);
		if (!fileInput) return;

		var container = fileInput.closest('.gcep-crop-wrapper');
		if (!container) return;

		var previewArea = container.querySelector('.gcep-crop-preview');
		var cropArea    = container.querySelector('.gcep-crop-area');
		var btnConfirm  = container.querySelector('.gcep-crop-confirm');
		var btnCancel   = container.querySelector('.gcep-crop-cancel');
		var resultImg   = container.querySelector('.gcep-crop-result');
		var resultWrap  = container.querySelector('.gcep-crop-result-wrap');
		var hiddenInput = container.querySelector('input[name="' + hiddenInputName + '"]');

		var config = CROP_CONFIG[type];
		var img = null;
		var cropBox = null;
		var isDragging = false;
		var isResizing = false;
		var startX, startY, startLeft, startTop, startW, startH;

		fileInput.addEventListener('change', function (e) {
			var file = e.target.files[0];
			if (!file || !file.type.startsWith('image/')) return;

			var reader = new FileReader();
			reader.onload = function (ev) {
				showCropUI(ev.target.result);
			};
			reader.readAsDataURL(file);
		});

		function showCropUI(src) {
			if (resultWrap) resultWrap.classList.add('hidden');
			cropArea.classList.remove('hidden');

			img = new Image();
			img.onload = function () {
				// Dimensionar imagem para caber na área de preview
				var maxW = previewArea.clientWidth || container.clientWidth || 400;
				var maxH = Math.min(400, window.innerHeight * 0.5);
				var scale = Math.min(maxW / img.naturalWidth, maxH / img.naturalHeight, 1);
				var dispW = Math.round(img.naturalWidth * scale);
				var dispH = Math.round(img.naturalHeight * scale);

				previewArea.innerHTML = '';
				img.style.width = dispW + 'px';
				img.style.height = dispH + 'px';
				img.style.display = 'block';
				img.draggable = false;
				previewArea.appendChild(img);
				previewArea.style.width = dispW + 'px';
				previewArea.style.height = dispH + 'px';
				previewArea.style.position = 'relative';

				// Criar overlay e cropBox
				createCropOverlay(dispW, dispH);
			};
			img.src = src;
		}

		function createCropOverlay(dispW, dispH) {
			// Remover overlay anterior
			var oldOverlay = previewArea.querySelector('.gcep-overlay');
			if (oldOverlay) oldOverlay.remove();

			var overlay = document.createElement('div');
			overlay.className = 'gcep-overlay';
			overlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;';

			// Calcular tamanho inicial do crop
			var cropW, cropH;
			if (config.ratio >= 1) {
				cropW = Math.min(dispW * 0.8, dispW);
				cropH = cropW / config.ratio;
				if (cropH > dispH * 0.8) {
					cropH = dispH * 0.8;
					cropW = cropH * config.ratio;
				}
			} else {
				cropH = Math.min(dispH * 0.8, dispH);
				cropW = cropH * config.ratio;
				if (cropW > dispW * 0.8) {
					cropW = dispW * 0.8;
					cropH = cropW / config.ratio;
				}
			}

			cropW = Math.round(cropW);
			cropH = Math.round(cropH);

			var initX = Math.round((dispW - cropW) / 2);
			var initY = Math.round((dispH - cropH) / 2);

			// Fundo escuro com buraco
			overlay.innerHTML =
				'<div class="gcep-crop-dim" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);pointer-events:none;"></div>' +
				'<div class="gcep-crop-box" style="position:absolute;left:' + initX + 'px;top:' + initY + 'px;width:' + cropW + 'px;height:' + cropH + 'px;border:2px solid #fff;box-shadow:0 0 0 9999px rgba(0,0,0,0.5);cursor:move;z-index:2;touch-action:none;">' +
					'<div class="gcep-crop-handle gcep-crop-handle-se" style="position:absolute;right:-6px;bottom:-6px;width:12px;height:12px;background:#fff;border-radius:50%;cursor:nwse-resize;z-index:3;touch-action:none;"></div>' +
				'</div>';

			previewArea.appendChild(overlay);
			cropBox = overlay.querySelector('.gcep-crop-box');

			// Eventos de drag (move)
			cropBox.addEventListener('mousedown', onDragStart);
			cropBox.addEventListener('touchstart', onDragStart, { passive: false });

			// Eventos de resize
			var handle = overlay.querySelector('.gcep-crop-handle-se');
			handle.addEventListener('mousedown', onResizeStart);
			handle.addEventListener('touchstart', onResizeStart, { passive: false });

			document.addEventListener('mousemove', onMove);
			document.addEventListener('touchmove', onMove, { passive: false });
			document.addEventListener('mouseup', onEnd);
			document.addEventListener('touchend', onEnd);
		}

		function getPos(e) {
			if (e.touches && e.touches.length > 0) {
				return { x: e.touches[0].clientX, y: e.touches[0].clientY };
			}
			return { x: e.clientX, y: e.clientY };
		}

		function onDragStart(e) {
			if (e.target.classList.contains('gcep-crop-handle-se')) return;
			e.preventDefault();
			isDragging = true;
			var pos = getPos(e);
			startX = pos.x;
			startY = pos.y;
			startLeft = parseInt(cropBox.style.left);
			startTop = parseInt(cropBox.style.top);
		}

		function onResizeStart(e) {
			e.preventDefault();
			e.stopPropagation();
			isResizing = true;
			var pos = getPos(e);
			startX = pos.x;
			startY = pos.y;
			startW = parseInt(cropBox.style.width);
			startH = parseInt(cropBox.style.height);
		}

		function onMove(e) {
			if (!isDragging && !isResizing) return;
			e.preventDefault();

			var pos = getPos(e);
			var dx = pos.x - startX;
			var dy = pos.y - startY;
			var parentW = previewArea.clientWidth;
			var parentH = previewArea.clientHeight;

			if (isDragging) {
				var newLeft = Math.max(0, Math.min(startLeft + dx, parentW - parseInt(cropBox.style.width)));
				var newTop = Math.max(0, Math.min(startTop + dy, parentH - parseInt(cropBox.style.height)));
				cropBox.style.left = newLeft + 'px';
				cropBox.style.top = newTop + 'px';
			}

			if (isResizing) {
				var newW = Math.max(60, startW + dx);
				var newH = newW / config.ratio;

				// Limitar ao container
				var boxLeft = parseInt(cropBox.style.left);
				var boxTop = parseInt(cropBox.style.top);
				if (boxLeft + newW > parentW) newW = parentW - boxLeft;
				newH = newW / config.ratio;
				if (boxTop + newH > parentH) {
					newH = parentH - boxTop;
					newW = newH * config.ratio;
				}

				cropBox.style.width = Math.round(newW) + 'px';
				cropBox.style.height = Math.round(newH) + 'px';
			}
		}

		function onEnd() {
			isDragging = false;
			isResizing = false;
		}

		if (btnConfirm) {
			btnConfirm.addEventListener('click', function () {
				doCrop();
			});
		}

		if (btnCancel) {
			btnCancel.addEventListener('click', function () {
				cropArea.classList.add('hidden');
				fileInput.value = '';
			});
		}

		function doCrop() {
			if (!img || !cropBox) return;

			var scaleX = img.naturalWidth / img.clientWidth;
			var scaleY = img.naturalHeight / img.clientHeight;

			var sx = parseInt(cropBox.style.left) * scaleX;
			var sy = parseInt(cropBox.style.top) * scaleY;
			var sw = parseInt(cropBox.style.width) * scaleX;
			var sh = parseInt(cropBox.style.height) * scaleY;

			var canvas = document.createElement('canvas');
			canvas.width = config.width;
			canvas.height = config.height;
			var ctx = canvas.getContext('2d');

			ctx.drawImage(img, sx, sy, sw, sh, 0, 0, config.width, config.height);

			var quality = config.quality || 0.9;
			canvas.toBlob(function (blob) {
				if (!blob) return;

				// Atualizar preview
				var url = URL.createObjectURL(blob);
				if (resultImg) {
					resultImg.src = url;
				}
				if (resultWrap) {
					resultWrap.classList.remove('hidden');
				}
				cropArea.classList.add('hidden');

				// Substituir o arquivo no input por um File a partir do blob
				var fileName = type + '_crop_' + Date.now() + '.webp';
				var croppedFile = new File([blob], fileName, { type: 'image/webp' });

				var dataTransfer = new DataTransfer();
				dataTransfer.items.add(croppedFile);
				fileInput.files = dataTransfer.files;

			}, 'image/webp', quality);
		}
	}

	// Inicializar crops quando DOM estiver pronto
	function initAllCrops() {
		initCrop('gcep_logo_input', 'logo', 'gcep_logo');
		initCrop('gcep_capa_input', 'capa', 'gcep_capa');
		initCrop('gcep-hero-img-input', 'hero', 'gcep_hero_imagem');
		initCrop('gcep-cat-img-input', 'categoria', 'category_image');
		initCrop('gcep-cta-img-input', 'cta', 'gcep_cta_imagem');
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAllCrops);
	} else {
		initAllCrops();
	}

	// Expor para uso externo se necessário
	window.GCEPCrop = { initCrop: initCrop };
})();
