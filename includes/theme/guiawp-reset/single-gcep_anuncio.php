<?php
/**
 * Template: Single Anúncio (página do anúncio)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.1.0 - 2026-03-11 - Galeria fotos, vídeos embed, CNPJ, novas redes sociais, descrição longa
 * @modified 1.9.9 - 2026-03-22 - Cores dinâmicas via CSS variables no link de categorias
 * @modified 2.1.0 - 2026-03-29 - Focus trap no lightbox de mídia para acessibilidade
 */

$post_id         = get_the_ID();
$status_anuncio  = (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true );

if ( 'publicado' !== $status_anuncio && ! current_user_can( 'edit_post', $post_id ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	return;
}

get_header();

// Track view
if ( 'publicado' === $status_anuncio && class_exists( 'GCEP_Analytics' ) ) {
	GCEP_Analytics::track_view( $post_id );
}
$meta      = [];
$keys      = [ 'GCEP_tipo_anuncio', 'GCEP_tipo_plano', 'GCEP_cnpj', 'GCEP_descricao_curta', 'GCEP_telefone', 'GCEP_whatsapp', 'GCEP_email', 'GCEP_endereco_completo', 'GCEP_site', 'GCEP_instagram', 'GCEP_facebook', 'GCEP_linkedin', 'GCEP_youtube', 'GCEP_x_twitter', 'GCEP_tiktok', 'GCEP_threads', 'GCEP_descricao_longa', 'GCEP_logo_ou_foto_principal', 'GCEP_foto_capa', 'GCEP_galeria_fotos', 'GCEP_galeria_videos', 'GCEP_latitude', 'GCEP_longitude' ];
foreach ( $keys as $k ) {
	$meta[ $k ] = get_post_meta( $post_id, $k, true );
}

$plano     = $meta['GCEP_tipo_plano'] ?: 'gratis';
$is_premium = 'premium' === $plano;
$show_free_adsense_slots = ! $is_premium && function_exists( 'guiawp_reset_is_adsense_enabled' ) && guiawp_reset_is_adsense_enabled();
$cat_terms = wp_get_object_terms( $post_id, 'gcep_categoria' );
$cats      = ! is_wp_error( $cat_terms ) ? $cat_terms : [];
$locs      = wp_get_object_terms( $post_id, 'gcep_localizacao', [ 'fields' => 'names' ] );
$logo_id   = $meta['GCEP_logo_ou_foto_principal'];
$capa_id   = $meta['GCEP_foto_capa'];
$share_url   = get_permalink();
$share_title = get_the_title();
$share_subject = sprintf(
	/* translators: %s: ad title */
	__( 'Confira este anúncio: %s', 'guiawp-reset' ),
	$share_title
);
$share_body = sprintf(
	/* translators: 1: ad title, 2: ad URL */
	__( 'Encontrei este anúncio no GuiaWP:%1$s%2$s', 'guiawp-reset' ),
	"\n\n" . $share_title . "\n",
	$share_url
);
$has_address                = ! empty( $meta['GCEP_endereco_completo'] );
$maps_query                 = ! empty( $meta['GCEP_endereco_completo'] ) ? urlencode( (string) $meta['GCEP_endereco_completo'] ) : '';
$google_maps_search_url     = $maps_query ? 'https://www.google.com/maps/search/' . $maps_query : '';
$google_maps_directions_url = $maps_query ? 'https://www.google.com/maps/dir/?api=1&destination=' . $maps_query : '';
$waze_url                   = $maps_query ? 'https://waze.com/ul?q=' . $maps_query : '';

// Galeria de fotos
$gallery_items = [];
if ( ! empty( $meta['GCEP_galeria_fotos'] ) ) {
	$galeria_ids = array_filter( array_map( 'absint', explode( ',', (string) $meta['GCEP_galeria_fotos'] ) ) );

	foreach ( $galeria_ids as $gid ) {
		$full_image_url = wp_get_attachment_image_url( $gid, 'full' );
		if ( ! $full_image_url ) {
			continue;
		}

		$thumb_image_url = wp_get_attachment_image_url( $gid, 'large' );
		$image_alt       = trim( (string) get_post_meta( $gid, '_wp_attachment_image_alt', true ) );

		$gallery_items[] = [
			'full'  => $full_image_url,
			'thumb' => $thumb_image_url ?: $full_image_url,
			'alt'   => '' !== $image_alt ? $image_alt : get_the_title(),
		];
	}
}
$gallery_chunks = array_chunk( $gallery_items, 4 );

// Galeria de vídeos
$videos = $meta['GCEP_galeria_videos'];
if ( ! is_array( $videos ) ) {
	$videos = [];
}

$video_items = [];
foreach ( $videos as $video ) {
	$video_url    = trim( (string) ( $video['url'] ?? '' ) );
	$video_titulo = trim( (string) ( $video['titulo'] ?? '' ) );
	$embed_url    = '';
	$lightbox_url = '';
	$thumb_url    = '';
	$provider     = '';

	if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]+)/', $video_url, $youtube_match ) ) {
		$video_id     = $youtube_match[1];
		$embed_url    = 'https://www.youtube.com/embed/' . $video_id . '?rel=0';
		$lightbox_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&rel=0';
		$thumb_url    = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
		$provider     = 'YouTube';
	} elseif ( preg_match( '/vimeo\.com\/(\d+)/', $video_url, $vimeo_match ) ) {
		$video_id     = $vimeo_match[1];
		$embed_url    = 'https://player.vimeo.com/video/' . $video_id;
		$lightbox_url = 'https://player.vimeo.com/video/' . $video_id . '?autoplay=1';
		$provider     = 'Vimeo';
	}

	if ( ! $embed_url ) {
		continue;
	}

	$video_items[] = [
		'title'        => '' !== $video_titulo ? $video_titulo : sprintf( __( 'Vídeo %d', 'guiawp-reset' ), count( $video_items ) + 1 ),
		'provider'     => $provider,
		'embed_url'    => $embed_url,
		'lightbox_url' => $lightbox_url ?: $embed_url,
		'thumb_url'    => $thumb_url,
	];
}
$video_chunks = array_chunk( $video_items, 2 );
?>

