<?php
/**
 * Exclusao de conta e anuncios com limpeza completa de midias
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.5.8 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Account_Deletion {

	// Transient prefix para codigo de confirmacao
	private const CODE_TRANSIENT_PREFIX = 'gcep_delete_code_';

	// Validade do codigo em segundos (10 minutos)
	private const CODE_EXPIRY = 600;

	public function init(): void {
		// AJAX: deletar anuncio (dono ou admin)
		add_action( 'wp_ajax_gcep_delete_anuncio', [ $this, 'ajax_delete_anuncio' ] );

		// AJAX: solicitar codigo de exclusao de conta
		add_action( 'wp_ajax_gcep_request_delete_code', [ $this, 'ajax_request_delete_code' ] );

		// AJAX: confirmar exclusao de conta
		add_action( 'wp_ajax_gcep_confirm_delete_account', [ $this, 'ajax_confirm_delete_account' ] );
	}

	/**
	 * AJAX: Deletar um anuncio individual (dono ou admin)
	 */
	public function ajax_delete_anuncio(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Sessao expirada.', 'guiawp' ) ] );
		}

		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Erro de seguranca.', 'guiawp' ) ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Anuncio invalido.', 'guiawp' ) ] );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'gcep_anuncio' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Anuncio nao encontrado.', 'guiawp' ) ] );
		}

		$user_id  = get_current_user_id();
		$is_owner = ( (int) $post->post_author === $user_id );
		$is_admin = current_user_can( 'manage_options' );

		if ( ! $is_owner && ! $is_admin ) {
			wp_send_json_error( [ 'message' => __( 'Voce nao tem permissao para remover este anuncio.', 'guiawp' ) ] );
		}

		// Limpar midias e deletar o post
		self::delete_anuncio_with_media( $post_id );

		wp_send_json_success( [
			'message' => __( 'Anuncio removido com sucesso.', 'guiawp' ),
			'post_id' => $post_id,
		] );
	}

	/**
	 * AJAX: Solicitar codigo de exclusao de conta por email
	 * Requer senha correta antes de enviar o codigo
	 */
	public function ajax_request_delete_code(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Sessao expirada.', 'guiawp' ) ] );
		}

		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Erro de seguranca.', 'guiawp' ) ] );
		}

		$password = (string) ( $_POST['password'] ?? '' );
		if ( '' === $password ) {
			wp_send_json_error( [ 'message' => __( 'Informe sua senha.', 'guiawp' ) ] );
		}

		$user = wp_get_current_user();

		// Validar senha
		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			wp_send_json_error( [ 'message' => __( 'Senha incorreta.', 'guiawp' ) ] );
		}

		// Gerar codigo de 6 digitos
		$code = str_pad( (string) wp_rand( 100000, 999999 ), 6, '0', STR_PAD_LEFT );

		// Salvar no transient (10 min)
		set_transient(
			self::CODE_TRANSIENT_PREFIX . $user->ID,
			wp_hash( $code ),
			self::CODE_EXPIRY
		);

		// Enviar email
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject   = sprintf( __( '[%s] Codigo de confirmacao para exclusao de conta', 'guiawp' ), $site_name );
		$body      = implode( "\n\n", [
			sprintf( __( 'Ola, %s.', 'guiawp' ), $user->display_name ?: $user->user_login ),
			__( 'Voce solicitou a exclusao permanente da sua conta.', 'guiawp' ),
			sprintf( __( 'Seu codigo de confirmacao: %s', 'guiawp' ), $code ),
			__( 'Este codigo expira em 10 minutos.', 'guiawp' ),
			__( 'Se voce nao solicitou esta acao, ignore este email. Sua conta permanecera segura.', 'guiawp' ),
		] );

		$sent = wp_mail( $user->user_email, $subject, $body );

		if ( ! $sent ) {
			delete_transient( self::CODE_TRANSIENT_PREFIX . $user->ID );
			wp_send_json_error( [ 'message' => __( 'Nao foi possivel enviar o email. Verifique a configuracao de SMTP.', 'guiawp' ) ] );
		}

		// Mascarar email para exibicao
		$email_parts  = explode( '@', $user->user_email );
		$local        = $email_parts[0];
		$domain       = $email_parts[1] ?? '';
		$masked_local = substr( $local, 0, 2 ) . str_repeat( '*', max( 1, strlen( $local ) - 2 ) );
		$masked_email = $masked_local . '@' . $domain;

		wp_send_json_success( [
			'message' => sprintf(
				__( 'Codigo enviado para %s. Valido por 10 minutos.', 'guiawp' ),
				$masked_email
			),
		] );
	}

	/**
	 * AJAX: Confirmar exclusao de conta com codigo
	 */
	public function ajax_confirm_delete_account(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Sessao expirada.', 'guiawp' ) ] );
		}

		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Erro de seguranca.', 'guiawp' ) ] );
		}

		$user = wp_get_current_user();
		$code = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );

		if ( '' === $code ) {
			wp_send_json_error( [ 'message' => __( 'Informe o codigo recebido por email.', 'guiawp' ) ] );
		}

		// Nao permitir exclusao de administradores
		if ( current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Contas de administrador nao podem ser excluidas por aqui.', 'guiawp' ) ] );
		}

		// Verificar codigo
		$stored_hash = get_transient( self::CODE_TRANSIENT_PREFIX . $user->ID );
		if ( false === $stored_hash ) {
			wp_send_json_error( [ 'message' => __( 'Codigo expirado. Solicite um novo codigo.', 'guiawp' ) ] );
		}

		if ( ! hash_equals( $stored_hash, wp_hash( $code ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Codigo incorreto.', 'guiawp' ) ] );
		}

		// Codigo valido — limpar transient
		delete_transient( self::CODE_TRANSIENT_PREFIX . $user->ID );

		// Executar exclusao completa
		$user_id = $user->ID;
		self::delete_user_completely( $user_id );

		wp_send_json_success( [
			'message'  => __( 'Conta excluida com sucesso. Voce sera redirecionado.', 'guiawp' ),
			'redirect' => home_url( '/' ),
		] );
	}

	/**
	 * Remove um anuncio e todas as suas midias
	 */
	public static function delete_anuncio_with_media( int $post_id ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		// O hook before_delete_post do GCEP_Image_Cleanup ja cuida das midias
		// Forcar wp_delete_post que dispara os hooks
		wp_delete_post( $post_id, true );
	}

	/**
	 * Remove completamente um usuario e todos os seus dados
	 * - Todos os anuncios + midias
	 * - Metadados do usuario
	 * - Analytics vinculados
	 * - Conta WP
	 */
	public static function delete_user_completely( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		// Buscar todos os anuncios do usuario (qualquer status)
		$anuncios = get_posts( [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => 'any',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		// Deletar cada anuncio (dispara hooks de cleanup de midias)
		foreach ( $anuncios as $anuncio_id ) {
			wp_delete_post( $anuncio_id, true );
		}

		// Limpar dados de analytics do usuario
		self::cleanup_user_analytics( $user_id );

		// Deslogar o usuario antes de deletar
		wp_logout();

		// Deletar a conta WP (sem reassign de conteudo)
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
	}

	/**
	 * Limpar registros de analytics vinculados ao usuario
	 */
	private static function cleanup_user_analytics( int $user_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'gcep_analytics';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$wpdb->delete( $table, [ 'user_id' => $user_id ], [ '%d' ] );
		}
	}
}
