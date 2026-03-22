<?php
/**
 * Lógica pura do fluxo de anúncios.
 *
 * Mantém as decisões de criação/edição desacopladas do WordPress para
 * facilitar testes e diagnósticos.
 *
 * @package GuiaWP
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'GCEP_ALLOW_STANDALONE_TESTS' ) ) {
	exit;
}

class GCEP_Anuncio_Flow {

	public static function classify_ai_result( array $ai_result, callable $is_generic_reason, string $fallback_reason ): array {
		$approved = ! empty( $ai_result['approved'] );
		$error    = trim( (string) ( $ai_result['error'] ?? '' ) );
		$reason   = trim( (string) ( $ai_result['justificativa'] ?? '' ) );

		if ( '' === $reason ) {
			$reason = $fallback_reason;
		}

		$has_blocking_rejection = ! $approved && '' === $error && ! $is_generic_reason( $reason );
		$should_bypass_blocking = '' !== $error || ( ! $approved && ! $has_blocking_rejection );

		return [
			'approved'                => $approved,
			'error'                   => $error,
			'reason'                  => $reason,
			'has_blocking_rejection'  => $has_blocking_rejection,
			'should_bypass_blocking'  => $should_bypass_blocking,
		];
	}

	public static function determine_edit_success_status( string $current_status, string $payment_status, string $plano, bool $is_upgrade ): string {
		$current_status = self::normalize_anuncio_status( $current_status );
		$payment_status = self::normalize_payment_status( $payment_status );
		$plano          = self::normalize_plano( $plano );

		if ( $is_upgrade ) {
			return 'aguardando_pagamento';
		}

		if ( 'premium' === $plano ) {
			if ( 'expirado' === $current_status ) {
				return 'expirado';
			}

			if ( 'pendente' === $payment_status || 'aguardando_pagamento' === $current_status ) {
				return 'aguardando_pagamento';
			}

			return 'publicado';
		}

		return 'publicado';
	}

	public static function determine_creation_outcome( string $plano, bool $ai_enabled, array $evaluation ): array {
		$plano = self::normalize_plano( $plano );

		if ( 'premium' === $plano ) {
			if ( ! empty( $evaluation['has_blocking_rejection'] ) ) {
				return [
					'status'         => 'rejeitado',
					'justificativa'  => (string) ( $evaluation['reason'] ?? '' ),
					'ai_unavailable' => false,
				];
			}

			return [
				'status'         => 'aguardando_pagamento',
				'justificativa'  => '',
				'ai_unavailable' => $ai_enabled && ! empty( $evaluation['should_bypass_blocking'] ) && empty( $evaluation['approved'] ),
			];
		}

		if ( ! $ai_enabled || ! empty( $evaluation['error'] ) ) {
			return [
				'status'         => 'aguardando_aprovacao',
				'justificativa'  => '',
				'ai_unavailable' => true,
			];
		}

		if ( ! empty( $evaluation['approved'] ) ) {
			return [
				'status'         => 'publicado',
				'justificativa'  => '',
				'ai_unavailable' => false,
			];
		}

		if ( ! empty( $evaluation['has_blocking_rejection'] ) ) {
			return [
				'status'         => 'rejeitado',
				'justificativa'  => (string) ( $evaluation['reason'] ?? '' ),
				'ai_unavailable' => false,
			];
		}

		return [
			'status'         => 'aguardando_aprovacao',
			'justificativa'  => '',
			'ai_unavailable' => true,
		];
	}

	private static function normalize_plano( string $plano ): string {
		$plano = strtolower( trim( $plano ) );
		return 'premium' === $plano ? 'premium' : 'gratis';
	}

	private static function normalize_anuncio_status( string $status ): string {
		$allowed = [
			'rascunho',
			'aguardando_pagamento',
			'aguardando_aprovacao',
			'publicado',
			'rejeitado',
			'expirado',
		];

		$status = strtolower( trim( $status ) );
		return in_array( $status, $allowed, true ) ? $status : 'rascunho';
	}

	private static function normalize_payment_status( string $status ): string {
		$allowed = [ 'pendente', 'pago' ];

		$status = strtolower( trim( $status ) );
		return in_array( $status, $allowed, true ) ? $status : '';
	}
}
