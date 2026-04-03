<?php
/**
 * Handler de autenticação (registro/login/logout)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Auth_Handler {

	private function get_password_reset_success_message(): string {
		return __( 'Atenção: olhe seu e-mail. Enviamos um link para você alterar sua senha com segurança.', 'guiawp' );
	}

	private function send_password_reset_email( WP_User $user ) {
		$reset_key = get_password_reset_key( $user );

		if ( is_wp_error( $reset_key ) ) {
			return $reset_key;
		}

		$reset_url = network_site_url(
			'wp-login.php?action=rp&key=' . rawurlencode( $reset_key ) . '&login=' . rawurlencode( $user->user_login ),
			'login'
		);
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject   = sprintf( __( '[%s] Redefinição de senha', 'guiawp' ), $site_name );
		$message   = implode(
			"\n\n",
			[
				sprintf( __( 'Olá, %s.', 'guiawp' ), $user->display_name ?: $user->user_login ),
				__( 'Recebemos um pedido para redefinir a senha da sua conta no GuiaWP.', 'guiawp' ),
				__( 'Para criar uma nova senha, acesse o link abaixo:', 'guiawp' ),
				$reset_url,
				__( 'Se você não solicitou essa alteração, pode ignorar esta mensagem.', 'guiawp' ),
			]
		);

		if ( ! wp_mail( $user->user_email, $subject, $message ) ) {
			return new WP_Error( 'mail_failed', __( 'Não foi possível enviar o e-mail de redefinição agora. Tente novamente em instantes.', 'guiawp' ) );
		}

		return true;
	}

	private function get_rate_limit_key( string $action, string $identifier = '' ): string {
		$ip         = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
		$identifier = strtolower( trim( $identifier ) );
		return 'gcep_rl_' . md5( $action . '|' . $identifier . '|' . $ip );
	}

	private function maybe_block_rate_limit( string $action, string $identifier, int $max_attempts, int $window, string $redirect ): void {
		$count = (int) get_transient( $this->get_rate_limit_key( $action, $identifier ) );

		if ( $count >= $max_attempts ) {
			GCEP_Helpers::redirect_with_message(
				$redirect,
				'error',
				sprintf(
					/* translators: %d: minutos */
					__( 'Muitas tentativas. Aguarde %d minutos e tente novamente.', 'guiawp' ),
					max( 1, (int) ceil( $window / MINUTE_IN_SECONDS ) )
				)
			);
		}
	}

	private function hit_rate_limit( string $action, string $identifier, int $window ): void {
		$key   = $this->get_rate_limit_key( $action, $identifier );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, $window );
	}

	private function clear_rate_limit( string $action, string $identifier ): void {
		delete_transient( $this->get_rate_limit_key( $action, $identifier ) );
	}

	public function handle_register(): void {
		if ( ! isset( $_POST['gcep_register_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_register_nonce'], 'gcep_register' ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', __( 'Erro de segurança. Tente novamente.', 'guiawp' ) );
		}

		$nome     = sanitize_text_field( wp_unslash( $_POST['gcep_nome'] ?? '' ) );
		$email    = sanitize_email( wp_unslash( $_POST['gcep_email'] ?? '' ) );
		$telefone = GCEP_Helpers::sanitize_phone( wp_unslash( $_POST['gcep_telefone'] ?? '' ) );
		$senha    = $_POST['gcep_senha'] ?? '';
		$window   = 15 * MINUTE_IN_SECONDS;
		$subject  = '' !== $email ? $email : $telefone;

		$this->maybe_block_rate_limit( 'register', $subject, 8, $window, home_url( '/cadastro' ) );
		$this->hit_rate_limit( 'register', $subject, $window );

		if ( empty( $nome ) || empty( $email ) || empty( $telefone ) || empty( $senha ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', __( 'Preencha todos os campos.', 'guiawp' ) );
		}

		if ( ! is_email( $email ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', __( 'E-mail inválido.', 'guiawp' ) );
		}

		if ( email_exists( $email ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', __( 'Este e-mail já está cadastrado.', 'guiawp' ) );
		}

		if ( strlen( $senha ) < 8 ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', __( 'A senha deve ter pelo menos 8 caracteres.', 'guiawp' ) );
		}

		$captcha_check = GCEP_Auth_Captcha::verify_request( 'register', $_POST );
		if ( is_wp_error( $captcha_check ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', $captcha_check->get_error_message() );
		}

		$user_id = wp_create_user( $email, $senha, $email );

		if ( is_wp_error( $user_id ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/cadastro' ), 'error', $user_id->get_error_message() );
		}

		wp_update_user( [
			'ID'           => $user_id,
			'display_name' => $nome,
			'first_name'   => $nome,
		] );

		$user = new WP_User( $user_id );
		$user->set_role( 'gcep_anunciante' );

		update_user_meta( $user_id, 'gcep_telefone', $telefone );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		$this->clear_rate_limit( 'register', $subject );

		wp_safe_redirect( home_url( '/painel' ) );
		exit;
	}

	public function handle_login(): void {
		if ( ! isset( $_POST['gcep_login_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_login_nonce'], 'gcep_login' ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/login' ), 'error', __( 'Erro de segurança.', 'guiawp' ) );
		}

		$email = sanitize_email( wp_unslash( $_POST['gcep_email'] ?? '' ) );
		$senha = $_POST['gcep_senha'] ?? '';
		$window = 15 * MINUTE_IN_SECONDS;
		$subject = '' !== $email ? $email : 'anonymous';

		$this->maybe_block_rate_limit( 'login', $subject, 6, $window, home_url( '/login' ) );

		if ( empty( $email ) || empty( $senha ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/login' ), 'error', __( 'Preencha todos os campos.', 'guiawp' ) );
		}

		$captcha_check = GCEP_Auth_Captcha::verify_request( 'login', $_POST );
		if ( is_wp_error( $captcha_check ) ) {
			GCEP_Helpers::redirect_with_message( home_url( '/login' ), 'error', $captcha_check->get_error_message() );
		}

		$user = wp_authenticate( $email, $senha );

		if ( is_wp_error( $user ) ) {
			$this->hit_rate_limit( 'login', $subject, $window );
			GCEP_Helpers::redirect_with_message( home_url( '/login' ), 'error', __( 'E-mail ou senha incorretos.', 'guiawp' ) );
		}

		$this->clear_rate_limit( 'login', $subject );
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url( '/painel-admin' ) );
		} else {
			wp_safe_redirect( home_url( '/painel' ) );
		}
		exit;
	}

	public function handle_logout(): void {
		if ( ! isset( $_POST['gcep_logout_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_logout_nonce'], 'gcep_logout' ) ) {
			// Fallback GET com nonce na URL (links de logout)
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'gcep_logout' ) ) {
				wp_die( __( 'Erro de segurança.', 'guiawp' ) );
			}
		}
		wp_logout();
		wp_safe_redirect( home_url() );
		exit;
	}

	public function ajax_request_password_reset(): void {
		check_ajax_referer( 'gcep_forgot_password', 'nonce' );

		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$window  = 15 * MINUTE_IN_SECONDS;
		$subject = '' !== $email ? $email : 'anonymous';
		$count   = (int) get_transient( $this->get_rate_limit_key( 'forgot_password', $subject ) );

		if ( $count >= 5 ) {
			wp_send_json_error( [
				'message' => sprintf(
					__( 'Muitas tentativas. Aguarde %d minutos e tente novamente.', 'guiawp' ),
					max( 1, (int) ceil( $window / MINUTE_IN_SECONDS ) )
				),
			], 429 );
		}

		$this->hit_rate_limit( 'forgot_password', $subject, $window );

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( [
				'message' => __( 'Digite um e-mail válido para continuar.', 'guiawp' ),
			], 400 );
		}

		$captcha_check = GCEP_Auth_Captcha::verify_request( 'reset', $_POST );
		if ( is_wp_error( $captcha_check ) ) {
			wp_send_json_error( [
				'message' => $captcha_check->get_error_message(),
			], 400 );
		}

		$user = get_user_by( 'email', $email );

		if ( $user instanceof WP_User ) {
			$result = $this->send_password_reset_email( $user );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( [
					'message' => $result->get_error_message(),
				], 500 );
			}
		}

		wp_send_json_success( [
			'message' => $this->get_password_reset_success_message(),
		] );
	}
}
