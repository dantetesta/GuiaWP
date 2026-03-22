<?php
/**
 * Template: Single Post (blog)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.5.1 - 2026-03-11 - Redesign completo: hero, tipografia, author card, nav, relacionados
 * @modified 1.5.3 - 2026-03-11 - Ocultar avatar meta, views unicas por IP, AdSense inline
 * @modified 1.5.6 - 2026-03-11 - Meta no hero, sidebar sticky com anuncios relacionados, fix copiar link
 * @modified 1.9.1 - 2026-03-21 - Overlay mais escuro no hero, video de destaque sobreposto
 */

get_header();

$post_cats     = get_the_category();
$cat_name      = ! empty( $post_cats ) ? $post_cats[0]->name : '';
$cat_link      = ! empty( $post_cats ) ? get_category_link( $post_cats[0]->term_id ) : '';
$word_count    = str_word_count( wp_strip_all_tags( get_the_content() ) );
$reading_time  = max( 1, ceil( $word_count / 200 ) );
$share_url     = get_permalink();
$share_title   = get_the_title();
$share_subject = sprintf(
	/* translators: %s: post title */
	__( 'Confira este artigo: %s', 'guiawp-reset' ),
	$share_title
);
$share_body = sprintf(
	/* translators: 1: post title, 2: post URL */
	__( 'Achei que este conteudo pode te interessar:%1$s%2$s', 'guiawp-reset' ),
	"\n\n" . $share_title . "\n",
	$share_url
);

// Tracking de view unica por IP/dia
if ( class_exists( 'GCEP_Analytics' ) && method_exists( 'GCEP_Analytics', 'track_blog_view' ) ) {
	GCEP_Analytics::track_blog_view( get_the_ID() );
}
$post_views = class_exists( 'GCEP_Analytics' ) && method_exists( 'GCEP_Analytics', 'get_blog_total_views' )
	? GCEP_Analytics::get_blog_total_views( get_the_ID() )
	: 0;

// Anuncios relacionados ao post (meta box)
$anuncios_rel_ids = class_exists( 'GCEP_Blog_Metabox' )
	? GCEP_Blog_Metabox::get_related_anuncios( get_the_ID() )
	: [];
$anuncios_rel = [];
foreach ( $anuncios_rel_ids as $aid ) {
	$ap = get_post( $aid );
	if ( $ap && 'publish' === $ap->post_status ) {
		$anuncios_rel[] = $ap;
	}
}
$has_sidebar = ! empty( $anuncios_rel );

// Video de destaque
$video_destaque_url = (string) get_post_meta( get_the_ID(), '_gcep_video_destaque', true );
$video_embed_html   = '';
if ( '' !== $video_destaque_url && class_exists( 'GCEP_Helpers' ) && method_exists( 'GCEP_Helpers', 'get_video_embed' ) ) {
	$video_embed_html = GCEP_Helpers::get_video_embed( $video_destaque_url );
}
?>

<?php while ( have_posts() ) : the_post(); ?>

