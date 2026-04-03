<?php
/**
 * Tema GuiaWP Reset - Functions
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.5 - 2026-03-21 - Versao null→1.0.0 no enqueue Google Fonts e Material Icons para cache busting
 * @modified 1.10.0 - 2026-03-28 - Schema.org aprimorado: sameAs social, geo, BreadcrumbList, WebSite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Converte cor hexadecimal para rgba (opacidade de 0 a 1)
function gcep_hex_to_rgba( $hex, $alpha = 1 ) {
	$hex = ltrim( $hex, '#' );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . floatval( $alpha ) . ')';
}

function guiawp_reset_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', [
		'height'      => 80,
		'width'       => 200,
		'flex-width'  => true,
		'flex-height' => true,
	] );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'automatic-feed-links' );

	register_nav_menus( [
		'primary'  => __( 'Menu Principal', 'guiawp-reset' ),
		'footer'   => __( 'Menu Rodapé', 'guiawp-reset' ),
	] );

	set_post_thumbnail_size( 800, 600, true );
	add_image_size( 'gcep-card', 400, 500, true );
	add_image_size( 'gcep-cover', 1200, 600, true );
}
add_action( 'after_setup_theme', 'guiawp_reset_setup' );

function guiawp_reset_disable_post_comments(): void {
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'post', 'trackbacks' );
}
add_action( 'init', 'guiawp_reset_disable_post_comments' );

function guiawp_reset_close_blog_comments( bool $open, int $post_id ): bool {
	if ( 'post' === get_post_type( $post_id ) ) {
		return false;
	}

	return $open;
}
add_filter( 'comments_open', 'guiawp_reset_close_blog_comments', 10, 2 );
add_filter( 'pings_open', 'guiawp_reset_close_blog_comments', 10, 2 );

function guiawp_reset_hide_post_comments( array $comments, int $post_id ): array {
	if ( 'post' === get_post_type( $post_id ) ) {
		return [];
	}

	return $comments;
}
add_filter( 'comments_array', 'guiawp_reset_hide_post_comments', 10, 2 );

function guiawp_reset_scripts() {
	// Google Fonts
	wp_enqueue_style( 'google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap', [], '1.0.0' );
	wp_enqueue_style( 'material-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', [], '1.0.0' );

	// Tema CSS mínimo
	wp_enqueue_style( 'guiawp-reset-style', get_stylesheet_uri(), [], '1.0.0' );

	// Mapa Leaflet no single anuncio
	if ( is_singular( 'gcep_anuncio' ) ) {
		wp_enqueue_script( 'guiawp-map', get_template_directory_uri() . '/assets/js/map.js', [], '1.5.4', true );
	}
}
add_action( 'wp_enqueue_scripts', 'guiawp_reset_scripts' );

/**
 * Injeta todas as cores do tema como CSS custom properties no :root
 * @author Dante Testa <https://dantetesta.com.br>
 * @modified 1.9.8 - 2026-03-22 - Expandido para injetar todas as 5 cores do tema
 */
function guiawp_reset_inject_theme_colors(): void {
	$map = [
		'cor_primaria'   => 'gcep-color-primary',
		'cor_secundaria' => 'gcep-color-secundaria',
		'cor_destaque'   => 'gcep-color-destaque',
		'cor_fundo'      => 'gcep-color-fundo',
		'cor_texto'      => 'gcep-color-texto',
		'cor_rodape'     => 'gcep-color-rodape',
		'cor_fundo_categorias' => 'gcep-color-fundo-categorias',
	];
	$defaults = [
		'cor_primaria'   => '#0052cc',
		'cor_secundaria' => '#f5f7f8',
		'cor_destaque'   => '#22c55e',
		'cor_fundo'      => '#f5f7f8',
		'cor_texto'      => '#0f172a',
		'cor_rodape'     => '#1e293b',
		'cor_fundo_categorias' => '#f5f7f8',
	];
	$css_vars = '';
	foreach ( $map as $key => $css_name ) {
		$val = class_exists( 'GCEP_Settings' ) ? GCEP_Settings::get( $key, $defaults[ $key ] ) : $defaults[ $key ];
		$css_vars .= '--' . $css_name . ':' . esc_attr( $val ) . ';';
	}
	echo '<style>:root{' . $css_vars . '}</style>' . "\n";
}
add_action( 'wp_head', 'guiawp_reset_inject_theme_colors', 5 );

function guiawp_reset_get_setting( string $key, $default = '' ) {
	if ( class_exists( 'GCEP_Settings' ) ) {
		return GCEP_Settings::get( $key, $default );
	}
	return $default;
}

function guiawp_reset_is_adsense_enabled(): bool {
	return '1' === (string) guiawp_reset_get_setting( 'adsense_enabled', '0' );
}

