<?php
/**
 * Template: Front Page (Home)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.2 - 2026-03-21 - Cards destaques redesenhados com layout do archive (badge categoria, descricao, localizacao, arrow btn)
 * @modified 2.1.0 - 2026-03-28 - Cards extraidos para partial reutilizavel (partials/card-anuncio.php)
 */

get_header();

$hero_titulo    = guiawp_reset_get_setting( 'hero_titulo', 'Encontre o que há de melhor na sua região.' );
$hero_subtitulo = guiawp_reset_get_setting( 'hero_subtitulo', 'O guia definitivo para serviços, lazer e gastronomia.' );
$hero_imagem    = guiawp_reset_get_setting( 'hero_imagem', '' );
$hero_ov_cor1   = guiawp_reset_get_setting( 'hero_overlay_cor1', '#0f172a' );
$hero_ov_cor2   = guiawp_reset_get_setting( 'hero_overlay_cor2', '#0f172a' );
$hero_ov_dir    = guiawp_reset_get_setting( 'hero_overlay_direcao', 'to bottom' );
$hero_ov_op1    = intval( guiawp_reset_get_setting( 'hero_overlay_opacidade1', '40' ) );
$hero_ov_op2    = intval( guiawp_reset_get_setting( 'hero_overlay_opacidade2', '80' ) );
$hero_ov_hex1   = $hero_ov_cor1 . str_pad( dechex( (int) round( $hero_ov_op1 * 2.55 ) ), 2, '0', STR_PAD_LEFT );
$hero_ov_hex2   = $hero_ov_cor2 . str_pad( dechex( (int) round( $hero_ov_op2 * 2.55 ) ), 2, '0', STR_PAD_LEFT );

$categorias = get_terms( [
	'taxonomy'   => 'gcep_categoria',
	'hide_empty' => false,
	'parent'     => 0,
	'number'     => 6,
] );

$destaques = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::get_featured_anuncios( 8 ) : [];

?>

