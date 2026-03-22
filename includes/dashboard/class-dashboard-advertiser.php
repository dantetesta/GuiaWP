<?php
/**
 * Dashboard do anunciante
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Dashboard_Advertiser {

	public function init(): void {
		add_action( 'admin_post_gcep_renew_anuncio', [ $this, 'handle_renew' ] );
		add_action( 'admin_post_gcep_save_profile', [ $this, 'handle_save_profile' ] );
		add_action( 'wp_ajax_gcep_filter_dashboard', [ $this, 'ajax_filter_dashboard' ] );
		add_action( 'wp_ajax_gcep_get_anuncio_scripts', [ $this, 'ajax_get_anuncio_scripts' ] );
		add_action( 'wp_ajax_gcep_save_anuncio_scripts', [ $this, 'ajax_save_anuncio_scripts' ] );
		add_action( 'wp_ajax_gcep_upload_avatar', [ $this, 'ajax_upload_avatar' ] );
		add_action( 'wp_ajax_gcep_remove_avatar', [ $this, 'ajax_remove_avatar' ] );
	}

	public function handle_renew(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		}

		if ( ! isset( $_POST['gcep_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_nonce'], 'gcep_renew_anuncio' ) ) {
			wp_die( __( 'Erro de segurança.', 'guiawp' ) );
		}

		$anuncio_id = absint( $_POST['anuncio_id'] ?? 0 );
		$plano_id   = absint( $_POST['plano_id'] ?? 0 );
		$user_id    = get_current_user_id();

		if ( $anuncio_id <= 0 || $plano_id <= 0 ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/renovar?anuncio_id=' . $anuncio_id ), 'error', __( 'Dados inválidos.', 'guiawp' ) );
			exit;
		}

		$post = get_post( $anuncio_id );
		if ( ! $post || 'gcep_anuncio' !== $post->post_type || (int) $post->post_author !== $user_id ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel' ), 'error', __( 'Anúncio não encontrado.', 'guiawp' ) );
			exit;
		}

		$plan = GCEP_Plans::get( $plano_id );
		if ( ! $plan ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/renovar?anuncio_id=' . $anuncio_id ), 'error', __( 'Plano não encontrado.', 'guiawp' ) );
			exit;
		}

		// Atualizar metas do plano no anúncio
		update_post_meta( $anuncio_id, 'GCEP_plano_id', $plano_id );
		update_post_meta( $anuncio_id, 'GCEP_vigencia_dias', (int) $plan['days'] );
		update_post_meta( $anuncio_id, 'GCEP_plano_preco', $plan['price'] );
		update_post_meta( $anuncio_id, 'GCEP_status_anuncio', 'aguardando_pagamento' );
		update_post_meta( $anuncio_id, 'GCEP_status_pagamento', 'pendente' );
		GCEP_Helpers::sync_anuncio_post_status( $anuncio_id, 'aguardando_pagamento' );

		// Redirecionar para pagamento
		wp_safe_redirect( home_url( '/painel/pagamento?anuncio_id=' . $anuncio_id ) );
		exit;
	}

	public function handle_save_profile(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		}

		if ( ! isset( $_POST['gcep_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_nonce'], 'gcep_save_profile' ) ) {
			wp_die( __( 'Erro de segurança.', 'guiawp' ) );
		}

		$user_id          = get_current_user_id();
		$nome             = sanitize_text_field( wp_unslash( $_POST['gcep_nome'] ?? '' ) );
		$email            = sanitize_email( wp_unslash( $_POST['gcep_email'] ?? '' ) );
		$telefone         = GCEP_Helpers::sanitize_phone( wp_unslash( $_POST['gcep_telefone'] ?? '' ) );
		$nova_senha       = (string) ( $_POST['gcep_nova_senha'] ?? '' );
		$confirmar_senha  = (string) ( $_POST['gcep_confirmar_senha'] ?? '' );

		if ( '' === $nome || '' === $email ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'error', __( 'Nome e e-mail são obrigatórios.', 'guiawp' ) );
		}

		if ( ! is_email( $email ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'error', __( 'Informe um e-mail válido.', 'guiawp' ) );
		}

		$email_owner = email_exists( $email );
		if ( $email_owner && (int) $email_owner !== $user_id ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'error', __( 'Este e-mail já está em uso.', 'guiawp' ) );
		}

		if ( '' !== $nova_senha || '' !== $confirmar_senha ) {
			if ( $nova_senha !== $confirmar_senha ) {
				GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'error', __( 'A confirmação da senha não confere.', 'guiawp' ) );
			}

			if ( strlen( $nova_senha ) < 6 ) {
				GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'error', __( 'A nova senha deve ter pelo menos 6 caracteres.', 'guiawp' ) );
			}
		}

		$user_data = [
			'ID'           => $user_id,
			'display_name' => $nome,
			'first_name'   => $nome,
			'nickname'     => $nome,
			'user_email'   => $email,
		];

		if ( '' !== $nova_senha ) {
			$user_data['user_pass'] = $nova_senha;
		}

		$updated = wp_update_user( $user_data );

		if ( is_wp_error( $updated ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'error', $updated->get_error_message() );
		}

		update_user_meta( $user_id, 'gcep_telefone', $telefone );

		if ( '' !== $nova_senha ) {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );
		}

		GCEP_Helpers::redirect_with_message( home_url( '/painel/perfil' ), 'success', __( 'Perfil atualizado com sucesso.', 'guiawp' ) );
	}

	public function ajax_upload_avatar(): void {
		check_ajax_referer( 'gcep_avatar_nonce', 'nonce' );

		if ( empty( $_FILES['gcep_avatar']['name'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Nenhum arquivo enviado.', 'guiawp' ) ] );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file    = $_FILES['gcep_avatar'];
		$user_id = get_current_user_id();

		$allowed = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
		if ( ! in_array( $file['type'], $allowed, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Formato invalido. Use JPG, PNG, WebP ou GIF.', 'guiawp' ) ] );
		}

		if ( $file['size'] > 5 * 1024 * 1024 ) {
			wp_send_json_error( [ 'message' => __( 'Arquivo muito grande. Maximo 5MB.', 'guiawp' ) ] );
		}

		$upload = wp_handle_upload( $file, [ 'test_form' => false ] );
		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( [ 'message' => $upload['error'] ] );
		}

		$editor = wp_get_image_editor( $upload['file'] );
		if ( is_wp_error( $editor ) ) {
			@unlink( $upload['file'] );
			wp_send_json_error( [ 'message' => __( 'Erro ao processar a imagem.', 'guiawp' ) ] );
		}

		// Crop centralizado 1:1
		$size = $editor->get_size();
		$min  = min( $size['width'], $size['height'] );
		$x    = (int) ( ( $size['width'] - $min ) / 2 );
		$y    = (int) ( ( $size['height'] - $min ) / 2 );
		$editor->crop( $x, $y, $min, $min, 500, 500 );
		$editor->set_quality( 90 );

		// Salvar como WebP
		$upload_dir = wp_upload_dir();
		$webp_name  = 'gcep-avatar-' . $user_id . '-' . time() . '.webp';
		$webp_path  = $upload_dir['path'] . '/' . $webp_name;
		$saved      = $editor->save( $webp_path, 'image/webp' );

		@unlink( $upload['file'] );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( [ 'message' => __( 'Erro ao salvar a imagem.', 'guiawp' ) ] );
		}

		$attachment = [
			'post_mime_type' => 'image/webp',
			'post_title'     => sanitize_file_name( 'avatar-' . $user_id ),
			'post_status'    => 'inherit',
		];

		$attach_id = wp_insert_attachment( $attachment, $saved['path'] );
		if ( ! $attach_id || is_wp_error( $attach_id ) ) {
			@unlink( $saved['path'] );
			wp_send_json_error( [ 'message' => __( 'Erro ao registrar a imagem.', 'guiawp' ) ] );
		}

		$metadata = wp_generate_attachment_metadata( $attach_id, $saved['path'] );
		wp_update_attachment_metadata( $attach_id, $metadata );

		// Remover avatar anterior
		$old_id = (int) get_user_meta( $user_id, 'gcep_avatar_id', true );
		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		update_user_meta( $user_id, 'gcep_avatar_id', $attach_id );

		wp_send_json_success( [ 'url' => wp_get_attachment_url( $attach_id ) ] );
	}

	public function ajax_remove_avatar(): void {
		check_ajax_referer( 'gcep_avatar_nonce', 'nonce' );

		$user_id = get_current_user_id();
		$old_id  = (int) get_user_meta( $user_id, 'gcep_avatar_id', true );

		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		delete_user_meta( $user_id, 'gcep_avatar_id' );

		wp_send_json_success();
	}

	public static function get_user_anuncios( int $user_id, int $limit = -1, array $meta_statuses = [] ): array {
		$args = [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => GCEP_Helpers::get_manageable_anuncio_post_statuses(),
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'author'         => $user_id,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( ! empty( $meta_statuses ) ) {
			$args['meta_query'] = [
				[
					'key'     => 'GCEP_status_anuncio',
					'value'   => array_map( 'sanitize_text_field', $meta_statuses ),
					'compare' => 'IN',
				],
			];
		}

		return get_posts( $args );
	}

	// Retorna posts + dados de paginacao
	public static function get_user_anuncios_paged( int $user_id, int $paged = 1, int $per_page = 10 ): array {
		$query = new WP_Query( [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => GCEP_Helpers::get_manageable_anuncio_post_statuses(),
			'posts_per_page' => $per_page,
			'author'         => $user_id,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'paged'          => $paged,
		] );

		$views = [];
		if ( class_exists( 'GCEP_Analytics' ) && ! empty( $query->posts ) ) {
			$views = GCEP_Analytics::get_views_for_posts( wp_list_pluck( $query->posts, 'ID' ) );
		}

		return [
			'posts' => $query->posts,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
			'paged' => $paged,
			'views' => $views,
		];
	}

	public static function get_user_available_periods( int $user_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT YEAR(post_date) AS period_year, MONTH(post_date) AS period_month
				FROM {$wpdb->posts}
				WHERE post_type = 'gcep_anuncio'
				AND post_author = %d
				AND post_status IN ('publish', 'draft')
				GROUP BY YEAR(post_date), MONTH(post_date)
				ORDER BY YEAR(post_date) DESC, MONTH(post_date) DESC",
				$user_id
			),
			ARRAY_A
		);

		$years          = [];
		$months_by_year = [];

		foreach ( $rows as $row ) {
			$year  = (int) ( $row['period_year'] ?? 0 );
			$month = (int) ( $row['period_month'] ?? 0 );

			if ( $year <= 0 || $month < 1 || $month > 12 ) {
				continue;
			}

			if ( ! in_array( $year, $years, true ) ) {
				$years[] = $year;
			}

			if ( ! isset( $months_by_year[ $year ] ) ) {
				$months_by_year[ $year ] = [];
			}

			if ( ! in_array( $month, $months_by_year[ $year ], true ) ) {
				$months_by_year[ $year ][] = $month;
			}
		}

		$latest_year  = ! empty( $years ) ? (int) $years[0] : 0;
		$latest_month = ( $latest_year > 0 && ! empty( $months_by_year[ $latest_year ] ) ) ? (int) $months_by_year[ $latest_year ][0] : 0;

		return [
			'years'          => $years,
			'months_by_year' => $months_by_year,
			'latest_year'    => $latest_year,
			'latest_month'   => $latest_month,
		];
	}

	public static function resolve_user_dashboard_period( int $user_id, int $requested_year = 0, int $requested_month = 0 ): array {
		$available_periods   = self::get_user_available_periods( $user_id );
		$available_years     = $available_periods['years'] ?? [];
		$months_by_year      = $available_periods['months_by_year'] ?? [];
		$has_period_filters  = ! empty( $available_years );
		$default_filter_year = $has_period_filters ? (int) ( $available_periods['latest_year'] ?? 0 ) : (int) current_time( 'Y' );
		$filter_year         = $requested_year > 0 ? $requested_year : $default_filter_year;

		if ( $has_period_filters && ! in_array( $filter_year, $available_years, true ) ) {
			$filter_year = $default_filter_year;
		}

		$available_months     = $has_period_filters ? ( $months_by_year[ $filter_year ] ?? [] ) : [];
		$default_filter_month = ! empty( $available_months ) ? (int) $available_months[0] : (int) current_time( 'm' );
		$filter_month         = $requested_month > 0 ? $requested_month : $default_filter_month;

		if ( $has_period_filters && ! in_array( $filter_month, $available_months, true ) ) {
			$filter_month = $default_filter_month;
		}

		return [
			'available_periods' => $available_periods,
			'available_years'   => $available_years,
			'months_by_year'    => $months_by_year,
			'available_months'  => $available_months,
			'has_periods'       => $has_period_filters,
			'year'              => $filter_year,
			'month'             => $filter_month,
		];
	}

	public static function get_user_stats( int $user_id, int $year = 0, int $month = 0 ): array {
		$stats = [
			'total'      => 0,
			'publicados' => 0,
			'pendentes'  => 0,
			'rejeitados' => 0,
			'visitas'    => 0,
		];
		global $wpdb;

		$query  = "SELECT COALESCE(pm.meta_value, 'rascunho') AS status, COUNT(*) AS total
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id
				AND pm.meta_key = 'GCEP_status_anuncio'
			WHERE p.post_type = 'gcep_anuncio'
			AND p.post_author = %d
			AND p.post_status IN ('publish', 'draft')";
		$params = [ $user_id ];

		if ( $year > 0 ) {
			$query    .= ' AND YEAR(p.post_date) = %d';
			$params[] = $year;
		}

		if ( $month > 0 ) {
			$query    .= ' AND MONTH(p.post_date) = %d';
			$params[] = $month;
		}

		$query .= " GROUP BY COALESCE(pm.meta_value, 'rascunho')";

		$rows = $wpdb->get_results(
			$wpdb->prepare( $query, $params ),
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$count  = (int) ( $row['total'] ?? 0 );
			$status = (string) ( $row['status'] ?? 'rascunho' );

			$stats['total'] += $count;

			if ( 'publicado' === $status ) {
				$stats['publicados'] += $count;
			} elseif ( in_array( $status, [ 'aguardando_aprovacao', 'aguardando_pagamento' ], true ) ) {
				$stats['pendentes'] += $count;
			} elseif ( 'rejeitado' === $status ) {
				$stats['rejeitados'] += $count;
			}
		}

		if ( class_exists( 'GCEP_Analytics' ) ) {
			$stats['visitas'] = GCEP_Analytics::get_user_total_views( $user_id, $year, $month );
		}

		return $stats;
	}

	public static function get_user_chart_data( int $user_id, int $year, int $month ): array {
		$days_in_month = (int) date( 't', strtotime( "$year-$month-01" ) );
		$chart_data = array_fill( 1, $days_in_month, 0 );

		if ( ! class_exists( 'GCEP_Analytics' ) ) {
			return $chart_data;
		}

		$views = GCEP_Analytics::get_user_views_by_month( $user_id, $year, $month );
		foreach ( $views as $day => $count ) {
			$chart_data[ $day ] = (int) $count;
		}

		return $chart_data;
	}

	public function ajax_filter_dashboard(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Sessão expirada.', 'guiawp' ) ], 401 );
		}

		check_ajax_referer( 'gcep_nonce', 'nonce' );

		$user_id = get_current_user_id();
		$period  = self::resolve_user_dashboard_period(
			$user_id,
			absint( $_POST['year'] ?? 0 ),
			absint( $_POST['month'] ?? 0 )
		);

		$filter_year  = (int) $period['year'];
		$filter_month = (int) $period['month'];
		$stats        = self::get_user_stats( $user_id, $filter_year, $filter_month );
		$chart_data   = self::get_user_chart_data( $user_id, $filter_year, $filter_month );

		wp_send_json_success( [
			'period' => [
				'year'  => $filter_year,
				'month' => $filter_month,
				'label' => sprintf(
					/* translators: 1: month name, 2: year */
					__( 'Visitas por Dia - %1$s/%2$s', 'guiawp' ),
					date_i18n( 'F', mktime( 0, 0, 0, $filter_month, 1 ) ),
					$filter_year
				),
			],
			'stats' => [
				'total'      => (int) ( $stats['total'] ?? 0 ),
				'publicados' => (int) ( $stats['publicados'] ?? 0 ),
				'pendentes'  => (int) ( $stats['pendentes'] ?? 0 ),
				'rejeitados' => (int) ( $stats['rejeitados'] ?? 0 ),
				'visitas'    => (int) ( $stats['visitas'] ?? 0 ),
			],
			'chart' => [
				'labels' => array_values( array_keys( $chart_data ) ),
				'data'   => array_values( array_map( 'intval', $chart_data ) ),
			],
		] );
	}

	public function ajax_get_anuncio_scripts(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Sessão expirada.', 'guiawp' ) ], 401 );
		}

		check_ajax_referer( 'gcep_anuncio_scripts', 'nonce' );

		$anuncio_id = absint( $_POST['anuncio_id'] ?? 0 );
		if ( $anuncio_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Anúncio inválido.', 'guiawp' ) ], 400 );
		}

		if ( ! class_exists( 'GCEP_Anuncio_Custom_Scripts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Recurso indisponível.', 'guiawp' ) ], 500 );
		}

		if ( ! GCEP_Anuncio_Custom_Scripts::can_manage_scripts( $anuncio_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Você não tem permissão para editar esses scripts.', 'guiawp' ) ], 403 );
		}

		if ( ! GCEP_Anuncio_Custom_Scripts::is_scripts_available_for_edit( $anuncio_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Os scripts premium só ficam disponíveis para anúncios premium salvos como rascunho ou publicado.', 'guiawp' ) ], 400 );
		}

		wp_send_json_success( [
			'title'   => get_the_title( $anuncio_id ),
			'scripts' => GCEP_Anuncio_Custom_Scripts::get_scripts( $anuncio_id ),
		] );
	}

	public function ajax_save_anuncio_scripts(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Sessão expirada.', 'guiawp' ) ], 401 );
		}

		check_ajax_referer( 'gcep_anuncio_scripts', 'nonce' );

		$anuncio_id = absint( $_POST['anuncio_id'] ?? 0 );
		if ( $anuncio_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Anúncio inválido.', 'guiawp' ) ], 400 );
		}

		if ( ! class_exists( 'GCEP_Anuncio_Custom_Scripts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Recurso indisponível.', 'guiawp' ) ], 500 );
		}

		if ( ! GCEP_Anuncio_Custom_Scripts::can_manage_scripts( $anuncio_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Você não tem permissão para editar esses scripts.', 'guiawp' ) ], 403 );
		}

		if ( ! GCEP_Anuncio_Custom_Scripts::is_scripts_available_for_edit( $anuncio_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Os scripts premium só ficam disponíveis para anúncios premium salvos como rascunho ou publicado.', 'guiawp' ) ], 400 );
		}

		GCEP_Anuncio_Custom_Scripts::save_scripts(
			$anuncio_id,
			[
				'head'       => (string) ( $_POST['head'] ?? '' ),
				'body_start' => (string) ( $_POST['body_start'] ?? '' ),
				'body_end'   => (string) ( $_POST['body_end'] ?? '' ),
			]
		);

		wp_send_json_success( [
			'message' => __( 'Scripts premium salvos com sucesso.', 'guiawp' ),
		] );
	}
}
