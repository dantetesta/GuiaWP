<?php
/**
 * Partial: Card de Anuncio reutilizavel
 *
 * Variaveis esperadas: $anuncio (WP_Post), $show_premium_badge (bool)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 2.1.0 - 2026-03-28
 */

if ( ! isset( $anuncio ) || ! $anuncio instanceof WP_Post ) {
	return;
}

$show_premium_badge = $show_premium_badge ?? true;

$card_id   = $anuncio->ID;
$card_desc = get_post_meta( $card_id, 'GCEP_descricao_curta', true );
$card_plan = get_post_meta( $card_id, 'GCEP_tipo_plano', true );

$card_cat_terms = wp_get_object_terms( $card_id, 'gcep_categoria', [ 'fields' => 'names' ] );
$card_loc_terms = wp_get_object_terms( $card_id, 'gcep_localizacao', [ 'fields' => 'names' ] );

$card_cat_label = ! empty( $card_cat_terms ) && ! is_wp_error( $card_cat_terms )
	? $card_cat_terms[0]
	: __( 'Anuncio local', 'guiawp-reset' );

$card_loc_label = ! empty( $card_loc_terms ) && ! is_wp_error( $card_loc_terms )
	? implode( ', ', $card_loc_terms )
	: __( 'Local nao informado', 'guiawp-reset' );
?>
<a href="<?php echo esc_url( get_permalink( $card_id ) ); ?>" class="gcep-card-anuncio group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_12px_40px_-28px_rgba(15,23,42,0.45)] ring-1 ring-white/70 transition-all duration-300 hover:-translate-y-1.5 hover:border-slate-300 hover:shadow-[0_22px_50px_-26px_rgba(15,23,42,0.38)]">
	<!-- Imagem 3:2 -->
	<div class="relative aspect-[3/2] overflow-hidden bg-slate-100">
		<?php if ( has_post_thumbnail( $card_id ) ) : ?>
			<?php echo get_the_post_thumbnail( $card_id, 'gcep-card', [ 'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105' ] ); ?>
		<?php else : ?>
			<div class="w-full h-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
				<span class="material-symbols-outlined text-5xl text-slate-300">image</span>
			</div>
		<?php endif; ?>
		<div class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-950/20 to-transparent"></div>
		<?php if ( $show_premium_badge && 'premium' === $card_plan ) : ?>
			<div class="absolute left-3 top-3 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-white shadow-lg" style="background:var(--gcep-color-destaque, #22c55e)"><?php esc_html_e( 'Destaque', 'guiawp-reset' ); ?></div>
		<?php endif; ?>
	</div>
	<!-- Conteudo -->
	<div class="flex flex-1 flex-col p-4 md:p-5">
		<span class="mb-2 inline-flex w-fit items-center rounded-full border px-2.5 py-0.5 text-[9px] font-medium uppercase tracking-[0.12em]" style="border-color:color-mix(in srgb,var(--gcep-color-primary) 30%,transparent);color:var(--gcep-color-primary)"><?php echo esc_html( $card_cat_label ); ?></span>
		<h3 class="text-base md:text-lg font-black leading-tight text-slate-900 transition-colors group-hover:text-primary line-clamp-2"><?php echo esc_html( $anuncio->post_title ); ?></h3>
		<?php if ( $card_desc ) : ?>
			<p class="mt-2 text-sm leading-6 text-slate-500 line-clamp-2"><?php echo esc_html( $card_desc ); ?></p>
		<?php endif; ?>
		<div class="mt-auto pt-4">
			<div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4 text-slate-600">
				<div class="min-w-0 flex items-center gap-2 text-sm font-medium">
					<span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-slate-100 text-slate-400">
						<span class="material-symbols-outlined text-[18px]">location_on</span>
					</span>
					<span class="truncate text-xs text-slate-500"><?php echo esc_html( $card_loc_label ); ?></span>
				</div>
				<span class="gcep-arrow-btn inline-flex h-9 w-9 flex-none items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400 transition-all">
					<span class="material-symbols-outlined text-[20px]">arrow_forward</span>
				</span>
			</div>
		</div>
	</div>
</a>