<!-- Hero -->
<section class="relative h-[420px] md:h-[600px] flex items-center justify-center overflow-hidden">
	<div class="absolute inset-0 z-0">
		<?php if ( $hero_imagem ) : ?>
			<img src="<?php echo esc_url( $hero_imagem ); ?>" alt="<?php echo esc_attr( $hero_titulo ); ?>" class="absolute inset-0 w-full h-full object-cover">
		<?php else : ?>
			<div class="w-full h-full bg-[#0052cc]/20"></div>
		<?php endif; ?>
		<div class="absolute inset-0 z-10" style="background:linear-gradient(<?php echo esc_attr( $hero_ov_dir ); ?>,<?php echo esc_attr( $hero_ov_hex1 ); ?>,<?php echo esc_attr( $hero_ov_hex2 ); ?>)"></div>
	</div>
	<div class="relative z-20 max-w-4xl w-full px-4 sm:px-6 text-center">
		<h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-4 md:mb-6 leading-tight">
			<?php echo esc_html( $hero_titulo ); ?>
		</h1>
		<p class="text-base md:text-xl text-white/90 mb-6 md:mb-10 font-medium"><?php echo esc_html( $hero_subtitulo ); ?></p>

		<!-- Barra de busca -->
		<form method="get" action="<?php echo esc_url( get_post_type_archive_link( 'gcep_anuncio' ) ); ?>" class="bg-white p-2 rounded-2xl shadow-2xl flex flex-col md:flex-row items-stretch gap-2 md:gap-0 max-w-4xl mx-auto">
			<!-- Linha 1 (mobile) / Col 1 (desktop): campo de texto -->
			<div class="flex-1 flex items-center px-4 border-b md:border-b-0 md:border-r border-slate-100">
				<span class="material-symbols-outlined text-slate-400 mr-2">search</span>
				<input type="text" name="s" class="w-full border-0 focus:ring-0 text-slate-900 text-sm py-4" placeholder="<?php esc_attr_e( 'O que você procura?', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Buscar profissionais e empresas', 'guiawp-reset' ); ?>">
			</div>
			<!-- Linha 2 (mobile): categorias + botão lado a lado | desktop: display:contents (transparente ao layout) -->
			<div class="flex items-stretch gap-2 md:gap-0 md:contents">
				<div class="flex-1 flex items-center px-4 md:border-r border-slate-100">
					<span class="material-symbols-outlined text-slate-400 mr-2">category</span>
					<select name="gcep_cat" class="w-full border-0 focus:ring-0 text-slate-900 text-sm py-4 bg-transparent" aria-label="<?php esc_attr_e( 'Filtrar por categoria', 'guiawp-reset' ); ?>">
						<option value=""><?php esc_html_e( 'Todas categorias', 'guiawp-reset' ); ?></option>
						<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
							<?php foreach ( $categorias as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<input type="hidden" name="post_type" value="gcep_anuncio">
				<button type="submit" class="bg-[#0052cc] text-white font-bold rounded-xl hover:bg-[#003d99] transition-colors shadow-lg flex items-center justify-center gap-2 px-4 md:px-8 py-4 whitespace-nowrap">
					<span class="material-symbols-outlined text-xl">search</span>
					<span class="hidden md:inline"><?php esc_html_e( 'Pesquisar', 'guiawp-reset' ); ?></span>
				</button>
			</div>
		</form>
	</div>
</section>

<!-- Categorias -->
<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
<section class="py-12 md:py-20" style="background:var(--gcep-color-fundo-categorias, #f5f7f8)">
	<div class="max-w-7xl mx-auto px-4 sm:px-6">
	<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8 md:mb-12">
		<div>
			<h3 class="text-2xl md:text-3xl font-extrabold mb-2"><?php esc_html_e( 'Explore por Categorias', 'guiawp-reset' ); ?></h3>
			<p class="text-slate-500 text-sm md:text-base"><?php esc_html_e( 'Tudo o que você precisa separado por nicho', 'guiawp-reset' ); ?></p>
		</div>
		<a class="text-primary font-bold flex items-center gap-1 hover:underline text-sm" href="<?php echo esc_url( home_url( '/categorias' ) ); ?>">
			<?php esc_html_e( 'Ver todas', 'guiawp-reset' ); ?> <span class="material-symbols-outlined">chevron_right</span>
		</a>
	</div>
	<div class="gcep-cat-carousel scroll-smooth">
		<?php foreach ( $categorias as $cat ) :
			$icon   = get_term_meta( $cat->term_id, 'gcep_icon', true ) ?: 'category';
			$img_id = absint( get_term_meta( $cat->term_id, 'gcep_image', true ) );
			$img_url = $img_id ? ( wp_get_attachment_image_url( $img_id, 'medium' ) ?: wp_get_attachment_url( $img_id ) ) : '';
		?>
		<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="gcep-cat-card group cursor-pointer snap-start block bg-white rounded-xl border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-[#0052cc]/30">
			<?php if ( $img_url ) : ?>
			<div style="aspect-ratio:4/3;" class="overflow-hidden bg-slate-100">
				<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
			</div>
			<?php else : ?>
			<div style="aspect-ratio:4/3;" class="overflow-hidden bg-slate-100 flex items-center justify-center">
				<span class="material-symbols-outlined text-slate-200" style="font-size:4rem">image</span>
			</div>
			<?php endif; ?>
			<div class="p-3 sm:p-3.5">
				<h4 class="font-bold text-xs sm:text-sm text-slate-800 leading-tight line-clamp-2 group-hover:text-[#0052cc] transition-colors"><?php echo esc_html( $cat->name ); ?></h4>
				<p class="text-[10px] sm:text-xs text-slate-400 font-medium mt-1"><?php printf( esc_html__( '%d+ locais', 'guiawp-reset' ), $cat->count ); ?></p>
			</div>
		</a>
		<?php endforeach; ?>
	</div>
	</div>
</section>
<?php endif; ?>

<!-- Destaques -->
<?php if ( ! empty( $destaques ) ) : ?>
<section class="py-12 md:py-20">
	<div class="max-w-7xl mx-auto px-4 sm:px-6">
		<div class="flex items-center justify-between mb-8 md:mb-12">
			<h3 class="text-2xl md:text-3xl font-extrabold text-slate-900"><?php esc_html_e( 'Anúncios em Destaque', 'guiawp-reset' ); ?></h3>
		</div>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
			<?php foreach ( $destaques as $anuncio ) :
				$show_premium_badge = true;
				include get_template_directory() . '/partials/card-anuncio.php';
			endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- CTA -->
<?php $cta_imagem = guiawp_reset_get_setting( 'cta_imagem', '' ); ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-20">
	<div class="rounded-2xl md:rounded-[2rem] relative overflow-hidden flex flex-col md:flex-row items-stretch text-white" style="background:var(--gcep-color-primary);min-height:280px;">
		<!-- Conteúdo à esquerda -->
		<div class="relative z-10 flex-1 p-8 md:p-12 flex flex-col justify-center">
			<h3 class="text-2xl md:text-4xl font-black mb-4 md:mb-5 max-w-lg"><?php esc_html_e( 'Divulgue seu negócio para milhares de pessoas', 'guiawp-reset' ); ?></h3>
			<p class="text-white/80 text-base md:text-lg mb-6 md:mb-8 max-w-md"><?php esc_html_e( 'Faça parte do maior guia de serviços e impulsione suas vendas.', 'guiawp-reset' ); ?></p>
			<div>
				<a href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>" class="inline-flex items-center gap-2 bg-white text-slate-900 font-bold px-8 py-4 rounded-xl hover:bg-slate-50 transition-colors shadow-lg">
					<span class="material-symbols-outlined text-[20px]">rocket_launch</span>
					<?php esc_html_e( 'Criar anúncio grátis', 'guiawp-reset' ); ?>
				</a>
			</div>
		</div>
		<?php if ( $cta_imagem ) : ?>
		<!-- Imagem à direita (apenas desktop) -->
		<div class="hidden md:block relative flex-shrink-0" style="width:42%;min-height:280px">
			<img src="<?php echo esc_url( $cta_imagem ); ?>" alt="" class="absolute inset-0 w-full h-full object-cover">
			<!-- Gradient de blend na borda esquerda -->
			<div class="absolute inset-y-0 left-0 pointer-events-none" style="width:9rem;background:linear-gradient(to right,var(--gcep-color-primary),transparent)"></div>
		</div>
		<?php else : ?>
		<!-- Elementos decorativos quando sem imagem -->
		<div class="absolute -right-16 -bottom-16 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
		<div class="absolute right-32 top-6 w-48 h-48 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
		<div class="absolute right-8 bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
		<?php endif; ?>
	</div>
</section>

<!-- Blog -->
<?php
$blog_url   = home_url( '/blog' );
$posts_blog = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 6,
] );

