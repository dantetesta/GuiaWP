<?php
/**
 * Funções auxiliares globais
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.4 - 2026-03-21 - Fix filtro destaques: valor correto 'gratis' ao inves de 'free', adiciona filtro status publicado
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Helpers {

	public static function get_anuncio_post_status( string $status ): string {
		return 'publicado' === self::normalize_anuncio_status( $status ) ? 'publish' : 'draft';
	}

	public static function get_manageable_anuncio_post_statuses(): array {
		return [ 'publish', 'draft' ];
	}

	public static function get_template( string $template, array $args = [] ): void {
		// Variaveis ficam disponiveis no escopo do template via $args['chave']
		// Compatibilidade: tambem disponibiliza como variaveis locais sem extract()
		foreach ( $args as $__key => $__val ) {
			$$__key = $__val;
		}
		unset( $__key, $__val );

		$file = GCEP_PLUGIN_DIR . 'templates/' . $template . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	public static function get_setting( string $key, $default = '' ) {
		$settings = get_option( 'gcep_settings', [] );
		return $settings[ $key ] ?? $default;
	}

	public static function get_page_url_by_path( string $path, string $fallback = '#' ): string {
		$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );

		if ( $page instanceof WP_Post ) {
			$permalink = get_permalink( $page );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		return $fallback;
	}

	public static function is_anunciante(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		return in_array( 'gcep_anunciante', (array) $user->roles, true );
	}

	public static function is_gcep_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function get_status_label( string $status ): string {
		$labels = [
			'rascunho'             => __( 'Rascunho', 'guiawp' ),
			'aguardando_pagamento' => __( 'Aguardando Pagamento', 'guiawp' ),
			'aguardando_aprovacao' => __( 'Aguardando Aprovação', 'guiawp' ),
			'publicado'            => __( 'Publicado', 'guiawp' ),
			'rejeitado'            => __( 'Rejeitado', 'guiawp' ),
			'expirado'             => __( 'Expirado', 'guiawp' ),
		];
		return $labels[ $status ] ?? $status;
	}

	public static function get_status_color( string $status ): string {
		$colors = [
			'rascunho'             => 'slate',
			'aguardando_pagamento' => 'amber',
			'aguardando_aprovacao' => 'blue',
			'publicado'            => 'emerald',
			'rejeitado'            => 'rose',
			'expirado'             => 'slate',
		];
		return $colors[ $status ] ?? 'slate';
	}

	/**
	 * Retorna classes CSS completas para o badge de status (evita classes dinamicas do Tailwind)
	 *
	 * @author Dante Testa <https://dantetesta.com.br>
	 * @since 1.8.8 - 2026-03-21
	 */
	public static function get_status_badge_classes( string $status ): string {
		$map = [
			'rascunho'             => 'bg-slate-100 text-slate-600 border-slate-200',
			'aguardando_pagamento' => 'bg-amber-50 text-amber-700 border-amber-200',
			'aguardando_aprovacao' => 'bg-sky-50 text-sky-700 border-sky-200',
			'publicado'            => 'bg-emerald-50 text-emerald-700 border-emerald-200',
			'rejeitado'            => 'bg-rose-50 text-rose-700 border-rose-200',
			'expirado'             => 'bg-slate-100 text-slate-500 border-slate-200',
		];
		return $map[ $status ] ?? 'bg-slate-100 text-slate-600 border-slate-200';
	}

	/**
	 * Retorna label curto do status (para tabelas compactas)
	 *
	 * @author Dante Testa <https://dantetesta.com.br>
	 * @since 1.8.8 - 2026-03-21
	 */
	public static function get_status_label_short( string $status ): string {
		$labels = [
			'rascunho'             => __( 'Rascunho', 'guiawp' ),
			'aguardando_pagamento' => __( 'Ag. Pagamento', 'guiawp' ),
			'aguardando_aprovacao' => __( 'Ag. Aprovação', 'guiawp' ),
			'publicado'            => __( 'Publicado', 'guiawp' ),
			'rejeitado'            => __( 'Rejeitado', 'guiawp' ),
			'expirado'             => __( 'Expirado', 'guiawp' ),
		];
		return $labels[ $status ] ?? $status;
	}

	public static function sync_anuncio_post_status( int $post_id, ?string $status = null ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'gcep_anuncio' !== $post->post_type ) {
			return;
		}

		if ( null === $status || '' === $status ) {
			$status = (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true );
		}

		$target_status = self::get_anuncio_post_status( $status );
		if ( $post->post_status === $target_status ) {
			return;
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			[ 'post_status' => $target_status ],
			[ 'ID' => $post_id ],
			[ '%s' ],
			[ '%d' ]
		);

		clean_post_cache( $post_id );
	}

	public static function sync_existing_anuncio_post_statuses(): void {
		global $wpdb;

		$wpdb->query(
			"UPDATE {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id
				AND pm.meta_key = 'GCEP_status_anuncio'
			SET p.post_status = CASE
				WHEN pm.meta_value = 'publicado' THEN 'publish'
				ELSE 'draft'
			END
			WHERE p.post_type = 'gcep_anuncio'
			AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')"
		);
	}

	public static function sanitize_phone( string $phone ): string {
		return preg_replace( '/[^0-9]/', '', $phone );
	}

	public static function format_phone( string $phone ): string {
		$clean = self::normalize_br_phone( $phone );
		$len   = strlen( $clean );
		if ( 11 === $len ) {
			return sprintf( '(%s) %s %s-%s', substr( $clean, 0, 2 ), substr( $clean, 2, 1 ), substr( $clean, 3, 4 ), substr( $clean, 7, 4 ) );
		}
		if ( 10 === $len ) {
			return sprintf( '(%s) %s-%s', substr( $clean, 0, 2 ), substr( $clean, 2, 4 ), substr( $clean, 6, 4 ) );
		}
		return $phone;
	}

	public static function get_whatsapp_url( string $phone, string $message = '' ): string {
		$clean = self::normalize_br_phone( $phone );

		if ( '' === $clean ) {
			return '';
		}

		$url = 'https://wa.me/55' . $clean;

		if ( '' !== $message ) {
			$url .= '?text=' . rawurlencode( $message );
		}

		return $url;
	}

	public static function get_user_initials( $user ): string {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'id', (int) $user );
		}

		if ( ! $user instanceof WP_User ) {
			return 'US';
		}

		$name = trim( (string) $user->display_name );
		if ( '' === $name ) {
			$name = trim( (string) $user->user_login );
		}

		if ( '' === $name ) {
			return 'US';
		}

		$parts = preg_split( '/\s+/', $name );
		$parts = array_values( array_filter( array_map( 'trim', (array) $parts ) ) );

		if ( empty( $parts ) ) {
			return 'US';
		}

		if ( 1 === count( $parts ) ) {
			$initials = mb_substr( $parts[0], 0, 2 );
		} else {
			$initials = mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 );
		}

		return mb_strtoupper( $initials );
	}

	public static function get_user_gravatar_url( $user, int $size = 96 ): string {
		// Avatar customizado tem prioridade sobre Gravatar
		$user_id   = $user instanceof \WP_User ? $user->ID : (int) $user;
		$avatar_id = (int) get_user_meta( $user_id, 'gcep_avatar_id', true );

		if ( $avatar_id > 0 ) {
			$custom_url = wp_get_attachment_image_url( $avatar_id, 'medium' );
			if ( $custom_url ) {
				return $custom_url;
			}
		}

		$avatar_url = get_avatar_url(
			$user,
			[
				'size'    => $size,
				'default' => '404',
			]
		);

		return is_string( $avatar_url ) ? $avatar_url : '';
	}

	public static function get_featured_anuncios( int $limit = 8 ): array {
		$limit     = max( 1, $limit );
		$cache_key = 'gcep_featured_anuncios_' . $limit;
		$post_ids  = get_transient( $cache_key );

		if ( false === $post_ids ) {
			// Busca apenas anuncios premium publicados em ordem aleatoria
			$pagantes = new WP_Query( [
				'post_type'              => 'gcep_anuncio',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'rand',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => [
					'relation' => 'AND',
					[
						'key'   => 'GCEP_status_anuncio',
						'value' => 'publicado',
					],
					[
						'key'     => 'GCEP_tipo_plano',
						'value'   => 'premium',
						'compare' => '=',
					],
				],
			] );

			$post_ids = $pagantes->posts;

			// Se premium >= limite, sorteia apenas entre eles
			if ( count( $post_ids ) >= $limit ) {
				shuffle( $post_ids );
				$post_ids = array_slice( $post_ids, 0, $limit );
			} else {
				// Complementa com gratuitos aleatorios ate atingir o limite
				$falta    = $limit - count( $post_ids );
				$gratuitos = new WP_Query( [
					'post_type'              => 'gcep_anuncio',
					'post_status'            => 'publish',
					'posts_per_page'         => $falta,
					'orderby'                => 'rand',
					'post__not_in'           => ! empty( $post_ids ) ? $post_ids : [ 0 ],
					'fields'                 => 'ids',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_query'             => [
						[
							'key'   => 'GCEP_status_anuncio',
							'value' => 'publicado',
						],
					],
				] );

				$post_ids = array_merge( $post_ids, $gratuitos->posts );
				shuffle( $post_ids );
			}

			$post_ids = array_values( array_unique( array_map( 'intval', $post_ids ) ) );

			set_transient( $cache_key, $post_ids, 15 * MINUTE_IN_SECONDS );
		}

		$posts = [];
		foreach ( (array) $post_ids as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
				$posts[] = $post;
			}
		}

		return $posts;
	}

	public static function redirect_with_message( string $url, string $type, string $message ): void {
		$url = add_query_arg( [
			'gcep_msg'  => urlencode( $message ),
			'gcep_type' => $type,
		], $url );
		wp_safe_redirect( $url );
		exit;
	}

	private static function normalize_br_phone( string $phone ): string {
		$clean = self::sanitize_phone( $phone );

		if ( strlen( $clean ) > 11 && 0 === strpos( $clean, '55' ) ) {
			$clean = substr( $clean, 2 );
		}

		if ( strlen( $clean ) > 11 ) {
			$clean = substr( $clean, 0, 11 );
		}

		return $clean;
	}

	/**
	 * Converte URL de YouTube ou Vimeo em HTML de iframe responsivo
	 *
	 * @author Dante Testa <https://dantetesta.com.br>
	 * @since 1.9.1 - 2026-03-21
	 */
	public static function get_video_embed( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$embed_url = '';

		// YouTube: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
		if ( preg_match( '/(?:youtube\.com\/(?:watch\?.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
			$embed_url = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
		}

		// Vimeo: vimeo.com/ID, player.vimeo.com/video/ID
		if ( '' === $embed_url && preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m ) ) {
			$embed_url = 'https://player.vimeo.com/video/' . $m[1] . '?byline=0&portrait=0';
		}

		if ( '' === $embed_url ) {
			return '';
		}

		return '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;">'
			. '<iframe src="' . esc_url( $embed_url ) . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" '
			. 'allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" '
			. 'allowfullscreen loading="lazy" title="' . esc_attr__( 'Video de destaque', 'guiawp' ) . '"></iframe>'
			. '</div>';
	}

	private static function normalize_anuncio_status( string $status ): string {
		$status = sanitize_key( $status );
		return '' !== $status ? $status : 'rascunho';
	}
}
