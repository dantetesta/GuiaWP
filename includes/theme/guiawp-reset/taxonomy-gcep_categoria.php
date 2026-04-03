<?php
/**
 * Template: Taxonomy de Categoria de Anúncios
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.8.3 - 2026-03-20
 * @modified 1.8.5 - 2026-03-20 - Filtros por titulo/cidade/estado e ordenacao por titulo/data/visitas
 * @modified 2.1.0 - 2026-03-28 - Cards extraidos para partial reutilizavel (partials/card-anuncio.php)
 */

get_header();

global $wp;

$term        = get_queried_object();
$term_name   = $term instanceof WP_Term ? $term->name : '';
$archive_url = get_post_type_archive_link( 'gcep_anuncio' );
$current_url = home_url( $wp->request );

// Parâmetros de filtro
$search_s    = isset( $_GET['s'] )            ? sanitize_text_field( wp_unslash( $_GET['s'] ) )            : '';
$filter_city = isset( $_GET['gcep_cidade'] )  ? sanitize_text_field( wp_unslash( $_GET['gcep_cidade'] ) )  : '';
$filter_uf   = isset( $_GET['gcep_estado'] )  ? strtoupper( sanitize_text_field( wp_unslash( $_GET['gcep_estado'] ) ) ) : '';
$order_by    = isset( $_GET['gcep_order'] )   ? sanitize_key( wp_unslash( $_GET['gcep_order'] ) )           : 'recent';
$paged       = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );

$has_filters = $search_s || $filter_city || $filter_uf || ( 'recent' !== $order_by );

// Mapa de ordenação → WP_Query args
$order_map = [
	'title_asc'  => [ 'orderby' => 'title',         'order' => 'ASC'  ],
	'title_desc' => [ 'orderby' => 'title',         'order' => 'DESC' ],
	'recent'     => [ 'orderby' => 'date',          'order' => 'DESC' ],
	'oldest'     => [ 'orderby' => 'date',          'order' => 'ASC'  ],
	'views_desc' => [ 'orderby' => 'views_desc',    'order' => 'DESC' ],
	'views_asc'  => [ 'orderby' => 'views_asc',     'order' => 'ASC'  ],
];
$order_cfg = $order_map[ $order_by ] ?? $order_map['recent'];

// Para ordenação por visitas: buscar IDs do analytics
$views_ids = null;
if ( in_array( $order_by, [ 'views_desc', 'views_asc' ], true ) && class_exists( 'GCEP_Analytics' ) ) {
	global $wpdb;
	$dir        = 'views_asc' === $order_by ? 'ASC' : 'DESC';
	$tbl        = $wpdb->prefix . 'gcep_analytics';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$views_ids  = $wpdb->get_col( "SELECT post_id FROM $tbl GROUP BY post_id ORDER BY COUNT(*) $dir" );
	$views_ids  = array_map( 'intval', (array) $views_ids );
}

// Construir WP_Query
$query_args = [
	'post_type'      => 'gcep_anuncio',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'tax_query'      => [
		[
			'taxonomy' => 'gcep_categoria',
			'field'    => 'term_id',
			'terms'    => $term instanceof WP_Term ? $term->term_id : 0,
		],
	],
	'meta_query'     => [
		'relation' => 'AND',
		[
			'key'   => 'GCEP_status_anuncio',
			'value' => 'publicado',
		],
	],
];

// Filtro por título
if ( $search_s ) {
	$query_args['s'] = $search_s;
}

// Filtro por cidade (LIKE)
if ( $filter_city ) {
	$query_args['meta_query'][] = [
		'key'     => 'GCEP_cidade',
		'value'   => $filter_city,
		'compare' => 'LIKE',
	];
}

// Filtro por estado (exact, UF)
if ( $filter_uf ) {
	$query_args['meta_query'][] = [
		'key'     => 'GCEP_estado',
		'value'   => $filter_uf,
		'compare' => '=',
	];
}

// Ordenação
if ( null !== $views_ids ) {
	if ( empty( $views_ids ) ) {
		$query_args['post__in'] = [ 0 ];
	} else {
		$query_args['post__in']  = $views_ids;
		$query_args['orderby']   = 'post__in';
	}
} else {
	$query_args['orderby'] = $order_cfg['orderby'];
	$query_args['order']   = $order_cfg['order'];
}

$query = new WP_Query( $query_args );

// URL base para paginação preservando filtros
$pagination_base = add_query_arg(
	array_filter( [
		's'          => $search_s ?: null,
		'gcep_cidade'=> $filter_city ?: null,
		'gcep_estado'=> $filter_uf ?: null,
		'gcep_order' => ( 'recent' !== $order_by ) ? $order_by : null,
		'paged'      => '%#%',
	] ),
	$current_url
);