if ( ! empty( $posts_blog ) ) :
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-12 md:pb-20">
	<div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-8 md:mb-10">
		<div>
			<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-[0.2em] mb-4">
				<span class="material-symbols-outlined text-base">article</span>
				<?php esc_html_e( 'Postagens Recentes', 'guiawp-reset' ); ?>
			</span>
			<h3 class="text-2xl md:text-3xl font-extrabold text-slate-900 mb-2"><?php esc_html_e( 'Insights novos para o seu negocio', 'guiawp-reset' ); ?></h3>
			<p class="text-slate-500 max-w-2xl text-sm md:text-base"><?php esc_html_e( 'Noticias, dicas praticas e tendencias para ajudar empresas locais a vender mais e se posicionar melhor.', 'guiawp-reset' ); ?></p>
		</div>
		<div class="flex items-center gap-3">
			<button type="button" id="gcep-blog-carousel-prev" class="w-12 h-12 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors">
				<span class="material-symbols-outlined">chevron_left</span>
			</button>
			<button type="button" id="gcep-blog-carousel-next" class="w-12 h-12 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors">
				<span class="material-symbols-outlined">chevron_right</span>
			</button>
			<a href="<?php echo esc_url( $blog_url ); ?>" class="ml-2 inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-700 transition-colors">
				<?php esc_html_e( 'Ir para o blog', 'guiawp-reset' ); ?>
				<span class="material-symbols-outlined text-base">north_east</span>
			</a>
		</div>
	</div>

	<div id="gcep-blog-carousel" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
		<?php foreach ( $posts_blog as $bp_index => $bp ) : ?>
			<article class="group bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-lg transition-all<?php echo $bp_index >= 3 ? ' hidden' : ''; ?>" data-blog-card>
				<a href="<?php echo esc_url( get_permalink( $bp->ID ) ); ?>" class="block h-44 md:h-48 bg-slate-100 overflow-hidden">
					<?php if ( has_post_thumbnail( $bp->ID ) ) : ?>
						<?php echo get_the_post_thumbnail( $bp->ID, 'medium_large', [ 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ] ); ?>
					<?php else : ?>
						<div class="w-full h-full flex items-center justify-center text-slate-300">
							<span class="material-symbols-outlined text-5xl">article</span>
						</div>
					<?php endif; ?>
				</a>
				<div class="p-4 md:p-5">
					<div class="flex items-center gap-2 text-[10px] md:text-xs font-bold uppercase tracking-[0.14em] text-slate-400 mb-2">
						<span><?php echo esc_html( get_the_date( 'd/m/Y', $bp ) ); ?></span>
						<?php
						$blog_post_categories = get_the_category( $bp->ID );
						if ( ! empty( $blog_post_categories ) ) :
						?>
							<span class="w-1 h-1 rounded-full bg-slate-300"></span>
							<span class="text-primary"><?php echo esc_html( $blog_post_categories[0]->name ); ?></span>
						<?php endif; ?>
					</div>
					<h4 class="font-bold text-sm md:text-base text-slate-900 leading-snug group-hover:text-primary transition-colors line-clamp-2">
						<a href="<?php echo esc_url( get_permalink( $bp->ID ) ); ?>"><?php echo esc_html( $bp->post_title ); ?></a>
					</h4>
					<p class="text-xs md:text-sm text-slate-500 mt-2 leading-relaxed line-clamp-2"><?php echo esc_html( wp_trim_words( get_the_excerpt( $bp->ID ) ?: $bp->post_content, 18 ) ); ?></p>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
