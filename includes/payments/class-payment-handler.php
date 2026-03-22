<?php
/**
 * Handler de pagamento — manual + gateways automaticos
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.3.0 - 2026-03-11 - Integracao Mercado Pago e Pagou via AJAX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Payment_Handler {

	/* ── Confirmacao manual (PIX estatico / comprovante) ── */

	public function handle_confirm(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		}

		if ( ! isset( $_POST['gcep_payment_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_payment_nonce'], 'gcep_confirm_payment' ) ) {
			wp_die( __( 'Erro de segurança.', 'guiawp' ) );
		}

		$anuncio_id = intval( $_POST['anuncio_id'] ?? 0 );
		$user_id    = get_current_user_id();

		if ( $anuncio_id <= 0 ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/anuncios' ), 'error', __( 'Anúncio não encontrado.', 'guiawp' ) );
		}

		$post = get_post( $anuncio_id );
		if ( ! $post || (int) $post->post_author !== $user_id ) {
			GCEP_Helpers::redirect_with_message( home_url( '/painel/anuncios' ), 'error', __( 'Permissão negada.', 'guiawp' ) );
		}

		self::confirm_payment( $anuncio_id );

		GCEP_Helpers::redirect_with_message(
			home_url( '/painel/anuncios' ),
			'success',
			__( 'Pagamento informado! Seu anúncio será verificado em breve.', 'guiawp' )
		);
	}

	/* ── AJAX: Criar cobranca via gateway ──────────────── */

	public function ajax_criar_cobranca(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Acesso negado.' ] );
		}
		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Erro de seguranca.' ] );
		}

		$anuncio_id = absint( $_POST['anuncio_id'] ?? 0 );
		$metodo     = sanitize_text_field( $_POST['metodo'] ?? 'pix' );
		$cpf        = sanitize_text_field( $_POST['cpf'] ?? '' );

		if ( $anuncio_id <= 0 || empty( $cpf ) ) {
			wp_send_json_error( [ 'message' => 'Dados incompletos.' ] );
		}

		$post = get_post( $anuncio_id );
		if ( ! $post || ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( [ 'message' => 'Permissao negada.' ] );
		}

		$gateway = GCEP_Gateway::get_active();
		if ( ! $gateway ) {
			wp_send_json_error( [ 'message' => 'Nenhum gateway de pagamento ativo.' ] );
		}

		$user  = wp_get_current_user();
		$preco = (float) get_post_meta( $anuncio_id, 'GCEP_plano_preco', true );
		if ( $preco <= 0 ) {
			wp_send_json_error( [ 'message' => 'Preco do plano invalido.' ] );
		}

		$dados_base = [
			'valor'     => $preco,
			'descricao' => sprintf( 'Anuncio Premium #%d - %s', $anuncio_id, get_the_title( $anuncio_id ) ),
			'email'     => $user->user_email,
			'nome'      => $user->display_name,
			'cpf'       => $cpf,
		];

		if ( 'pix' === $metodo ) {
			$resultado = $gateway->criar_cobranca_pix( $dados_base );
		} elseif ( in_array( $metodo, [ 'credit_card', 'debit_card' ], true ) ) {
			if ( ! $gateway->suporta_cartao() ) {
				wp_send_json_error( [ 'message' => 'Gateway ativo nao suporta cartao.' ] );
			}
			$dados_base['cartao'] = [
				'numero'  => sanitize_text_field( $_POST['cartao_numero'] ?? '' ),
				'mes'     => sanitize_text_field( $_POST['cartao_mes'] ?? '' ),
				'ano'     => sanitize_text_field( $_POST['cartao_ano'] ?? '' ),
				'cvv'     => sanitize_text_field( $_POST['cartao_cvv'] ?? '' ),
				'titular' => sanitize_text_field( $_POST['cartao_titular'] ?? '' ),
			];
			$resultado = $gateway->criar_cobranca_cartao( $dados_base );
		} else {
			wp_send_json_error( [ 'message' => 'Metodo de pagamento invalido.' ] );
			return;
		}

		if ( empty( $resultado['sucesso'] ) ) {
			wp_send_json_error( [ 'message' => $resultado['erro'] ?? 'Erro ao criar cobranca.' ] );
		}

		// Salvar gateway_id no post meta para polling e webhook
		$gateway_id = $resultado['gateway_id'] ?? '';
		if ( $gateway_id ) {
			update_post_meta( $anuncio_id, 'GCEP_gateway_payment_id', $gateway_id );
			update_post_meta( $anuncio_id, 'GCEP_gateway_nome', $gateway->get_id() );
			update_post_meta( $anuncio_id, 'GCEP_gateway_metodo', $metodo );
		}

		// Cartao aprovado na hora? Confirmar pagamento
		if ( in_array( $metodo, [ 'credit_card', 'debit_card' ], true ) && ! empty( $resultado['aprovado'] ) ) {
			self::confirm_payment( $anuncio_id );
		}

		wp_send_json_success( $resultado );
	}

	/* ── AJAX: Verificar status (polling) ──────────────── */

	public function ajax_verificar_pagamento(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Acesso negado.' ] );
		}
		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Erro de seguranca.' ] );
		}

		$anuncio_id = absint( $_POST['anuncio_id'] ?? 0 );
		if ( $anuncio_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Anuncio invalido.' ] );
		}

		// Ja confirmado localmente?
		$status_pag = get_post_meta( $anuncio_id, 'GCEP_status_pagamento', true );
		if ( 'pago' === $status_pag ) {
			wp_send_json_success( [ 'pago' => true ] );
		}

		$gateway_id = get_post_meta( $anuncio_id, 'GCEP_gateway_payment_id', true );
		if ( empty( $gateway_id ) ) {
			wp_send_json_success( [ 'pago' => false ] );
		}

		$gateway = GCEP_Gateway::get_active();
		if ( ! $gateway ) {
			wp_send_json_success( [ 'pago' => false ] );
		}

		$resultado = $gateway->verificar_pagamento( $gateway_id );

		if ( ! empty( $resultado['pago'] ) ) {
			// Anti-fraude: cross-check de valor (Mercado Pago)
			if ( isset( $resultado['dados']['transaction_amount'] ) ) {
				$valor_gw = round( (float) $resultado['dados']['transaction_amount'], 2 );
				$valor_db = round( (float) get_post_meta( $anuncio_id, 'GCEP_plano_preco', true ), 2 );
				if ( $valor_db > 0 && abs( $valor_gw - $valor_db ) > 0.01 ) {
					error_log( sprintf(
						'[GuiaWP] Polling fraude: anuncio=%d valor_gw=%.2f valor_db=%.2f',
						$anuncio_id, $valor_gw, $valor_db
					) );
					wp_send_json_success( [ 'pago' => false ] );
				}
			}

			self::confirm_payment( $anuncio_id );
			wp_send_json_success( [ 'pago' => true ] );
		}

		wp_send_json_success( [ 'pago' => false ] );
	}

	/* ── Confirmar pagamento (logica centralizada) ─────── */

	public static function confirm_payment( int $anuncio_id ): void {
		update_post_meta( $anuncio_id, 'GCEP_pagamento_confirmado_em', current_time( 'mysql' ) );
		update_post_meta( $anuncio_id, 'GCEP_status_pagamento', 'pago' );

		$plano = get_post_meta( $anuncio_id, 'GCEP_tipo_plano', true );
		$ai_ok = get_post_meta( $anuncio_id, 'GCEP_ai_validado', true );

		if ( 'premium' === $plano ) {
			if ( '1' === $ai_ok || ! GCEP_AI_Validator::is_enabled() ) {
				$days = (int) get_post_meta( $anuncio_id, 'GCEP_vigencia_dias', true );
				if ( $days > 0 ) {
					GCEP_Expiration::set_expiration( $anuncio_id, $days );
				}
				update_post_meta( $anuncio_id, 'GCEP_status_anuncio', 'publicado' );
				GCEP_Helpers::sync_anuncio_post_status( $anuncio_id, 'publicado' );
			} else {
				update_post_meta( $anuncio_id, 'GCEP_status_anuncio', 'aguardando_aprovacao' );
				GCEP_Helpers::sync_anuncio_post_status( $anuncio_id, 'aguardando_aprovacao' );
			}
		} else {
			update_post_meta( $anuncio_id, 'GCEP_status_anuncio', 'aguardando_aprovacao' );
			GCEP_Helpers::sync_anuncio_post_status( $anuncio_id, 'aguardando_aprovacao' );
		}
	}

	/* ── Dados de pagamento manual ─────────────────────── */

	public static function get_payment_info(): array {
		return [
			'chave_pix'      => GCEP_Settings::get( 'chave_pix' ),
			'nome_recebedor' => GCEP_Settings::get( 'nome_recebedor' ),
			'cidade'         => GCEP_Settings::get( 'cidade_recebedor' ),
			'link_pagamento' => GCEP_Settings::get( 'link_pagamento' ),
			'instrucoes'     => GCEP_Settings::get( 'texto_instrucoes_pagamento' ),
			'whatsapp'       => GCEP_Settings::get( 'whatsapp_comprovante' ),
			'prazo'          => GCEP_Settings::get( 'prazo_aprovacao_horas', '24' ),
		];
	}
}
