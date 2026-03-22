<?php
/**
 * Modal de scripts premium por anúncio.
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.7.4 - 2026-03-12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="gcep-anuncio-scripts-modal" class="fixed inset-0 z-[2000] hidden">
	<div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" data-gcep-scripts-close></div>
	<div class="relative z-10 min-h-full flex items-center justify-center p-4">
		<div class="w-full max-w-4xl rounded-[2rem] bg-white shadow-2xl border border-slate-200 overflow-hidden">
			<div class="px-6 lg:px-8 py-5 border-b border-slate-200 flex items-start justify-between gap-4">
				<div>
					<p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400"><?php esc_html_e( 'Scripts Premium', 'guiawp' ); ?></p>
					<h3 id="gcep-anuncio-scripts-title" class="text-2xl font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Editar Scripts do Anúncio', 'guiawp' ); ?></h3>
					<p class="text-sm text-slate-500 mt-2"><?php esc_html_e( 'Use esse espaço para pixels, analytics e scripts de conversão do anúncio premium. Os códigos só são executados na página pública publicada.', 'guiawp' ); ?></p>
				</div>
				<button type="button" class="w-11 h-11 rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors flex items-center justify-center" data-gcep-scripts-close aria-label="<?php esc_attr_e( 'Fechar', 'guiawp' ); ?>">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<form id="gcep-anuncio-scripts-form" class="px-6 lg:px-8 py-6 space-y-5">
				<input type="hidden" name="anuncio_id" id="gcep-anuncio-scripts-id" value="">

				<div id="gcep-anuncio-scripts-loading" class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm font-medium text-slate-600">
					<div class="flex items-center gap-3">
						<span class="inline-block w-4 h-4 rounded-full border-2 border-slate-300 border-t-[#0052cc] animate-spin"></span>
						<span><?php esc_html_e( 'Carregando scripts do anúncio...', 'guiawp' ); ?></span>
					</div>
				</div>

				<div id="gcep-anuncio-scripts-feedback" class="hidden rounded-2xl border px-4 py-3 text-sm font-medium"></div>

				<div class="grid grid-cols-1 gap-5">
					<div>
						<label for="gcep-anuncio-script-head" class="block text-sm font-bold text-slate-800 mb-2"><?php esc_html_e( 'Head', 'guiawp' ); ?></label>
						<textarea id="gcep-anuncio-script-head" name="head" rows="6" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-xs text-slate-700 focus:ring-2 focus:ring-[#0052cc] focus:border-[#0052cc]" placeholder="Ex.: script base do Meta Pixel, Google tag ou Hotjar"></textarea>
					</div>

					<div>
						<label for="gcep-anuncio-script-body-start" class="block text-sm font-bold text-slate-800 mb-2"><?php esc_html_e( 'Body Início', 'guiawp' ); ?></label>
						<textarea id="gcep-anuncio-script-body-start" name="body_start" rows="5" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-xs text-slate-700 focus:ring-2 focus:ring-[#0052cc] focus:border-[#0052cc]" placeholder="Ex.: GTM noscript ou pixel com fallback"></textarea>
					</div>

					<div>
						<label for="gcep-anuncio-script-body-end" class="block text-sm font-bold text-slate-800 mb-2"><?php esc_html_e( 'Body Fim', 'guiawp' ); ?></label>
						<textarea id="gcep-anuncio-script-body-end" name="body_end" rows="5" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-xs text-slate-700 focus:ring-2 focus:ring-[#0052cc] focus:border-[#0052cc]" placeholder="Ex.: scripts finais de conversão ou tracking adicional"></textarea>
					</div>
				</div>

				<div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
					<?php esc_html_e( 'Atenção: esses códigos são executados no domínio do site. Use apenas scripts confiáveis do seu analytics, pixel ou tag manager.', 'guiawp' ); ?>
				</div>

				<div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
					<button type="button" class="px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors" data-gcep-scripts-close>
						<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
					</button>
					<button type="submit" id="gcep-anuncio-scripts-save" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-[#0052cc] text-white font-bold hover:bg-[#003d99] transition-colors shadow-lg shadow-[#0052cc]/20">
						<span class="material-symbols-outlined text-[18px]">code_blocks</span>
						<span><?php esc_html_e( 'Salvar Scripts', 'guiawp' ); ?></span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
