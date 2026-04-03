<?php
/**
 * Registro de assets front-end
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.1.0 - 2026-03-11 - Adicionados scripts ViaCEP e formulário de anúncio
 * @modified 1.8.0 - 2026-03-20 - REQUEST_URI usa apenas path (sem query string); gcep-crop.js removido de /categorias (usa crop próprio)
 * @modified 1.9.8 - 2026-03-21 - Integração intl-tel-input CDN para campos telefone/WhatsApp com bandeiras DDI
 * @modified 2.1.0 - 2026-03-29 - Adicionado gcep-focus-trap.js para acessibilidade (focus trap e Escape em modais/menus)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Assets {

	public function enqueue_frontend(): void {
		wp_enqueue_style(
			'gcep-tailwind',
			GCEP_PLUGIN_URL . 'assets/css/tailwind.css',
			[],
			GCEP_VERSION
		);

		wp_enqueue_style(
			'gcep-frontend',
			GCEP_PLUGIN_URL . 'assets/css/frontend.css',
			[ 'gcep-tailwind' ],
			GCEP_VERSION
		);

		// Focus trap — acessibilidade para modais/menus (carregado antes de todos)
		wp_enqueue_script(
			'gcep-focus-trap',
			GCEP_PLUGIN_URL . 'assets/js/gcep-focus-trap.js',
			[],
			GCEP_VERSION,
			true
		);

		// Toast global — carregado antes de todos os outros scripts
		wp_enqueue_script(
			'gcep-toast',
			GCEP_PLUGIN_URL . 'assets/js/gcep-toast.js',
			[],
			GCEP_VERSION,
			true
		);

		wp_enqueue_script(
			'gcep-frontend',
			GCEP_PLUGIN_URL . 'assets/js/frontend.js',
			[ 'gcep-toast' ],
			GCEP_VERSION,
			true
		);

		wp_localize_script( 'gcep-frontend', 'gcepData', [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'gcep_nonce' ),
			'homeUrl'  => home_url(),
			'loginUrl' => home_url( '/login' ),
			'debug'    => defined( 'WP_DEBUG' ) && WP_DEBUG,
		] );

		// Extrair apenas o path da URI (ignora query string para evitar falsos positivos)
		$uri = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );

		// Script do painel admin (status AJAX)
		if ( str_contains( $uri, 'painel-admin' ) ) {
			wp_enqueue_script(
				'gcep-admin',
				GCEP_PLUGIN_URL . 'assets/js/gcep-admin.js',
				[ 'gcep-frontend' ],
				GCEP_VERSION,
				true
			);
		}

		// Crop apenas na página de configurações (hero image)
		// /categorias usa sistema de crop próprio self-contained — gcep-crop.js não deve ser carregado lá
		if ( str_contains( $uri, 'painel-admin/configuracoes' ) ) {
			wp_enqueue_script(
				'gcep-crop',
				GCEP_PLUGIN_URL . 'assets/js/gcep-crop.js',
				[],
				GCEP_VERSION,
				true
			);
		}

		// Script da página de pagamento
		if ( str_contains( $uri, 'painel/pagamento' ) || str_contains( $uri, 'painel-admin/pagamento' ) || str_contains( $uri, 'painel/renovar' ) ) {
			wp_enqueue_script(
				'gcep-payment',
				GCEP_PLUGIN_URL . 'assets/js/gcep-payment.js',
				[ 'gcep-frontend' ],
				GCEP_VERSION,
				true
			);
		}

		// Leaflet e scripts do formulario de anuncio (criar/editar) e mapa publico
		$is_form_page = str_contains( $uri, 'painel/criar-anuncio' )
			|| str_contains( $uri, 'painel/editar-anuncio' )
			|| str_contains( $uri, 'painel-admin/editar-anuncio' );
		$is_map_page = str_contains( $uri, 'anuncios-mapa' );

		if ( $is_form_page || $is_map_page ) {
			wp_enqueue_style(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				[],
				'1.9.4'
			);
			wp_enqueue_script(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				[],
				'1.9.4',
				true
			);
		}

		if ( $is_form_page ) {
			$form_script_version = GCEP_VERSION;
			$form_script_file    = GCEP_PLUGIN_DIR . 'assets/js/gcep-multistep.js';
			if ( file_exists( $form_script_file ) ) {
				$form_script_version .= '.' . filemtime( $form_script_file );
			}

			wp_enqueue_script(
				'gcep-viacep',
				GCEP_PLUGIN_URL . 'assets/js/gcep-viacep.js',
				[ 'leaflet' ],
				GCEP_VERSION,
				true
			);
			wp_enqueue_script(
				'gcep-crop',
				GCEP_PLUGIN_URL . 'assets/js/gcep-crop.js',
				[],
				GCEP_VERSION,
				true
			);
			wp_enqueue_script(
				'gcep-multistep',
				GCEP_PLUGIN_URL . 'assets/js/gcep-multistep.js',
				[],
				$form_script_version,
				true
			);
			wp_enqueue_script(
				'gcep-gallery',
				GCEP_PLUGIN_URL . 'assets/js/gcep-gallery.js',
				[ 'gcep-frontend' ],
				GCEP_VERSION,
				true
			);
		}

		// Script do mapa público de anúncios
		if ( $is_map_page ) {
			wp_enqueue_script(
				'gcep-map',
				GCEP_PLUGIN_URL . 'assets/js/gcep-map.js',
				[ 'leaflet', 'gcep-frontend' ],
				GCEP_VERSION,
				true
			);
		}

		// intl-tel-input — bandeiras DDI nos campos telefone/WhatsApp
		$needs_intl_tel = $is_form_page
			|| str_contains( $uri, 'painel/perfil' )
			|| str_contains( $uri, 'painel-admin/configuracoes' )
			|| str_contains( $uri, 'cadastro' );

		if ( $needs_intl_tel ) {
			wp_enqueue_style(
				'intl-tel-input',
				'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/css/intlTelInput.min.css',
				[],
				'25.3.1'
			);
			wp_enqueue_script(
				'intl-tel-input',
				'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/intlTelInput.min.js',
				[],
				'25.3.1',
				true
			);
			wp_enqueue_script(
				'gcep-intl-tel',
				GCEP_PLUGIN_URL . 'assets/js/gcep-intl-tel.js',
				[ 'intl-tel-input' ],
				GCEP_VERSION,
				true
			);
		}
	}
}
