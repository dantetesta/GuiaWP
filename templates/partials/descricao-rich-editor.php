<?php
/**
 * Editor rico restrito para descricao premium.
 *
 * @package GuiaWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$editor_name        = isset( $editor_name ) ? (string) $editor_name : 'gcep_descricao_longa';
$editor_value       = isset( $editor_value ) ? (string) $editor_value : '';
$editor_placeholder = isset( $editor_placeholder ) ? (string) $editor_placeholder : '';
$ai_enabled         = ! empty( $ai_enabled );
$editor_html        = GCEP_AI_Validator::sanitize_description_html( $editor_value );
?>
<div class="space-y-4" data-rich-text-wrapper>
	<div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white">
		<div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 p-3 sm:flex-row sm:items-center sm:justify-between">
			<div class="flex flex-wrap gap-2" data-editor-toolbar>
				<button type="button" data-editor-action="h2" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Título', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">title</span>
				</button>
				<button type="button" data-editor-action="h3" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Subtítulo', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">short_text</span>
				</button>
				<button type="button" data-editor-action="bold" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Negrito', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">format_bold</span>
				</button>
				<button type="button" data-editor-action="italic" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Itálico', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">format_italic</span>
				</button>
				<button type="button" data-editor-action="unorderedList" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Lista', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
				</button>
				<button type="button" data-editor-action="orderedList" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Lista numerada', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
				</button>
				<button type="button" data-editor-action="link" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Link externo', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">link</span>
				</button>
				<button type="button" data-editor-action="table" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-[#0052cc] hover:text-[#0052cc]" title="<?php esc_attr_e( 'Inserir tabela', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">table_view</span>
				</button>
				<button type="button" data-editor-action="clear" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-rose-400 hover:text-rose-500" title="<?php esc_attr_e( 'Limpar formatação', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[18px]">format_clear</span>
				</button>
			</div>

			<?php if ( $ai_enabled ) : ?>
				<button type="button" data-ai-toggle class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0052cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#003d99]">
					<span class="material-symbols-outlined text-[18px]">auto_awesome</span>
					<?php esc_html_e( 'Gerar com IA', 'guiawp' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $ai_enabled ) : ?>
			<div data-ai-panel class="relative hidden border-b border-slate-200 bg-[#0052cc]/[0.03] p-4">
				<div data-ai-loading class="hidden absolute inset-0 z-10 flex items-center justify-center rounded-none bg-white/80 backdrop-blur-sm">
					<div class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm">
						<span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-[#0052cc]/25 border-t-[#0052cc]"></span>
						<?php esc_html_e( 'Gerando descrição com IA...', 'guiawp' ); ?>
					</div>
				</div>
				<div class="space-y-3">
					<div>
						<label class="mb-2 block text-sm font-semibold text-slate-700" for="<?php echo esc_attr( $editor_name ); ?>_ai_context"><?php esc_html_e( 'Contexto inicial para a IA', 'guiawp' ); ?></label>
						<textarea id="<?php echo esc_attr( $editor_name ); ?>_ai_context" rows="4" data-ai-context-input class="w-full rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-[#0052cc]/20" placeholder="<?php echo esc_attr( $editor_placeholder ?: __( 'Explique o que a empresa faz, diferenciais, público, região atendida e o tom desejado.', 'guiawp' ) ); ?>"></textarea>
					</div>
					<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
						<p class="text-xs leading-5 text-slate-500"><?php esc_html_e( 'A IA usa esse contexto junto com título, categoria e dados do anúncio para montar uma descrição mais profissional.', 'guiawp' ); ?></p>
						<div class="flex flex-wrap gap-2">
							<button type="button" data-ai-cancel class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-white">
								<?php esc_html_e( 'Fechar', 'guiawp' ); ?>
							</button>
							<button type="button" data-ai-generate class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
								<span class="material-symbols-outlined text-[18px]">bolt</span>
								<?php esc_html_e( 'Gerar descrição', 'guiawp' ); ?>
							</button>
						</div>
					</div>
					<p data-ai-feedback class="hidden rounded-lg border px-3 py-2 text-xs font-medium"></p>
				</div>
			</div>
		<?php endif; ?>

		<p data-ai-status class="hidden mx-4 rounded-lg border px-3 py-2 text-xs font-medium"></p>

		<div
			data-rich-editor
			contenteditable="true"
			class="min-h-[260px] rounded-b-xl bg-white p-4 text-base leading-7 text-slate-900 outline-none"
			spellcheck="true"
		><?php echo $editor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>

	<textarea name="<?php echo esc_attr( $editor_name ); ?>" data-rich-text-input class="hidden"><?php echo esc_textarea( $editor_html ); ?></textarea>

	<p class="text-xs leading-5 text-slate-500">
		<?php esc_html_e( 'Aceita títulos, parágrafos, listas, tabelas e links externos. Não permite imagens, vídeos, embeds ou outras mídias.', 'guiawp' ); ?>
	</p>
</div>