function guiawp_reset_get_adsense_block_html(): string {
	if ( ! guiawp_reset_is_adsense_enabled() ) {
		return '';
	}

	return trim( (string) guiawp_reset_get_setting( 'adsense_in_article', '' ) );
}

function guiawp_reset_render_adsense_slot( string $context = '' ): void {
	$ad_block = guiawp_reset_get_adsense_block_html();
	if ( '' === $ad_block ) {
		return;
	}

	$context_class = '' !== $context ? ' gcep-ad-slot--' . sanitize_html_class( $context ) : '';

	echo '<div class="gcep-ad-slot' . esc_attr( $context_class ) . ' my-8 flex justify-center" aria-label="' . esc_attr__( 'Publicidade', 'guiawp-reset' ) . '">';
	echo $ad_block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Código salvo pelo admin.
	echo '</div>';
}

function guiawp_reset_get_current_url(): string {
	$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' );
	return home_url( $request_uri );
}

function guiawp_reset_get_meta_description(): string {
	if ( is_singular( 'gcep_anuncio' ) ) {
		$post_id     = get_queried_object_id();
		$short       = (string) get_post_meta( $post_id, 'GCEP_descricao_curta', true );
		$long        = (string) get_post_meta( $post_id, 'GCEP_descricao_longa', true );
		$description = '' !== $short ? $short : wp_trim_words( wp_strip_all_tags( $long ), 32, '' );
		return trim( $description );
	}

	if ( is_singular( 'post' ) ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 32, '' );
			return trim( wp_strip_all_tags( $excerpt ) );
		}
	}

	if ( is_post_type_archive( 'gcep_anuncio' ) ) {
		return __( 'Encontre empresas, profissionais e servicos locais com filtros por categoria, localizacao e destaque.', 'guiawp-reset' );
	}

	if ( is_tax( 'gcep_categoria' ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && ! empty( $term->description ) ) {
			return wp_trim_words( $term->description, 25 );
		}
		$nome_guia = guiawp_reset_get_setting( 'nome_guia', 'GuiaWP' );
		return sprintf( __( 'Encontre profissionais e empresas na categoria %s no %s.', 'guiawp-reset' ), $term->name, $nome_guia );
	}

	$request_path = trim( (string) parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ), '/' );
	if ( 'blog' === $request_path || 0 === strpos( $request_path, 'blog/' ) ) {
		return __( 'Conteudo editorial com dicas, tendencias e estrategias para negocios locais e profissionais.', 'guiawp-reset' );
	}

	if ( is_front_page() ) {
		return guiawp_reset_get_setting( 'hero_subtitulo', get_bloginfo( 'description' ) );
	}

	if ( is_search() ) {
		return sprintf( __( 'Resultados da busca por %s no GuiaWP.', 'guiawp-reset' ), get_search_query() );
	}

	return get_bloginfo( 'description' );
}

function guiawp_reset_get_robots_content(): string {
	$request_path = trim( (string) parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ), '/' );

	if ( preg_match( '#^(login|cadastro|painel|painel-admin)(/|$)#', $request_path ) ) {
		return 'noindex,nofollow';
	}

	if ( is_search() || is_404() ) {
		return 'noindex,follow';
	}

	return '';
}

function guiawp_reset_get_social_image(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
		if ( $image ) {
			return $image;
		}
	}

	$hero_image = guiawp_reset_get_setting( 'hero_imagem', '' );
	if ( '' !== $hero_image ) {
		return $hero_image;
	}

	return guiawp_reset_get_setting( 'logo_url', '' );
}

