<?php
/**
 * Configurações do plugin
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.6.0 - 2026-03-14 - Suporte a provedor Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Settings {

	/** Cache estático — evita desserializar a option em cada chamada */
	private static ?array $cache = null;

	private static array $defaults = [
		'nome_guia'           => 'GuiaWP',
		'telefone_principal'  => '',
		'email_principal'     => '',
		'whatsapp_principal'  => '',
		'instagram_url'       => '',
		'facebook_url'        => '',
		'x_url'               => '',
		'logo_url'            => '',
		'logo_attachment_id'  => '',
		'logo_largura'        => '150',
		'favicon_url'         => '',
		'cor_primaria'        => '#0052cc',
		'cor_secundaria'      => '#f5f7f8',
		'cor_destaque'        => '#22c55e',
		'cor_fundo'           => '#f5f7f8',
		'cor_texto'           => '#0f172a',
		'cor_rodape'          => '#1e293b',
		'cor_rodape_cor2'     => '#0f172a',
		'cor_rodape_tipo'     => 'solido',
		'cor_rodape_direcao'  => 'to bottom',
		'cor_rodape_opacidade'=> '100',
		'cor_rodape_titulo'   => '#f1f5f9',
		'cor_rodape_texto'    => '#94a3b8',
		'cor_rodape_link'     => '#cbd5e1',
		'cor_rodape_link_hover'=> '#ffffff',
		'cor_fundo_categorias'=> '#f5f7f8',
		'borda_raio'          => '0.5rem',
		'chave_pix'           => '',
		'nome_recebedor'      => '',
		'cidade_recebedor'    => '',
		'link_pagamento'      => '',
		'texto_instrucoes_pagamento' => '',
		'whatsapp_comprovante'=> '',
		'prazo_aprovacao_horas' => '24',
		'hero_titulo'         => 'Encontre o que há de melhor na sua região.',
		'hero_subtitulo'      => 'O guia definitivo para serviços, lazer e gastronomia.',
		'hero_imagem'         => '',
		'hero_imagem_id'      => '',
		'cta_imagem'          => '',
		'cta_imagem_id'       => '',
		'hero_overlay_cor1'   => '#0f172a',
		'hero_overlay_cor2'   => '#0f172a',
		'hero_overlay_direcao'=> 'to bottom',
		'hero_overlay_opacidade1' => '40',
		'hero_overlay_opacidade2' => '80',
		'banner_principal'    => '',
		'texto_premium'       => '',
		'imagem_premium'      => '',
		// IA (provedor ativo)
		'ia_provider'         => 'openai',
		'ia_auto_approve'     => '0',
		'ia_prompt'           => '',
		// OpenAI
		'openai_api_key'      => '',
		'openai_auto_approve' => '0',
		'openai_model'        => 'gpt-4o-mini',
		'openai_prompt'       => '',
		// Groq
		'groq_api_key'        => '',
		'groq_model'          => 'llama-3.3-70b-versatile',
		// Gemini Imagen
		'gemini_imagen_api_key' => '',
		// Mercado Pago
		'mercadopago_access_token'    => '',
		'mercadopago_public_key'      => '',
		'mercadopago_webhook_secret'  => '',
		// Pagou.com.br
		'pagou_api_key'       => '',
		// Gateway ativo
		'gateway_ativo'       => '',
		// AdSense / Monetizacao
		'adsense_enabled'          => '0',
		'adsense_script'           => '',
		'adsense_in_article'       => '',
		'adsense_intervalo_palavras' => '600',
		// Protecao de auth
		'auth_captcha_provider'    => 'none',
		'auth_google_site_key'     => '',
		'auth_google_secret_key'   => '',
		'auth_google_min_score'    => '0.5',
		'auth_turnstile_site_key'  => '',
		'auth_turnstile_secret_key'=> '',
	];

	private static function load(): array {
		if ( null === self::$cache ) {
			self::$cache = get_option( 'gcep_settings', [] );
		}
		return self::$cache;
	}

	public static function get( string $key, $default = null ) {
		$settings = self::load();
		if ( null !== $default ) {
			return $settings[ $key ] ?? $default;
		}
		return $settings[ $key ] ?? ( self::$defaults[ $key ] ?? '' );
	}

	public static function get_all(): array {
		return wp_parse_args( self::load(), self::$defaults );
	}

	public static function update( string $key, $value ): void {
		$settings = self::load();
		$settings[ $key ] = $value;
		update_option( 'gcep_settings', $settings );
		self::$cache = $settings;
	}

	// Campos que armazenam URLs
	private static array $url_fields = [
		'logo_url', 'favicon_url', 'hero_imagem', 'banner_principal',
		'imagem_premium', 'link_pagamento', 'instagram_url', 'facebook_url', 'x_url',
	];

	// Campos que aceitam texto longo (textarea)
	private static array $textarea_fields = [
		'texto_instrucoes_pagamento', 'openai_prompt', 'ia_prompt',
	];

	// Campos que aceitam HTML/scripts (salvos apenas por admins)
	private static array $raw_html_fields = [
		'adsense_script', 'adsense_in_article',
	];

	private static function normalize_url_value( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, '//' ) ) {
			$value = 'https:' . $value;
		} elseif ( ! preg_match( '#^[a-z][a-z0-9+\-.]*://#i', $value ) ) {
			$value = 'https://' . ltrim( $value, '/' );
		}

		return esc_url_raw( $value );
	}

	public static function save_all( array $data ): void {
		$settings = get_option( 'gcep_settings', [] );
		foreach ( $data as $key => $value ) {
			if ( array_key_exists( $key, self::$defaults ) ) {
				if ( in_array( $key, self::$url_fields, true ) ) {
					$settings[ $key ] = self::normalize_url_value( (string) $value );
				} elseif ( in_array( $key, self::$raw_html_fields, true ) ) {
					// Scripts de anuncio: salvar raw apenas se o usuario for admin
					$settings[ $key ] = current_user_can( 'unfiltered_html' )
						? wp_unslash( $value )
						: wp_kses( $value, [
							'script' => [ 'async' => true, 'src' => true, 'crossorigin' => true, 'data-ad-client' => true ],
							'ins'    => [ 'class' => true, 'style' => true, 'data-ad-client' => true, 'data-ad-slot' => true, 'data-ad-format' => true, 'data-full-width-responsive' => true ],
						] );
				} elseif ( in_array( $key, self::$textarea_fields, true ) ) {
					$settings[ $key ] = sanitize_textarea_field( $value );
				} else {
					$settings[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		update_option( 'gcep_settings', $settings );
		self::$cache = $settings;
	}
}
