<?php
/**
 * Classe abstrata base para gateways de pagamento
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class GCEP_Gateway {

	protected const TIMEOUT = 15;

	/**
	 * Cria cobranca PIX
	 *
	 * @param array $dados [valor, descricao, email, nome, cpf]
	 * @return array [sucesso, gateway_id, pix_codigo, pix_qr_image, erro?]
	 */
	abstract public function criar_cobranca_pix( array $dados ): array;

	/**
	 * Verifica status de pagamento
	 *
	 * @param string $gateway_id ID do pagamento no gateway
	 * @return array [sucesso, pago, dados?]
	 */
	abstract public function verificar_pagamento( string $gateway_id ): array;

	/**
	 * Retorna o identificador do gateway
	 */
	abstract public function get_id(): string;

	/**
	 * Retorna se o gateway suporta cartao
	 */
	public function suporta_cartao(): bool {
		return false;
	}

	/**
	 * Cria cobranca via cartao (override nos gateways que suportam)
	 *
	 * @param array $dados [valor, descricao, email, nome, cpf, cartao => [numero, mes, ano, cvv, titular]]
	 * @return array [sucesso, gateway_id, aprovado, erro?]
	 */
	public function criar_cobranca_cartao( array $dados ): array {
		return [ 'sucesso' => false, 'erro' => 'Gateway nao suporta cartao.' ];
	}

	/**
	 * Faz requisicao POST via wp_remote_post
	 */
	protected function http_post( string $url, array $body, array $headers = [] ): array {
		$response = wp_remote_post( $url, [
			'timeout' => static::TIMEOUT,
			'headers' => array_merge( [ 'Content-Type' => 'application/json' ], $headers ),
			'body'    => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'sucesso' => false, 'erro' => $response->get_error_message(), 'status' => 0, 'dados' => [] ];
		}

		$status = wp_remote_retrieve_response_code( $response );
		$dados  = json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];

		return [
			'sucesso' => $status >= 200 && $status < 300,
			'status'  => $status,
			'dados'   => $dados,
		];
	}

	/**
	 * Faz requisicao GET via wp_remote_get
	 */
	protected function http_get( string $url, array $headers = [] ): array {
		$response = wp_remote_get( $url, [
			'timeout' => static::TIMEOUT,
			'headers' => array_merge( [ 'Content-Type' => 'application/json' ], $headers ),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'sucesso' => false, 'erro' => $response->get_error_message(), 'status' => 0, 'dados' => [] ];
		}

		$status = wp_remote_retrieve_response_code( $response );
		$dados  = json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];

		return [
			'sucesso' => $status >= 200 && $status < 300,
			'status'  => $status,
			'dados'   => $dados,
		];
	}

	/**
	 * Gera UUID v4 para idempotencia
	 */
	protected function uuid_v4(): string {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			wp_rand( 0, 0xffff ), wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0x0fff ) | 0x4000,
			wp_rand( 0, 0x3fff ) | 0x8000,
			wp_rand( 0, 0xffff ), wp_rand( 0, 0xffff ), wp_rand( 0, 0xffff )
		);
	}

	/**
	 * Retorna instancia do gateway ativo conforme configuracao
	 */
	public static function get_active(): ?self {
		$gateway_id = GCEP_Settings::get( 'gateway_ativo' );

		switch ( $gateway_id ) {
			case 'mercadopago':
				return new GCEP_Gateway_MercadoPago();
			case 'pagou':
				return new GCEP_Gateway_Pagou();
			default:
				return null;
		}
	}
}
