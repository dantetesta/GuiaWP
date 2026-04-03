<?php
/**
 * Template: Archive de Anúncios (listagem com filtros)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.7.7 - 2026-03-14 - Cards aspect-ratio 3:2, border-radius unificado, sidebar com botao fixo e scroll interno, off-canvas mobile
 * @modified 1.8.9 - 2026-03-21 - Sidebar filtros sticky com botao Aplicar sempre visivel no rodape
 * @modified 2.1.0 - 2026-03-29 - Focus trap e Escape no off-canvas de filtros mobile para acessibilidade
 * @modified 2.1.0 - 2026-03-28 - Cards extraidos para partial reutilizavel (partials/card-anuncio.php)
 */

get_header();

$categorias          = get_terms( [ 'taxonomy' => 'gcep_categoria', 'hide_empty' => false ] );
$selected_categories = array_filter( array_map( 'sanitize_text_field', (array) ( $_GET['gcep_cat'] ?? [] ) ) );

if ( 1 === count( $selected_categories ) && '' === reset( $selected_categories ) ) {
	$selected_categories = [];
}

$paged       = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$archive_url = get_post_type_archive_link( 'gcep_anuncio' );

$args = [
	'post_type'      => 'gcep_anuncio',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'meta_query'     => [
		[
			'key'   => 'GCEP_status_anuncio',
			'value' => 'publicado',
		],
	],
];

if ( ! empty( $_GET['s'] ) ) {
	$args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
}

if ( ! empty( $selected_categories ) ) {
	$args['tax_query'] = [
		[
			'taxonomy' => 'gcep_categoria',
			'field'    => 'slug',
			'terms'    => $selected_categories,
		],
	];
}

$query              = new WP_Query( $args );
$has_active_filters = ! empty( $selected_categories ) || ! empty( $_GET['s'] );
?>

<!-- Off-Canvas Mobile: Overlay -->
<div id="gcep-filter-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" aria-hidden="true"></div>

