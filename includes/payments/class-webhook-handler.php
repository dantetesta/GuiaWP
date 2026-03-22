<?php
/**
 * Handler de webhooks de gateways de pagamento
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 * @modified 1.8.0 - 2026-03-20 - confirm_payment() desduplicado: delega para GCEP_Payment_Handler::confirm_payment()
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Webhook_Handler {

	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'gcep/v1', '/webhook/mercadopago', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_mercadopago' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * Processa webhook do Mercado Pago
	 * URL: /wp-json/gcep/v1/webhook/mercadopago
	 */
	public function handle_mercadopago( WP_REST_Request $request ): WP_REST_Response {
		$body_raw = $request->get_body();
		$headers  = $request->get_headers();

		// Normalizar headers (WP REST converte para lowercase com underscores)
		$normalized = [];
		foreach ( $headers as $key => $values ) {
			$normalized[ str_replace( '_', '-', $key ) ] = is_array( $values ) ? $values[0] : $values;
		}

		// Validar HMAC-SHA256
		$secret = GCEP_Settings::get( 'mercadopago_webhook_secret' );
		if ( empty( $secret ) ) {
			return new WP_REST_Response( [ 'erro' => 'Webhook secret nao configurado' ], 503 );
		}

		if ( ! GCEP_Gateway_MercadoPago::validar_webhook_hmac( $body_raw, $normalized ) ) {
			return new WP_REST_Response( [ 'erro' => 'Assinatura invalida' ], 401 );
		}

		$dados = json_decode( $body_raw, true );
		if ( empty( $dados ) ) {
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		$tipo = $dados['type'] ?? $dados['action'] ?? '';

		// Processar apenas eventos de pagamento
		if ( ! in_array( $tipo, [ 'payment.created', 'payment.updated', 'payment' ], true ) ) {
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		$payment_id = (string) ( $dados['data']['id'] ?? '' );
		if ( empty( $payment_id ) ) {
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		// Buscar anuncio vinculado a este payment_id
		$anuncio_id = $this->find_anuncio_by_gateway_id( $payment_id );
		if ( ! $anuncio_id ) {
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		// Idempotencia: ja pago?
		$status_atual = get_post_meta( $anuncio_id, 'GCEP_status_pagamento', true );
		if ( 'pago' === $status_atual ) {
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		// Verificar status do pagamento na API
		$gateway   = new GCEP_Gateway_MercadoPago();
		$resultado = $gateway->verificar_pagamento( $payment_id );

		if ( ! $resultado['pago'] ) {
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		// Anti-fraude: cross-check de valor
		$valor_mp = round( (float) ( $resultado['dados']['transaction_amount'] ?? 0 ), 2 );
		$valor_db = round( (float) get_post_meta( $anuncio_id, 'GCEP_plano_preco', true ), 2 );

		if ( $valor_db > 0 && abs( $valor_mp - $valor_db ) > 0.01 ) {
			error_log( sprintf(
				'[GuiaWP] Webhook fraude detectada: payment_id=%s anuncio=%d valor_mp=%.2f valor_db=%.2f',
				$payment_id, $anuncio_id, $valor_mp, $valor_db
			) );
			return new WP_REST_Response( [ 'ok' => true ], 200 );
		}

		// Delega para a lógica centralizada em GCEP_Payment_Handler
		GCEP_Payment_Handler::confirm_payment( $anuncio_id );

		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	/**
	 * Busca anuncio pelo gateway_id salvo em post_meta
	 */
	private function find_anuncio_by_gateway_id( string $gateway_id ): int {
		global $wpdb;

		$post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key = 'GCEP_gateway_payment_id' AND meta_value = %s
			 LIMIT 1",
			$gateway_id
		) );

		return (int) $post_id;
	}
}
