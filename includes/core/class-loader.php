<?php
/**
 * Carregador principal do plugin
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.7.7 - 2026-03-14 - maybe_sync_installation executa flush diferido via gcep_needs_flush apos init
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Loader {

	private static ?GCEP_Loader $instance = null;

	public static function get_instance(): GCEP_Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		$this->load_dependencies();
		$this->register_hooks();
	}

	private function load_dependencies(): void {
		$dir = GCEP_PLUGIN_DIR . 'includes/';

		require_once $dir . 'core/class-activator.php';
		require_once $dir . 'helpers/class-helpers.php';
		require_once $dir . 'cpt/class-anuncio-cpt.php';
		require_once $dir . 'taxonomies/class-categoria-taxonomy.php';
		require_once $dir . 'taxonomies/class-localizacao-taxonomy.php';
		require_once $dir . 'meta/class-anuncio-meta.php';
		require_once $dir . 'core/class-roles.php';
		require_once $dir . 'settings/class-settings.php';
		require_once $dir . 'core/class-assets.php';
		require_once $dir . 'core/class-router.php';
		require_once $dir . 'forms/class-auth-captcha.php';
		require_once $dir . 'forms/class-auth-handler.php';
		require_once $dir . 'forms/class-anuncio-flow.php';
		require_once $dir . 'media/class-image-cleanup.php';
		require_once $dir . 'forms/class-anuncio-handler.php';
		require_once $dir . 'forms/class-gallery-handler.php';
		require_once $dir . 'dashboard/class-dashboard-advertiser.php';
		require_once $dir . 'dashboard/class-dashboard-admin.php';
		require_once $dir . 'scripts/class-anuncio-custom-scripts.php';
		require_once $dir . 'payments/class-gateway.php';
		require_once $dir . 'payments/class-gateway-mercadopago.php';
		require_once $dir . 'payments/class-gateway-pagou.php';
		require_once $dir . 'payments/class-payment-handler.php';
		require_once $dir . 'payments/class-webhook-handler.php';
		require_once $dir . 'analytics/class-analytics.php';
		require_once $dir . 'plans/class-plans.php';
		require_once $dir . 'ai/class-ai-validator.php';
		require_once $dir . 'ai/class-gemini-imagen.php';
		require_once $dir . 'expiration/class-expiration.php';
		require_once $dir . 'theme/class-theme-installer.php';
		require_once $dir . 'blog/class-blog-metabox.php';
		require_once $dir . 'account/class-account-deletion.php';
		require_once $dir . 'map/class-map-handler.php';
	}

	private function register_hooks(): void {
		// CPT e taxonomias
		$anuncio_cpt = new GCEP_Anuncio_CPT();
		add_action( 'init', [ $anuncio_cpt, 'register' ] );

		$cat_tax = new GCEP_Categoria_Taxonomy();
		add_action( 'init', [ $cat_tax, 'register' ] );

		$loc_tax = new GCEP_Localizacao_Taxonomy();
		add_action( 'init', [ $loc_tax, 'register' ] );

		// Meta boxes (apenas admin)
		if ( is_admin() ) {
			$meta = new GCEP_Anuncio_Meta();
			add_action( 'add_meta_boxes', [ $meta, 'register_meta_boxes' ] );
			add_action( 'save_post_gcep_anuncio', [ $meta, 'save_meta' ] );

			$blog_metabox = new GCEP_Blog_Metabox();
			$blog_metabox->init();
		}

		// Assets
		$assets = new GCEP_Assets();
		add_action( 'wp_enqueue_scripts', [ $assets, 'enqueue_frontend' ] );

		// Rotas
		$router = new GCEP_Router();
		add_action( 'init', [ $router, 'register_routes' ] );
		add_action( 'init', [ $this, 'maybe_sync_installation' ], 20 );
		add_filter( 'template_include', [ $router, 'load_template' ] );

		// Handlers de formulário (admin_post — apenas em submissões)
		$auth = new GCEP_Auth_Handler();
		add_action( 'admin_post_nopriv_gcep_register', [ $auth, 'handle_register' ] );
		add_action( 'admin_post_nopriv_gcep_login', [ $auth, 'handle_login' ] );
		add_action( 'admin_post_gcep_logout', [ $auth, 'handle_logout' ] );

		// AJAX handlers — registrar apenas quando necessário
		if ( wp_doing_ajax() ) {
			$this->register_ajax_handlers( $auth );
		}

		// Scripts customizados de anúncios (hooks no wp_head/wp_footer)
		$anuncio_scripts = new GCEP_Anuncio_Custom_Scripts();
		$anuncio_scripts->init();

		// Dashboards (admin_post handlers para formulários)
		$dash_adv = new GCEP_Dashboard_Advertiser();
		add_action( 'init', [ $dash_adv, 'init' ] );

		$dash_admin = new GCEP_Dashboard_Admin();
		add_action( 'init', [ $dash_admin, 'init' ] );

		// Pagamentos (admin_post handler)
		$payment = new GCEP_Payment_Handler();
		add_action( 'admin_post_gcep_confirm_payment', [ $payment, 'handle_confirm' ] );

		// Webhook de gateways (REST API)
		$webhook = new GCEP_Webhook_Handler();
		$webhook->init();

		// Limpeza de imagens orfas ao deletar anuncios
		$image_cleanup = new GCEP_Image_Cleanup();
		$image_cleanup->init();

		// Expiração de anúncios
		$expiration = new GCEP_Expiration();
		$expiration->init();

		// Exclusao de conta e anuncios
		$account_deletion = new GCEP_Account_Deletion();
		$account_deletion->init();

		// Gemini Imagen (geração de imagens com IA)
		$gemini_imagen = new GCEP_Gemini_Imagen();
		$gemini_imagen->init();

		// Mapa de anuncios (nopriv handlers registrados no init do handler)
		$map_handler = new GCEP_Map_Handler();
		$map_handler->init();

		// Redirecionar anunciante para fora do wp-admin
		add_action( 'admin_init', [ $this, 'redirect_anunciante' ] );
		add_filter( 'show_admin_bar', [ $this, 'hide_admin_bar' ] );
	}

	/**
	 * Registra handlers AJAX apenas quando wp_doing_ajax() é true
	 *
	 * @author Dante Testa <https://dantetesta.com.br>
	 * @since 2.1.0 - 2026-03-29
	 */
	private function register_ajax_handlers( GCEP_Auth_Handler $auth ): void {
		// Auth
		add_action( 'wp_ajax_nopriv_gcep_request_password_reset', [ $auth, 'ajax_request_password_reset' ] );
		add_action( 'wp_ajax_gcep_request_password_reset', [ $auth, 'ajax_request_password_reset' ] );

		// Anúncios
		$anuncio_handler = new GCEP_Anuncio_Handler();
		add_action( 'admin_post_gcep_save_anuncio', [ $anuncio_handler, 'handle_save' ] );
		add_action( 'wp_ajax_gcep_submit_anuncio', [ $anuncio_handler, 'ajax_submit_anuncio' ] );
		add_action( 'wp_ajax_gcep_edit_anuncio', [ $anuncio_handler, 'ajax_edit_anuncio' ] );
		add_action( 'wp_ajax_gcep_get_validation_reason', [ $anuncio_handler, 'ajax_get_validation_reason' ] );
		add_action( 'wp_ajax_gcep_generate_descricao_ai', [ $anuncio_handler, 'ajax_generate_descricao_ai' ] );

		// Galeria
		$gallery = new GCEP_Gallery_Handler();
		add_action( 'wp_ajax_gcep_create_draft', [ $gallery, 'ajax_create_draft' ] );
		add_action( 'wp_ajax_gcep_upload_gallery_photo', [ $gallery, 'ajax_upload_gallery_photo' ] );
		add_action( 'wp_ajax_gcep_remove_gallery_photo', [ $gallery, 'ajax_remove_gallery_photo' ] );

		// Pagamentos
		$payment = new GCEP_Payment_Handler();
		add_action( 'wp_ajax_gcep_criar_cobranca', [ $payment, 'ajax_criar_cobranca' ] );
		add_action( 'wp_ajax_gcep_verificar_pagamento', [ $payment, 'ajax_verificar_pagamento' ] );
	}

	public function redirect_anunciante(): void {
		if ( wp_doing_ajax() ) {
			return;
		}
		$user = wp_get_current_user();
		if ( in_array( 'gcep_anunciante', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url( '/painel' ) );
			exit;
		}
	}

	public function maybe_sync_installation(): void {
		$installed_version = get_option( 'gcep_plugin_version', '' );

		if ( GCEP_VERSION !== $installed_version ) {
			GCEP_Activator::sync_installation();
		}

		if ( get_option( 'gcep_needs_flush' ) ) {
			flush_rewrite_rules( true );
			delete_option( 'gcep_needs_flush' );
		}
	}

	public function hide_admin_bar( $show ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		if ( in_array( 'gcep_anunciante', (array) $user->roles, true ) ) {
			return false;
		}
		return $show;
	}
}
