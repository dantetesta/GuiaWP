<?php
/**
 * Template: Listagem de todas as categorias em ordem alfabética
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.9.2 - 2026-03-21
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categorias = get_terms( [
	'taxonomy'   => 'gcep_categoria',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );

if ( is_wp_error( $categorias ) ) {
	$categorias = [];
}

$cor_primaria = GCEP_Settings::get( 'cor_primaria', '#0052cc' );
?>

<div class="bg-white min-h-screen">

	<!-- Header -->
	<div class="bg-slate-50 border-b border-slate-200">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12">
			<nav class="flex items-center gap-2 text-xs text-slate-400 mb-4">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-slate-600 transition-colors"><?php esc_html_e( 'Início', 'guiawp' ); ?></a>
				<span class="material-symbols-outlined text-xs">chevron_right</span>
				<span class="text-slate-600 font-medium"><?php esc_html_e( 'Categorias', 'guiawp' ); ?></span>
			</nav>
			<h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 mb-2"><?php esc_html_e( 'Todas as Categorias', 'guiawp' ); ?></h1>
			<p class="text-slate-500 text-sm md:text-base">
				<?php printf( esc_html__( '%d categorias disponíveis', 'guiawp' ), count( $categorias ) ); ?>
			</p>
		</div>
	</div>

	<!-- Grid de categorias -->
	<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12">

		<?php if ( empty( $categorias ) ) : ?>
		<div class="text-center py-16">
			<span class="material-symbols-outlined text-slate-200" style="font-size:4rem;">category</span>
			<p class="text-slate-500 mt-4 text-sm"><?php esc_html_e( 'Nenhuma categoria encontrada.', 'guiawp' ); ?></p>
		</div>
		<?php else : ?>
		<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 md:gap-5">
			<?php foreach ( $categorias as $cat ) :
				$img_id  = absint( get_term_meta( $cat->term_id, 'gcep_image', true ) );
				$img_url = $img_id ? ( wp_get_attachment_image_url( $img_id, 'medium' ) ?: wp_get_attachment_url( $img_id ) ) : '';
			?>
			<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="group block bg-white rounded-xl border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-color:#e2e8f0;">
				<?php if ( $img_url ) : ?>
				<div style="aspect-ratio:4/3;" class="overflow-hidden bg-slate-100">
					<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
				</div>
				<?php else : ?>
				<div style="aspect-ratio:4/3;" class="overflow-hidden bg-slate-100 flex items-center justify-center">
					<span class="material-symbols-outlined text-slate-200" style="font-size:3rem;">image</span>
				</div>
				<?php endif; ?>
				<div class="p-3 sm:p-3.5">
					<h2 class="font-bold text-xs sm:text-sm text-slate-800 leading-tight line-clamp-2 transition-colors" style="transition:color 0.2s;" onmouseover="this.style.color='<?php echo esc_attr( $cor_primaria ); ?>'" onmouseout="this.style.color=''"><?php echo esc_html( $cat->name ); ?></h2>
					<p class="text-[10px] sm:text-xs text-slate-400 font-medium mt-1">
						<?php printf( esc_html__( '%d+ locais', 'guiawp' ), $cat->count ); ?>
					</p>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

	</div>
</div>

<?php get_footer(); ?>
