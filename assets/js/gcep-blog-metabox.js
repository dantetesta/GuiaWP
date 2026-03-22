/**
 * Meta box: busca e selecao de anuncios relacionados ao post
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.5.6 - 2026-03-11
 */
(function () {
	'use strict';

	var config = window.gcepBlogMetabox || {};
	var input = document.getElementById('gcep-anuncios-busca');
	var resultados = document.getElementById('gcep-anuncios-resultados');
	var lista = document.getElementById('gcep-anuncios-selecionados');

	if (!input || !resultados || !lista) return;

	var debounceTimer = null;

	function getSelectedIds() {
		var hiddens = lista.querySelectorAll('input[name="gcep_anuncios_rel[]"]');
		var ids = [];
		hiddens.forEach(function (h) { ids.push(parseInt(h.value, 10)); });
		return ids;
	}

	function addAnuncio(id, title) {
		if (getSelectedIds().length >= config.maxAnuncios) {
			gcepToast(config.textos.limite, 'warning');
			return;
		}
		if (getSelectedIds().indexOf(id) !== -1) return;

		var li = document.createElement('li');
		li.setAttribute('data-id', id);
		li.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:6px 8px;margin-bottom:4px;background:#f0f0f1;border-radius:4px;font-size:13px;';

		var span = document.createElement('span');
		span.textContent = title;

		var hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.name = 'gcep_anuncios_rel[]';
		hidden.value = id;

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'gcep-remover-anuncio';
		btn.style.cssText = 'background:none;border:none;color:#d63638;cursor:pointer;font-size:16px;line-height:1;';
		btn.innerHTML = '&times;';
		btn.title = 'Remover';
		btn.addEventListener('click', function () { li.remove(); });

		li.appendChild(span);
		li.appendChild(hidden);
		li.appendChild(btn);
		lista.appendChild(li);

		resultados.style.display = 'none';
		input.value = '';
	}

	function buscar(termo) {
		resultados.innerHTML = '<div style="padding:8px;color:#888;font-size:12px;">' + config.textos.buscando + '</div>';
		resultados.style.display = 'block';

		var url = config.ajaxUrl + '?action=gcep_buscar_anuncios&nonce=' + encodeURIComponent(config.nonce) + '&termo=' + encodeURIComponent(termo);

		fetch(url)
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res.success || !res.data || !res.data.length) {
					resultados.innerHTML = '<div style="padding:8px;color:#888;font-size:12px;">' + config.textos.nenhum + '</div>';
					return;
				}

				var selectedIds = getSelectedIds();
				var html = '';
				res.data.forEach(function (item) {
					var disabled = selectedIds.indexOf(item.id) !== -1;
					var badge = item.plano === 'premium' ? ' <span style="background:#f59e0b;color:#fff;font-size:10px;padding:1px 5px;border-radius:3px;margin-left:4px;">Premium</span>' : '';
					html += '<div class="gcep-anuncio-resultado" data-id="' + item.id + '" data-title="' + item.title.replace(/"/g, '&quot;') + '" style="padding:7px 10px;cursor:' + (disabled ? 'default' : 'pointer') + ';font-size:13px;border-bottom:1px solid #f0f0f1;' + (disabled ? 'opacity:0.5;' : '') + '">';
					html += item.title + badge;
					html += '</div>';
				});
				resultados.innerHTML = html;

				resultados.querySelectorAll('.gcep-anuncio-resultado').forEach(function (el) {
					el.addEventListener('click', function () {
						var id = parseInt(el.getAttribute('data-id'), 10);
						var title = el.getAttribute('data-title');
						addAnuncio(id, title);
					});
				});
			})
			.catch(function () {
				resultados.innerHTML = '<div style="padding:8px;color:#d63638;font-size:12px;">Erro na busca.</div>';
			});
	}

	input.addEventListener('input', function () {
		clearTimeout(debounceTimer);
		var val = input.value.trim();
		if (val.length < 2) {
			resultados.style.display = 'none';
			return;
		}
		debounceTimer = setTimeout(function () { buscar(val); }, 300);
	});

	input.addEventListener('focus', function () {
		if (input.value.trim().length >= 2) {
			buscar(input.value.trim());
		}
	});

	document.addEventListener('click', function (e) {
		if (!e.target.closest('#gcep-anuncios-rel-wrap')) {
			resultados.style.display = 'none';
		}
	});

	// Delegacao para botoes de remover existentes
	lista.addEventListener('click', function (e) {
		var btn = e.target.closest('.gcep-remover-anuncio');
		if (btn) {
			btn.closest('li').remove();
		}
	});
})();