<main class="max-w-[1400px] mx-auto w-full px-4 md:px-10 py-6 md:py-10">

	<!-- Foto de Capa -->
	<?php if ( $capa_id || has_post_thumbnail() ) : ?>
	<div class="rounded-2xl overflow-hidden mb-8 md:mb-12 shadow-sm h-[200px] md:h-[400px]">
		<?php
		$img_id = $capa_id ?: get_post_thumbnail_id();
		echo wp_get_attachment_image( $img_id, 'gcep-cover', false, [ 'class' => 'w-full h-full object-cover' ] );
		?>
	</div>
	<?php endif; ?>

	<!-- Perfil Header -->
	<div class="flex flex-col md:flex-row justify-between items-start gap-8 border-b border-slate-100 pb-10">
		<div class="flex flex-col md:flex-row gap-8 items-center md:items-start text-center md:text-left">
			<?php if ( $logo_id ) : ?>
			<div class="size-28 md:size-36 rounded-2xl bg-white border-2 border-slate-50 p-2.5 shadow-xl shadow-slate-200/50 flex-shrink-0">
				<?php echo wp_get_attachment_image( $logo_id, 'thumbnail', false, [ 'class' => 'w-full h-full object-contain rounded-xl' ] ); ?>
			</div>
			<?php endif; ?>
			<div class="flex flex-col pt-2">
				<div class="flex items-center justify-center md:justify-start gap-2.5 mb-2">
					<h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight"><?php the_title(); ?></h1>
				</div>
				<?php if ( ! empty( $cats ) ) : ?>
					<p class="text-lg md:text-xl mb-4"><?php
						$cat_links = [];
						foreach ( $cats as $ct ) {
							$cat_links[] = '<a href="' . esc_url( get_term_link( $ct ) ) . '" class="font-bold transition-colors" style="color:var(--gcep-color-primary, #0052cc);" onmouseover="this.style.opacity=\'0.7\'" onmouseout="this.style.opacity=\'1\'">' . esc_html( $ct->name ) . '</a>';
						}
						echo implode( ', ', $cat_links );
					?></p>
				<?php endif; ?>
				<div class="flex flex-wrap justify-center md:justify-start items-center gap-5 text-slate-500 text-[15px] font-medium">
					<?php if ( ! empty( $locs ) ) : ?>
					<span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">location_on</span> <?php echo esc_html( implode( ', ', $locs ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			</div>
			<div class="w-full md:w-auto flex flex-col items-stretch md:items-end gap-3">
				<?php if ( $has_address ) : ?>
				<button type="button" class="gcep-scroll-to-map inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl bg-white/90 backdrop-blur border border-slate-200 text-slate-700 font-bold text-xs shadow-sm hover:bg-[#0052cc] hover:text-white hover:border-[#0052cc] hover:-translate-y-0.5 transition-all" data-scroll-target="#gcep-location-section">
					<span class="material-symbols-outlined text-[16px]">navigation</span>
					<?php esc_html_e( 'Como chegar', 'guiawp-reset' ); ?>
				</button>
				<?php endif; ?>
				<div class="flex flex-col items-center md:items-end gap-1.5">
				<span class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400"><?php esc_html_e( 'Compartilhar', 'guiawp-reset' ); ?></span>
				<div class="flex items-center gap-1.5" style="flex-wrap:nowrap;">
					<a href="https://wa.me/?text=<?php echo rawurlencode( $share_title . ' ' . $share_url ); ?>" target="_blank" rel="noopener noreferrer" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:all 0.2s;" onmouseover="this.style.background='#25d366';this.style.color='#fff';this.style.borderColor='#25d366'" onmouseout="this.style.background='';this.style.color='#94a3b8';this.style.borderColor='#e2e8f0'" title="WhatsApp" aria-label="WhatsApp">
						<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.56 2 2.1 6.46 2.1 11.93c0 1.75.46 3.46 1.33 4.96L2 22l5.24-1.37a9.9 9.9 0 0 0 4.79 1.22h.01c5.47 0 9.93-4.46 9.93-9.93a9.85 9.85 0 0 0-2.92-7.01Zm-7.02 15.26h-.01a8.3 8.3 0 0 1-4.23-1.16l-.3-.18-3.11.81.83-3.03-.2-.31a8.25 8.25 0 0 1-1.27-4.37c0-4.56 3.71-8.27 8.28-8.27 2.21 0 4.28.86 5.84 2.42a8.2 8.2 0 0 1 2.42 5.84c0 4.56-3.71 8.27-8.25 8.27Zm4.54-6.19c-.25-.13-1.47-.73-1.7-.81-.23-.08-.39-.13-.56.13-.16.25-.64.81-.78.98-.14.16-.29.19-.54.06-.25-.13-1.04-.38-1.98-1.21-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.42.08-.16.04-.31-.02-.43-.06-.13-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42h-.47c-.16 0-.43.06-.65.31-.23.25-.86.84-.86 2.04s.88 2.36 1 2.52c.13.16 1.74 2.66 4.21 3.73.59.25 1.05.4 1.41.51.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29Z"/></svg>
					</a>
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( $share_url ); ?>" target="_blank" rel="noopener noreferrer" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:all 0.2s;" onmouseover="this.style.background='#1877f2';this.style.color='#fff';this.style.borderColor='#1877f2'" onmouseout="this.style.background='';this.style.color='#94a3b8';this.style.borderColor='#e2e8f0'" title="Facebook" aria-label="Facebook">
						<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M13.5 21v-7h2.35l.35-2.73H13.5V9.53c0-.79.22-1.32 1.35-1.32h1.44V5.78c-.25-.03-1.11-.08-2.11-.08-2.09 0-3.52 1.27-3.52 3.61v2.01H8.29V14h2.37v7h2.84Z"/></svg>
					</a>
					<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( $share_url ); ?>" target="_blank" rel="noopener noreferrer" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:all 0.2s;" onmouseover="this.style.background='#0a66c2';this.style.color='#fff';this.style.borderColor='#0a66c2'" onmouseout="this.style.background='';this.style.color='#94a3b8';this.style.borderColor='#e2e8f0'" title="LinkedIn" aria-label="LinkedIn">
						<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M6.94 8.5A1.56 1.56 0 1 1 6.94 5.4a1.56 1.56 0 0 1 0 3.1ZM8.3 18.6H5.58V9.86H8.3v8.74ZM18.42 18.6h-2.71v-4.25c0-1.01-.02-2.32-1.41-2.32-1.41 0-1.63 1.1-1.63 2.24v4.33H9.95V9.86h2.6v1.19h.04c.36-.69 1.25-1.41 2.57-1.41 2.75 0 3.26 1.81 3.26 4.16v4.8Z"/></svg>
					</a>
					<a href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode( $share_url ); ?>&text=<?php echo rawurlencode( $share_title ); ?>" target="_blank" rel="noopener noreferrer" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:all 0.2s;" onmouseover="this.style.background='#0f172a';this.style.color='#fff';this.style.borderColor='#0f172a'" onmouseout="this.style.background='';this.style.color='#94a3b8';this.style.borderColor='#e2e8f0'" title="X" aria-label="X">
						<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M18.9 2H22l-6.77 7.74L23.2 22h-6.25l-4.9-6.41L6.44 22H3.33l7.24-8.27L1 2h6.41l4.43 5.85L18.9 2Zm-1.09 18.13h1.72L6.48 3.78H4.63l13.18 16.35Z"/></svg>
					</a>
					<a href="mailto:?subject=<?php echo rawurlencode( $share_subject ); ?>&body=<?php echo rawurlencode( $share_body ); ?>" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:all 0.2s;" onmouseover="this.style.background='#475569';this.style.color='#fff';this.style.borderColor='#475569'" onmouseout="this.style.background='';this.style.color='#94a3b8';this.style.borderColor='#e2e8f0'" title="E-mail" aria-label="E-mail">
						<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M20 5H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 2v.01L12 12 4 7.01V7h16ZM4 17V9.24l7.4 4.63a1 1 0 0 0 1.2 0L20 9.24V17H4Z"/></svg>
					</a>
					<button type="button" class="gcep-copy-share-link" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:all 0.2s;background:transparent;cursor:pointer;" onmouseover="this.style.background='#0052cc';this.style.color='#fff';this.style.borderColor='#0052cc'" onmouseout="this.style.background='';this.style.color='#94a3b8';this.style.borderColor='#e2e8f0'" data-link="<?php echo esc_attr( $share_url ); ?>" data-default-label="<?php esc_attr_e( 'Copiar link', 'guiawp-reset' ); ?>" data-success-label="<?php esc_attr_e( 'Link copiado', 'guiawp-reset' ); ?>" title="<?php esc_attr_e( 'Copiar link', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Copiar link', 'guiawp-reset' ); ?>">
						<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M10.59 13.41a1 1 0 0 1 0-1.41l3.59-3.59a3 3 0 1 1 4.24 4.24l-2.12 2.12a3 3 0 0 1-4.24 0 1 1 0 0 1 1.41-1.41 1 1 0 0 0 1.41 0L17 11.24a1 1 0 1 0-1.41-1.41L12 13.41a1 1 0 0 1-1.41 0Zm2.82-2.82a1 1 0 0 1 0 1.41L9.83 15.6a3 3 0 0 1-4.24-4.24l2.12-2.12a3 3 0 0 1 4.24 0 1 1 0 1 1-1.41 1.41 1 1 0 0 0-1.41 0L7 12.77a1 1 0 0 0 1.41 1.41L12 10.59a1 1 0 0 1 1.41 0Z"/></svg>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 mt-8 md:mt-12">
		<!-- Conteúdo Principal -->
		<div class="lg:col-span-8 space-y-14">
			<?php if ( $show_free_adsense_slots && function_exists( 'guiawp_reset_render_adsense_slot' ) ) : ?>
			<section>
				<?php guiawp_reset_render_adsense_slot( 'single-anuncio-before-about' ); ?>
			</section>
			<?php endif; ?>

			<!-- Sobre: Premium exibe apenas descrição longa, Grátis exibe apenas descrição curta -->
			<?php if ( $is_premium && $meta['GCEP_descricao_longa'] ) : ?>
			<section>
				<h2 class="text-2xl font-extrabold mb-6 tracking-tight"><?php esc_html_e( 'Sobre', 'guiawp-reset' ); ?></h2>
				<div class="prose prose-lg max-w-none text-slate-600 leading-relaxed">
					<?php echo GCEP_AI_Validator::sanitize_description_html( (string) $meta['GCEP_descricao_longa'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
			<?php elseif ( ! $is_premium && $meta['GCEP_descricao_curta'] ) : ?>
			<section>
				<h2 class="text-2xl font-extrabold mb-6 tracking-tight"><?php esc_html_e( 'Sobre', 'guiawp-reset' ); ?></h2>
				<div class="prose prose-lg max-w-none text-slate-600 leading-relaxed font-medium">
					<p><?php echo esc_html( $meta['GCEP_descricao_curta'] ); ?></p>
				</div>
			</section>
			<?php endif; ?>

			<!-- Galeria de Fotos (premium) -->
			<?php if ( $is_premium && ! empty( $gallery_items ) ) : ?>
			<section>
				<div class="flex items-center justify-between gap-4 mb-6">
					<h2 class="text-2xl font-extrabold tracking-tight"><?php esc_html_e( 'Galeria de Fotos', 'guiawp-reset' ); ?></h2>
					<?php if ( count( $gallery_chunks ) > 1 ) : ?>
					<div class="hidden sm:flex items-center gap-2 text-sm text-slate-400">
						<span class="material-symbols-outlined text-base">photo_library</span>
						<?php printf( esc_html__( '%d imagens', 'guiawp-reset' ), count( $gallery_items ) ); ?>
					</div>
					<?php endif; ?>
				</div>
				<div class="gcep-media-carousel space-y-4" data-carousel>
					<div class="relative overflow-hidden">
						<?php foreach ( $gallery_chunks as $chunk_index => $chunk ) : ?>
						<div class="gcep-carousel-slide <?php echo 0 === $chunk_index ? '' : 'hidden'; ?>" data-carousel-slide>
							<div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
								<?php foreach ( $chunk as $item_index => $image_item ) :
									$global_index = ( $chunk_index * 4 ) + $item_index;
								?>
								<button type="button" class="rounded-2xl overflow-hidden aspect-square group relative bg-slate-100" data-lightbox-group="images" data-lightbox-index="<?php echo esc_attr( $global_index ); ?>">
									<img src="<?php echo esc_url( $image_item['thumb'] ); ?>" alt="<?php echo esc_attr( $image_item['alt'] ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
									<span class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/10 transition-colors"></span>
								</button>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php if ( count( $gallery_chunks ) > 1 ) : ?>
					<div class="flex items-center justify-between">
						<button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-carousel-prev>
							<span class="material-symbols-outlined text-base">arrow_back</span>
							<?php esc_html_e( 'Anterior', 'guiawp-reset' ); ?>
						</button>
						<div class="text-sm font-semibold text-slate-400" data-carousel-counter>1 / <?php echo esc_html( count( $gallery_chunks ) ); ?></div>
						<button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-carousel-next>
							<?php esc_html_e( 'Próxima', 'guiawp-reset' ); ?>
							<span class="material-symbols-outlined text-base">arrow_forward</span>
						</button>
					</div>
					<?php endif; ?>
				</div>
			</section>
			<?php endif; ?>

			<!-- Galeria de Vídeos (premium) -->
			<?php if ( $is_premium && ! empty( $video_items ) ) : ?>
			<section>
				<h2 class="text-2xl font-extrabold mb-6 tracking-tight"><?php esc_html_e( 'Vídeos', 'guiawp-reset' ); ?></h2>
				<?php if ( 1 === count( $video_items ) ) :
					$single_video = $video_items[0];
				?>
				<div>
					<?php if ( $single_video['title'] ) : ?>
						<h4 class="font-bold text-slate-800 mb-3"><?php echo esc_html( $single_video['title'] ); ?></h4>
					<?php endif; ?>
					<div class="aspect-video rounded-xl overflow-hidden bg-slate-100">
						<iframe src="<?php echo esc_url( $single_video['embed_url'] ); ?>" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
					</div>
				</div>
				<?php else : ?>
				<div class="gcep-media-carousel space-y-4" data-carousel>
					<div class="relative overflow-hidden">
						<?php foreach ( $video_chunks as $chunk_index => $chunk ) : ?>
						<div class="gcep-carousel-slide <?php echo 0 === $chunk_index ? '' : 'hidden'; ?>" data-carousel-slide>
							<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
								<?php foreach ( $chunk as $item_index => $video_item ) :
									$global_index = ( $chunk_index * 2 ) + $item_index;
								?>
								<button type="button" class="group text-left" data-lightbox-group="videos" data-lightbox-index="<?php echo esc_attr( $global_index ); ?>">
									<div class="aspect-video rounded-2xl overflow-hidden bg-slate-900 relative">
										<?php if ( ! empty( $video_item['thumb_url'] ) ) : ?>
										<img src="<?php echo esc_url( $video_item['thumb_url'] ); ?>" alt="<?php echo esc_attr( $video_item['title'] ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
										<?php else : ?>
										<div class="w-full h-full bg-gradient-to-br from-slate-800 via-slate-900 to-black flex items-center justify-center">
											<span class="text-sm font-bold uppercase tracking-[0.2em] text-white/70"><?php echo esc_html( $video_item['provider'] ); ?></span>
										</div>
										<?php endif; ?>
										<div class="absolute inset-0 bg-slate-950/20 group-hover:bg-slate-950/35 transition-colors"></div>
										<div class="absolute inset-0 flex items-center justify-center">
											<span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/90 text-slate-900 shadow-lg group-hover:scale-105 transition-transform">
												<span class="material-symbols-outlined text-3xl">play_arrow</span>
											</span>
										</div>
									</div>
									<div class="mt-3">
										<h4 class="font-bold text-slate-900 group-hover:text-primary transition-colors"><?php echo esc_html( $video_item['title'] ); ?></h4>
										<p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 mt-1"><?php echo esc_html( $video_item['provider'] ); ?></p>
									</div>
								</button>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<div class="flex items-center justify-between">
						<button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-carousel-prev>
							<span class="material-symbols-outlined text-base">arrow_back</span>
							<?php esc_html_e( 'Anterior', 'guiawp-reset' ); ?>
						</button>
						<div class="text-sm font-semibold text-slate-400" data-carousel-counter>1 / <?php echo esc_html( count( $video_chunks ) ); ?></div>
						<button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-carousel-next>
							<?php esc_html_e( 'Próxima', 'guiawp-reset' ); ?>
							<span class="material-symbols-outlined text-base">arrow_forward</span>
						</button>
					</div>
				</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

				<?php if ( $meta['GCEP_endereco_completo'] ) : ?>
				<section id="gcep-location-section" class="scroll-mt-28">
					<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
						<div>
							<h2 class="text-2xl font-extrabold tracking-tight"><?php esc_html_e( 'Localização', 'guiawp-reset' ); ?></h2>
						<p class="text-sm text-slate-500 mt-2 font-medium"><?php echo esc_html( $meta['GCEP_endereco_completo'] ); ?></p>
					</div>
					<div class="flex flex-col sm:flex-row gap-3">
						<a href="<?php echo esc_url( $google_maps_search_url ); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-white border-2 border-slate-200 px-4 py-3 text-sm font-bold rounded-xl hover:bg-slate-50 transition-all">
							<span class="material-symbols-outlined text-primary">map</span>
							<?php esc_html_e( 'Google Maps', 'guiawp-reset' ); ?>
						</a>
						<a href="<?php echo esc_url( $waze_url ); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-[#33CCFF] text-white px-4 py-3 text-sm font-bold rounded-xl hover:opacity-90 transition-all">
							<span class="material-symbols-outlined">navigation</span>
							<?php esc_html_e( 'Waze', 'guiawp-reset' ); ?>
						</a>
					</div>
				</div>
				<div id="gcep-map-<?php echo esc_attr( $post_id ); ?>" class="h-72 md:h-[420px] w-full rounded-3xl overflow-hidden border border-slate-100 bg-slate-100 premium-shadow" data-address="<?php echo esc_attr( $meta['GCEP_endereco_completo'] ); ?>" data-lat="<?php echo esc_attr( $meta['GCEP_latitude'] ?? '' ); ?>" data-lng="<?php echo esc_attr( $meta['GCEP_longitude'] ?? '' ); ?>"></div>
			</section>
			<?php if ( $show_free_adsense_slots && function_exists( 'guiawp_reset_render_adsense_slot' ) ) : ?>
			<section>
				<?php guiawp_reset_render_adsense_slot( 'single-anuncio-after-map' ); ?>
			</section>
			<?php endif; ?>
			<?php endif; ?>
		</div>

		<!-- Sidebar -->
		<div class="lg:col-span-4 lg:self-start">
			<div class="space-y-8 lg:sticky lg:top-24">
				<!-- Card de Contato -->
				<div class="bg-white border border-slate-100 rounded-3xl premium-shadow p-8">
					<h4 class="text-xl font-extrabold mb-8 tracking-tight"><?php esc_html_e( 'Informações de Contato', 'guiawp-reset' ); ?></h4>

					<div class="space-y-4 mb-10">
						<?php if ( $meta['GCEP_whatsapp'] ) : ?>
						<a href="<?php echo esc_url( GCEP_Helpers::get_whatsapp_url( (string) $meta['GCEP_whatsapp'] ) ); ?>" target="_blank" rel="noopener" class="w-full flex items-center justify-center gap-3 h-14 rounded-2xl bg-[#25D366] text-white font-bold hover:opacity-90 transition-all shadow-lg shadow-[#25D366]/20">
							<span class="material-symbols-outlined text-2xl">chat</span> WhatsApp
						</a>
						<?php endif; ?>

						<?php if ( $meta['GCEP_telefone'] ) : ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $meta['GCEP_telefone'] ) ); ?>" class="w-full flex items-center justify-center gap-3 h-14 rounded-2xl bg-[#0052cc] text-white font-bold hover:bg-[#003d99] hover:shadow-lg transition-all">
							<span class="material-symbols-outlined text-2xl">call</span> <?php echo esc_html( GCEP_Helpers::format_phone( (string) $meta['GCEP_telefone'] ) ); ?>
						</a>
						<?php endif; ?>
					</div>

					<div class="space-y-7">
						<?php if ( $meta['GCEP_email'] ) : ?>
						<div class="flex items-center gap-4 group">
							<div class="size-11 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
								<span class="material-symbols-outlined">mail</span>
							</div>
							<div>
								<p class="text-[11px] text-slate-400 uppercase font-extrabold tracking-widest mb-0.5"><?php esc_html_e( 'E-mail', 'guiawp-reset' ); ?></p>
								<p class="text-[15px] font-bold text-slate-800"><?php echo esc_html( $meta['GCEP_email'] ); ?></p>
							</div>
						</div>
						<?php endif; ?>

						<?php if ( $meta['GCEP_site'] ) : ?>
						<div class="flex items-center gap-4 group">
							<div class="size-11 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
								<span class="material-symbols-outlined">public</span>
							</div>
							<div>
								<p class="text-[11px] text-slate-400 uppercase font-extrabold tracking-widest mb-0.5"><?php esc_html_e( 'Website', 'guiawp-reset' ); ?></p>
								<a class="text-[15px] font-bold text-primary hover:underline" href="<?php echo esc_url( $meta['GCEP_site'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( str_replace( [ 'https://', 'http://' ], '', $meta['GCEP_site'] ) ); ?></a>
							</div>
						</div>
						<?php endif; ?>

						<!-- CNPJ (apenas empresas) -->
						<?php if ( 'empresa' === $meta['GCEP_tipo_anuncio'] && $meta['GCEP_cnpj'] ) : ?>
						<div class="flex items-center gap-4 group">
							<div class="size-11 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-[#0052cc] transition-colors">
								<span class="material-symbols-outlined">badge</span>
							</div>
							<div>
								<p class="text-[11px] text-slate-400 uppercase font-extrabold tracking-widest mb-0.5"><?php esc_html_e( 'CNPJ', 'guiawp-reset' ); ?></p>
								<p class="text-[15px] font-bold text-slate-800"><?php echo esc_html( $meta['GCEP_cnpj'] ); ?></p>
							</div>
						</div>
						<?php endif; ?>

						<!-- Redes Sociais -->
						<?php
						require_once GCEP_PLUGIN_DIR . 'templates/partials/social-icons.php';
						$svg_icons = gcep_social_svg_icons( 'w-5 h-5' );
						$socials = [];
						if ( $meta['GCEP_instagram'] )  $socials[] = [ 'url' => $meta['GCEP_instagram'],  'svg' => $svg_icons['instagram'],  'label' => 'Instagram' ];
						if ( $meta['GCEP_facebook'] )   $socials[] = [ 'url' => $meta['GCEP_facebook'],   'svg' => $svg_icons['facebook'],   'label' => 'Facebook' ];
						if ( $meta['GCEP_linkedin'] )   $socials[] = [ 'url' => $meta['GCEP_linkedin'],   'svg' => $svg_icons['linkedin'],   'label' => 'LinkedIn' ];
						if ( $meta['GCEP_youtube'] )    $socials[] = [ 'url' => $meta['GCEP_youtube'],    'svg' => $svg_icons['youtube'],    'label' => 'YouTube' ];
						if ( $meta['GCEP_x_twitter'] )  $socials[] = [ 'url' => $meta['GCEP_x_twitter'],  'svg' => $svg_icons['x_twitter'],  'label' => 'X' ];
						if ( $meta['GCEP_tiktok'] )     $socials[] = [ 'url' => $meta['GCEP_tiktok'],     'svg' => $svg_icons['tiktok'],     'label' => 'TikTok' ];
						if ( $meta['GCEP_threads'] )    $socials[] = [ 'url' => $meta['GCEP_threads'],    'svg' => $svg_icons['threads'],    'label' => 'Threads' ];

						if ( ! empty( $socials ) ) :
						?>
						<div class="flex items-center gap-4 group">
							<div class="size-11 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-[#0052cc] transition-colors">
								<span class="material-symbols-outlined">share</span>
							</div>
							<div class="flex flex-wrap gap-3">
								<?php foreach ( $socials as $social ) : ?>
								<a class="text-slate-400 hover:text-[#0052cc] transition-colors" href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $social['label'] ); ?>">
									<?php echo $social['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Anúncios Relacionados -->
	<?php
		$related = get_posts( [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'post__not_in'   => [ $post_id ],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => [ [ 'key' => 'GCEP_status_anuncio', 'value' => 'publicado' ] ],
			'tax_query'      => [ [ 'taxonomy' => 'gcep_categoria', 'field' => 'term_id', 'terms' => wp_list_pluck( $cats, 'term_id' ) ] ],
		] );

		if ( ! empty( $related ) ) :
	?>
	<section class="mt-12 md:mt-20">
		<h2 class="text-xl md:text-2xl font-extrabold mb-6 md:mb-8 tracking-tight"><?php esc_html_e( 'Anúncios Relacionados', 'guiawp-reset' ); ?></h2>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
			<?php foreach ( $related as $rel ) :
				$rel_desc = get_post_meta( $rel->ID, 'GCEP_descricao_curta', true );
				$rel_locs = wp_get_object_terms( $rel->ID, 'gcep_localizacao', [ 'fields' => 'names' ] );
				$rel_cats = wp_get_object_terms( $rel->ID, 'gcep_categoria', [ 'fields' => 'names' ] );
				$rel_cat_label = ! empty( $rel_cats ) ? $rel_cats[0] : __( 'Anúncio local', 'guiawp-reset' );
				$rel_loc_label = ! empty( $rel_locs ) ? implode( ', ', $rel_locs ) : __( 'Local não informado', 'guiawp-reset' );
			?>
			<?php
				// Imagem do card: featured image > foto_capa > logo/foto_principal
				$_rel_img_html = '';
				$_rel_candidates = [
					get_post_thumbnail_id( $rel->ID ),
					get_post_meta( $rel->ID, 'GCEP_foto_capa', true ),
					get_post_meta( $rel->ID, 'GCEP_logo_ou_foto_principal', true ),
				];
				foreach ( $_rel_candidates as $_rc_id ) {
					if ( ! $_rc_id ) continue;
					$_html = wp_get_attachment_image( (int) $_rc_id, 'medium', false, [
						'class'   => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
						'alt'     => esc_attr( $rel->post_title ),
						'loading' => 'lazy',
					] );
					if ( $_html ) {
						$_rel_img_html = $_html;
						break;
					}
				}
			?>
			<a href="<?php echo esc_url( get_permalink( $rel->ID ) ); ?>" class="group flex h-full flex-col overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-[0_12px_40px_-28px_rgba(15,23,42,0.45)] ring-1 ring-white/70 transition-all duration-300 hover:-translate-y-1.5 hover:border-slate-300 hover:shadow-[0_22px_50px_-26px_rgba(15,23,42,0.38)]">
				<div class="relative h-48 overflow-hidden bg-slate-100">
					<?php if ( $_rel_img_html ) : ?>
						<?php echo $_rel_img_html; ?>
					<?php else : ?>
						<div class="w-full h-full bg-slate-100 flex items-center justify-center"><span class="material-symbols-outlined text-4xl text-slate-300">image</span></div>
					<?php endif; ?>
					<div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/30 via-slate-950/10 to-transparent"></div>
				</div>
				<div class="flex flex-1 flex-col p-5 md:p-6">
					<span class="mb-3 inline-flex w-fit rounded-full bg-[#0052cc]/8 px-3 py-1 text-[11px] font-black uppercase tracking-[0.18em] text-[#0052cc]"><?php echo esc_html( $rel_cat_label ); ?></span>
					<h4 class="text-xl font-black leading-tight text-slate-900 transition-colors group-hover:text-primary"><?php echo esc_html( $rel->post_title ); ?></h4>
					<?php if ( $rel_desc ) : ?>
						<p class="mt-3 text-sm leading-6 text-slate-500 line-clamp-3"><?php echo esc_html( $rel_desc ); ?></p>
					<?php endif; ?>
					<div class="mt-auto pt-5">
						<div class="flex items-center justify-between gap-4 border-t border-slate-200/80 pt-4 text-slate-600">
							<div class="min-w-0 flex items-center gap-2.5 text-sm font-medium">
								<span class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-slate-100 text-slate-500">
									<span class="material-symbols-outlined text-[20px]">location_on</span>
								</span>
								<span class="truncate"><?php echo esc_html( $rel_loc_label ); ?></span>
							</div>
							<span class="inline-flex h-11 w-11 flex-none items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400 transition-all group-hover:border-[#0052cc]/20 group-hover:bg-[#0052cc] group-hover:text-white">
								<span class="material-symbols-outlined text-[24px]">arrow_forward</span>
							</span>
						</div>
					</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

</main>

<div id="gcep-media-lightbox" class="fixed inset-0 z-[999] hidden items-center justify-center bg-slate-950/90 p-4 sm:p-6">
	<div class="absolute inset-0" data-lightbox-close></div>
	<div class="relative w-full max-w-6xl">
		<div class="flex items-start justify-between gap-4 text-white mb-4">
			<div>
				<p id="gcep-lightbox-title" class="text-lg sm:text-xl font-black"></p>
				<p id="gcep-lightbox-counter" class="text-sm text-white/60 mt-1"></p>
			</div>
			<button type="button" class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 transition-colors" data-lightbox-close aria-label="<?php esc_attr_e( 'Fechar mídia', 'guiawp-reset' ); ?>">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>
		<div class="relative">
			<button type="button" id="gcep-lightbox-prev" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors disabled:opacity-30 disabled:cursor-not-allowed" aria-label="<?php esc_attr_e( 'Mídia anterior', 'guiawp-reset' ); ?>">
				<span class="material-symbols-outlined">arrow_back</span>
			</button>
			<div id="gcep-lightbox-body" class="w-full min-h-[260px] sm:min-h-[420px] rounded-3xl overflow-hidden bg-black flex items-center justify-center"></div>
			<button type="button" id="gcep-lightbox-next" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors disabled:opacity-30 disabled:cursor-not-allowed" aria-label="<?php esc_attr_e( 'Próxima mídia', 'guiawp-reset' ); ?>">
				<span class="material-symbols-outlined">arrow_forward</span>
			</button>
		</div>
	</div>
</div>

<script>
	(function(){
		var mediaItems = {
			images: <?php echo wp_json_encode( $gallery_items ); ?>,
			videos: <?php echo wp_json_encode( $video_items ); ?>
		};
		var copyButtons = document.querySelectorAll('.gcep-copy-share-link');
		var scrollButtons = document.querySelectorAll('.gcep-scroll-to-map');

	function fallbackCopy(text) {
		var tempInput = document.createElement('input');
		tempInput.type = 'text';
		tempInput.value = text;
		tempInput.setAttribute('readonly', 'readonly');
		tempInput.style.position = 'fixed';
		tempInput.style.opacity = '0';
		document.body.appendChild(tempInput);
		tempInput.select();
		tempInput.setSelectionRange(0, tempInput.value.length);
		var copied = false;

		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}

		document.body.removeChild(tempInput);
		return copied;
	}

		copyButtons.forEach(function(button){
			button.addEventListener('click', function(){
			var link = button.getAttribute('data-link') || '';
			var defaultLabel = button.getAttribute('data-default-label') || '';
			var successLabel = button.getAttribute('data-success-label') || defaultLabel;
			if (!link) return;

			function markSuccess() {
				button.setAttribute('title', successLabel);
				button.setAttribute('aria-label', successLabel);
				button.classList.add('bg-primary', 'text-white');
				window.setTimeout(function(){
					button.setAttribute('title', defaultLabel);
					button.setAttribute('aria-label', defaultLabel);
					button.classList.remove('bg-primary', 'text-white');
				}, 1800);
			}

			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(link).then(markSuccess).catch(function(){
					if (fallbackCopy(link)) {
						markSuccess();
					}
				});
				return;
			}

			if (fallbackCopy(link)) {
				markSuccess();
			}
			});
		});

		scrollButtons.forEach(function(button){
			button.addEventListener('click', function(){
				var selector = button.getAttribute('data-scroll-target');
				var target = selector ? document.querySelector(selector) : null;
				if (!target) return;

				var offset = 96;
				var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
				window.scrollTo({
					top: top > 0 ? top : 0,
					behavior: 'smooth'
				});
			});
		});

		document.querySelectorAll('[data-carousel]').forEach(function(carousel){
		var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-slide]'));
		var prevButton = carousel.querySelector('[data-carousel-prev]');
		var nextButton = carousel.querySelector('[data-carousel-next]');
		var counter = carousel.querySelector('[data-carousel-counter]');
		var currentIndex = 0;

		if (slides.length <= 1) {
			if (prevButton) prevButton.classList.add('hidden');
			if (nextButton) nextButton.classList.add('hidden');
			if (counter) counter.classList.add('hidden');
			return;
		}

		function renderCarousel() {
			slides.forEach(function(slide, slideIndex){
				slide.classList.toggle('hidden', slideIndex !== currentIndex);
			});

			if (counter) {
				counter.textContent = String(currentIndex + 1) + ' / ' + String(slides.length);
			}

			if (prevButton) {
				prevButton.disabled = currentIndex === 0;
			}

			if (nextButton) {
				nextButton.disabled = currentIndex === slides.length - 1;
			}
		}

		if (prevButton) {
			prevButton.addEventListener('click', function(){
				if (currentIndex <= 0) return;
				currentIndex -= 1;
				renderCarousel();
			});
		}

		if (nextButton) {
			nextButton.addEventListener('click', function(){
				if (currentIndex >= slides.length - 1) return;
				currentIndex += 1;
				renderCarousel();
			});
		}

		renderCarousel();
	});

	var lightbox = document.getElementById('gcep-media-lightbox');
	var lightboxBody = document.getElementById('gcep-lightbox-body');
	var lightboxTitle = document.getElementById('gcep-lightbox-title');
	var lightboxCounter = document.getElementById('gcep-lightbox-counter');
	var lightboxPrev = document.getElementById('gcep-lightbox-prev');
	var lightboxNext = document.getElementById('gcep-lightbox-next');
	var lightboxState = {
		group: '',
		index: 0,
		trigger: null
	};

	function getLightboxItems() {
		return Array.isArray(mediaItems[lightboxState.group]) ? mediaItems[lightboxState.group] : [];
	}

	function renderLightbox() {
		var items = getLightboxItems();
		var item = items[lightboxState.index];

		if (!lightbox || !lightboxBody || !item) {
			return;
		}

		if (lightboxTitle) {
			lightboxTitle.textContent = item.title || item.alt || '';
		}

		if (lightboxCounter) {
			lightboxCounter.textContent = String(lightboxState.index + 1) + ' / ' + String(items.length);
		}

		if (lightboxPrev) {
			lightboxPrev.disabled = lightboxState.index === 0;
			lightboxPrev.classList.toggle('hidden', items.length <= 1);
		}

		if (lightboxNext) {
			lightboxNext.disabled = lightboxState.index === items.length - 1;
			lightboxNext.classList.toggle('hidden', items.length <= 1);
		}

		if ('videos' === lightboxState.group) {
			lightboxBody.innerHTML = '<div class="w-full aspect-video"><iframe src="' + String(item.lightbox_url || item.embed_url || '') + '" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
			return;
		}

		lightboxBody.innerHTML = '<img src="' + String(item.full || '') + '" alt="' + String(item.alt || '') + '" class="max-h-[82vh] w-auto max-w-full object-contain">';
	}

	function openLightbox(group, index) {
		if (!lightbox) {
			return;
		}

		var items = Array.isArray(mediaItems[group]) ? mediaItems[group] : [];
		if (!items.length || typeof items[index] === 'undefined') {
			return;
		}

		lightboxState.group = group;
		lightboxState.index = index;
		renderLightbox();
		lightbox.classList.remove('hidden');
		lightbox.classList.add('flex');
		document.body.classList.add('overflow-hidden');
		if (window.gcepFocusTrap) { window.gcepFocusTrap.activate(lightbox, closeLightbox); }
	}

	function closeLightbox() {
		if (!lightbox) {
			return;
		}

		lightbox.classList.add('hidden');
		lightbox.classList.remove('flex');
		document.body.classList.remove('overflow-hidden');

		if (lightboxBody) {
			lightboxBody.innerHTML = '';
		}
		if (window.gcepFocusTrap) { window.gcepFocusTrap.deactivate(); }
		if (lightboxState.trigger) { lightboxState.trigger.focus(); }
	}

	function moveLightbox(step) {
		var items = getLightboxItems();
		var nextIndex = lightboxState.index + step;

		if (nextIndex < 0 || nextIndex >= items.length) {
			return;
		}

		lightboxState.index = nextIndex;
		renderLightbox();
	}

	document.querySelectorAll('[data-lightbox-group][data-lightbox-index]').forEach(function(trigger){
		trigger.addEventListener('click', function(){
			var group = trigger.getAttribute('data-lightbox-group') || '';
			var index = Number(trigger.getAttribute('data-lightbox-index') || 0);
			lightboxState.trigger = trigger;
			openLightbox(group, index);
		});
	});

	if (lightboxPrev) {
		lightboxPrev.addEventListener('click', function(){
			moveLightbox(-1);
		});
	}

	if (lightboxNext) {
		lightboxNext.addEventListener('click', function(){
			moveLightbox(1);
		});
	}

	if (lightbox) {
		lightbox.querySelectorAll('[data-lightbox-close]').forEach(function(closeTrigger){
			closeTrigger.addEventListener('click', closeLightbox);
		});
	}

	document.addEventListener('keydown', function(event){
		if (!lightbox || lightbox.classList.contains('hidden')) {
			return;
		}

		if ('Escape' === event.key) {
			closeLightbox();
		} else if ('ArrowLeft' === event.key) {
			moveLightbox(-1);
		} else if ('ArrowRight' === event.key) {
			moveLightbox(1);
		}
	});
})();
</script>

<?php get_footer(); ?>
