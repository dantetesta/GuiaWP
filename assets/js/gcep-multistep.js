/**
 * Formulario Multi-Step para criacao/edicao de anuncios
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.1.0 - 2026-03-11
 * @modified 1.9.6 - 2026-03-21 - Redesign 5 steps, barra de progresso linear, nav sticky bottom
 * @modified 1.9.7 - 2026-03-21 - 6 steps (contato/endereco separados)
 * @modified 1.9.8 - 2026-03-21 - 7 steps (galeria separada de midia)
 */
(function () {
	'use strict';

	var form = document.getElementById('gcep-multistep-form');
	if (!form) return;

	var steps       = form.querySelectorAll('[data-step]');
	var stepLabels  = document.querySelectorAll('[data-step-label]');
	var progressBar = document.getElementById('gcep-progress-bar');
	var stepCounter = document.getElementById('gcep-step-counter');
	var formHeader  = document.getElementById('gcep-form-header');
	var navBottom   = document.getElementById('gcep-nav-bottom');
	var btnPrev     = document.getElementById('gcep-step-prev');
	var btnNext     = document.getElementById('gcep-step-next');
	var btnSubmit   = document.getElementById('gcep-step-submit');
	var currentStep = 1;
	var totalSteps  = steps.length;
	var isEditMode  = form.getAttribute('data-edit-mode') === '1';

	// Elementos do overlay de resultado (somente criacao)
	var resultOverlay  = document.getElementById('gcep-result-overlay');
	var resultLoading  = document.getElementById('gcep-result-loading');
	var resultApproved = document.getElementById('gcep-result-approved');
	var resultRejected = document.getElementById('gcep-result-rejected');
	var GENERIC_VALIDATION_FALLBACK = 'A validação automática encontrou problemas, mas não retornou uma justificativa detalhada. Revise título, descrição e dados de contato antes de tentar novamente.';

	function debugLog() {
		if (!window.gcepData || !gcepData.debug || !window.console || typeof window.console.log !== 'function') {
			return;
		}

		window.console.log.apply(window.console, arguments);
	}

	function getPlano() {
		// No modo edicao com upgrade disponivel, verificar radio de upgrade
		var editPlanoRadio = form.querySelector('input[name="gcep_edit_plano"]:checked');
		if (editPlanoRadio) return editPlanoRadio.value;
		// No modo edicao sem upgrade (premium travado), vem via hidden
		var hidden = form.querySelector('input[name="gcep_tipo_plano"][type="hidden"]');
		if (hidden) return hidden.value;
		var checked = form.querySelector('input[name="gcep_tipo_plano"]:checked');
		return checked ? checked.value : 'gratis';
	}

	function getTipo() {
		var checked = form.querySelector('input[name="gcep_tipo_anuncio"]:checked');
		return checked ? checked.value : 'empresa';
	}

	var richTextWrappers = Array.prototype.slice.call(form.querySelectorAll('[data-rich-text-wrapper]'));

	function sanitizeLinkUrl(url) {
		var normalized = String(url || '').trim();
		if (!normalized) return '';

		if (!/^(https?:\/\/|mailto:|tel:)/i.test(normalized)) {
			normalized = 'https://' + normalized.replace(/^\/+/, '');
		}

		if (!/^(https?:\/\/|mailto:|tel:)/i.test(normalized)) {
			return '';
		}

		return normalized;
	}

	function sanitizeEditorNode(node, doc) {
		if (node.nodeType === Node.TEXT_NODE) {
			return doc.createTextNode(node.textContent || '');
		}

		if (node.nodeType !== Node.ELEMENT_NODE) {
			return null;
		}

		var tagName = node.tagName.toUpperCase();
		var passthroughTags = ['SPAN', 'FONT'];
		var blockToParagraph = ['DIV', 'SECTION', 'ARTICLE'];
		var allowedTags = {
			P: [],
			BR: [],
			STRONG: [],
			B: [],
			EM: [],
			I: [],
			U: [],
			UL: [],
			OL: [],
			LI: [],
			H2: [],
			H3: [],
			H4: [],
			BLOCKQUOTE: [],
			TABLE: [],
			THEAD: [],
			TBODY: [],
			TR: [],
			TH: ['scope'],
			TD: ['colspan', 'rowspan'],
			A: ['href', 'target', 'rel']
		};

		if (passthroughTags.indexOf(tagName) !== -1) {
			var fragment = doc.createDocumentFragment();
			Array.prototype.forEach.call(node.childNodes, function (child) {
				var cleanChild = sanitizeEditorNode(child, doc);
				if (cleanChild) fragment.appendChild(cleanChild);
			});
			return fragment;
		}

		if (blockToParagraph.indexOf(tagName) !== -1) {
			tagName = 'P';
		}

		if (!allowedTags[tagName]) {
			var fallback = doc.createDocumentFragment();
			Array.prototype.forEach.call(node.childNodes, function (child) {
				var cleanChild = sanitizeEditorNode(child, doc);
				if (cleanChild) fallback.appendChild(cleanChild);
			});
			return fallback;
		}

		var cleanNode = doc.createElement(tagName.toLowerCase());
		var allowedAttributes = allowedTags[tagName];

		allowedAttributes.forEach(function (attribute) {
			if (!node.hasAttribute(attribute)) return;
			var attributeValue = String(node.getAttribute(attribute) || '').trim();
			if (!attributeValue) return;

			if (tagName === 'A' && attribute === 'href') {
				attributeValue = sanitizeLinkUrl(attributeValue);
				if (!attributeValue) return;
			}

			cleanNode.setAttribute(attribute, attributeValue);
		});

		if (tagName === 'A') {
			if (!cleanNode.getAttribute('href')) {
				var anchorFallback = doc.createDocumentFragment();
				Array.prototype.forEach.call(node.childNodes, function (child) {
					var cleanChild = sanitizeEditorNode(child, doc);
					if (cleanChild) anchorFallback.appendChild(cleanChild);
				});
				return anchorFallback;
			}

			cleanNode.setAttribute('target', '_blank');
			cleanNode.setAttribute('rel', 'noopener noreferrer nofollow');
		}

		Array.prototype.forEach.call(node.childNodes, function (child) {
			var cleanChild = sanitizeEditorNode(child, doc);
			if (cleanChild) cleanNode.appendChild(cleanChild);
		});

		if (
			['P', 'LI', 'TH', 'TD', 'H2', 'H3', 'H4', 'BLOCKQUOTE'].indexOf(tagName) !== -1 &&
			!String(cleanNode.textContent || '').trim() &&
			!cleanNode.querySelector('br')
		) {
			return null;
		}

		return cleanNode;
	}

	function sanitizeEditorHtml(html) {
		var temp = document.createElement('div');
		var wrapper = document.createElement('div');
		temp.innerHTML = String(html || '');

		Array.prototype.forEach.call(temp.childNodes, function (node) {
			var cleanNode = sanitizeEditorNode(node, document);
			if (cleanNode) wrapper.appendChild(cleanNode);
		});

		return wrapper.innerHTML
			.replace(/<(p|h2|h3|h4|li|th|td)>\s*(?:<br\s*\/?>|&nbsp;|\s)*<\/\1>/gi, '')
			.replace(/\u00a0/g, ' ')
			.trim();
	}

	function syncRichEditor(wrapper, refreshSurface) {
		var surface = wrapper.querySelector('[data-rich-editor]');
		var input = wrapper.querySelector('[data-rich-text-input]');
		if (!surface || !input) return;

		var cleanHtml = sanitizeEditorHtml(surface.innerHTML);
		input.value = cleanHtml;

		if (refreshSurface && surface.innerHTML !== cleanHtml) {
			surface.innerHTML = cleanHtml;
		}
	}

	function syncAllRichEditors(refreshSurface) {
		richTextWrappers.forEach(function (wrapper) {
			syncRichEditor(wrapper, refreshSurface);
		});
	}

	function execRichEditorCommand(wrapper, action) {
		var surface = wrapper.querySelector('[data-rich-editor]');
		if (!surface) return;

		surface.focus();

		switch (action) {
			case 'bold':
				document.execCommand('bold', false, null);
				break;
			case 'italic':
				document.execCommand('italic', false, null);
				break;
			case 'unorderedList':
				document.execCommand('insertUnorderedList', false, null);
				break;
			case 'orderedList':
				document.execCommand('insertOrderedList', false, null);
				break;
			case 'h2':
			case 'h3':
			case 'h4':
			case 'p':
				document.execCommand('formatBlock', false, '<' + action + '>');
				break;
			case 'link':
				var url = window.prompt('Digite a URL externa do link:');
				url = sanitizeLinkUrl(url);
				if (!url) return;
				document.execCommand('createLink', false, url);
				Array.prototype.forEach.call(surface.querySelectorAll('a[href]'), function (link) {
					link.setAttribute('target', '_blank');
					link.setAttribute('rel', 'noopener noreferrer nofollow');
				});
				break;
			case 'table':
				document.execCommand(
					'insertHTML',
					false,
					'<table><thead><tr><th>Item</th><th>Detalhe</th></tr></thead><tbody><tr><td>Exemplo</td><td>Descreva aqui</td></tr></tbody></table><p></p>'
				);
				break;
			case 'clear':
				document.execCommand('removeFormat', false, null);
				document.execCommand('unlink', false, null);
				break;
		}

		syncRichEditor(wrapper, true);
	}

	function setAiFeedback(feedbackEl, type, text) {
		if (!feedbackEl) return;

		feedbackEl.classList.remove(
			'hidden',
			'border-emerald-200',
			'bg-emerald-50',
			'text-emerald-700',
			'border-rose-200',
			'bg-rose-50',
			'text-rose-700',
			'border-slate-200',
			'bg-slate-100',
			'text-slate-600'
		);

		if (type === 'success') {
			feedbackEl.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
		} else if (type === 'error') {
			feedbackEl.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
		} else {
			feedbackEl.classList.add('border-slate-200', 'bg-slate-100', 'text-slate-600');
		}

		feedbackEl.textContent = text;
	}

	function collectAiDescriptionPayload(brief) {
		var payload = new FormData();
		var nonceField = form.querySelector('input[name="gcep_anuncio_nonce"]');
		var titleField = form.querySelector('input[name="gcep_titulo"]');
		var shortDescField = form.querySelector('textarea[name="gcep_descricao_curta"]');
		var siteField = form.querySelector('input[name="gcep_site"]');
		var cityField = form.querySelector('input[name="gcep_cidade"]');
		var stateField = form.querySelector('input[name="gcep_estado"]');
		var categoryField = form.querySelector('select[name="gcep_categoria[]"]');

		payload.append('action', 'gcep_generate_descricao_ai');
		if (nonceField) {
			payload.append('gcep_anuncio_nonce', nonceField.value);
		} else if (window.gcepData && gcepData.nonce) {
			payload.append('nonce', gcepData.nonce);
		}

		payload.append('contexto_inicial', brief);
		payload.append('gcep_titulo', titleField ? titleField.value : '');
		payload.append('gcep_tipo_anuncio', getTipo());
		payload.append('gcep_tipo_plano', getPlano());
		payload.append('gcep_descricao_curta', shortDescField ? shortDescField.value : '');
		payload.append('gcep_site', siteField ? siteField.value : '');
		payload.append('gcep_cidade', cityField ? cityField.value : '');
		payload.append('gcep_estado', stateField ? stateField.value : '');

		if (categoryField && categoryField.value) {
			Array.prototype.forEach.call(categoryField.selectedOptions, function (option) {
				payload.append('gcep_categoria[]', option.value);
			});
		}

		return payload;
	}

	function initRichEditors() {
		richTextWrappers.forEach(function (wrapper) {
			var surface = wrapper.querySelector('[data-rich-editor]');
			var toolbarButtons = wrapper.querySelectorAll('[data-editor-action]');
			var aiToggle = wrapper.querySelector('[data-ai-toggle]');
			var aiPanel = wrapper.querySelector('[data-ai-panel]');
			var aiLoading = wrapper.querySelector('[data-ai-loading]');
			var aiCancel = wrapper.querySelector('[data-ai-cancel]');
			var aiGenerate = wrapper.querySelector('[data-ai-generate]');
			var aiInput = wrapper.querySelector('[data-ai-context-input]');
			var aiFeedback = wrapper.querySelector('[data-ai-feedback]');
			var aiStatus = wrapper.querySelector('[data-ai-status]');
			var originalGenerateHtml = aiGenerate ? aiGenerate.innerHTML : '';

			function toggleAiLoading(isLoading) {
				if (aiLoading) {
					aiLoading.classList.toggle('hidden', !isLoading);
				}

				if (aiInput) {
					aiInput.disabled = isLoading;
				}

				if (aiCancel) {
					aiCancel.disabled = isLoading;
					aiCancel.classList.toggle('opacity-60', isLoading);
				}

				if (aiToggle) {
					aiToggle.disabled = isLoading;
					aiToggle.classList.toggle('opacity-60', isLoading);
				}

				if (aiGenerate) {
					aiGenerate.disabled = isLoading;
					aiGenerate.innerHTML = isLoading
						? '<span class="inline-flex h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span><span>Gerando...</span>'
						: originalGenerateHtml;
				}
			}

			if (surface) {
				surface.addEventListener('input', function () {
					syncRichEditor(wrapper, false);
				});

				surface.addEventListener('blur', function () {
					syncRichEditor(wrapper, true);
				});

				surface.addEventListener('paste', function (event) {
					event.preventDefault();
					var text = (event.clipboardData || window.clipboardData).getData('text/plain');
					document.execCommand('insertText', false, text);
					syncRichEditor(wrapper, true);
				});

				syncRichEditor(wrapper, true);
			}

			toolbarButtons.forEach(function (button) {
				button.addEventListener('mousedown', function (event) {
					event.preventDefault();
				});

				button.addEventListener('click', function () {
					execRichEditorCommand(wrapper, this.getAttribute('data-editor-action'));
				});
			});

			if (aiToggle && aiPanel) {
				aiToggle.addEventListener('click', function () {
					if (aiStatus) {
						aiStatus.classList.add('hidden');
					}
					aiPanel.classList.toggle('hidden');
					if (!aiPanel.classList.contains('hidden') && aiInput) {
						aiInput.focus();
					}
				});
			}

			if (aiCancel && aiPanel) {
				aiCancel.addEventListener('click', function () {
					aiPanel.classList.add('hidden');
				});
			}

			if (aiGenerate && aiInput) {
				aiGenerate.addEventListener('click', function () {
					var brief = String(aiInput.value || '').trim();
					var currentContent = sanitizeEditorHtml(surface ? surface.innerHTML : '');

					if (!brief) {
						setAiFeedback(aiFeedback, 'error', 'Descreva rapidamente o negócio para a IA montar o anúncio.');
						return;
					}

					if (currentContent && !window.confirm('A descrição atual será substituída pela versão gerada com IA. Continuar?')) {
						return;
					}

					setAiFeedback(aiFeedback, 'loading', 'Gerando descrição profissional com IA...');
					if (aiStatus) {
						aiStatus.classList.add('hidden');
					}
					toggleAiLoading(true);

					fetch(gcepData.ajaxUrl, {
						method: 'POST',
						body: collectAiDescriptionPayload(brief)
					})
						.then(function (response) { return response.json(); })
						.then(function (result) {
							if (!result || !result.success || !result.data || !result.data.html) {
								throw new Error(result && result.data && result.data.message ? result.data.message : 'Não foi possível gerar a descrição agora.');
							}

							if (surface) {
								surface.innerHTML = result.data.html;
							}

							syncRichEditor(wrapper, true);
							if (aiInput) {
								aiInput.value = '';
							}
							if (aiPanel) {
								aiPanel.classList.add('hidden');
							}
							if (aiFeedback) {
								aiFeedback.classList.add('hidden');
							}
							setAiFeedback(aiStatus, 'success', result.data.message || 'Descrição gerada com sucesso.');
							if (surface) {
								surface.focus();
								surface.scrollIntoView({ behavior: 'smooth', block: 'center' });
							}
						})
						.catch(function (error) {
							setAiFeedback(aiFeedback, 'error', error && error.message ? error.message : 'Erro ao gerar descrição com IA.');
						})
						.finally(function () {
							toggleAiLoading(false);
						});
				});
			}
		});
	}

	// ==================== Navegacao ====================
	function showStep(n) {
		currentStep = n;

		// Exibir/ocultar paineis de step
		steps.forEach(function (s) {
			var sn = parseInt(s.dataset.step);
			s.classList.toggle('hidden', sn !== n);
		});

		// Atualizar barra de progresso (largura proporcional ao step atual)
		if (progressBar) {
			var pct = (n / totalSteps) * 100;
			progressBar.style.width = pct + '%';
		}

		// Atualizar cores dos labels de step no header
		stepLabels.forEach(function (label) {
			var sn = parseInt(label.dataset.stepLabel);
			label.classList.remove('text-[#0052cc]', 'text-emerald-500', 'text-slate-400');

			if (sn < n) {
				// Step concluido
				label.classList.add('text-emerald-500');
			} else if (sn === n) {
				// Step atual
				label.classList.add('text-[#0052cc]');
			} else {
				// Step futuro
				label.classList.add('text-slate-400');
			}
		});

		// Atualizar contador mobile
		if (stepCounter) {
			stepCounter.textContent = n + ' / ' + totalSteps;
		}

		// Botoes de navegacao
		if (btnPrev) btnPrev.classList.toggle('invisible', n === 1);
		if (btnNext) btnNext.style.display = (n === totalSteps) ? 'none' : 'inline-flex';
		if (btnSubmit) btnSubmit.style.display = (n === totalSteps) ? 'inline-flex' : 'none';

		// Scroll suave para o topo do header
		if (formHeader) {
			formHeader.scrollIntoView({ behavior: 'smooth', block: 'start' });
		} else {
			form.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}

	// ==================== Validacao ====================
	function validateStep(n) {
		var stepEl = form.querySelector('[data-step="' + n + '"]');
		if (!stepEl) return true;

		var valid = true;

		var requiredFields = stepEl.querySelectorAll('[required]');
		requiredFields.forEach(function (field) {
			if (!field.value || !field.value.trim()) {
				field.classList.add('border-rose-400', 'ring-2', 'ring-rose-200');
				valid = false;
				var evtName = field.tagName === 'SELECT' ? 'change' : 'input';
				field.addEventListener(evtName, function () {
					field.classList.remove('border-rose-400', 'ring-2', 'ring-rose-200');
				}, { once: true });
			}
		});

		// Validar vigencia se premium selecionado (criacao ou upgrade na edicao)
		if (n === 1 && getPlano() === 'premium') {
			// No modo edicao premium travado, pular (plano ja definido)
			var hiddenPlano = form.querySelector('#gcep_tipo_plano_hidden');
			var isLockedPremium = isEditMode && hiddenPlano && hiddenPlano.dataset.originalPlano === 'premium';
			if (!isLockedPremium) {
				var planoRadio = form.querySelector('input[name="gcep_plano_radio"]:checked');
				if (!planoRadio) {
					valid = false;
					var premiumSection = document.getElementById('gcep-premium-plans') || document.getElementById('gcep-edit-premium-plans');
					if (premiumSection) {
						premiumSection.classList.add('ring-2', 'ring-rose-200', 'rounded-xl');
						premiumSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
						var planRadios = form.querySelectorAll('input[name="gcep_plano_radio"]');
						planRadios.forEach(function(r) {
							r.addEventListener('change', function() {
								premiumSection.classList.remove('ring-2', 'ring-rose-200', 'rounded-xl');
							}, { once: true });
						});
					}
					gcepToast('Selecione um plano de vigência para continuar.', 'warning');
					return false;
				}
			}
		}

		if (!valid) {
			var first = stepEl.querySelector('.border-rose-400');
			if (first) first.focus();
		}

		return valid;
	}

	// ==================== Rascunho (somente criacao) ====================
	function createDraftIfNeeded(callback) {
		if (isEditMode) { callback(); return; }
		var postIdField = document.getElementById('gcep_anuncio_id');
		if (!postIdField || parseInt(postIdField.value, 10) > 0) {
			callback();
			return;
		}

		if (btnNext) btnNext.disabled = true;

		var formData = new FormData();
		formData.append('action', 'gcep_create_draft');
		formData.append('nonce', gcepData.nonce);
		formData.append('tipo_anuncio', getTipo());
		formData.append('tipo_plano', getPlano());

		fetch(gcepData.ajaxUrl, { method: 'POST', body: formData })
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (btnNext) btnNext.disabled = false;
				if (res.success && res.data && res.data.post_id) {
					postIdField.value = res.data.post_id;
					callback();
				} else {
					gcepToast(res.data && res.data.message ? res.data.message : 'Erro ao criar rascunho.', 'error');
				}
			})
			.catch(function() {
				if (btnNext) btnNext.disabled = false;
				gcepToast('Erro de rede ao criar rascunho.', 'error');
			});
	}

	// ==================== Ocultar/exibir form e elementos UI ====================
	function hideFormUI() {
		form.style.display = 'none';
		if (formHeader) formHeader.style.display = 'none';
		if (navBottom) navBottom.style.display = 'none';
	}

	function restoreFormUI() {
		form.style.display = '';
		if (formHeader) formHeader.style.display = '';
		if (navBottom) navBottom.style.display = '';
	}

	// ==================== Submit via AJAX ====================
	function submitAnuncio() {
		syncAllRichEditors(true);

		// Esconder form, header e nav, mostrar overlay
		hideFormUI();
		if (resultOverlay) {
			resultOverlay.classList.remove('hidden');
			resultLoading.classList.remove('hidden');
			resultApproved.classList.add('hidden');
			resultRejected.classList.add('hidden');
		}

		var formData = new FormData(form);
		formData.set('action', 'gcep_submit_anuncio');

		fetch(gcepData.ajaxUrl, { method: 'POST', body: formData })
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (resultLoading) resultLoading.classList.add('hidden');

				debugLog('GCEP Submit Response:', res);

				if (!res.success) {
					showFormAgain();
					gcepToast(res.data && res.data.message ? res.data.message : 'Erro ao enviar anúncio.', 'error');
					return;
				}

				var data = res.data;
				debugLog('Status:', data.status, 'Justificativa:', data.justificativa);

				if (data.status === 'rejeitado') {
					showRejected(data.justificativa || '', data.message || '', data.post_id || 0);
				} else {
					showApproved(data);
				}
			})
			.catch(function() {
				if (resultLoading) resultLoading.classList.add('hidden');
				showFormAgain();
				gcepToast('Erro de rede ao enviar anúncio. Tente novamente.', 'error');
			});
	}

	// ==================== Submit edicao via AJAX ====================
	function submitEditAnuncio() {
		syncAllRichEditors(true);

		hideFormUI();
		if (resultOverlay) {
			resultOverlay.classList.remove('hidden');
			if (resultLoading) resultLoading.classList.remove('hidden');
			if (resultApproved) resultApproved.classList.add('hidden');
			if (resultRejected) resultRejected.classList.add('hidden');
		}

		var formData = new FormData(form);
		formData.set('action', 'gcep_edit_anuncio');

		fetch(gcepData.ajaxUrl, { method: 'POST', body: formData })
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (resultLoading) resultLoading.classList.add('hidden');

				debugLog('GCEP Edit Response:', res);

				if (!res.success) {
					showFormAgain();
					gcepToast(res.data && res.data.message ? res.data.message : 'Erro ao salvar anúncio.', 'error');
					return;
				}

				var data = res.data;

				if (data.status === 'rejeitado') {
					showRejected(data.justificativa || '', data.message || '', data.post_id || 0);
				} else {
					showApproved(data);
				}
			})
			.catch(function() {
				if (resultLoading) resultLoading.classList.add('hidden');
				showFormAgain();
				gcepToast('Erro de rede. Tente novamente.', 'error');
			});
	}

	function showApproved(data) {
		if (!resultApproved) return;
		resultApproved.classList.remove('hidden');

		var title = document.getElementById('gcep-result-approved-title');
		var msg   = document.getElementById('gcep-result-approved-msg');
		var btn   = document.getElementById('gcep-result-approved-btn');
		var label = document.getElementById('gcep-result-approved-btn-label');

		if (data.status === 'saved') {
			if (title) title.textContent = 'Alteracoes salvas!';
			if (msg)   msg.textContent = data.message || 'Seu anuncio foi atualizado com sucesso.';
			if (label) label.textContent = 'Ver Meus Anuncios';
			if (btn)   btn.href = data.redirect || '#';
			setTimeout(function() {
				if (data.redirect) window.location.href = data.redirect;
			}, 2000);
		} else if (data.status === 'aguardando_pagamento') {
			if (title) title.textContent = 'Anúncio aprovado!';
			if (msg)   msg.textContent = 'Redirecionando para o pagamento...';
			if (label) label.textContent = 'Ir para Pagamento';
			if (btn)   btn.href = data.redirect || '#';
			setTimeout(function() {
				if (data.redirect) window.location.href = data.redirect;
			}, 2000);
		} else if (data.status === 'publicado') {
			if (title) title.textContent = 'Anúncio publicado!';
			if (msg)   msg.textContent = 'Seu anúncio já está no ar.';
			if (label) label.textContent = 'Ver Meus Anuncios';
			if (btn)   btn.href = data.redirect || '#';
		} else {
			if (title) title.textContent = 'Anúncio enviado!';
			if (msg)   msg.textContent = 'Aguardando aprovação do administrador.';
			if (label) label.textContent = 'Ver Meus Anuncios';
			if (btn)   btn.href = data.redirect || '#';
		}
	}

	function fetchValidationReason(postId, callback) {
		var nonceField = form.querySelector('input[name="gcep_anuncio_nonce"]');
		if (!window.gcepData || !gcepData.ajaxUrl || !nonceField || !postId) {
			callback('');
			return;
		}

		var data = new FormData();
		data.append('action', 'gcep_get_validation_reason');
		data.append('post_id', String(postId));
		data.append('gcep_anuncio_nonce', nonceField.value);

		fetch(gcepData.ajaxUrl, { method: 'POST', body: data })
			.then(function (response) { return response.json(); })
			.then(function (result) {
				if (result && result.success && result.data) {
					callback(String(result.data.justificativa || '').trim());
					return;
				}
				callback('');
			})
			.catch(function () {
				callback('');
			});
	}

	function isGenericValidationMessage(text) {
		var normalized = String(text || '').trim().toLowerCase();
		return !normalized
			|| normalized.indexOf('não retornou uma justificativa') !== -1
			|| normalized.indexOf('nao retornou uma justificativa') !== -1
			|| normalized.indexOf('corrija e tente novamente') !== -1;
	}

	function renderRejectedDetails(details) {
		var finalDetails = String(details || '').trim();
		if (!finalDetails) {
			finalDetails = GENERIC_VALIDATION_FALLBACK;
		}

		debugLog('renderRejectedDetails:', finalDetails);

		var inlineText = document.getElementById('gcep-inline-reason-text');
		if (inlineText) {
			inlineText.textContent = finalDetails;
		}
	}

	function showRejected(justificativa, message, postId) {
		if (!resultRejected) return;
		resultRejected.classList.remove('hidden');

		var details = '';

		debugLog('showRejected chamado - justificativa:', justificativa, 'message:', message);

		if (typeof justificativa === 'string' && justificativa.trim()) {
			details = justificativa.trim();
		} else if (typeof message === 'string' && message.trim()) {
			details = message.trim();
		}

		if (!details) {
			details = GENERIC_VALIDATION_FALLBACK;
		}

		debugLog('Justificativa final exibida:', details);
		renderRejectedDetails(details);

		if (postId) {
			fetchValidationReason(postId, function (fetchedReason) {
				if (!fetchedReason) {
					return;
				}

				if (isGenericValidationMessage(details) || fetchedReason !== details) {
					renderRejectedDetails(fetchedReason);
				}
			});
		}
	}

	function showFormAgain() {
		restoreFormUI();
		if (resultOverlay) resultOverlay.classList.add('hidden');
		if (resultLoading) resultLoading.classList.add('hidden');
		if (resultApproved) resultApproved.classList.add('hidden');
		if (resultRejected) resultRejected.classList.add('hidden');
		if (btnSubmit) btnSubmit.disabled = false;
		// No modo edicao, voltar ao ultimo step; na criacao, voltar ao step 2
		showStep(isEditMode ? totalSteps : 2);
	}

	// ==================== Eventos dos botoes ====================
	if (btnNext) {
		btnNext.addEventListener('click', function () {
			if (!validateStep(currentStep)) return;
			if (currentStep < totalSteps) {
				if (currentStep === 1) {
					createDraftIfNeeded(function () {
						showStep(currentStep + 1);
					});
				} else {
					showStep(currentStep + 1);
				}
			}
		});
	}

	if (btnPrev) {
		btnPrev.addEventListener('click', function () {
			if (currentStep > 1) {
				showStep(currentStep - 1);
			}
		});
	}

	// Botao de submit: AJAX tanto na criacao quanto na edicao
	if (btnSubmit) {
		btnSubmit.addEventListener('click', function () {
			if (!validateStep(currentStep)) return;
			btnSubmit.disabled = true;
			if (isEditMode) {
				submitEditAnuncio();
			} else {
				submitAnuncio();
			}
		});
	}

	// Botao "Corrigir Anuncio" no overlay de rejeicao
	var fixBtn = document.getElementById('gcep-result-fix-btn');
	if (fixBtn) {
		fixBtn.addEventListener('click', function () {
			showFormAgain();
		});
	}

	// ==================== Campos condicionais ====================
	function toggleTipoFields() {
		var isEmpresa = getTipo() === 'empresa';
		var cnpjEls = form.querySelectorAll('[data-show-tipo="empresa"]');
		cnpjEls.forEach(function (el) {
			el.classList.toggle('hidden', !isEmpresa);
		});
	}

	function togglePlanoFields() {
		var isPremium = getPlano() === 'premium';
		var premiumEls = form.querySelectorAll('[data-premium-only]');
		premiumEls.forEach(function (el) {
			el.classList.toggle('hidden', !isPremium);
		});
		var gratisEls = form.querySelectorAll('[data-gratis-only]');
		gratisEls.forEach(function (el) {
			el.classList.toggle('hidden', isPremium);
		});

		var premiumPlans = document.getElementById('gcep-premium-plans');
		if (premiumPlans) {
			premiumPlans.classList.toggle('hidden', !isPremium);
			if (!isPremium) {
				var planoIdField = document.getElementById('gcep_plano_id');
				if (planoIdField) planoIdField.value = '0';
			}
		}
	}

	var tipoRadios = form.querySelectorAll('input[name="gcep_tipo_anuncio"]');
	tipoRadios.forEach(function (radio) {
		radio.addEventListener('change', toggleTipoFields);
	});
	toggleTipoFields();

	var planoRadios = form.querySelectorAll('input[name="gcep_tipo_plano"]');
	planoRadios.forEach(function (radio) {
		radio.addEventListener('change', togglePlanoFields);
	});

	// Upgrade de plano na edicao: sincronizar radio gcep_edit_plano com hidden e campos
	var editPlanoRadios = form.querySelectorAll('input[name="gcep_edit_plano"]');
	var hiddenPlanoField = document.getElementById('gcep_tipo_plano_hidden');
	var editPremiumPlans = document.getElementById('gcep-edit-premium-plans');
	var editUpgradeInfo = document.getElementById('gcep-edit-upgrade-info');

	if (hiddenPlanoField) {
		hiddenPlanoField.dataset.originalPlano = hiddenPlanoField.value;
	}

	editPlanoRadios.forEach(function (radio) {
		radio.addEventListener('change', function () {
			var isPremiumUpgrade = this.value === 'premium';
			if (hiddenPlanoField) hiddenPlanoField.value = this.value;
			if (editPremiumPlans) editPremiumPlans.classList.toggle('hidden', !isPremiumUpgrade);
			if (editUpgradeInfo) editUpgradeInfo.classList.toggle('hidden', !isPremiumUpgrade);
			if (!isPremiumUpgrade) {
				var planoIdField = document.getElementById('gcep_plano_id');
				if (planoIdField) planoIdField.value = '0';
			}
			// Alterar texto e estilo do botao submit conforme upgrade
			var submitIcon = document.getElementById('gcep-submit-icon');
			var submitLabel = document.getElementById('gcep-submit-label');
			if (btnSubmit) {
				if (isPremiumUpgrade) {
					btnSubmit.classList.remove('bg-emerald-500', 'hover:bg-emerald-600', 'shadow-emerald-500/20');
					btnSubmit.classList.add('bg-amber-500', 'hover:bg-amber-600', 'shadow-amber-500/20');
					if (submitIcon) submitIcon.textContent = 'payments';
					if (submitLabel) submitLabel.textContent = 'Salvar e Pagar';
				} else {
					btnSubmit.classList.remove('bg-amber-500', 'hover:bg-amber-600', 'shadow-amber-500/20');
					btnSubmit.classList.add('bg-emerald-500', 'hover:bg-emerald-600', 'shadow-emerald-500/20');
					if (submitIcon) submitIcon.textContent = 'check_circle';
					if (submitLabel) submitLabel.textContent = 'Salvar Alterações';
				}
			}
			togglePlanoFields();
		});
	});

	togglePlanoFields();

	// Sincronizar selecao de plano premium com campo hidden
	var planoRadiosPremium = form.querySelectorAll('input[name="gcep_plano_radio"]');
	planoRadiosPremium.forEach(function (radio) {
		radio.addEventListener('change', function () {
			var planoIdField = document.getElementById('gcep_plano_id');
			if (planoIdField) planoIdField.value = this.value;
		});
	});

	// ==================== Mascaras ====================
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

	var telInputs = form.querySelectorAll('input[name="gcep_telefone"], input[name="gcep_whatsapp"]');
	telInputs.forEach(function (input) {
		var applyMask = function () {
			input.value = formatPhoneValue(input.value);
		};

		input.addEventListener('input', applyMask);
		applyMask();
	});

	// ==================== Repeater de videos ====================
	var videoContainer = document.getElementById('gcep-videos-container');
	var addVideoBtn    = document.getElementById('gcep-add-video');

	function criarVideoItem(titulo, url) {
		var div = document.createElement('div');
		div.className = 'gcep-video-item flex flex-col sm:flex-row gap-3 items-start bg-slate-50 p-4 rounded-xl border border-slate-200';

		div.innerHTML =
			'<div class="flex-1 w-full space-y-3">' +
				'<input type="text" name="gcep_video_titulo[]" value="' + (titulo || '') + '" placeholder="Titulo do video" class="w-full rounded-lg border-slate-200 bg-white p-3 text-sm focus:ring-2 focus:ring-[#0052cc]/20 outline-none">' +
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

	initRichEditors();

	// Iniciar no step 1
	showStep(1);
})();