<script>
(function() {
	var container = document.getElementById('gcep-blog-carousel');
	var prev = document.getElementById('gcep-blog-carousel-prev');
	var next = document.getElementById('gcep-blog-carousel-next');
	if (!container || !prev || !next) return;

	var cards = container.querySelectorAll('[data-blog-card]');
	var perPage = 3;
	var page = 0;
	var totalPages = Math.ceil(cards.length / perPage);

	function render() {
		cards.forEach(function(card, i) {
			var start = page * perPage;
			var end = start + perPage;
			if (i >= start && i < end) {
				card.classList.remove('hidden');
			} else {
				card.classList.add('hidden');
			}
		});
		prev.style.opacity = page <= 0 ? '0.3' : '1';
		prev.style.pointerEvents = page <= 0 ? 'none' : 'auto';
		next.style.opacity = page >= totalPages - 1 ? '0.3' : '1';
		next.style.pointerEvents = page >= totalPages - 1 ? 'none' : 'auto';
	}

	prev.addEventListener('click', function() {
		if (page > 0) { page--; render(); }
	});

	next.addEventListener('click', function() {
		if (page < totalPages - 1) { page++; render(); }
	});

	render();
})();
</script>
<?php endif; ?>

<script>
// Hover do botao arrow nos cards de anuncio
(function(){
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
})();
</script>
<style>
/* Categorias: mobile carrossel horizontal, desktop grid 6 colunas */
.gcep-cat-carousel{display:flex;overflow-x:auto;-webkit-overflow-scrolling:touch;scroll-snap-type:x mandatory;scrollbar-width:none;-ms-overflow-style:none;}
.gcep-cat-carousel::-webkit-scrollbar{display:none;}
.gcep-cat-card{width:calc((100vw - 2rem)/3.5);min-width:calc((100vw - 2rem)/3.5);flex-shrink:0;scroll-snap-align:start;}
@media (min-width:640px){
	.gcep-cat-carousel{display:grid!important;grid-template-columns:repeat(3,1fr);overflow:visible;gap:0.75rem;}
	.gcep-cat-card{width:auto!important;min-width:0!important;}
}
@media (min-width:1024px){
	.gcep-cat-carousel{grid-template-columns:repeat(6,1fr);gap:1rem;}
}
</style>
<?php get_footer(); ?>