<!-- Off-Canvas Mobile: Painel -->
<div id="gcep-filter-offcanvas" class="fixed inset-y-0 left-0 z-50 w-[85vw] max-w-sm bg-white shadow-2xl flex flex-col -translate-x-full transition-transform duration-300 ease-out lg:hidden">
	<!-- Cabeçalho off-canvas -->
	<div class="shrink-0 flex items-center justify-between px-5 py-4 border-b border-slate-100">
		<div class="flex items-center gap-2.5">
			<span class="material-symbols-outlined text-xl" style="color:var(--gcep-color-primary)">tune</span>
			<h3 class="font-bold text-slate-900"><?php esc_html_e( 'Filtros', 'guiawp-reset' ); ?></h3>
		</div>
		<div class="flex items-center gap-4">
			<a href="<?php echo esc_url( $archive_url ); ?>" class="text-xs font-bold hover:underline" style="color:var(--gcep-color-primary)"><?php esc_html_e( 'Limpar', 'guiawp-reset' ); ?></a>
			<button id="gcep-filter-close" type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-full hover:bg-slate-100 text-slate-500 transition-colors" aria-label="<?php esc_attr_e( 'Fechar filtros', 'guiawp-reset' ); ?>">
				<span class="material-symbols-outlined text-xl">close</span>
			</button>
		</div>
	</div>
	<!-- Formulário mobile -->
	<form method="get" action="<?php echo esc_url( $archive_url ); ?>" id="gcep-filter-form-mobile" class="flex flex-col flex-1 min-h-0">
		<input type="hidden" name="post_type" value="gcep_anuncio">
		<div class="flex-1 overflow-y-auto px-5 py-5 space-y-6">
			<div>
				<label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-3"><?php esc_html_e( 'Buscar', 'guiawp-reset' ); ?></label>
				<div class="relative">
					<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
					<input type="text" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" class="w-full pl-9 pr-3 py-2 bg-slate-50 border-none rounded-lg text-sm focus:ring-1" style="--tw-ring-color:var(--gcep-color-primary)" placeholder="<?php esc_attr_e( 'Nome ou serviço', 'guiawp-reset' ); ?>">
				</div>
			</div>
			<div class="h-px bg-slate-100"></div>
			<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
			<div>
				<label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-3"><?php esc_html_e( 'Categoria', 'guiawp-reset' ); ?></label>
				<div class="space-y-2.5">
					<?php foreach ( $categorias as $cat ) : ?>
					<label class="flex items-center gap-3 cursor-pointer group">
						<input type="checkbox" name="gcep_cat[]" value="<?php echo esc_attr( $cat->slug ); ?>" <?php checked( in_array( $cat->slug, $selected_categories, true ) ); ?> class="rounded border-slate-300 text-primary focus:ring-primary">
						<span class="text-sm text-slate-700 group-hover:text-primary"><?php echo esc_html( $cat->name ); ?></span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<!-- Rodapé fixo com botão sempre visível -->
		<div class="shrink-0 px-5 py-4 border-t border-slate-100 bg-white">
			<button type="submit" class="w-full py-3 text-white font-bold rounded-xl text-sm hover:opacity-90 hover:shadow-lg transition-all" style="background:var(--gcep-color-primary)">
				<?php esc_html_e( 'Aplicar Filtros', 'guiawp-reset' ); ?>
			</button>
		</div>
	</form>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
	<!-- Breadcrumbs e Header -->
	<div class="mb-6 md:mb-8">
		<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4 uppercase tracking-wider font-semibold">
			<a class="hover:text-primary" href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Início', 'guiawp-reset' ); ?></a>
			<span class="material-symbols-outlined text-xs">chevron_right</span>
			<span class="text-slate-900"><?php esc_html_e( 'Anúncios', 'guiawp-reset' ); ?></span>
		</nav>
		<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
			<div>
				<h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php esc_html_e( 'Profissionais e Empresas', 'guiawp-reset' ); ?></h1>
				<p class="text-slate-600 mt-1">
					<?php printf( esc_html__( '%d resultados encontrados.', 'guiawp-reset' ), $query->found_posts ); ?>
				</p>
			</div>
			<!-- Botão de filtros mobile -->
			<button id="gcep-filter-open" type="button" class="lg:hidden self-start inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 shadow-sm hover:border-slate-300 transition-all">
				<span class="material-symbols-outlined text-lg">tune</span>
				<?php esc_html_e( 'Filtros', 'guiawp-reset' ); ?>
				<?php if ( $has_active_filters ) : ?>
				<span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-white text-[10px] font-black" style="background:var(--gcep-color-primary)">✓</span>
				<?php endif; ?>
			</button>
		</div>
	</div>

	<div class="flex gap-6 lg:gap-8">
		<!-- Sidebar Filtros (somente desktop) -->
		<aside class="hidden lg:block w-72" style="flex-shrink:0;align-self:flex-start;position:sticky;top:5rem;max-height:calc(100vh - 6rem);">
			<form method="get" action="<?php echo esc_url( $archive_url ); ?>" id="gcep-filter-form" class="bg-white border border-slate-200 rounded-2xl" style="display:flex;flex-direction:column;height:100%;overflow:hidden;">
				<input type="hidden" name="post_type" value="gcep_anuncio">
				<!-- Cabeçalho: título + limpar + botão aplicar -->
				<div class="px-6 pt-5 pb-4" style="flex-shrink:0;">
					<div class="flex items-center justify-between mb-4">
						<h3 class="font-bold text-slate-900"><?php esc_html_e( 'Filtros', 'guiawp-reset' ); ?></h3>
						<a href="<?php echo esc_url( $archive_url ); ?>" class="text-xs font-bold hover:underline" style="color:var(--gcep-color-primary)"><?php esc_html_e( 'Limpar', 'guiawp-reset' ); ?></a>
					</div>
					<button type="submit" class="w-full py-3 text-white font-bold rounded-xl text-sm hover:opacity-90 hover:shadow-lg transition-all" style="background:var(--gcep-color-primary)">
						<?php esc_html_e( 'Aplicar Filtros', 'guiawp-reset' ); ?>
					</button>
				</div>
				<div class="border-t border-slate-100"></div>
				<!-- Área com scroll interno -->
				<div class="px-6 py-5 space-y-6" style="flex:1 1 0%;overflow-y:auto;min-height:0;">
					<div>
						<label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-3"><?php esc_html_e( 'Buscar', 'guiawp-reset' ); ?></label>
						<div class="relative">
							<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
							<input type="text" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" class="w-full pl-9 pr-3 py-2 bg-slate-50 border-none rounded-lg text-sm focus:ring-1 focus:ring-primary" placeholder="<?php esc_attr_e( 'Nome ou serviço', 'guiawp-reset' ); ?>">
						</div>
					</div>
					<div class="h-px bg-slate-100"></div>
					<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
					<div>
						<label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-3"><?php esc_html_e( 'Categoria', 'guiawp-reset' ); ?></label>
						<div class="space-y-2.5" style="max-height:320px;overflow-y:auto;padding-right:4px;">
							<?php foreach ( $categorias as $cat ) : ?>
							<label class="flex items-center gap-3 cursor-pointer group">
								<input type="checkbox" name="gcep_cat[]" value="<?php echo esc_attr( $cat->slug ); ?>" <?php checked( in_array( $cat->slug, $selected_categories, true ) ); ?> class="rounded border-slate-300 text-primary focus:ring-primary">
								<span class="text-sm text-slate-700 group-hover:text-primary"><?php echo esc_html( $cat->name ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</form>
		</aside>

		<!-- Grid de Anúncios -->
		<div class="flex-1 min-w-0">
			<?php if ( $query->have_posts() ) : ?>
			<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
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
				<h3 class="text-xl font-bold text-slate-900 mb-2"><?php esc_html_e( 'Nenhum anúncio encontrado', 'guiawp-reset' ); ?></h3>
				<p class="text-slate-500"><?php esc_html_e( 'Tente mudar os filtros da busca.', 'guiawp-reset' ); ?></p>
			</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</div>
</main>

<script>
(function () {
	var openBtn   = document.getElementById('gcep-filter-open');
	var closeBtn  = document.getElementById('gcep-filter-close');
	var overlay   = document.getElementById('gcep-filter-overlay');
	var offcanvas = document.getElementById('gcep-filter-offcanvas');

	if ( ! offcanvas ) return;

	function openFilters() {
		offcanvas.classList.remove('-translate-x-full');
		overlay.classList.remove('hidden');
		document.body.style.overflow = 'hidden';
		if (window.gcepFocusTrap) { window.gcepFocusTrap.activate(offcanvas, closeFilters); }
	}

	function closeFilters() {
		offcanvas.classList.add('-translate-x-full');
		overlay.classList.add('hidden');
		document.body.style.overflow = '';
		if (window.gcepFocusTrap) { window.gcepFocusTrap.deactivate(); }
		if (openBtn) { openBtn.focus(); }
	}

	if ( openBtn )  openBtn.addEventListener('click', openFilters);
	if ( closeBtn ) closeBtn.addEventListener('click', closeFilters);
	if ( overlay )  overlay.addEventListener('click', closeFilters);

	var cards = document.querySelectorAll('.gcep-card-anuncio');
	cards.forEach(function(card) {
		var arrow = card.querySelector('.gcep-arrow-btn');
		if (!arrow) return;
		card.addEventListener('mouseenter', function() {
			arrow.style.background = 'var(--gcep-color-primary)';
			arrow.style.borderColor = 'transparent';
			arrow.style.color = '#fff';
		});
		card.addEventListener('mouseleave', function() {
			arrow.style.background = '';
			arrow.style.borderColor = '';
			arrow.style.color = '';
		});
	});
}());
</script>
<?php get_footer(); ?>
