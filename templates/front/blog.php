<?php
/**
 * Template: Blog publico
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.4.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search_query  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$category_slug = isset( $_GET['categoria'] ) ? sanitize_title( wp_unslash( $_GET['categoria'] ) ) : '';
$current_page  = max( 1, absint( $_GET['pagina'] ?? 1 ) );
$blog_url      = home_url( '/blog' );

$query_args = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 9,
	'paged'               => $current_page,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
];

if ( '' !== $search_query ) {
	$query_args['s'] = $search_query;
}

if ( '' !== $category_slug ) {
	$query_args['category_name'] = $category_slug;
}

$blog_posts  = new WP_Query( $query_args );
$categories  = get_categories( [ 'hide_empty' => true ] );
$page_title  = '' !== $search_query ? sprintf( __( 'Resultados para "%s"', 'guiawp' ), $search_query ) : __( 'Blog', 'guiawp' );
$page_lead   = __( 'Conteudo editorial com dicas, tendencias e estrategias para negocios locais e profissionais.', 'guiawp' );

get_header();
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12">
	<section class="mb-6 md:mb-10 bg-white rounded-2xl border border-slate-200 px-4 py-4 sm:px-6 sm:py-5 shadow-sm">
		<div class="flex flex-col lg:flex-row lg:items-center lg:gap-6">
			<div class="flex items-center gap-3 mb-4 lg:mb-0 lg:shrink-0">
				<span class="material-symbols-outlined text-xl text-primary">article</span>
				<h1 class="text-xl md:text-2xl font-black tracking-tight text-slate-900"><?php echo esc_html( $page_title ); ?></h1>
			</div>

			<form method="get" action="<?php echo esc_url( $blog_url ); ?>" class="flex flex-col sm:flex-row sm:items-center gap-3 lg:flex-1">
				<input type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" class="flex-1 rounded-lg border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Buscar no blog...', 'guiawp' ); ?>">
				<select name="categoria" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none sm:w-44">
					<option value=""><?php esc_html_e( 'Todas categorias', 'guiawp' ); ?></option>
					<?php foreach ( $categories as $category ) : ?>
						<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $category_slug, $category->slug ); ?>><?php echo esc_html( $category->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="flex items-center gap-2">
					<button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-900 text-white text-sm font-bold hover:bg-slate-700 transition-colors">
						<?php esc_html_e( 'Pesquisar', 'guiawp' ); ?>
					</button>
					<a href="<?php echo esc_url( $blog_url ); ?>" class="px-4 py-2.5 rounded-lg border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
						<?php esc_html_e( 'Limpar', 'guiawp' ); ?>
					</a>
				</div>
			</form>
		</div>
	</section>

	<?php if ( $blog_posts->have_posts() ) : ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 md:gap-8">
			<?php while ( $blog_posts->have_posts() ) : $blog_posts->the_post(); ?>
				<article class="bg-white rounded-[1.75rem] border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl transition-all group">
					<a href="<?php the_permalink(); ?>" class="block aspect-[16/10] bg-slate-100 overflow-hidden">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', [ 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ] ); ?>
						<?php else : ?>
							<div class="w-full h-full flex items-center justify-center text-slate-300">
								<span class="material-symbols-outlined text-6xl">article</span>
							</div>
						<?php endif; ?>
					</a>
					<div class="p-6">
						<div class="flex flex-wrap items-center gap-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-3">
							<span><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></span>
							<?php
							$post_categories = get_the_category();
							if ( ! empty( $post_categories ) ) :
							?>
								<span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
								<span class="text-primary"><?php echo esc_html( $post_categories[0]->name ); ?></span>
							<?php endif; ?>
						</div>
						<h2 class="text-xl font-black text-slate-900 leading-tight group-hover:text-primary transition-colors">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<p class="text-sm text-slate-500 mt-3 leading-relaxed"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 24 ) ); ?></p>
						<div class="mt-5">
							<a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:gap-3 transition-all">
								<?php esc_html_e( 'Ler postagem', 'guiawp' ); ?>
								<span class="material-symbols-outlined text-base">arrow_forward</span>
							</a>
						</div>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php if ( $blog_posts->max_num_pages > 1 ) : ?>
			<div class="mt-12">
				<?php
				$pagination_links = paginate_links(
					[
						'base'      => add_query_arg( 'pagina', '%#%', $blog_url ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => (int) $blog_posts->max_num_pages,
						'mid_size'  => 1,
						'end_size'  => 1,
						'prev_text' => __( 'Anterior', 'guiawp' ),
						'next_text' => __( 'Próxima', 'guiawp' ),
						'add_args'  => array_filter(
							[
								's'         => $search_query,
								'categoria' => $category_slug,
							]
						),
						'type'      => 'array',
					]
				);

				if ( ! empty( $pagination_links ) ) :
				?>
					<nav class="flex justify-center" aria-label="<?php esc_attr_e( 'Paginação do blog', 'guiawp' ); ?>">
						<div class="flex flex-wrap items-center justify-center gap-3">
							<?php
							foreach ( $pagination_links as $pagination_link ) :
								$is_current = false !== strpos( $pagination_link, 'current' );
								$is_dots    = false !== strpos( $pagination_link, 'dots' );
								$is_prev    = false !== strpos( $pagination_link, 'prev' );
								$is_next    = false !== strpos( $pagination_link, 'next' );

								if ( $is_dots ) :
									?>
									<span class="inline-flex h-14 min-w-[3rem] items-center justify-center px-2 text-xl font-black text-slate-400">…</span>
									<?php
									continue;
								endif;

								$item_classes = 'inline-flex h-14 min-w-[3rem] items-center justify-center rounded-2xl border px-5 text-base font-black transition-all';

								if ( $is_current ) {
									$item_classes .= ' border-primary bg-primary text-white shadow-lg shadow-primary/20';
								} elseif ( $is_prev || $is_next ) {
									$item_classes .= ' border-slate-200 bg-white text-slate-900 hover:border-slate-300 hover:bg-slate-50';
								} else {
									$item_classes .= ' border-slate-200 bg-white text-slate-500 hover:border-primary/30 hover:text-primary';
								}

								$pagination_link = preg_replace(
									'/class="[^"]*"/',
									'class="' . esc_attr( $item_classes ) . '"',
									$pagination_link,
									1
								);

								echo wp_kses_post( $pagination_link );
							endforeach;
							?>
						</div>
					</nav>
				<?php endif; ?>
				?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div class="bg-white rounded-2xl md:rounded-[2rem] border border-slate-200 p-8 md:p-16 text-center shadow-sm">
			<span class="material-symbols-outlined text-6xl text-slate-300 block mb-4">article</span>
			<h2 class="text-2xl font-black text-slate-900 mb-2"><?php esc_html_e( 'Nenhuma postagem encontrada', 'guiawp' ); ?></h2>
			<p class="text-slate-500"><?php esc_html_e( 'Ajuste os filtros ou volte em breve para conferir novidades.', 'guiawp' ); ?></p>
		</div>
	<?php endif; ?>
</main>

<?php
wp_reset_postdata();
get_footer();
