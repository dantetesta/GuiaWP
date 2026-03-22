<?php
/**
 * Handler de criação/edição de anúncios via front-end
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.1.0 - 2026-03-11 - Adicionados campos de endereço, CNPJ, galeria, vídeos, redes sociais e localização automática
 * @modified 1.4.1 - 2026-03-11 - Submit via AJAX com resultado inline (aprovado/rejeitado sem redirect)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Anuncio_Handler {

	private ?string $trace_id = null;

	private function get_validation_reason_fallback(): string {
		return __( 'A validação automática reprovou o anúncio, mas não retornou uma justificativa detalhada. Revise título, descrição e dados de contato antes de tentar novamente.', 'guiawp' );
	}

	private function is_generic_validation_reason( string $reason ): bool {
		$normalized = strtolower( trim( sanitize_textarea_field( $reason ) ) );

		if ( '' === $normalized ) {
			return true;
		}

		$generic_fragments = [
			'não informou uma justificativa detalhada',
			'nao informou uma justificativa detalhada',
			'não retornou uma justificativa detalhada',
			'nao retornou uma justificativa detalhada',
			'corrija os problemas apontados',
			'corrija e tente novamente',
		];

		foreach ( $generic_fragments as $fragment ) {
			if ( str_contains( $normalized, $fragment ) ) {
				return true;
			}
		}

		return false;
	}

	private function normalize_description_for_validation( string $html ): string {
		$sanitized = GCEP_AI_Validator::sanitize_description_html( $html );
		return sanitize_textarea_field( wp_strip_all_tags( $sanitized, true ) );
	}

	private function get_category_names_from_request(): string {
		if ( empty( $_POST['gcep_categoria'] ) ) {
			return '';
		}

		$cat_ids = array_map( 'intval', (array) $_POST['gcep_categoria'] );
		$terms   = get_terms( [
			'taxonomy'   => 'gcep_categoria',
			'include'    => $cat_ids,
			'fields'     => 'names',
			'hide_empty' => false,
		] );

		if ( is_wp_error( $terms ) ) {
			return '';
		}

		return implode( ', ', $terms );
	}

	private function set_anuncio_status( int $post_id, string $status ): void {
		update_post_meta( $post_id, 'GCEP_status_anuncio', $status );
		GCEP_Helpers::sync_anuncio_post_status( $post_id, $status );
	}

	private function get_trace_id(): string {
		if ( null === $this->trace_id ) {
			$this->trace_id = gmdate( 'YmdHis' ) . '-' . wp_generate_password( 8, false, false );
		}

		return $this->trace_id;
	}

	private function get_payment_redirect( int $post_id, string $context = 'user' ): string {
		$route = 'admin' === $context ? '/painel-admin/pagamento?anuncio_id=' : '/painel/pagamento?anuncio_id=';
		return home_url( $route . $post_id );
	}

	private function get_edit_success_status( int $post_id, string $plano, bool $is_upgrade ): string {
		return GCEP_Anuncio_Flow::determine_edit_success_status(
			(string) get_post_meta( $post_id, 'GCEP_status_anuncio', true ),
			(string) get_post_meta( $post_id, 'GCEP_status_pagamento', true ),
			$plano,
			$is_upgrade
		);
	}

	private function should_log_anuncio_debug(): bool {
		$default = defined( 'WP_DEBUG' ) && WP_DEBUG;
		return (bool) apply_filters( 'gcep_enable_anuncio_debug_log', $default );
	}

	private function sanitize_debug_value( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			$sanitized = [];
			$counter   = 0;

			foreach ( $value as $key => $item ) {
				$sanitized[ $key ] = $this->sanitize_debug_value( $item );
				$counter++;

				if ( $counter >= 12 ) {
					$sanitized['__truncated'] = true;
					break;
				}
			}

			return $sanitized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		if ( is_scalar( $value ) ) {
			$string = sanitize_textarea_field( (string) $value );
			return mb_strlen( $string ) > 350 ? mb_substr( $string, 0, 347 ) . '...' : $string;
		}

		return gettype( $value );
	}

	private function track_flow_event( string $event, int $post_id = 0, array $context = [] ): void {
		$entry = [
			'trace_id' => $this->get_trace_id(),
			'time'     => current_time( 'mysql' ),
			'event'    => $event,
			'context'  => $this->sanitize_debug_value( $context ),
		];

		if ( $post_id > 0 ) {
			$history   = get_post_meta( $post_id, 'GCEP_debug_flow_events', true );
			$history   = is_array( $history ) ? $history : [];
			$history[] = $entry;
			if ( count( $history ) > 25 ) {
				$history = array_slice( $history, -25 );
			}

			update_post_meta( $post_id, 'GCEP_debug_flow_events', $history );
			update_post_meta( $post_id, 'GCEP_debug_last_flow_event', $entry );
			update_post_meta( $post_id, 'GCEP_debug_last_trace_id', $this->get_trace_id() );
		}

		do_action( 'gcep_anuncio_flow_event', $entry, $post_id );

		if ( $this->should_log_anuncio_debug() ) {
			error_log( '[GuiaWP][AnuncioFlow] ' . wp_json_encode( array_merge( [ 'post_id' => $post_id ], $entry ) ) );
		}
	}

	private function reset_payment_state( int $post_id ): void {
		delete_post_meta( $post_id, 'GCEP_status_pagamento' );
		delete_post_meta( $post_id, 'GCEP_gateway_payment_id' );
		delete_post_meta( $post_id, 'GCEP_gateway_nome' );
		delete_post_meta( $post_id, 'GCEP_gateway_metodo' );
		delete_post_meta( $post_id, 'GCEP_pagamento_confirmado_em' );
	}

	private function mark_payment_pending( int $post_id ): void {
		update_post_meta( $post_id, 'GCEP_status_pagamento', 'pendente' );
		delete_post_meta( $post_id, 'GCEP_gateway_payment_id' );
		delete_post_meta( $post_id, 'GCEP_gateway_nome' );
		delete_post_meta( $post_id, 'GCEP_gateway_metodo' );
		delete_post_meta( $post_id, 'GCEP_pagamento_confirmado_em' );
	}

	private function build_request_ai_payload( string $titulo, string $tipo, string $plano ): array {
		$endereco_parts = array_filter( [
			sanitize_text_field( wp_unslash( $_POST['gcep_logradouro'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['gcep_numero'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['gcep_bairro'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['gcep_cidade'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['gcep_estado'] ?? '' ) ),
		] );

		return [
			'titulo'          => $titulo,
			'tipo_anuncio'    => $tipo,
			'tipo_plano'      => $plano,
			'descricao_curta' => sanitize_text_field( wp_unslash( $_POST['gcep_descricao_curta'] ?? '' ) ),
			'descricao_longa' => $this->normalize_description_for_validation( (string) wp_unslash( $_POST['gcep_descricao_longa'] ?? '' ) ),
			'telefone'        => sanitize_text_field( wp_unslash( $_POST['gcep_telefone'] ?? '' ) ),
			'whatsapp'        => sanitize_text_field( wp_unslash( $_POST['gcep_whatsapp'] ?? '' ) ),
			'email'           => sanitize_email( wp_unslash( $_POST['gcep_email'] ?? '' ) ),
			'site'            => esc_url_raw( wp_unslash( $_POST['gcep_site'] ?? '' ) ),
			'cnpj'            => sanitize_text_field( wp_unslash( $_POST['gcep_cnpj'] ?? '' ) ),
			'endereco'        => implode( ', ', $endereco_parts ),
			'categorias'      => $this->get_category_names_from_request(),
		];
	}

	private function classify_ai_result( array $ai_result ): array {
		return GCEP_Anuncio_Flow::classify_ai_result(
			$ai_result,
			fn( string $reason ): bool => $this->is_generic_validation_reason( $reason ),
			$this->get_validation_reason_fallback()
		);
	}

	private function persist_ai_validation_state( int $post_id, array $evaluation ): void {
		update_post_meta( $post_id, 'GCEP_ai_ultima_validacao', current_time( 'mysql' ) );
		update_post_meta( $post_id, 'GCEP_ai_ultimo_trace_id', $this->get_trace_id() );

		if ( ! empty( $evaluation['approved'] ) ) {
			update_post_meta( $post_id, 'GCEP_ai_validado', '1' );
			update_post_meta( $post_id, 'GCEP_ai_justificativa', '' );
			delete_post_meta( $post_id, 'GCEP_ai_ultimo_erro' );
			return;
		}

		update_post_meta( $post_id, 'GCEP_ai_validado', '0' );

		if ( ! empty( $evaluation['has_blocking_rejection'] ) ) {
			update_post_meta( $post_id, 'GCEP_ai_justificativa', (string) ( $evaluation['reason'] ?? '' ) );
			delete_post_meta( $post_id, 'GCEP_ai_ultimo_erro' );
			return;
		}

		update_post_meta( $post_id, 'GCEP_ai_justificativa', (string) ( $evaluation['reason'] ?? '' ) );

		if ( ! empty( $evaluation['error'] ) ) {
			update_post_meta( $post_id, 'GCEP_ai_ultimo_erro', (string) $evaluation['error'] );
			return;
		}

		delete_post_meta( $post_id, 'GCEP_ai_ultimo_erro' );
	}

	private function build_edit_success_response( int $post_id, string $plano, bool $is_upgrade, string $redirect, string $context = 'user', bool $ai_unavailable = false ): array {
		$final_status = $this->get_edit_success_status( $post_id, $plano, $is_upgrade );
		$this->set_anuncio_status( $post_id, $final_status );

		if ( 'aguardando_pagamento' === $final_status ) {
			return [
				'status'        => 'aguardando_pagamento',
				'post_id'       => $post_id,
				'redirect'      => $this->get_payment_redirect( $post_id, $context ),
				'message'       => $ai_unavailable
					? __( 'Alterações salvas. A validação por IA não retornou um parecer confiável e o anúncio seguirá para pagamento/revisão.', 'guiawp' )
					: __( 'Upgrade aprovado! Redirecionando para o pagamento...', 'guiawp' ),
				'final_status'  => $final_status,
				'trace_id'      => $this->get_trace_id(),
			];
		}

		if ( 'expirado' === $final_status ) {
			return [
				'status'       => 'saved',
				'post_id'      => $post_id,
				'redirect'     => $redirect,
				'message'      => __( 'Alterações salvas, mas o anúncio permanece expirado até a renovação.', 'guiawp' ),
				'final_status' => $final_status,
				'trace_id'     => $this->get_trace_id(),
			];
		}

		return [
			'status'       => 'saved',
			'post_id'      => $post_id,
			'redirect'     => $redirect,
			'message'      => $ai_unavailable
				? __( 'Alterações salvas. A IA não retornou uma justificativa válida para bloquear o anúncio.', 'guiawp' )
				: __( 'Anúncio atualizado e publicado com sucesso!', 'guiawp' ),
			'final_status' => $final_status,
			'trace_id'     => $this->get_trace_id(),
		];
	}

	private function process_create_submission( int $user_id, int $post_id, string $titulo, string $tipo, string $plano, int $plano_id ): array|\WP_Error {
		if ( 'premium' === $plano ) {
			if ( $plano_id <= 0 ) {
				return new WP_Error( 'gcep_missing_plan', __( 'Selecione um plano de vigência para continuar.', 'guiawp' ) );
			}

			if ( ! GCEP_Plans::get( $plano_id ) ) {
				return new WP_Error( 'gcep_invalid_plan', __( 'O plano premium selecionado não é válido.', 'guiawp' ) );
			}
		}

		$post_id = $this->save_anuncio_data(
			$post_id,
			$user_id,
			$titulo,
			$tipo,
			$plano,
			$plano_id,
			[
				'is_upgrade' => false,
			]
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->track_flow_event(
			'create_saved',
			$post_id,
			[
				'plano' => $plano,
				'tipo'  => $tipo,
			]
		);

		return $this->resolve_submission_flow( $post_id, $plano );
	}

	private function process_edit_submission( int $user_id, int $post_id, string $titulo, string $tipo, string $plano, int $plano_id, string $context ): array|\WP_Error {
		$redirect = 'admin' === $context
			? home_url( '/painel-admin/anuncios' )
			: home_url( '/painel/anuncios' );

		$plano_atual       = (string) get_post_meta( $post_id, 'GCEP_tipo_plano', true );
		$is_upgrade        = 'gratis' === $plano_atual && 'premium' === $plano;
		$is_premium_locked = 'premium' === $plano_atual;

		if ( $is_premium_locked ) {
			$plano    = 'premium';
			$plano_id = absint( get_post_meta( $post_id, 'GCEP_plano_id', true ) );
		}

		if ( $is_upgrade && $plano_id <= 0 ) {
			return new WP_Error( 'gcep_missing_plan', __( 'Selecione um plano de vigência para o upgrade.', 'guiawp' ) );
		}

		if ( $is_upgrade && ! GCEP_Plans::get( $plano_id ) ) {
			return new WP_Error( 'gcep_invalid_plan', __( 'O plano premium selecionado não é válido.', 'guiawp' ) );
		}

		$ai_payload = $this->build_request_ai_payload( $titulo, $tipo, $plano );
		$ai_result  = GCEP_AI_Validator::validate_from_data( $ai_payload );
		$evaluation = $this->classify_ai_result( $ai_result );

		$this->track_flow_event(
			'edit_ai_evaluated',
			$post_id,
			[
				'context'         => $context,
				'plano_atual'     => $plano_atual,
				'plano_final'     => $plano,
				'is_upgrade'      => $is_upgrade,
				'ai_approved'     => $evaluation['approved'],
				'ai_error'        => $evaluation['error'],
				'ai_reason'       => $evaluation['reason'],
				'ai_raw'          => (string) ( $ai_result['raw_response'] ?? '' ),
				'ai_should_bypass'=> $evaluation['should_bypass_blocking'],
			]
		);

		if ( ! empty( $evaluation['has_blocking_rejection'] ) ) {
			$this->persist_ai_validation_state( $post_id, $evaluation );
			$this->track_flow_event( 'edit_blocked_by_ai', $post_id, [ 'reason' => $evaluation['reason'] ] );

			return [
				'status'        => 'rejeitado',
				'justificativa' => $evaluation['reason'],
				'post_id'       => $post_id,
				'message'       => __( 'As alterações não foram salvas. Corrija os problemas apontados.', 'guiawp' ),
				'trace_id'      => $this->get_trace_id(),
			];
		}

		$post_id = $this->save_anuncio_data(
			$post_id,
			$user_id,
			$titulo,
			$tipo,
			$plano,
			$plano_id,
			[
				'is_upgrade' => $is_upgrade,
			]
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 'premium' === $plano && $is_upgrade ) {
			$this->mark_payment_pending( $post_id );
		} elseif ( 'gratis' === $plano ) {
			$this->reset_payment_state( $post_id );
		}

		$this->persist_ai_validation_state( $post_id, $evaluation );
		$this->track_flow_event(
			'edit_saved',
			$post_id,
			[
				'context'        => $context,
				'plano'          => $plano,
				'is_upgrade'     => $is_upgrade,
				'ai_unavailable' => ! empty( $evaluation['should_bypass_blocking'] ) && empty( $evaluation['approved'] ),
			]
		);

		return $this->build_edit_success_response(
			$post_id,
			$plano,
			$is_upgrade,
			$redirect,
			$context,
			! empty( $evaluation['should_bypass_blocking'] ) && empty( $evaluation['approved'] )
		);
	}

	// ==================== AJAX: Submeter anuncio (criacao) ====================
	public function ajax_submit_anuncio(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message'  => __( 'Voce precisa estar logado.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		if ( ! check_ajax_referer( 'gcep_save_anuncio', 'gcep_anuncio_nonce', false ) ) {
			wp_send_json_error( [
				'message'  => __( 'Erro de seguranca.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		$user_id  = get_current_user_id();
		$post_id  = intval( $_POST['gcep_anuncio_id'] ?? 0 );
		$titulo   = sanitize_text_field( wp_unslash( $_POST['gcep_titulo'] ?? '' ) );
		$tipo     = sanitize_text_field( wp_unslash( $_POST['gcep_tipo_anuncio'] ?? 'empresa' ) );
		$plano    = sanitize_text_field( wp_unslash( $_POST['gcep_tipo_plano'] ?? 'gratis' ) );
		$plano_id = absint( $_POST['gcep_plano_id'] ?? 0 );

		if ( empty( $titulo ) ) {
			wp_send_json_error( [
				'message'  => __( 'O titulo e obrigatorio.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		// Verificar propriedade
		if ( $post_id > 0 ) {
			$existing = get_post( $post_id );
			if ( ! $existing || (int) $existing->post_author !== $user_id ) {
				wp_send_json_error( [
					'message'  => __( 'Permissao negada.', 'guiawp' ),
					'trace_id' => $this->get_trace_id(),
				] );
			}
		}

		$this->track_flow_event(
			'create_request_received',
			$post_id,
			[
				'tipo'     => $tipo,
				'plano'    => $plano,
				'plano_id' => $plano_id,
			]
		);

		$result = $this->process_create_submission( $user_id, $post_id, $titulo, $tipo, $plano, $plano_id );

		if ( is_wp_error( $result ) ) {
			$this->track_flow_event( 'create_save_failed', $post_id, [ 'error' => $result->get_error_message() ] );
			wp_send_json_error( [
				'message'  => $result->get_error_message(),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		wp_send_json_success( $result );
	}

	// ==================== AJAX: Editar anuncio (valida IA antes de salvar) ====================
	public function ajax_edit_anuncio(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message'  => __( 'Voce precisa estar logado.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		if ( ! check_ajax_referer( 'gcep_save_anuncio', 'gcep_anuncio_nonce', false ) ) {
			wp_send_json_error( [
				'message'  => __( 'Erro de seguranca.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		$user_id  = get_current_user_id();
		$post_id  = intval( $_POST['gcep_anuncio_id'] ?? 0 );
		$titulo   = sanitize_text_field( wp_unslash( $_POST['gcep_titulo'] ?? '' ) );
		$tipo     = sanitize_text_field( wp_unslash( $_POST['gcep_tipo_anuncio'] ?? 'empresa' ) );
		$plano    = sanitize_text_field( wp_unslash( $_POST['gcep_tipo_plano'] ?? 'gratis' ) );
		$plano_id = absint( $_POST['gcep_plano_id'] ?? 0 );
		$context  = sanitize_key( wp_unslash( $_POST['gcep_edit_context'] ?? 'user' ) );

		if ( $post_id <= 0 ) {
			wp_send_json_error( [
				'message'  => __( 'ID do anuncio invalido.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		if ( empty( $titulo ) ) {
			wp_send_json_error( [
				'message'  => __( 'O titulo e obrigatorio.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		$existing = get_post( $post_id );
		$is_admin = current_user_can( 'manage_options' );
		if ( ! $existing || ( (int) $existing->post_author !== $user_id && ! $is_admin ) ) {
			wp_send_json_error( [
				'message'  => __( 'Permissao negada.', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		$this->track_flow_event(
			'edit_request_received',
			$post_id,
			[
				'context'  => $context,
				'tipo'     => $tipo,
				'plano'    => $plano,
				'plano_id' => $plano_id,
			]
		);

		$result = $this->process_edit_submission( $user_id, $post_id, $titulo, $tipo, $plano, $plano_id, $context );

		if ( is_wp_error( $result ) ) {
			$this->track_flow_event( 'edit_save_failed', $post_id, [ 'error' => $result->get_error_message() ] );
			wp_send_json_error( [
				'message'  => $result->get_error_message(),
				'trace_id' => $this->get_trace_id(),
			] );
		}

		wp_send_json_success( $result );
	}

	public function ajax_get_validation_reason(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Voce precisa estar logado.', 'guiawp' ) ] );
		}

		$valid_nonce = check_ajax_referer( 'gcep_save_anuncio', 'gcep_anuncio_nonce', false );
		if ( false === $valid_nonce ) {
			$valid_nonce = check_ajax_referer( 'gcep_nonce', 'nonce', false );
		}

		if ( false === $valid_nonce ) {
			wp_send_json_error( [ 'message' => __( 'Erro de seguranca.', 'guiawp' ) ] );
		}

		$post_id  = absint( $_POST['post_id'] ?? 0 );
		$user_id  = get_current_user_id();
		$is_admin = current_user_can( 'manage_options' );
		$post     = get_post( $post_id );

		if ( $post_id <= 0 || ! $post || 'gcep_anuncio' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Anuncio invalido.', 'guiawp' ) ] );
		}

		if ( (int) $post->post_author !== $user_id && ! $is_admin ) {
			wp_send_json_error( [ 'message' => __( 'Permissao negada.', 'guiawp' ) ] );
		}

		$reason = trim( (string) get_post_meta( $post_id, 'GCEP_ai_justificativa', true ) );

		wp_send_json_success( [
			'justificativa' => '' !== $reason ? $reason : $this->get_validation_reason_fallback(),
		] );
	}

	public function ajax_generate_descricao_ai(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Voce precisa estar logado.', 'guiawp' ) ] );
		}

		$valid_nonce = check_ajax_referer( 'gcep_save_anuncio', 'gcep_anuncio_nonce', false );
		if ( false === $valid_nonce ) {
			$valid_nonce = check_ajax_referer( 'gcep_nonce', 'nonce', false );
		}

		if ( false === $valid_nonce ) {
			wp_send_json_error( [ 'message' => __( 'Erro de seguranca.', 'guiawp' ) ] );
		}

		if ( ! GCEP_AI_Validator::can_generate_content() ) {
			wp_send_json_error( [ 'message' => __( 'A geração com IA não está disponível no momento.', 'guiawp' ) ] );
		}

		$titulo = sanitize_text_field( wp_unslash( $_POST['gcep_titulo'] ?? '' ) );
		$brief  = sanitize_textarea_field( wp_unslash( $_POST['contexto_inicial'] ?? '' ) );

		if ( '' === $brief ) {
			wp_send_json_error( [ 'message' => __( 'Informe um contexto inicial para a IA montar o anúncio.', 'guiawp' ) ] );
		}

		$result = GCEP_AI_Validator::generate_description_html( [
			'contexto_inicial' => $brief,
			'titulo'           => $titulo,
			'tipo_anuncio'     => sanitize_text_field( wp_unslash( $_POST['gcep_tipo_anuncio'] ?? '' ) ),
			'tipo_plano'       => sanitize_text_field( wp_unslash( $_POST['gcep_tipo_plano'] ?? '' ) ),
			'categorias'       => $this->get_category_names_from_request(),
			'descricao_curta'  => sanitize_text_field( wp_unslash( $_POST['gcep_descricao_curta'] ?? '' ) ),
			'cidade'           => sanitize_text_field( wp_unslash( $_POST['gcep_cidade'] ?? '' ) ),
			'estado'           => sanitize_text_field( wp_unslash( $_POST['gcep_estado'] ?? '' ) ),
			'site'             => esc_url_raw( wp_unslash( $_POST['gcep_site'] ?? '' ) ),
		] );

		if ( ! empty( $result['error'] ) ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		wp_send_json_success( [
			'html'    => $result['html'],
			'message' => __( 'Descrição gerada com IA. Revise e ajuste antes de salvar.', 'guiawp' ),
		] );
	}

	// ==================== Form POST: Editar anuncio (redirect) ====================
	public function handle_save(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		}

		if ( ! isset( $_POST['gcep_anuncio_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_anuncio_nonce'], 'gcep_save_anuncio' ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/criar-anuncio' ), 'error', __( 'Erro de seguranca.', 'guiawp' ) );
		}

		$user_id  = get_current_user_id();
		$post_id  = intval( $_POST['gcep_anuncio_id'] ?? 0 );
		$titulo   = sanitize_text_field( wp_unslash( $_POST['gcep_titulo'] ?? '' ) );
		$tipo     = sanitize_text_field( wp_unslash( $_POST['gcep_tipo_anuncio'] ?? 'empresa' ) );
		$plano    = sanitize_text_field( wp_unslash( $_POST['gcep_tipo_plano'] ?? 'gratis' ) );
		$plano_id = absint( $_POST['gcep_plano_id'] ?? 0 );
		$context  = sanitize_key( wp_unslash( $_POST['gcep_edit_context'] ?? 'user' ) );
		$is_admin_context = 'admin' === $context;
		$redirect_back = $post_id > 0
			? home_url( ( $is_admin_context ? '/painel-admin/editar-anuncio?id=' : '/painel/editar-anuncio?id=' ) . $post_id )
			: home_url( '/painel/criar-anuncio' );
		$list_redirect = $is_admin_context
			? home_url( '/painel-admin/anuncios' )
			: home_url( '/painel/anuncios' );

		if ( empty( $titulo ) ) {
			GCEP_Helpers::redirect_with_message( $redirect_back, 'error', __( 'O titulo e obrigatorio.', 'guiawp' ) );
		}

		$is_admin = current_user_can( 'manage_options' );
		if ( $post_id > 0 ) {
			$existing = get_post( $post_id );
			if ( ! $existing || ( (int) $existing->post_author !== $user_id && ! $is_admin ) ) {
				GCEP_Helpers::redirect_with_message( $list_redirect, 'error', __( 'Permissao negada.', 'guiawp' ) );
			}
		}

		$this->track_flow_event(
			'non_ajax_request_received',
			$post_id,
			[
				'context'  => $context,
				'tipo'     => $tipo,
				'plano'    => $plano,
				'plano_id' => $plano_id,
			]
		);

		$result = $post_id > 0
			? $this->process_edit_submission( $user_id, $post_id, $titulo, $tipo, $plano, $plano_id, $context )
			: $this->process_create_submission( $user_id, $post_id, $titulo, $tipo, $plano, $plano_id );

		if ( is_wp_error( $result ) ) {
			$this->track_flow_event( 'non_ajax_request_failed', $post_id, [ 'error' => $result->get_error_message() ] );
			GCEP_Helpers::redirect_with_message( $redirect_back, 'error', $result->get_error_message() );
		}

		if ( 'rejeitado' === ( $result['status'] ?? '' ) ) {
			$rejection_redirect = $redirect_back;
			if ( $post_id <= 0 && ! empty( $result['post_id'] ) ) {
				$rejection_redirect = home_url( '/painel/editar-anuncio?id=' . absint( $result['post_id'] ) );
			}

			GCEP_Helpers::redirect_with_message(
				$rejection_redirect,
				'error',
				(string) ( $result['justificativa'] ?? $this->get_validation_reason_fallback() )
			);
		}

		if ( 'aguardando_pagamento' === ( $result['status'] ?? '' ) && ! empty( $result['redirect'] ) ) {
			wp_safe_redirect( esc_url_raw( (string) $result['redirect'] ) );
			exit;
		}

		GCEP_Helpers::redirect_with_message(
			$list_redirect,
			'success',
			(string) ( $result['message'] ?? __( 'Anuncio atualizado com sucesso!', 'guiawp' ) )
		);
	}

	// ==================== Salvar dados do anuncio (compartilhado) ====================
	private function save_anuncio_data( int $post_id, int $user_id, string $titulo, string $tipo, string $plano, int $plano_id, array $args = [] ): int|\WP_Error {
		$is_new        = $post_id <= 0;
		$current_status = $post_id > 0 ? (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true ) : 'rascunho';
		$current_status = '' !== $current_status ? $current_status : 'rascunho';

		$post_data = [
			'post_title'  => $titulo,
			'post_type'   => 'gcep_anuncio',
			'post_status' => GCEP_Helpers::get_anuncio_post_status( $current_status ),
		];

		if ( $post_id > 0 ) {
			$post_data['ID'] = $post_id;
			wp_update_post( $post_data );
		} else {
			$post_data['post_author'] = $user_id;
			$post_id = wp_insert_post( $post_data );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( '' === (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true ) ) {
			update_post_meta( $post_id, 'GCEP_status_anuncio', $current_status );
		}

		// Meta fields de texto
		$meta_fields = [
			'GCEP_tipo_anuncio', 'GCEP_tipo_plano', 'GCEP_cnpj',
			'GCEP_descricao_curta', 'GCEP_telefone', 'GCEP_whatsapp', 'GCEP_email',
			'GCEP_cep', 'GCEP_logradouro', 'GCEP_numero', 'GCEP_complemento',
			'GCEP_bairro', 'GCEP_cidade', 'GCEP_estado',
			'GCEP_site', 'GCEP_instagram', 'GCEP_facebook', 'GCEP_linkedin',
			'GCEP_youtube', 'GCEP_x_twitter', 'GCEP_tiktok', 'GCEP_threads',
		];

		update_post_meta( $post_id, 'GCEP_tipo_anuncio', $tipo );
		update_post_meta( $post_id, 'GCEP_tipo_plano', $plano );

		if ( 'premium' === $plano && $plano_id > 0 ) {
			$plan_data = GCEP_Plans::get( $plano_id );
			if ( $plan_data ) {
				update_post_meta( $post_id, 'GCEP_plano_id', $plano_id );
				update_post_meta( $post_id, 'GCEP_vigencia_dias', (int) $plan_data['days'] );
				update_post_meta( $post_id, 'GCEP_plano_preco', $plan_data['price'] );
			}
		} elseif ( 'premium' !== $plano ) {
			delete_post_meta( $post_id, 'GCEP_plano_id' );
			delete_post_meta( $post_id, 'GCEP_vigencia_dias' );
			delete_post_meta( $post_id, 'GCEP_plano_preco' );
			$this->reset_payment_state( $post_id );
		}

		if ( 'premium' === $plano ) {
			delete_post_meta( $post_id, 'GCEP_descricao_curta' );
			if ( $is_new && '' === (string) get_post_meta( $post_id, 'GCEP_status_pagamento', true ) ) {
				$this->mark_payment_pending( $post_id );
			}
		} else {
			delete_post_meta( $post_id, 'GCEP_descricao_longa' );
		}

		foreach ( $meta_fields as $key ) {
			$form_key = strtolower( str_replace( 'GCEP_', 'gcep_', $key ) );
			if ( isset( $_POST[ $form_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $form_key ] ) );
				if ( in_array( $key, [ 'GCEP_telefone', 'GCEP_whatsapp' ], true ) ) {
					$value = GCEP_Helpers::sanitize_phone( $value );
				}
				update_post_meta( $post_id, $key, $value );
			}
		}

		// Coordenadas geograficas (latitude/longitude)
		if ( isset( $_POST['gcep_latitude'] ) ) {
			$lat = floatval( $_POST['gcep_latitude'] );
			update_post_meta( $post_id, 'GCEP_latitude', ( $lat >= -90 && $lat <= 90 ) ? $lat : '' );
		}
		if ( isset( $_POST['gcep_longitude'] ) ) {
			$lng = floatval( $_POST['gcep_longitude'] );
			update_post_meta( $post_id, 'GCEP_longitude', ( $lng >= -180 && $lng <= 180 ) ? $lng : '' );
		}

		$this->build_full_address( $post_id );

		if ( ! empty( $_POST['gcep_categoria'] ) ) {
			wp_set_object_terms( $post_id, array_map( 'intval', (array) $_POST['gcep_categoria'] ), 'gcep_categoria' );
		}

		$this->set_location_terms( $post_id );

		if ( 'premium' === $plano && isset( $_POST['gcep_descricao_longa'] ) ) {
			$descricao_longa = GCEP_AI_Validator::sanitize_description_html( (string) wp_unslash( $_POST['gcep_descricao_longa'] ) );
			update_post_meta( $post_id, 'GCEP_descricao_longa', $descricao_longa );
		}

		if ( ! empty( $_FILES['gcep_logo']['name'] ) ) {
			$this->handle_image_upload( $post_id, 'gcep_logo', 'GCEP_logo_ou_foto_principal' );
		}

		if ( ! empty( $_FILES['gcep_capa']['name'] ) ) {
			$this->handle_image_upload( $post_id, 'gcep_capa', 'GCEP_foto_capa' );
			$attachment_id = get_post_meta( $post_id, 'GCEP_foto_capa', true );
			if ( $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		if ( 'premium' === $plano ) {
			$this->save_video_gallery( $post_id );
		}

		return $post_id;
	}

	// ==================== Resolver fluxo pos-submit ====================
	private function resolve_submission_flow( int $post_id, string $plano ): array {
		$ai_enabled = GCEP_AI_Validator::is_enabled();
		$ai_result  = $ai_enabled
			? GCEP_AI_Validator::validate( $post_id )
			: [
				'approved'      => false,
				'justificativa' => '',
				'error'         => '',
			];
		$evaluation = $this->classify_ai_result( $ai_result );
		$outcome    = GCEP_Anuncio_Flow::determine_creation_outcome( $plano, $ai_enabled, $evaluation );

		$this->persist_ai_validation_state( $post_id, $evaluation );
		$this->track_flow_event(
			'create_ai_resolved',
			$post_id,
			[
				'plano'          => $plano,
				'ai_enabled'     => $ai_enabled,
				'ai_approved'    => $evaluation['approved'],
				'ai_error'       => $evaluation['error'],
				'ai_reason'      => $evaluation['reason'],
				'ai_raw'         => (string) ( $ai_result['raw_response'] ?? '' ),
				'final_outcome'  => $outcome['status'],
			]
		);

		if ( 'rejeitado' === $outcome['status'] ) {
			$this->reset_payment_state( $post_id );
			$this->set_anuncio_status( $post_id, 'rejeitado' );
			return [
				'status'        => 'rejeitado',
				'justificativa' => $outcome['justificativa'] ?: $this->get_validation_reason_fallback(),
				'post_id'       => $post_id,
				'message'       => __( 'Seu anúncio não foi aprovado pela validação automática.', 'guiawp' ),
				'trace_id'      => $this->get_trace_id(),
			];
		}

		if ( 'aguardando_pagamento' === $outcome['status'] ) {
			$this->mark_payment_pending( $post_id );
			$this->set_anuncio_status( $post_id, 'aguardando_pagamento' );
			return [
				'status'      => 'aguardando_pagamento',
				'post_id'     => $post_id,
				'redirect'    => $this->get_payment_redirect( $post_id, 'user' ),
				'message'     => ! empty( $outcome['ai_unavailable'] )
					? __( 'Anúncio salvo. A IA não retornou um parecer confiável, então ele seguirá para pagamento e revisão.', 'guiawp' )
					: __( 'Anúncio aprovado! Redirecionando para o pagamento...', 'guiawp' ),
				'trace_id'    => $this->get_trace_id(),
			];
		}

		$this->reset_payment_state( $post_id );
		$this->set_anuncio_status( $post_id, $outcome['status'] );

		if ( 'publicado' === $outcome['status'] ) {
			return [
				'status'   => 'publicado',
				'post_id'  => $post_id,
				'redirect' => home_url( '/painel/anuncios' ),
				'message'  => __( 'Anúncio aprovado e publicado com sucesso!', 'guiawp' ),
				'trace_id' => $this->get_trace_id(),
			];
		}

		return [
			'status'   => 'aguardando_aprovacao',
			'post_id'  => $post_id,
			'redirect' => home_url( '/painel/anuncios' ),
			'message'  => ! empty( $outcome['ai_unavailable'] )
				? __( 'Anúncio enviado para aprovação manual.', 'guiawp' )
				: __( 'Anúncio enviado para aprovação!', 'guiawp' ),
			'trace_id' => $this->get_trace_id(),
		];
	}

	/**
	 * Monta o endereço completo a partir dos campos separados
	 */
	private function build_full_address( int $post_id ): void {
		$logradouro  = get_post_meta( $post_id, 'GCEP_logradouro', true );
		$numero      = get_post_meta( $post_id, 'GCEP_numero', true );
		$complemento = get_post_meta( $post_id, 'GCEP_complemento', true );
		$bairro      = get_post_meta( $post_id, 'GCEP_bairro', true );
		$cidade      = get_post_meta( $post_id, 'GCEP_cidade', true );
		$estado      = get_post_meta( $post_id, 'GCEP_estado', true );
		$cep         = get_post_meta( $post_id, 'GCEP_cep', true );

		$partes = array_filter( [
			$logradouro,
			$numero ? 'nº ' . $numero : '',
			$complemento,
			$bairro,
			$cidade && $estado ? $cidade . '/' . $estado : ( $cidade ?: $estado ),
			$cep ? 'CEP ' . $cep : '',
		] );

		$endereco_completo = implode( ', ', $partes );
		update_post_meta( $post_id, 'GCEP_endereco_completo', $endereco_completo );
	}

	/**
	 * Cria/reutiliza termos de localização hierárquica (Estado > Cidade)
	 */
	private function set_location_terms( int $post_id ): void {
		$estado = sanitize_text_field( get_post_meta( $post_id, 'GCEP_estado', true ) );
		$cidade = sanitize_text_field( get_post_meta( $post_id, 'GCEP_cidade', true ) );

		if ( empty( $estado ) || empty( $cidade ) ) {
			return;
		}

		$taxonomy = 'gcep_localizacao';

		// Buscar ou criar o termo do estado
		$estado_term = term_exists( $estado, $taxonomy, 0 );
		if ( ! $estado_term ) {
			$estado_term = wp_insert_term( $estado, $taxonomy, [ 'parent' => 0 ] );
		}

		if ( is_wp_error( $estado_term ) ) {
			return;
		}

		$estado_term_id = is_array( $estado_term ) ? (int) $estado_term['term_id'] : (int) $estado_term;

		// Buscar ou criar o termo da cidade como filho do estado
		$cidade_term = term_exists( $cidade, $taxonomy, $estado_term_id );
		if ( ! $cidade_term ) {
			$cidade_term = wp_insert_term( $cidade, $taxonomy, [ 'parent' => $estado_term_id ] );
		}

		if ( is_wp_error( $cidade_term ) ) {
			return;
		}

		$cidade_term_id = is_array( $cidade_term ) ? (int) $cidade_term['term_id'] : (int) $cidade_term;

		// Atribuir ambos os termos ao anúncio
		wp_set_object_terms( $post_id, [ $estado_term_id, $cidade_term_id ], $taxonomy );
	}

	/**
	 * Upload de galeria de fotos (múltiplos arquivos)
	 */
	private function handle_gallery_upload( int $post_id ): void {
		if ( empty( $_FILES['gcep_galeria'] ) || empty( $_FILES['gcep_galeria']['name'][0] ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$ids_existentes = get_post_meta( $post_id, 'GCEP_galeria_fotos', true );
		$ids = ! empty( $ids_existentes ) ? explode( ',', $ids_existentes ) : [];

		$files = $_FILES['gcep_galeria'];
		$count = count( $files['name'] );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( empty( $files['name'][ $i ] ) ) {
				continue;
			}

			$_FILES['gcep_galeria_single'] = [
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			];

			$attachment_id = media_handle_upload( 'gcep_galeria_single', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				$ids[] = $attachment_id;
			}
		}

		// Máximo 20 fotos
		$ids = array_slice( array_unique( array_filter( $ids ) ), 0, 20 );
		update_post_meta( $post_id, 'GCEP_galeria_fotos', implode( ',', $ids ) );
	}

	/**
	 * Salva a galeria de vídeos (repeater título + URL)
	 */
	private function save_video_gallery( int $post_id ): void {
		$titulos = $_POST['gcep_video_titulo'] ?? [];
		$urls    = $_POST['gcep_video_url'] ?? [];

		if ( ! is_array( $titulos ) || ! is_array( $urls ) ) {
			return;
		}

		$videos = [];
		$max = min( count( $titulos ), count( $urls ), 10 );

		for ( $i = 0; $i < $max; $i++ ) {
			$url = esc_url_raw( wp_unslash( $urls[ $i ] ?? '' ) );
			if ( empty( $url ) ) {
				continue;
			}
			$videos[] = [
				'titulo' => sanitize_text_field( wp_unslash( $titulos[ $i ] ?? '' ) ),
				'url'    => $url,
			];
		}

		update_post_meta( $post_id, 'GCEP_galeria_videos', $videos );
	}

	private function handle_image_upload( int $post_id, string $file_key, string $meta_key ): void {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( $file_key, $post_id );
		if ( ! is_wp_error( $attachment_id ) ) {
			// Deletar imagem antiga para evitar orfaos
			GCEP_Image_Cleanup::replace_single_image( $post_id, $meta_key, $attachment_id );
		}
	}
}
