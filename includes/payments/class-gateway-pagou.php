<?php
/**
 * Gateway Pagou.com.br — Apenas PIX
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Gateway_Pagou extends GCEP_Gateway {

	private const API_URL         = 'https://api.pagou.com.br';
	private const PIX_EXPIRY_SEC  = 3600;

	public function get_id(): string {
		return 'pagou';
	}

	public function suporta_cartao(): bool {
		return false;
	}

	private function api_key(): string {
		return GCEP_Settings::get( 'pagou_api_key' );
	}

	private function auth_headers(): array {
		return [
			'X-API-KEY'    => $this->api_key(),
			'Content-Type' => 'application/json',
			'User-Agent'   => 'GuiaWP/' . GCEP_VERSION,
		];
	}

	/* ── PIX ─────────────────────────────────────────────── */

	public function criar_cobranca_pix( array $dados ): array {
		$cpf_limpo = preg_replace( '/\D/', '', $dados['cpf'] ?? '' );

		if ( 11 !== strlen( $cpf_limpo ) ) {
			return [ 'sucesso' => false, 'erro' => 'CPF deve ter 11 digitos.' ];
		}

		$payload = [
			'amount'      => round( (float) $dados['valor'], 2 ),
			'description' => sanitize_text_field( $dados['descricao'] ?? 'Anuncio Premium' ),
			'expiration'  => self::PIX_EXPIRY_SEC,
			'payer'       => [
				'name'     => sanitize_text_field( $dados['nome'] ?? '' ),
				'document' => $cpf_limpo,
			],
		];

		$response = $this->http_post( self::API_URL . '/v1/pix', $payload, $this->auth_headers() );

		if ( ! $response['sucesso'] ) {
			$erro = $response['dados']['message'] ?? 'Erro ao criar PIX na Pagou.';
			return [ 'sucesso' => false, 'erro' => $erro ];
		}

		$body = $response['dados'];

		// Pagou retorna base64 puro, sem prefixo
		$qr_base64 = $body['payload']['image'] ?? '';
		$qr_image  = ! empty( $qr_base64 ) ? 'data:image/png;base64,' . $qr_base64 : '';

		return [
			'sucesso'      => true,
			'gateway_id'   => (string) ( $body['id'] ?? '' ),
			'pix_codigo'   => $body['payload']['data'] ?? '',
			'pix_qr_image' => $qr_image,
		];
	}

	/* ── VERIFICAR STATUS ────────────────────────────────── */

	public function verificar_pagamento( string $gateway_id ): array {
		$headers = [
			'X-API-KEY'    => $this->api_key(),
			'Content-Type' => 'application/json',
		];

		$response = $this->http_get( self::API_URL . '/v1/pix/' . $gateway_id, $headers );

		if ( ! $response['sucesso'] ) {
			return [ 'sucesso' => false, 'pago' => false ];
		}

		$body = $response['dados'];

		// Regra de ouro da Pagou: verificar paid_at, nao apenas status
		$pago = ! empty( $body['paid_at'] );

		return [
			'sucesso' => true,
			'pago'    => $pago,
			'dados'   => $body,
		];
	}
}
