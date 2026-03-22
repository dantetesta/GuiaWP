<?php
/**
 * Partial: Paginação de usuários (param 'pagina')
 *
 * Variáveis esperadas: $paged, $pages, $total, $base_url
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.4.7 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $pages ) || $pages <= 1 ) {
	return;
}

$paged    = max( 1, (int) ( $paged ?? 1 ) );
$base_url = $base_url ?? '';

$separator = str_contains( $base_url, '?' ) ? '&' : '?';
?>
<nav class="flex items-center justify-between px-6 py-4 border-t border-slate-200" aria-label="<?php esc_attr_e( 'Paginacao', 'guiawp' ); ?>">
	<p class="text-sm text-slate-500">
		<?php printf( esc_html__( 'Pagina %1$d de %2$d', 'guiawp' ), $paged, $pages ); ?>
		<?php if ( ! empty( $total ) ) : ?>
			<span class="text-slate-400">&middot; <?php printf( esc_html__( '%d itens', 'guiawp' ), $total ); ?></span>
		<?php endif; ?>
	</p>
	<div class="flex items-center gap-1">
		<?php if ( $paged > 1 ) : ?>
		<a href="<?php echo esc_url( $base_url . $separator . 'pagina=' . ( $paged - 1 ) ); ?>" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
			<span class="material-symbols-outlined text-[16px]">chevron_left</span>
			<?php esc_html_e( 'Anterior', 'guiawp' ); ?>
		</a>
		<?php endif; ?>

		<?php
		$range = 2;
		$start = max( 1, $paged - $range );
		$end   = min( $pages, $paged + $range );

		if ( $start > 1 ) : ?>
			<a href="<?php echo esc_url( $base_url . $separator . 'pagina=1' ); ?>" class="px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">1</a>
			<?php if ( $start > 2 ) : ?>
				<span class="px-2 text-slate-400 text-sm">&hellip;</span>
			<?php endif; ?>
		<?php endif;

		for ( $i = $start; $i <= $end; $i++ ) :
			if ( $i === $paged ) : ?>
				<span class="px-3 py-2 text-sm font-bold text-white bg-[#0052cc] rounded-lg"><?php echo esc_html( $i ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $base_url . $separator . 'pagina=' . $i ); ?>" class="px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"><?php echo esc_html( $i ); ?></a>
			<?php endif;
		endfor;

		if ( $end < $pages ) : ?>
			<?php if ( $end < $pages - 1 ) : ?>
				<span class="px-2 text-slate-400 text-sm">&hellip;</span>
			<?php endif; ?>
			<a href="<?php echo esc_url( $base_url . $separator . 'pagina=' . $pages ); ?>" class="px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"><?php echo esc_html( $pages ); ?></a>
		<?php endif; ?>

		<?php if ( $paged < $pages ) : ?>
		<a href="<?php echo esc_url( $base_url . $separator . 'pagina=' . ( $paged + 1 ) ); ?>" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
			<?php esc_html_e( 'Proxima', 'guiawp' ); ?>
			<span class="material-symbols-outlined text-[16px]">chevron_right</span>
		</a>
		<?php endif; ?>
	</div>
</nav>