<?php if ( has_post_thumbnail() ) : ?>
<?php
// Quando tem video, hero mais alto para acomodar titulo + video sobreposto
$hero_class = '' !== $video_embed_html ? 'h-[420px] sm:h-[500px] md:h-[600px]' : 'h-[280px] sm:h-[360px] md:h-[480px]';
?>
<div class="relative w-full <?php echo $hero_class; ?> overflow-hidden">
	<?php the_post_thumbnail( 'full', [ 'class' => 'w-full h-full object-cover' ] ); ?>
	<div class="absolute inset-0" style="background:linear-gradient(to top, rgba(15,23,42,0.92) 0%, rgba(15,23,42,0.6) 40%, rgba(15,23,42,0.15) 70%, transparent 100%);"></div>
	<div class="absolute left-0 right-0 px-4 sm:px-6" style="<?php echo '' !== $video_embed_html ? 'top:50%;transform:translateY(-70%)' : 'bottom:0;padding-bottom:2rem'; ?>">
		<div class="max-w-4xl mx-auto">
			<?php if ( $cat_name ) : ?>
			<a href="<?php echo esc_url( $cat_link ); ?>" class="inline-block px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-white text-xs font-bold uppercase tracking-wider mb-4 hover:bg-white/30 transition-colors">
				<?php echo esc_html( $cat_name ); ?>
			</a>
			<?php endif; ?>
			<h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight tracking-tight">
				<?php the_title(); ?>
			</h1>
			<div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-4 text-sm text-white/70">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="flex items-center gap-1">
					<span class="material-symbols-outlined text-sm">calendar_today</span>
					<?php echo esc_html( get_the_date() ); ?>
				</time>
				<span class="w-1 h-1 rounded-full bg-white/40"></span>
				<span class="flex items-center gap-1">
					<span class="material-symbols-outlined text-sm">schedule</span>
					<?php printf( esc_html__( '%d min de leitura', 'guiawp-reset' ), $reading_time ); ?>
				</span>
				<?php if ( $post_views > 0 ) : ?>
				<span class="w-1 h-1 rounded-full bg-white/40"></span>
				<span class="flex items-center gap-1">
					<span class="material-symbols-outlined text-sm">visibility</span>
					<?php printf( esc_html__( '%s visualizacoes', 'guiawp-reset' ), number_format_i18n( $post_views ) ); ?>
				</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php if ( '' !== $video_embed_html ) : ?>
<div style="margin-top:-150px;padding-bottom:2rem;position:relative;z-index:10;">
	<div class="max-w-3xl mx-auto px-4 sm:px-6">
		<div style="border-radius:12px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
			<?php echo $video_embed_html; ?>
		</div>
	</div>