// Label do ordenador ativo
$order_labels = [
	'recent'     => __( 'Mais recentes', 'guiawp-reset' ),
	'oldest'     => __( 'Mais antigos',  'guiawp-reset' ),
	'title_asc'  => __( 'Título A→Z',    'guiawp-reset' ),
	'title_desc' => __( 'Título Z→A',    'guiawp-reset' ),
	'views_desc' => __( 'Mais visitados','guiawp-reset' ),
	'views_asc'  => __( 'Menos visitados','guiawp-reset' ),
];
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">

	<!-- Breadcrumbs e Header -->
	<div class="mb-5 md:mb-6">
		<nav class="flex items-center gap-2 text-xs text-slate-500 mb-3 uppercase tracking-wider font-semibold">
			<a class="hover:text-primary" href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Início', 'guiawp-reset' ); ?></a>
			<span class="material-symbols-outlined text-xs">chevron_right</span>
			<a class="hover:text-primary" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Anúncios', 'guiawp-reset' ); ?></a>
			<span class="material-symbols-outlined text-xs">chevron_right</span>
			<span class="text-slate-900"><?php echo esc_html( $term_name ); ?></span>
		</nav>
		<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
			<div>
				<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $term_name ); ?></h2>
				<p class="text-slate-500 mt-1 text-sm">
					<?php printf( esc_html__( '%d resultado(s) encontrado(s).', 'guiawp-reset' ), $query->found_posts ); ?>
				</p>
			</div>
			<?php if ( $has_filters ) : ?>
			<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-red-500 transition-colors self-start sm:self-auto">
				<span class="material-symbols-outlined text-sm">close</span>
				<?php esc_html_e( 'Limpar filtros', 'guiawp-reset' ); ?>
			</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Barra de Filtros -->
	<form method="get" action="<?php echo esc_url( get_term_link( $term ) ); ?>" class="mb-6 bg-white border border-slate-200 rounded-2xl p-4 flex flex-col sm:flex-row flex-wrap items-end gap-3">
		<!-- Título -->
		<div class="flex-1 min-w-[150px]">
			<label class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.12em] block mb-1.5"><?php esc_html_e( 'Título', 'guiawp-reset' ); ?></label>
			<input type="text" name="s" value="<?php echo esc_attr( $search_s ); ?>" placeholder="<?php esc_attr_e( 'Buscar…', 'guiawp-reset' ); ?>" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary bg-slate-50">
		</div>
		<!-- Cidade -->
		<div class="flex-1 min-w-[130px]">
			<label class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.12em] block mb-1.5"><?php esc_html_e( 'Cidade', 'guiawp-reset' ); ?></label>
			<input type="text" name="gcep_cidade" value="<?php echo esc_attr( $filter_city ); ?>" placeholder="<?php esc_attr_e( 'São Paulo', 'guiawp-reset' ); ?>" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary bg-slate-50">
		</div>
		<!-- Estado (UF) -->
		<div class="w-20">
			<label class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.12em] block mb-1.5"><?php esc_html_e( 'UF', 'guiawp-reset' ); ?></label>
			<input type="text" name="gcep_estado" value="<?php echo esc_attr( $filter_uf ); ?>" placeholder="SP" maxlength="2" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary bg-slate-50 uppercase text-center">
		</div>
		<!-- Ordenar por -->
		<div class="flex-1 min-w-[150px]">
			<label class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.12em] block mb-1.5"><?php esc_html_e( 'Ordenar por', 'guiawp-reset' ); ?></label>
			<select name="gcep_order" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary bg-slate-50">
				<?php foreach ( $order_labels as $val => $lbl ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $order_by, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<!-- Botão filtrar -->
		<button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-colors shadow-sm shrink-0" style="background:var(--gcep-color-primary)">
			<span class="material-symbols-outlined text-[18px]">tune</span>
			<?php esc_html_e( 'Filtrar', 'guiawp-reset' ); ?>
		</button>
	</form>

	<!-- Grid de Anúncios -->
	<?php if ( $query->have_posts() ) : ?>
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
		<?php while ( $query->have_posts() ) : $query->the_post();
			$anuncio            = get_post();
			$show_premium_badge = true;
			include get_template_directory() . '/partials/card-anuncio.php';
		endwhile; ?>
	</div>

	<!-- Paginação -->
	<?php if ( $query->max_num_pages > 1 ) : ?>
	<div class="mt-12 flex items-center justify-center gap-2">
		<?php
		echo paginate_links( [
			'base'      => $pagination_base,
			'format'    => '',
			'total'     => $query->max_num_pages,
			'current'   => $paged,
			'prev_text' => '<span class="material-symbols-outlined">chevron_left</span>',
			'next_text' => '<span class="material-symbols-outlined">chevron_right</span>',
			'type'      => 'list',
		] );
		?>
	</div>
	<?php endif; ?>

	<?php else : ?>
	<div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
		<span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">search_off</span>
		<h3 class="text-xl font-bold text-slate-900 mb-2"><?php esc_html_e( 'Nenhum anúncio encontrado.', 'guiawp-reset' ); ?></h3>
		<?php if ( $has_filters ) : ?>
		<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold hover:underline" style="color:var(--gcep-color-primary)">
			<span class="material-symbols-outlined text-base">close</span>
			<?php esc_html_e( 'Limpar filtros', 'guiawp-reset' ); ?>
		</a>
		<?php else : ?>
		<a href="<?php echo esc_url( $archive_url ); ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-bold hover:underline" style="color:var(--gcep-color-primary)">
			<span class="material-symbols-outlined text-base">arrow_back</span>
			<?php esc_html_e( 'Ver todos os anúncios', 'guiawp-reset' ); ?>
		</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>

</main>

<script>
(function () {
	var cards = document.querySelectorAll('.gcep-card-anuncio');
	cards.forEach(function(card) {
		var arrow = card.querySelector('.gcep-arrow-btn');
		if (!arrow) return;
		card.addEventListener('mouseenter', function() {
			arrow.style.background  = 'var(--gcep-color-primary)';
			arrow.style.borderColor = 'transparent';
			arrow.style.color       = '#fff';
		});
		card.addEventListener('mouseleave', function() {
			arrow.style.background  = '';
			arrow.style.borderColor = '';
			arrow.style.color       = '';
		});
	});
	// Forçar UF maiúscula
	var ufInput = document.querySelector('input[name="gcep_estado"]');
	if (ufInput) {
		ufInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
	}
}());
</script>

<?php get_footer(); ?>
