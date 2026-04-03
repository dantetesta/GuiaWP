<?php
/**
 * Handler do mapa de anúncios — endpoints AJAX
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.7.0 - 2026-03-11
 * @modified 1.9.4 - 2026-03-21 - Adicionado campo capa no AJAX, fix encoding titulo
 * @modified 1.9.5 - 2026-03-21 - Eliminado N+1 queries (5500→~10), transient cache, cache flags na WP_Query
 * @modified 1.9.7 - 2026-03-21 - Adicionado endereco_completo no payload AJAX para modal do mapa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Map_Handler {

	public function init(): void {
		add_action( 'wp_ajax_gcep_map_anuncios', [ $this, 'ajax_map_anuncios' ] );
		add_action( 'wp_ajax_nopriv_gcep_map_anuncios', [ $this, 'ajax_map_anuncios' ] );
		add_action( 'wp_ajax_gcep_map_view', [ $this, 'ajax_map_view' ] );
		add_action( 'wp_ajax_nopriv_gcep_map_view', [ $this, 'ajax_map_view' ] );

		// Invalidar cache do mapa quando anuncios mudam
		add_action( 'save_post_gcep_anuncio', [ $this, 'invalidar_cache' ] );
		add_action( 'delete_post', [ $this, 'invalidar_cache' ] );
	}

	// Invalidar transient do mapa
	public function invalidar_cache(): void {
		delete_transient( 'gcep_map_anuncios' );
	}

	// Retorna anuncios com lat/lng para exibir como pins no mapa
	public function ajax_map_anuncios(): void {

		// Filtros do usuario
		$busca = ! empty( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$cat   = ! empty( $_GET['gcep_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_cat'] ) ) : '';

		// Cache apenas para requests sem filtro (listagem padrao)
		$usar_cache = ( '' === $busca && '' === $cat );
		if ( $usar_cache ) {
			$cached = get_transient( 'gcep_map_anuncios' );
			if ( false !== $cached ) {
				wp_send_json_success( $cached );
			}
		}

		$args = [
			'post_type'              => 'gcep_anuncio',
			'post_status'            => 'publish',
			'posts_per_page'         => 500,
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
			'meta_query'             => [
				'relation' => 'AND',
				[
					'key'   => 'GCEP_status_anuncio',
					'value' => 'publicado',
				],
				[
					'key'     => 'GCEP_latitude',
					'value'   => '',
					'compare' => '!=',
				],
				[
					'key'     => 'GCEP_longitude',
					'value'   => '',
					'compare' => '!=',
				],
			],
		];

		if ( '' !== $busca ) {
			$args['s'] = $busca;
		}

		if ( '' !== $cat ) {
			$cats = array_map( 'sanitize_text_field', explode( ',', $cat ) );
			$args['tax_query'] = [
				[
					'taxonomy' => 'gcep_categoria',
					'field'    => 'slug',
					'terms'    => $cats,
				],
			];
		}

		$query    = new WP_Query( $args );
		$anuncios = [];

		// Coletar IDs para pre-carregar meta de uma vez
		$post_ids = [];
		foreach ( $query->posts as $post ) {
			$post_ids[] = $post->ID;
		}

		// Pre-carregar meta e terms de todos os posts em batch (elimina N+1)
		if ( ! empty( $post_ids ) ) {
			update_meta_cache( 'post', $post_ids );
			update_object_term_cache( $post_ids, 'gcep_anuncio' );
		}

		foreach ( $query->posts as $post ) {
			$id  = $post->ID;
			$lat = (float) get_post_meta( $id, 'GCEP_latitude', true );
			$lng = (float) get_post_meta( $id, 'GCEP_longitude', true );

			if ( 0.0 === $lat && 0.0 === $lng ) {
				continue;
			}

			$plano = get_post_meta( $id, 'GCEP_tipo_plano', true );
			$cats  = wp_get_object_terms( $id, 'gcep_categoria', [ 'fields' => 'names' ] );
			$locs  = wp_get_object_terms( $id, 'gcep_localizacao', [ 'fields' => 'names' ] );

			$logo_id = get_post_meta( $id, 'GCEP_logo_ou_foto_principal', true );
			$logo    = $logo_id ? wp_get_attachment_image_url( (int) $logo_id, 'thumbnail' ) : '';

			$capa_id = get_post_meta( $id, 'GCEP_foto_capa', true );
			$capa    = $capa_id ? wp_get_attachment_image_url( (int) $capa_id, 'medium_large' ) : '';

			$anuncios[] = [
				'id'        => $id,
				'titulo'    => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
				'url'       => get_permalink( $post ),
				'lat'       => $lat,
				'lng'       => $lng,
				'plano'     => $plano ?: 'gratis',
				'categoria' => ! empty( $cats ) && ! is_wp_error( $cats ) ? $cats[0] : '',
				'local'     => ! empty( $locs ) && ! is_wp_error( $locs ) ? implode( ', ', $locs ) : '',
				'logo'      => $logo,
				'capa'      => $capa,
				'telefone'  => get_post_meta( $id, 'GCEP_telefone', true ),
				'whatsapp'  => get_post_meta( $id, 'GCEP_whatsapp', true ),
				'email'     => get_post_meta( $id, 'GCEP_email', true ),
				'instagram' => get_post_meta( $id, 'GCEP_instagram', true ),
				'site'      => get_post_meta( $id, 'GCEP_site', true ),
				'descricao' => get_post_meta( $id, 'GCEP_descricao_curta', true ),
				'endereco'  => get_post_meta( $id, 'GCEP_endereco_completo', true ),
			];
		}

		wp_reset_postdata();

		// Cachear resultado padrao por 10 minutos
		if ( $usar_cache ) {
			set_transient( 'gcep_map_anuncios', $anuncios, 10 * MINUTE_IN_SECONDS );
		}

		wp_send_json_success( $anuncios );
	}

	// Registra visita ao abrir modal do pin no mapa
	public function ajax_map_view(): void {
		$post_id = absint( $_POST['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'ID inválido.' ] );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'gcep_anuncio' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => 'Anúncio não encontrado.' ] );
		}

		GCEP_Analytics::track_view( $post_id );
		wp_send_json_success();
	}
}