</div>
<?php endif; ?>
<?php else : ?>
<div class="bg-gradient-to-br from-primary/10 via-slate-50 to-slate-100 pt-8 md:pt-14 pb-8 md:pb-12">
	<div class="max-w-4xl mx-auto px-4 sm:px-6">
		<?php if ( $cat_name ) : ?>
		<a href="<?php echo esc_url( $cat_link ); ?>" class="inline-block px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-4 hover:bg-primary/20 transition-colors">
			<?php echo esc_html( $cat_name ); ?>
		</a>
		<?php endif; ?>
		<h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-slate-900 leading-tight tracking-tight">
			<?php the_title(); ?>
		</h1>
		<div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-4 text-sm text-slate-500">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="flex items-center gap-1">
				<span class="material-symbols-outlined text-sm">calendar_today</span>
				<?php echo esc_html( get_the_date() ); ?>
			</time>
			<span class="w-1 h-1 rounded-full bg-slate-300"></span>
			<span class="flex items-center gap-1">
				<span class="material-symbols-outlined text-sm">schedule</span>
				<?php printf( esc_html__( '%d min de leitura', 'guiawp-reset' ), $reading_time ); ?>
			</span>
			<?php if ( $post_views > 0 ) : ?>
			<span class="w-1 h-1 rounded-full bg-slate-300"></span>
			<span class="flex items-center gap-1">
				<span class="material-symbols-outlined text-sm">visibility</span>
				<?php printf( esc_html__( '%s visualizacoes', 'guiawp-reset' ), number_format_i18n( $post_views ) ); ?>
			</span>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-10">
	<div class="<?php echo $has_sidebar ? 'lg:grid lg:grid-cols-[1fr_300px] lg:gap-10' : 'max-w-3xl mx-auto'; ?>">

	<!-- Coluna principal -->
	<div class="min-w-0">

	<!-- Conteudo do post -->
	<article class="gcep-article-content">
		<?php the_content(); ?>
	</article>

	<!-- Tags -->
	<?php
	$post_tags = get_the_tags();
	if ( ! empty( $post_tags ) ) :
	?>
	<div class="flex flex-wrap items-center gap-2 pb-8 border-b border-slate-200">
		<span class="material-symbols-outlined text-base text-slate-400">sell</span>
		<?php foreach ( $post_tags as $tag ) : ?>
		<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="px-3 py-1 rounded-full bg-slate-100 text-xs font-semibold text-slate-600 hover:bg-primary/10 hover:text-primary transition-colors">
			<?php echo esc_html( $tag->name ); ?>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- Compartilhar -->
	<div class="py-6 md:py-8 border-b border-slate-200">
		<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
			<div>
				<span class="text-sm font-bold text-slate-700"><?php esc_html_e( 'Compartilhar', 'guiawp-reset' ); ?></span>
				<p class="text-sm text-slate-500 mt-1"><?php esc_html_e( 'Envie este artigo para outras pessoas ou copie o link.', 'guiawp-reset' ); ?></p>
			</div>
			<div class="flex flex-wrap items-center gap-2">
				<a href="https://wa.me/?text=<?php echo rawurlencode( $share_title . ' ' . $share_url ); ?>" target="_blank" rel="noopener noreferrer" class="gcep-share-button w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-[#25d366] hover:text-white transition-all" title="<?php esc_attr_e( 'Compartilhar no WhatsApp', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Compartilhar no WhatsApp', 'guiawp-reset' ); ?>">
					<svg viewBox="0 0 24 24" class="w-5 h-5 fill-current" aria-hidden="true"><path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.56 2 2.1 6.46 2.1 11.93c0 1.75.46 3.46 1.33 4.96L2 22l5.24-1.37a9.9 9.9 0 0 0 4.79 1.22h.01c5.47 0 9.93-4.46 9.93-9.93a9.85 9.85 0 0 0-2.92-7.01Zm-7.02 15.26h-.01a8.3 8.3 0 0 1-4.23-1.16l-.3-.18-3.11.81.83-3.03-.2-.31a8.25 8.25 0 0 1-1.27-4.37c0-4.56 3.71-8.27 8.28-8.27 2.21 0 4.28.86 5.84 2.42a8.2 8.2 0 0 1 2.42 5.84c0 4.56-3.71 8.27-8.25 8.27Zm4.54-6.19c-.25-.13-1.47-.73-1.7-.81-.23-.08-.39-.13-.56.13-.16.25-.64.81-.78.98-.14.16-.29.19-.54.06-.25-.13-1.04-.38-1.98-1.21-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.42.08-.16.04-.31-.02-.43-.06-.13-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42h-.47c-.16 0-.43.06-.65.31-.23.25-.86.84-.86 2.04s.88 2.36 1 2.52c.13.16 1.74 2.66 4.21 3.73.59.25 1.05.4 1.41.51.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29Z"/></svg>
				</a>
				<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( $share_url ); ?>" target="_blank" rel="noopener noreferrer" class="gcep-share-button w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-[#1877f2] hover:text-white transition-all" title="<?php esc_attr_e( 'Compartilhar no Facebook', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Compartilhar no Facebook', 'guiawp-reset' ); ?>">
					<svg viewBox="0 0 24 24" class="w-5 h-5 fill-current" aria-hidden="true"><path d="M13.5 21v-7h2.35l.35-2.73H13.5V9.53c0-.79.22-1.32 1.35-1.32h1.44V5.78c-.25-.03-1.11-.08-2.11-.08-2.09 0-3.52 1.27-3.52 3.61v2.01H8.29V14h2.37v7h2.84Z"/></svg>
				</a>
				<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( $share_url ); ?>" target="_blank" rel="noopener noreferrer" class="gcep-share-button w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-[#0a66c2] hover:text-white transition-all" title="<?php esc_attr_e( 'Compartilhar no LinkedIn', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Compartilhar no LinkedIn', 'guiawp-reset' ); ?>">
					<svg viewBox="0 0 24 24" class="w-5 h-5 fill-current" aria-hidden="true"><path d="M6.94 8.5A1.56 1.56 0 1 1 6.94 5.4a1.56 1.56 0 0 1 0 3.1ZM8.3 18.6H5.58V9.86H8.3v8.74ZM18.42 18.6h-2.71v-4.25c0-1.01-.02-2.32-1.41-2.32-1.41 0-1.63 1.1-1.63 2.24v4.33H9.95V9.86h2.6v1.19h.04c.36-.69 1.25-1.41 2.57-1.41 2.75 0 3.26 1.81 3.26 4.16v4.8Z"/></svg>
				</a>
				<a href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode( $share_url ); ?>&text=<?php echo rawurlencode( $share_title ); ?>" target="_blank" rel="noopener noreferrer" class="gcep-share-button w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-900 hover:text-white transition-all" title="<?php esc_attr_e( 'Compartilhar no X', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Compartilhar no X', 'guiawp-reset' ); ?>">
					<svg viewBox="0 0 24 24" class="w-5 h-5 fill-current" aria-hidden="true"><path d="M18.9 2H22l-6.77 7.74L23.2 22h-6.25l-4.9-6.41L6.44 22H3.33l7.24-8.27L1 2h6.41l4.43 5.85L18.9 2Zm-1.09 18.13h1.72L6.48 3.78H4.63l13.18 16.35Z"/></svg>
				</a>
				<a href="mailto:?subject=<?php echo rawurlencode( $share_subject ); ?>&body=<?php echo rawurlencode( $share_body ); ?>" class="gcep-share-button w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-700 hover:text-white transition-all" title="<?php esc_attr_e( 'Compartilhar por e-mail', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Compartilhar por e-mail', 'guiawp-reset' ); ?>">
					<svg viewBox="0 0 24 24" class="w-5 h-5 fill-current" aria-hidden="true"><path d="M20 5H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 2v.01L12 12 4 7.01V7h16ZM4 17V9.24l7.4 4.63a1 1 0 0 0 1.2 0L20 9.24V17H4Z"/></svg>
				</a>
				<button type="button" class="gcep-copy-share-link w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-primary hover:text-white transition-all" data-link="<?php echo esc_attr( $share_url ); ?>" data-default-label="<?php esc_attr_e( 'Copiar link', 'guiawp-reset' ); ?>" data-success-label="<?php esc_attr_e( 'Link copiado', 'guiawp-reset' ); ?>" title="<?php esc_attr_e( 'Copiar link', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Copiar link', 'guiawp-reset' ); ?>">
					<span class="material-symbols-outlined text-xl" aria-hidden="true">link</span>
				</button>
			</div>
		</div>
	</div>

	<!-- Navegacao prev/next -->
	<?php
	$prev_post = get_previous_post();
	$next_post = get_next_post();
	if ( $prev_post || $next_post ) :
	?>
	<nav class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-8 md:py-10 border-b border-slate-200">
		<?php if ( $prev_post ) : ?>
		<a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="group flex items-center gap-3 p-4 rounded-xl hover:bg-slate-50 transition-colors">
			<span class="material-symbols-outlined text-xl text-slate-300 group-hover:text-primary transition-colors">arrow_back</span>
			<div class="min-w-0">
				<span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider"><?php esc_html_e( 'Anterior', 'guiawp-reset' ); ?></span>
				<p class="text-sm font-bold text-slate-700 truncate group-hover:text-primary transition-colors"><?php echo esc_html( $prev_post->post_title ); ?></p>
			</div>
		</a>
		<?php else : ?>
		<div></div>
		<?php endif; ?>
		<?php if ( $next_post ) : ?>
		<a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="group flex items-center justify-end gap-3 p-4 rounded-xl hover:bg-slate-50 transition-colors text-right">
			<div class="min-w-0">
				<span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider"><?php esc_html_e( 'Próximo', 'guiawp-reset' ); ?></span>
				<p class="text-sm font-bold text-slate-700 truncate group-hover:text-primary transition-colors"><?php echo esc_html( $next_post->post_title ); ?></p>
			</div>
			<span class="material-symbols-outlined text-xl text-slate-300 group-hover:text-primary transition-colors">arrow_forward</span>
		</a>
		<?php endif; ?>
	</nav>
<?php endif; ?>

	</div><!-- /.min-w-0 coluna principal -->

	<?php if ( $has_sidebar ) : ?>
	<!-- Sidebar: anuncios relacionados -->
	<aside class="hidden lg:block">
		<div class="sticky top-24 space-y-6">
			<div>
				<h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
					<span class="material-symbols-outlined text-sm">storefront</span>
					<?php esc_html_e( 'Anuncios relacionados', 'guiawp-reset' ); ?>
				</h3>
				<div class="space-y-4">
					<?php foreach ( $anuncios_rel as $anuncio_post ) :
						$anuncio_id    = $anuncio_post->ID;
						$anuncio_plano = (string) get_post_meta( $anuncio_id, 'GCEP_tipo_plano', true );
						$anuncio_desc  = (string) get_post_meta( $anuncio_id, 'GCEP_descricao_curta', true );
						if ( '' === $anuncio_desc ) {
							$anuncio_desc = wp_trim_words( wp_strip_all_tags( (string) get_post_meta( $anuncio_id, 'GCEP_descricao_longa', true ) ), 15, '...' );
						}
						$anuncio_cats = wp_get_object_terms( $anuncio_id, 'gcep_categoria', [ 'fields' => 'names' ] );
						$anuncio_cat  = ! is_wp_error( $anuncio_cats ) && ! empty( $anuncio_cats ) ? $anuncio_cats[0] : '';

						// Imagem: featured image > foto_capa > logo/foto_principal
						$_anuncio_img_html = '';
						$_img_candidates = [
							get_post_thumbnail_id( $anuncio_id ),
							get_post_meta( $anuncio_id, 'GCEP_foto_capa', true ),
							get_post_meta( $anuncio_id, 'GCEP_logo_ou_foto_principal', true ),
						];
						foreach ( $_img_candidates as $_cand_id ) {
							if ( ! $_cand_id ) continue;
							$_html = wp_get_attachment_image( (int) $_cand_id, 'medium', false, [
								'class'   => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300',
								'alt'     => esc_attr( $anuncio_post->post_title ),
								'loading' => 'lazy',
							] );
							if ( $_html ) {
								$_anuncio_img_html = $_html;
								break;
							}
						}
					?>
					<a href="<?php echo esc_url( get_permalink( $anuncio_id ) ); ?>" class="group block bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-lg transition-all">
						<?php if ( $_anuncio_img_html ) : ?>
						<div class="h-36 bg-slate-100 overflow-hidden">
							<?php echo $_anuncio_img_html; ?>
						</div>
						<?php endif; ?>
						<div class="p-4">
							<?php if ( 'premium' === $anuncio_plano ) : ?>
							<span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1">
								<span class="material-symbols-outlined text-xs">workspace_premium</span>
								Premium
							</span>
							<?php endif; ?>
							<?php if ( $anuncio_cat ) : ?>
							<span class="block text-[10px] font-bold text-primary uppercase tracking-wider mb-1"><?php echo esc_html( $anuncio_cat ); ?></span>
							<?php endif; ?>
							<h4 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-primary transition-colors"><?php echo esc_html( $anuncio_post->post_title ); ?></h4>
							<?php if ( $anuncio_desc ) : ?>
							<p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed"><?php echo esc_html( $anuncio_desc ); ?></p>
							<?php endif; ?>
							<span class="inline-flex items-center gap-1 text-xs font-bold text-primary mt-3 group-hover:gap-2 transition-all">
								<?php esc_html_e( 'Ver anuncio', 'guiawp-reset' ); ?>
								<span class="material-symbols-outlined text-sm">arrow_forward</span>
							</span>
						</div>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</aside>
	<?php endif; ?>

	</div><!-- /.grid -->
</main>

<!-- Posts relacionados -->
<?php
$related_args = [
	'post_type'      => 'post',
	'posts_per_page' => 3,
	'post__not_in'   => [ get_the_ID() ],
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
];
if ( ! empty( $post_cats ) ) {
	$related_args['cat'] = $post_cats[0]->term_id;
}
$related = new WP_Query( $related_args );
if ( $related->have_posts() ) :
?>
<section class="bg-white border-t border-slate-200 py-12 md:py-16">
	<div class="max-w-5xl mx-auto px-4 sm:px-6">
		<h2 class="text-xl md:text-2xl font-black text-slate-900 mb-8 text-center"><?php esc_html_e( 'Leia também', 'guiawp-reset' ); ?></h2>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
			<?php while ( $related->have_posts() ) : $related->the_post(); ?>
			<a href="<?php the_permalink(); ?>" class="group block bg-white rounded-xl border border-slate-200 overflow-hidden hover-lift">
				<?php if ( has_post_thumbnail() ) : ?>
				<div class="h-44 overflow-hidden">
					<?php the_post_thumbnail( 'medium_large', [ 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300' ] ); ?>
				</div>
				<?php endif; ?>
				<div class="p-4 sm:p-5">
					<?php
					$rel_cats = get_the_category();
					if ( ! empty( $rel_cats ) ) :
					?>
					<span class="text-[11px] font-bold text-primary uppercase tracking-wider"><?php echo esc_html( $rel_cats[0]->name ); ?></span>
					<?php endif; ?>
					<h3 class="text-sm font-bold text-slate-900 mt-1 leading-snug line-clamp-2 group-hover:text-primary transition-colors"><?php the_title(); ?></h3>
					<p class="text-xs text-slate-400 mt-2"><?php echo esc_html( get_the_date() ); ?></p>
				</div>
			</a>
			<?php endwhile; ?>
		</div>
	</div>
</section>
<?php
endif;
wp_reset_postdata();
?>

<?php endwhile; ?>

<script>
(function(){
	var copyButtons = document.querySelectorAll('.gcep-copy-share-link');
	if (!copyButtons.length) return;

	function fallbackCopy(text) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', '');
		ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;';
		document.body.appendChild(ta);
		ta.focus();
		ta.select();
		var ok = false;
		try { ok = document.execCommand('copy'); } catch(e) { ok = false; }
		document.body.removeChild(ta);
		return ok;
	}

	copyButtons.forEach(function(btn){
		btn.addEventListener('click', function(){
			var link = btn.getAttribute('data-link') || '';
			var defaultLabel = btn.getAttribute('data-default-label') || '';
			var successLabel = btn.getAttribute('data-success-label') || defaultLabel;
			if (!link) return;

			function onSuccess() {
				btn.setAttribute('title', successLabel);
				btn.setAttribute('aria-label', successLabel);
				btn.classList.add('bg-primary', 'text-white');
				// Tooltip visual
				var tip = document.createElement('span');
				tip.textContent = successLabel;
				tip.style.cssText = 'position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;font-size:11px;padding:4px 10px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:50;';
				btn.style.position = 'relative';
				btn.appendChild(tip);
				setTimeout(function(){
					btn.setAttribute('title', defaultLabel);
					btn.setAttribute('aria-label', defaultLabel);
					btn.classList.remove('bg-primary', 'text-white');
					if (tip.parentNode) tip.remove();
				}, 1800);
			}

			// Tentar Clipboard API primeiro (funciona em HTTPS)
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(link).then(onSuccess).catch(function(){
					if (fallbackCopy(link)) onSuccess();
				});
				return;
			}
			// Fallback para HTTP/.local
			if (fallbackCopy(link)) onSuccess();
		});
	});
})();
</script>

<?php get_footer(); ?>
