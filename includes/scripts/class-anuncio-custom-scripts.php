<?php
/**
 * Scripts customizados por anúncio premium.
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.7.4 - 2026-03-12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Anuncio_Custom_Scripts {

	public const META_HEAD       = 'GCEP_custom_script_head';
	public const META_BODY_START = 'GCEP_custom_script_body_start';
	public const META_BODY_END   = 'GCEP_custom_script_body_end';

	public function init(): void {
		add_action( 'wp_head', [ $this, 'output_head_script' ], 60 );
		add_action( 'wp_body_open', [ $this, 'output_body_start_script' ], 5 );
		add_action( 'wp_footer', [ $this, 'output_body_end_script' ], 60 );
	}

	public static function is_scripts_available_for_edit( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'gcep_anuncio' !== $post->post_type ) {
			return false;
		}

		$plano  = (string) get_post_meta( $post_id, 'GCEP_tipo_plano', true );
		$status = (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true );

		return 'premium' === $plano && in_array( $status ?: 'rascunho', [ 'rascunho', 'publicado' ], true );
	}

	public static function can_manage_scripts( int $post_id, int $user_id = 0 ): bool {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'gcep_anuncio' !== $post->post_type ) {
			return false;
		}

		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return (int) $post->post_author === $user_id;
	}

	public static function get_scripts( int $post_id ): array {
		return [
			'head'       => (string) get_post_meta( $post_id, self::META_HEAD, true ),
			'body_start' => (string) get_post_meta( $post_id, self::META_BODY_START, true ),
			'body_end'   => (string) get_post_meta( $post_id, self::META_BODY_END, true ),
		];
	}

	public static function save_scripts( int $post_id, array $scripts ): void {
		$normalized = [
			self::META_HEAD       => self::sanitize_script_snippet( (string) ( $scripts['head'] ?? '' ) ),
			self::META_BODY_START => self::sanitize_script_snippet( (string) ( $scripts['body_start'] ?? '' ) ),
			self::META_BODY_END   => self::sanitize_script_snippet( (string) ( $scripts['body_end'] ?? '' ) ),
		];

		foreach ( $normalized as $meta_key => $value ) {
			if ( '' === $value ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	public function output_head_script(): void {
		$this->output_slot( self::META_HEAD, 'head' );
	}

	public function output_body_start_script(): void {
		$this->output_slot( self::META_BODY_START, 'body-start' );
	}

	public function output_body_end_script(): void {
		$this->output_slot( self::META_BODY_END, 'body-end' );
	}

	private function output_slot( string $meta_key, string $slot_label ): void {
		$post_id = $this->get_renderable_post_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$content = (string) get_post_meta( $post_id, $meta_key, true );
		if ( '' === trim( $content ) ) {
			return;
		}

		echo "\n<!-- GuiaWP Premium Scripts: {$slot_label} / anúncio {$post_id} -->\n";
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Recurso premium explícito para pixels/scripts do anúncio.
		echo "\n<!-- /GuiaWP Premium Scripts: {$slot_label} -->\n";
	}

	private function get_renderable_post_id(): int {
		if ( ! is_singular( 'gcep_anuncio' ) ) {
			return 0;
		}

		$post_id = get_queried_object_id();
		if ( $post_id <= 0 ) {
			return 0;
		}

		$plano  = (string) get_post_meta( $post_id, 'GCEP_tipo_plano', true );
		$status = (string) get_post_meta( $post_id, 'GCEP_status_anuncio', true );

		if ( 'premium' !== $plano || 'publicado' !== $status ) {
			return 0;
		}

		return $post_id;
	}

	private static function sanitize_script_snippet( string $value ): string {
		$value = wp_unslash( $value );
		$value = str_replace( [ "\0", '<?php', '<?', '?>' ], '', $value );
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		return mb_substr( $value, 0, 20000 );
	}
}
