<?php
/**
 * Gateway Mercado Pago — PIX, Cartao de Credito e Debito
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Gateway_MercadoPago extends GCEP_Gateway {

	private const API_URL        = 'https://api.mercadopago.com';
	private const PIX_EXPIRY_MIN = 30;

	public function get_id(): string {
		return 'mercadopago';
	}

	public function suporta_cartao(): bool {
		return (bool) apply_filters( 'gcep_allow_server_side_card_processing', false, $this->get_id() );
	}

	private function access_token(): string {
		return GCEP_Settings::get( 'mercadopago_access_token' );
	}

	private function auth_headers(): array {
		return [
			'Authorization'    => 'Bearer ' . $this->access_token(),
			'Content-Type'     => 'application/json',
			'X-Idempotency-Key' => $this->uuid_v4(),
		];
	}

	/* ── PIX ─────────────────────────────────────────────── */

	public function criar_cobranca_pix( array $dados ): array {
		$payload = [
			'transaction_amount' => round( (float) $dados['valor'], 2 ),
			'description'        => sanitize_text_field( $dados['descricao'] ?? 'Anuncio Premium' ),
			'payment_method_id'  => 'pix',
			'date_of_expiration' => gmdate( 'Y-m-d\TH:i:s.000P', strtotime( '+' . self::PIX_EXPIRY_MIN . ' minutes' ) ),
			'payer'              => [
				'email'          => sanitize_email( $dados['email'] ),
				'first_name'     => sanitize_text_field( $dados['nome'] ?? '' ),
				'identification' => [
					'type'   => 'CPF',
					'number' => preg_replace( '/\D/', '', $dados['cpf'] ?? '' ),
				],
			],
		];

		$response = $this->http_post( self::API_URL . '/v1/payments', $payload, $this->auth_headers() );

		if ( ! $response['sucesso'] ) {
			$erro = $response['dados']['message'] ?? 'Erro ao criar cobranca PIX no Mercado Pago.';
			return [ 'sucesso' => false, 'erro' => $erro ];
		}

		$body = $response['dados'];
		$td   = $body['point_of_interaction']['transaction_data'] ?? [];

		return [
			'sucesso'      => true,
			'gateway_id'   => (string) ( $body['id'] ?? '' ),
			'pix_codigo'   => $td['qr_code'] ?? '',
			'pix_qr_image' => ! empty( $td['qr_code_base64'] ) ? 'data:image/png;base64,' . $td['qr_code_base64'] : '',
		];
	}

	/* ── CARTAO (Credito / Debito) ───────────────────────── */

	public function criar_cobranca_cartao( array $dados ): array {
		// 1. Tokenizar cartao
		$token_payload = [
			'card_number'      => preg_replace( '/\D/', '', $dados['cartao']['numero'] ),
			'expiration_month' => (int) $dados['cartao']['mes'],
			'expiration_year'  => (int) $dados['cartao']['ano'],
			'security_code'    => $dados['cartao']['cvv'],
			'cardholder'       => [
				'name'           => sanitize_text_field( strtoupper( $dados['cartao']['titular'] ?? $dados['nome'] ?? '' ) ),
				'identification' => [
					'type'   => 'CPF',
					'number' => preg_replace( '/\D/', '', $dados['cpf'] ?? '' ),
				],
			],
		];

		$token_resp = $this->http_post( self::API_URL . '/v1/card_tokens', $token_payload, $this->auth_headers() );

		if ( ! $token_resp['sucesso'] ) {
			$code = (string) ( $token_resp['dados']['cause'][0]['code'] ?? '' );
			return [ 'sucesso' => false, 'erro' => $this->map_token_error( $code ) ];
		}

		$card_token = $token_resp['dados']['id'] ?? '';
		if ( empty( $card_token ) ) {
			return [ 'sucesso' => false, 'erro' => 'Falha ao tokenizar cartao.' ];
		}

		// 2. Criar pagamento
		$payment_payload = [
			'transaction_amount' => round( (float) $dados['valor'], 2 ),
			'description'        => sanitize_text_field( $dados['descricao'] ?? 'Anuncio Premium' ),
			'installments'       => 1,
			'token'              => $card_token,
			'payer'              => [
				'email'          => sanitize_email( $dados['email'] ),
				'first_name'     => sanitize_text_field( $dados['nome'] ?? '' ),
				'identification' => [
					'type'   => 'CPF',
					'number' => preg_replace( '/\D/', '', $dados['cpf'] ?? '' ),
				],
			],
		];

		$response = $this->http_post( self::API_URL . '/v1/payments', $payment_payload, $this->auth_headers() );

		if ( ! $response['sucesso'] ) {
			$status_detail = $response['dados']['status_detail'] ?? '';
			return [ 'sucesso' => false, 'erro' => $this->map_rejection_error( $status_detail ) ];
		}

		$body   = $response['dados'];
		$status = $body['status'] ?? '';

		return [
			'sucesso'    => true,
			'gateway_id' => (string) ( $body['id'] ?? '' ),
			'aprovado'   => 'approved' === $status,
			'status'     => $status,
			'erro'       => 'rejected' === $status ? $this->map_rejection_error( $body['status_detail'] ?? '' ) : '',
		];
	}

	/* ── VERIFICAR STATUS ────────────────────────────────── */

	public function verificar_pagamento( string $gateway_id ): array {
		$headers = [
			'Authorization' => 'Bearer ' . $this->access_token(),
			'Content-Type'  => 'application/json',
		];

		$response = $this->http_get( self::API_URL . '/v1/payments/' . $gateway_id, $headers );

		if ( ! $response['sucesso'] ) {
			return [ 'sucesso' => false, 'pago' => false ];
		}

		$status = $response['dados']['status'] ?? '';

		return [
			'sucesso' => true,
			'pago'    => 'approved' === $status,
			'status'  => $status,
			'dados'   => $response['dados'],
		];
	}

	/* ── WEBHOOK HMAC ────────────────────────────────────── */

	public static function validar_webhook_hmac( string $body_raw, array $headers ): bool {
		$secret = GCEP_Settings::get( 'mercadopago_webhook_secret' );
		if ( empty( $secret ) ) {
			return false;
		}

		$x_signature  = $headers['x-signature'] ?? $headers['HTTP_X_SIGNATURE'] ?? '';
		$x_request_id = $headers['x-request-id'] ?? $headers['HTTP_X_REQUEST_ID'] ?? '';

		if ( empty( $x_signature ) ) {
			return false;
		}

		$parts = [];
		foreach ( explode( ',', $x_signature ) as $part ) {
			$pieces = explode( '=', trim( $part ), 2 );
			if ( 2 === count( $pieces ) ) {
				$parts[ trim( $pieces[0] ) ] = trim( $pieces[1] );
			}
		}

		$ts   = $parts['ts'] ?? '';
		$hash = $parts['v1'] ?? '';

		if ( empty( $ts ) || empty( $hash ) ) {
			return false;
		}

		$body    = json_decode( $body_raw, true );
		$data_id = (string) ( $body['data']['id'] ?? '' );

		$manifest = "id:{$data_id};request-id:{$x_request_id};ts:{$ts};";
		$computed = hash_hmac( 'sha256', $manifest, $secret );

		return hash_equals( $computed, $hash );
	}

	/* ── MAPAS DE ERROS ──────────────────────────────────── */

	private function map_token_error( string $code ): string {
		$map = [
			'205'  => 'Numero do cartao invalido.',
			'208'  => 'Mes de validade invalido.',
			'209'  => 'Ano de validade invalido.',
			'212'  => 'CPF invalido.',
			'224'  => 'CVV invalido.',
			'E301' => 'Numero do cartao invalido.',
			'E302' => 'CVV invalido.',
		];
		return $map[ $code ] ?? 'Erro ao processar dados do cartao.';
	}

	private function map_rejection_error( string $detail ): string {
		$map = [
			'cc_rejected_bad_filled_card_number'  => 'Numero do cartao invalido.',
			'cc_rejected_bad_filled_date'         => 'Data de validade invalida.',
			'cc_rejected_bad_filled_security_code' => 'CVV invalido.',
			'cc_rejected_insufficient_amount'     => 'Saldo insuficiente.',
			'cc_rejected_high_risk'               => 'Pagamento recusado por seguranca. Tente outro meio.',
			'cc_rejected_other_reason'            => 'Pagamento recusado. Tente outro cartao.',
			'cc_rejected_call_for_authorize'      => 'Voce precisa autorizar o pagamento junto ao banco.',
			'cc_rejected_card_disabled'           => 'Cartao desabilitado. Entre em contato com o banco.',
			'cc_rejected_max_attempts'            => 'Limite de tentativas excedido. Tente outro cartao.',
		];
		return $map[ $detail ] ?? 'Pagamento recusado. Tente novamente.';
	}
}