function guiawp_reset_output_seo_meta(): void {
	if ( is_admin() ) {
		return;
	}

	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || class_exists( 'The_SEO_Framework\\Load' ) ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = trim( guiawp_reset_get_meta_description() );
	$url         = guiawp_reset_get_current_url();
	$image       = guiawp_reset_get_social_image();
	$robots      = guiawp_reset_get_robots_content();
	$type        = is_singular( 'post' ) ? 'article' : ( is_singular( 'gcep_anuncio' ) ? 'business.business' : 'website' );

	if ( '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	if ( '' !== $robots ) {
		echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
	}

	if ( ! is_singular() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}

	echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', get_locale() ) ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

	if ( '' !== $description ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	if ( '' !== $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
	} else {
		echo '<meta name="twitter:card" content="summary">' . "\n";
	}

	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";

	if ( '' !== $description ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'guiawp_reset_output_seo_meta', 2 );

function guiawp_reset_output_schema(): void {
	if ( is_admin() ) {
		return;
	}

	if ( is_singular( 'gcep_anuncio' ) ) {
		$post_id = get_queried_object_id();
		$status  = (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true );

		if ( 'publicado' !== $status ) {
			return;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'LocalBusiness',
			'name'        => get_the_title( $post_id ),
			'description' => guiawp_reset_get_meta_description(),
			'url'         => get_permalink( $post_id ),
		];

		$image = guiawp_reset_get_social_image();
		if ( '' !== $image ) {
			$schema['image'] = $image;
		}

		$telephone = (string) get_post_meta( $post_id, 'GCEP_telefone', true );
		if ( '' !== $telephone ) {
			$schema['telephone'] = '+55' . GCEP_Helpers::sanitize_phone( $telephone );
		}

		$email = (string) get_post_meta( $post_id, 'GCEP_email', true );
		if ( '' !== $email ) {
			$schema['email'] = $email;
		}

		$same_as = array_filter( [
			get_post_meta( $post_id, 'GCEP_site', true ),
			get_post_meta( $post_id, 'GCEP_instagram', true ) ? 'https://instagram.com/' . ltrim( get_post_meta( $post_id, 'GCEP_instagram', true ), '@' ) : '',
			get_post_meta( $post_id, 'GCEP_facebook', true ),
			get_post_meta( $post_id, 'GCEP_linkedin', true ),
			get_post_meta( $post_id, 'GCEP_youtube', true ),
			get_post_meta( $post_id, 'GCEP_x_twitter', true ) ? 'https://x.com/' . ltrim( get_post_meta( $post_id, 'GCEP_x_twitter', true ), '@' ) : '',
			get_post_meta( $post_id, 'GCEP_tiktok', true ) ? 'https://tiktok.com/@' . ltrim( get_post_meta( $post_id, 'GCEP_tiktok', true ), '@' ) : '',
		] );
		if ( ! empty( $same_as ) ) {
			$schema['sameAs'] = array_values( $same_as );
		}

		$address = [
			'@type'           => 'PostalAddress',
			'streetAddress'   => trim( implode( ', ', array_filter( [
				(string) get_post_meta( $post_id, 'GCEP_logradouro', true ),
				(string) get_post_meta( $post_id, 'GCEP_numero', true ),
				(string) get_post_meta( $post_id, 'GCEP_complemento', true ),
				(string) get_post_meta( $post_id, 'GCEP_bairro', true ),
			] ) ) ),
			'addressLocality' => (string) get_post_meta( $post_id, 'GCEP_cidade', true ),
			'addressRegion'   => (string) get_post_meta( $post_id, 'GCEP_estado', true ),
			'postalCode'      => (string) get_post_meta( $post_id, 'GCEP_cep', true ),
			'addressCountry'  => 'BR',
		];

		$address = array_filter( $address );
		if ( count( $address ) > 1 ) {
			$schema['address'] = $address;
		}

		// Coordenadas geográficas
		$lat = get_post_meta( $post_id, 'GCEP_latitude', true );
		$lng = get_post_meta( $post_id, 'GCEP_longitude', true );
		if ( $lat && $lng ) {
			$schema['geo'] = [
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $lat,
				'longitude' => (float) $lng,
			];
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

		// BreadcrumbList para single anuncio
		$breadcrumb = [
			'@context' => 'https://schema.org',
			'@type'    => 'BreadcrumbList',
			'itemListElement' => [
				[
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => __( 'Início', 'guiawp-reset' ),
					'item'     => home_url(),
				],
				[
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => __( 'Anúncios', 'guiawp-reset' ),
					'item'     => get_post_type_archive_link( 'gcep_anuncio' ),
				],
				[
					'@type'    => 'ListItem',
					'position' => 3,
					'name'     => get_the_title(),
				],
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		return;
	}

	if ( is_singular( 'post' ) ) {
		$post_id = get_queried_object_id();
		$schema  = [
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'headline'         => get_the_title( $post_id ),
			'description'      => guiawp_reset_get_meta_description(),
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'mainEntityOfPage' => get_permalink( $post_id ),
			'author'           => [
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
			],
			'publisher'        => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			],
		];

		$image = guiawp_reset_get_social_image();
		if ( '' !== $image ) {
			$schema['image'] = $image;
		}

		$categories = get_the_category( $post_id );
		if ( ! empty( $categories ) ) {
			$schema['articleSection'] = $categories[0]->name;
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	// BreadcrumbList para arquivo de anuncios
	if ( is_post_type_archive( 'gcep_anuncio' ) ) {
		$breadcrumb = [
			'@context' => 'https://schema.org',
			'@type'    => 'BreadcrumbList',
			'itemListElement' => [
				[
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => __( 'Início', 'guiawp-reset' ),
					'item'     => home_url(),
				],
				[
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => __( 'Anúncios', 'guiawp-reset' ),
				],
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	// BreadcrumbList para taxonomia de categoria
	if ( is_tax( 'gcep_categoria' ) ) {
		$term = get_queried_object();
		$breadcrumb = [
			'@context' => 'https://schema.org',
			'@type'    => 'BreadcrumbList',
			'itemListElement' => [
				[
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => __( 'Início', 'guiawp-reset' ),
					'item'     => home_url(),
				],
				[
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => __( 'Anúncios', 'guiawp-reset' ),
					'item'     => get_post_type_archive_link( 'gcep_anuncio' ),
				],
				[
					'@type'    => 'ListItem',
					'position' => 3,
					'name'     => $term->name,
				],
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	// WebSite schema na pagina inicial com SearchAction
	if ( is_front_page() ) {
		$website = [
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'name'     => guiawp_reset_get_setting( 'nome_guia', get_bloginfo( 'name' ) ),
			'url'      => home_url(),
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => [
					'@type'        => 'EntryPoint',
					'urlTemplate'  => get_post_type_archive_link( 'gcep_anuncio' ) . '?s={search_term_string}',
				],
				'query-input' => 'required name=search_term_string',
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'guiawp_reset_output_schema', 20 );

// Remover admin bar no front-end para todos os usuários
add_filter( 'show_admin_bar', '__return_false' );

/**
 * Injetar script principal do AdSense no <head>
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.5.3 - 2026-03-11
 */
function guiawp_reset_adsense_head_script(): void {
	if ( is_admin() ) {
		return;
	}
	if ( ! guiawp_reset_is_adsense_enabled() ) {
		return;
	}
	$script = guiawp_reset_get_setting( 'adsense_script', '' );
	if ( '' !== trim( $script ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Script salvo pelo admin
		echo $script . "\n";
	}
}
add_action( 'wp_head', 'guiawp_reset_adsense_head_script', 25 );

/**
 * Inserir anuncios AdSense inline no conteudo de posts do blog
 *
 * Logica:
 * - Se conteudo < intervalo: anuncio apenas no final
 * - Se conteudo >= intervalo: insere a cada X palavras
 * - Ultimo trecho so recebe anuncio se >= intervalo (evita anuncio colado no fim)
 * - Anuncio final sempre presente
 *
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.5.3 - 2026-03-11
 */
function guiawp_reset_adsense_in_content( string $content ): string {
	if ( ! is_singular( 'post' ) || ! is_main_query() ) {
		return $content;
	}

	$ad_block = guiawp_reset_get_adsense_block_html();
	if ( '' === $ad_block ) {
		return $content;
	}

	$intervalo = absint( guiawp_reset_get_setting( 'adsense_intervalo_palavras', 600 ) );
	if ( $intervalo < 200 ) {
		$intervalo = 600;
	}

	// Wrapper visual para o anuncio
	$ad_html = '<div class="gcep-ad-slot my-8 flex justify-center" aria-hidden="true">' . $ad_block . '</div>';

	// Separar conteudo em paragrafos (preservando tags HTML)
	$paragraphs = preg_split( '/(<\/(?:p|div|blockquote|ul|ol|figure|table|pre|h[2-6])>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( false === $paragraphs || count( $paragraphs ) < 2 ) {
		return $content . $ad_html;
	}

	// Reconstruir blocos (conteudo + tag de fechamento)
	$blocks     = [];
	$temp       = '';
	foreach ( $paragraphs as $part ) {
		$temp .= $part;
		if ( preg_match( '/<\/(?:p|div|blockquote|ul|ol|figure|table|pre|h[2-6])>/i', $part ) ) {
			$blocks[] = $temp;
			$temp     = '';
		}
	}
	if ( '' !== trim( $temp ) ) {
		$blocks[] = $temp;
	}

	$total_words = str_word_count( wp_strip_all_tags( $content ) );

	// Conteudo menor que o intervalo: anuncio so no final
	if ( $total_words < $intervalo ) {
		return $content . $ad_html;
	}

	// Inserir anuncios a cada X palavras
	$output          = '';
	$word_count      = 0;
	$ads_inserted    = 0;

	foreach ( $blocks as $i => $block ) {
		$block_words = str_word_count( wp_strip_all_tags( $block ) );
		$word_count += $block_words;
		$output     .= $block;

		// Verificar se atingiu o intervalo
		if ( $word_count >= $intervalo ) {
			$remaining_words = $total_words - ( $word_count );
			// So inserir se restam palavras suficientes apos o anuncio
			// (evita anuncio colado no final quando falta pouco conteudo)
			if ( $remaining_words >= ( $intervalo * 0.3 ) && $i < count( $blocks ) - 1 ) {
				$output     .= $ad_html;
				$word_count  = 0;
				$ads_inserted++;
			}
		}
	}

	// Anuncio final sempre presente
	$output .= $ad_html;

	return $output;
}
add_filter( 'the_content', 'guiawp_reset_adsense_in_content', 99 );
