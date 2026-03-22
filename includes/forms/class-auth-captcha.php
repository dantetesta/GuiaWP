<?php
/**
 * Captcha dos formulários públicos de autenticação.
 *
 * @package GuiaWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Auth_Captcha {

	public static function get_provider(): string {
		$provider = sanitize_key( (string) GCEP_Settings::get( 'auth_captcha_provider', 'none' ) );
		$allowed  = [ 'none', 'google_v3', 'turnstile', 'math' ];

		return in_array( $provider, $allowed, true ) ? $provider : 'none';
	}

	public static function get_active_provider(): string {
		$provider = self::get_provider();

		if ( 'google_v3' === $provider ) {
			if ( '' === self::get_google_site_key() || '' === self::get_google_secret_key() ) {
				return 'none';
			}
		}

		if ( 'turnstile' === $provider ) {
			if ( '' === self::get_turnstile_site_key() || '' === self::get_turnstile_secret_key() ) {
				return 'none';
			}
		}

		return $provider;
	}

	public static function get_context_config( string $context ): array {
		$provider = self::get_active_provider();
		$context  = sanitize_key( $context );
		$config   = [
			'provider' => $provider,
			'action'   => 'gcep_' . $context,
			'math'     => null,
		];

		if ( 'google_v3' === $provider ) {
			$config['site_key']  = self::get_google_site_key();
			$config['threshold'] = self::get_google_min_score();
		}

		if ( 'turnstile' === $provider ) {
			$config['site_key'] = self::get_turnstile_site_key();
		}

		if ( 'math' === $provider ) {
			$config['math'] = self::create_math_challenge( $context );
		}

		return $config;
	}

	public static function verify_request( string $context, array $source ) {
		$provider = self::get_active_provider();
		$context  = sanitize_key( $context );

		if ( 'none' === $provider ) {
			return true;
		}

		if ( 'google_v3' === $provider ) {
			$token = sanitize_text_field( wp_unslash( $source['gcep_recaptcha_token'] ?? '' ) );
			return self::verify_google_v3( $context, $token );
		}

		if ( 'turnstile' === $provider ) {
			$token = sanitize_text_field( wp_unslash( $source['cf-turnstile-response'] ?? '' ) );
			return self::verify_turnstile( $context, $token );
		}

		if ( 'math' === $provider ) {
			$challenge_id = sanitize_text_field( wp_unslash( $source['gcep_math_challenge_id'] ?? '' ) );
			$answer       = sanitize_text_field( wp_unslash( $source['gcep_math_answer'] ?? '' ) );
			return self::verify_math( $context, $challenge_id, $answer );
		}

		return true;
	}

	public static function get_google_site_key(): string {
		return trim( (string) GCEP_Settings::get( 'auth_google_site_key', '' ) );
	}

	public static function get_google_min_score(): float {
		$score = (float) GCEP_Settings::get( 'auth_google_min_score', '0.5' );
		return min( 0.9, max( 0.1, $score ) );
	}

	public static function get_turnstile_site_key(): string {
		return trim( (string) GCEP_Settings::get( 'auth_turnstile_site_key', '' ) );
	}

	private static function get_google_secret_key(): string {
		return trim( (string) GCEP_Settings::get( 'auth_google_secret_key', '' ) );
	}

	private static function get_turnstile_secret_key(): string {
		return trim( (string) GCEP_Settings::get( 'auth_turnstile_secret_key', '' ) );
	}

	private static function create_math_challenge( string $context ): array {
		$left     = random_int( 2, 9 );
		$right    = random_int( 1, 8 );
		$operator = random_int( 0, 1 ) ? '+' : '-';

		if ( '-' === $operator && $right > $left ) {
			$temp  = $left;
			$left  = $right;
			$right = $temp;
		}

		$answer = '+' === $operator ? $left + $right : $left - $right;
		$id     = wp_generate_password( 20, false, false );

		set_transient(
			self::get_math_transient_key( $id ),
			[
				'context' => sanitize_key( $context ),
				'answer'  => (string) $answer,
			],
			30 * MINUTE_IN_SECONDS
		);

		return [
			'id'       => $id,
			'question' => sprintf(
				/* translators: 1: numero 1, 2: operador, 3: numero 2 */
				__( 'Quanto é %1$d %2$s %3$d?', 'guiawp' ),
				$left,
				$operator,
				$right
			),
		];
	}

	private static function verify_math( string $context, string $challenge_id, string $answer ) {
		if ( '' === $challenge_id || '' === $answer ) {
			return new WP_Error( 'captcha_missing', __( 'Resolva a verificação matemática para continuar.', 'guiawp' ) );
		}

		$challenge = get_transient( self::get_math_transient_key( $challenge_id ) );

		if ( ! is_array( $challenge ) || empty( $challenge['answer'] ) ) {
			return new WP_Error( 'captcha_expired', __( 'A verificação expirou. Atualize a página e tente novamente.', 'guiawp' ) );
		}

		if ( sanitize_key( (string) ( $challenge['context'] ?? '' ) ) !== sanitize_key( $context ) ) {
			return new WP_Error( 'captcha_invalid', __( 'Não foi possível validar a proteção do formulário.', 'guiawp' ) );
		}

		if ( trim( $answer ) !== (string) $challenge['answer'] ) {
			return new WP_Error( 'captcha_wrong', __( 'O resultado da conta está incorreto.', 'guiawp' ) );
		}

		delete_transient( self::get_math_transient_key( $challenge_id ) );

		return true;
	}

	private static function verify_google_v3( string $context, string $token ) {
		if ( '' === $token ) {
			return new WP_Error( 'captcha_missing', __( 'Não foi possível validar o reCAPTCHA. Tente novamente.', 'guiawp' ) );
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			[
				'timeout' => 10,
				'body'    => [
					'secret'   => self::get_google_secret_key(),
					'response' => $token,
					'remoteip' => self::get_remote_ip(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'captcha_request_failed', __( 'Não foi possível validar o reCAPTCHA agora. Tente novamente.', 'guiawp' ) );
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['success'] ) ) {
			return new WP_Error( 'captcha_failed', __( 'A validação automática não foi concluída. Tente novamente.', 'guiawp' ) );
		}

		$expected_action = 'gcep_' . sanitize_key( $context );
		$action          = sanitize_key( (string) ( $body['action'] ?? '' ) );
		$score           = isset( $body['score'] ) ? (float) $body['score'] : 0.0;

		if ( '' !== $action && $expected_action !== $action ) {
			return new WP_Error( 'captcha_action_invalid', __( 'A validação automática retornou uma ação inválida.', 'guiawp' ) );
		}

		if ( $score < self::get_google_min_score() ) {
			return new WP_Error( 'captcha_score_low', __( 'A validação automática considerou esta tentativa suspeita. Tente novamente.', 'guiawp' ) );
		}

		return true;
	}

	private static function verify_turnstile( string $context, string $token ) {
		if ( '' === $token ) {
			return new WP_Error( 'captcha_missing', __( 'Conclua a validação do Turnstile para continuar.', 'guiawp' ) );
		}

		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			[
				'timeout' => 10,
				'body'    => [
					'secret'   => self::get_turnstile_secret_key(),
					'response' => $token,
					'remoteip' => self::get_remote_ip(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'captcha_request_failed', __( 'Não foi possível validar o Turnstile agora. Tente novamente.', 'guiawp' ) );
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['success'] ) ) {
			return new WP_Error( 'captcha_failed', __( 'A validação automática não foi concluída. Tente novamente.', 'guiawp' ) );
		}

		$expected_action = 'gcep_' . sanitize_key( $context );
		$action          = sanitize_key( (string) ( $body['action'] ?? '' ) );

		if ( '' !== $action && $expected_action !== $action ) {
			return new WP_Error( 'captcha_action_invalid', __( 'O Turnstile retornou uma ação inválida.', 'guiawp' ) );
		}

		return true;
	}

	private static function get_math_transient_key( string $challenge_id ): string {
		return 'gcep_math_captcha_' . md5( $challenge_id );
	}

	private static function get_remote_ip(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	}
}
