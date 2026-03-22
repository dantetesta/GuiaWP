<?php
/**
 * Modal reutilizável de geração de imagem com IA (Nanobanana 2)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.9.0 - 2026-03-21
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

if ( ! GCEP_Gemini_Imagen::has_api_key() ) {
	return;
}

$cor_primaria = GCEP_Settings::get( 'cor_primaria', '#0052cc' );
$cor_hover    = '#003d99';
?>

<!-- Modal de Geração de Imagem com IA -->
<div id="gcep-imagen-modal" class="hidden fixed inset-0" style="z-index:99999;" role="dialog" aria-modal="true" aria-labelledby="gcep-imagen-title">
	<div class="absolute inset-0 gcep-imagen-overlay" style="background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);"></div>

	<div class="absolute inset-0 flex items-center justify-center p-4">
		<div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
			<!-- Header -->
			<div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
				<div class="flex items-center gap-3">
					<div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:<?php echo esc_attr( $cor_primaria ); ?>15;">
						<span class="material-symbols-outlined text-lg" style="color:<?php echo esc_attr( $cor_primaria ); ?>;">auto_awesome</span>
					</div>
					<div>
						<h3 id="gcep-imagen-title" class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Gerar Imagem com IA', 'guiawp' ); ?></h3>
						<p id="gcep-imagen-subtitle" class="text-[11px] text-slate-400"></p>
					</div>
				</div>
				<button type="button" class="gcep-imagen-close p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
					<span class="material-symbols-outlined text-lg">close</span>
				</button>
			</div>

			<!-- Body -->
			<div class="px-6 py-5 space-y-4">
				<div class="space-y-2">
					<label class="text-sm font-semibold text-slate-700 block"><?php esc_html_e( 'Descreva a imagem desejada', 'guiawp' ); ?></label>
					<textarea id="gcep-imagen-prompt" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50 p-3 text-sm outline-none resize-none transition-all" style="border:1px solid #e2e8f0;" placeholder="<?php esc_attr_e( 'Ex: Restaurante italiano aconchegante com iluminação quente...', 'guiawp' ); ?>"></textarea>
				</div>

				<!-- Botão melhorar prompt -->
				<button type="button" id="gcep-imagen-enhance" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all" style="background:<?php echo esc_attr( $cor_primaria ); ?>0d;border:1px solid <?php echo esc_attr( $cor_primaria ); ?>33;color:<?php echo esc_attr( $cor_primaria ); ?>;">
					<span class="material-symbols-outlined text-sm">magic_button</span>
					<?php esc_html_e( 'Melhorar prompt com IA', 'guiawp' ); ?>
					<span id="gcep-imagen-enhance-spinner" class="hidden">
						<svg class="animate-spin h-3.5 w-3.5" style="color:<?php echo esc_attr( $cor_primaria ); ?>;" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
					</span>
				</button>

				<!-- Preview -->
				<div id="gcep-imagen-preview-wrap" class="hidden">
					<div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-50">
						<img id="gcep-imagen-preview" src="" alt="" class="w-full object-contain" style="max-height:16rem;">
						<button type="button" id="gcep-imagen-retry" class="absolute top-2 right-2 p-1.5 rounded-lg bg-white/90 border border-slate-200 text-slate-500 transition-all shadow-sm" title="<?php esc_attr_e( 'Gerar novamente', 'guiawp' ); ?>">
							<span class="material-symbols-outlined text-base">refresh</span>
						</button>
					</div>
				</div>

				<!-- Loading -->
				<div id="gcep-imagen-loading" class="hidden">
					<div class="flex flex-col items-center justify-center py-8 gap-3">
						<div class="relative w-12 h-12">
							<div class="absolute inset-0 rounded-full" style="border:4px solid <?php echo esc_attr( $cor_primaria ); ?>20;"></div>
							<div class="absolute inset-0 rounded-full animate-spin" style="border:4px solid <?php echo esc_attr( $cor_primaria ); ?>;border-top-color:transparent;"></div>
						</div>
						<p class="text-sm text-slate-500 font-medium"><?php esc_html_e( 'Gerando imagem...', 'guiawp' ); ?></p>
						<p class="text-[11px] text-slate-400"><?php esc_html_e( 'Isso pode levar alguns segundos', 'guiawp' ); ?></p>
					</div>
				</div>

				<!-- Erro -->
				<div id="gcep-imagen-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-start gap-2">
					<span class="material-symbols-outlined text-sm shrink-0 mt-0.5">error</span>
					<span id="gcep-imagen-error-msg"></span>
				</div>
			</div>

			<!-- Footer -->
			<div class="flex items-center gap-3 px-6 py-4 border-t border-slate-100" style="background:#f8fafc;">
				<button type="button" id="gcep-imagen-generate" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 text-white rounded-xl font-bold text-sm transition-all shadow-sm" style="background:<?php echo esc_attr( $cor_primaria ); ?>;">
					<span class="material-symbols-outlined text-base">auto_awesome</span>
					<span id="gcep-imagen-generate-text"><?php esc_html_e( 'Gerar Imagem', 'guiawp' ); ?></span>
				</button>
				<button type="button" id="gcep-imagen-use" class="hidden flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 text-white rounded-xl font-bold text-sm transition-all shadow-sm" style="background:#16a34a;">
					<span class="material-symbols-outlined text-base">check_circle</span>
					<?php esc_html_e( 'Usar esta imagem', 'guiawp' ); ?>
				</button>
				<button type="button" class="gcep-imagen-close px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl font-semibold text-sm hover:bg-slate-100 transition-all">
					<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<script>
(function(){
	'use strict';

	var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var ajaxNonce = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';

	var state = {
		tipo: 'categoria',
		onSelect: null,
		generatedAttachmentId: null,
		generatedUrl: null,
		busy: false
	};

	var modal     = document.getElementById('gcep-imagen-modal');
	var prompt    = document.getElementById('gcep-imagen-prompt');
	var subtitle  = document.getElementById('gcep-imagen-subtitle');
	var enhanceBtn = document.getElementById('gcep-imagen-enhance');
	var enhanceSpin = document.getElementById('gcep-imagen-enhance-spinner');
	var genBtn    = document.getElementById('gcep-imagen-generate');
	var genText   = document.getElementById('gcep-imagen-generate-text');
	var useBtn    = document.getElementById('gcep-imagen-use');
	var preview   = document.getElementById('gcep-imagen-preview');
	var previewWrap = document.getElementById('gcep-imagen-preview-wrap');
	var loading   = document.getElementById('gcep-imagen-loading');
	var errorWrap = document.getElementById('gcep-imagen-error');
	var errorMsg  = document.getElementById('gcep-imagen-error-msg');
	var retryBtn  = document.getElementById('gcep-imagen-retry');

	function open(tipo, callback) {
		state.tipo = tipo || 'categoria';
		state.onSelect = callback || null;
		state.generatedAttachmentId = null;
		state.generatedUrl = null;
		state.busy = false;

		subtitle.textContent = state.tipo === 'categoria'
			? '<?php echo esc_js( __( 'Categoria · Quadrada 400×400px · WebP', 'guiawp' ) ); ?>'
			: '<?php echo esc_js( __( 'Blog · Paisagem 16:9 · WebP', 'guiawp' ) ); ?>';

		prompt.value = '';
		previewWrap.classList.add('hidden');
		loading.classList.add('hidden');
		errorWrap.classList.add('hidden');
		useBtn.classList.add('hidden');
		genBtn.classList.remove('hidden');

		modal.classList.remove('hidden');
		prompt.focus();
	}

	function close() {
		if (state.busy) return;
		modal.classList.add('hidden');
	}

	function showError(msg) {
		errorMsg.textContent = msg;
		errorWrap.classList.remove('hidden');
	}

	function hideError() {
		errorWrap.classList.add('hidden');
	}

	function enhancePrompt() {
		if (state.busy) return;
		var val = prompt.value.trim();
		if (!val) {
			showError('<?php echo esc_js( __( 'Digite um prompt antes de melhorar.', 'guiawp' ) ); ?>');
			return;
		}
		hideError();
		state.busy = true;
		enhanceSpin.classList.remove('hidden');
		enhanceBtn.disabled = true;

		var fd = new FormData();
		fd.append('action', 'gcep_imagen_enhance');
		fd.append('nonce', ajaxNonce);
		fd.append('prompt', val);
		fd.append('contexto', state.tipo);

		fetch(ajaxUrl, {method:'POST', body: fd})
			.then(function(r){return r.json()})
			.then(function(res){
				state.busy = false;
				enhanceSpin.classList.add('hidden');
				enhanceBtn.disabled = false;
				if (res.success) {
					prompt.value = res.data.enhanced_prompt;
				} else {
					showError(res.data.message || '<?php echo esc_js( __( 'Erro ao melhorar prompt.', 'guiawp' ) ); ?>');
				}
			})
			.catch(function(e){
				state.busy = false;
				enhanceSpin.classList.add('hidden');
				enhanceBtn.disabled = false;
				showError(e.message || '<?php echo esc_js( __( 'Erro de rede.', 'guiawp' ) ); ?>');
			});
	}

	function generateImage() {
		if (state.busy) return;
		var val = prompt.value.trim();
		if (!val) {
			showError('<?php echo esc_js( __( 'Digite um prompt para gerar a imagem.', 'guiawp' ) ); ?>');
			return;
		}
		hideError();
		state.busy = true;
		previewWrap.classList.add('hidden');
		loading.classList.remove('hidden');
		genBtn.disabled = true;
		useBtn.classList.add('hidden');

		var fd = new FormData();
		fd.append('action', 'gcep_imagen_generate');
		fd.append('nonce', ajaxNonce);
		fd.append('prompt', val);
		fd.append('tipo', state.tipo);

		fetch(ajaxUrl, {method:'POST', body: fd})
			.then(function(r){return r.json()})
			.then(function(res){
				state.busy = false;
				loading.classList.add('hidden');
				genBtn.disabled = false;
				if (res.success) {
					state.generatedAttachmentId = res.data.attachment_id;
					state.generatedUrl = res.data.url;
					preview.src = res.data.url;
					previewWrap.classList.remove('hidden');
					useBtn.classList.remove('hidden');
					genBtn.classList.add('hidden');
				} else {
					showError(res.data.message || '<?php echo esc_js( __( 'Erro ao gerar imagem.', 'guiawp' ) ); ?>');
				}
			})
			.catch(function(e){
				state.busy = false;
				loading.classList.add('hidden');
				genBtn.disabled = false;
				showError(e.message || '<?php echo esc_js( __( 'Erro de rede.', 'guiawp' ) ); ?>');
			});
	}

	function useImage() {
		if (!state.generatedAttachmentId || !state.onSelect) return;
		state.onSelect(state.generatedAttachmentId, state.generatedUrl);
		close();
	}

	function retry() {
		previewWrap.classList.add('hidden');
		useBtn.classList.add('hidden');
		genBtn.classList.remove('hidden');
		generateImage();
	}

	modal.querySelectorAll('.gcep-imagen-close, .gcep-imagen-overlay').forEach(function(el){
		el.addEventListener('click', close);
	});
	enhanceBtn.addEventListener('click', enhancePrompt);
	genBtn.addEventListener('click', generateImage);
	useBtn.addEventListener('click', useImage);
	retryBtn.addEventListener('click', retry);

	document.addEventListener('keydown', function(e){
		if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
			close();
		}
	});

	window.gcepImagenModal = { open: open };
})();
</script>
