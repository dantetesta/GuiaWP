<?php
/**
 * Limpeza e gestao de imagens — prevencao de orfaos
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.1 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Image_Cleanup {

	// Meta keys que armazenam attachment IDs de imagens unicas
	private const SINGLE_IMAGE_METAS = [
		'GCEP_logo_ou_foto_principal',
		'GCEP_foto_capa',
	];

	// Meta key da galeria (IDs separados por virgula)
	private const GALLERY_META = 'GCEP_galeria_fotos';

	/**
	 * Registra hooks para limpeza automatica
	 */
	public function init(): void {
		// Ao deletar permanentemente um anuncio
		add_action( 'before_delete_post', [ $this, 'on_delete_post' ], 10, 1 );

		// Ao mover para lixeira — tambem limpa imagens (sao exclusivas do anuncio)
		add_action( 'trashed_post', [ $this, 'on_delete_post' ], 10, 1 );
	}

	/**
	 * Remove todas as imagens vinculadas ao anuncio ao deletar/lixeira
	 */
	public function on_delete_post( int $post_id ): void {
		if ( 'gcep_anuncio' !== get_post_type( $post_id ) ) {
			return;
		}

		// Imagens unicas (logo, capa)
		foreach ( self::SINGLE_IMAGE_METAS as $meta_key ) {
			$att_id = (int) get_post_meta( $post_id, $meta_key, true );
			if ( $att_id > 0 ) {
				self::safe_delete_attachment( $att_id, $post_id );
			}
		}

		// Galeria de fotos
		$gallery_str = get_post_meta( $post_id, self::GALLERY_META, true );
		if ( ! empty( $gallery_str ) ) {
			$ids = array_filter( array_map( 'intval', explode( ',', $gallery_str ) ) );
			foreach ( $ids as $att_id ) {
				self::safe_delete_attachment( $att_id, $post_id );
			}
		}

		// Thumbnail (pode ser a mesma que capa)
		$thumb_id = (int) get_post_thumbnail_id( $post_id );
		if ( $thumb_id > 0 ) {
			self::safe_delete_attachment( $thumb_id, $post_id );
		}
	}

	/**
	 * Substitui imagem em meta key — deleta a antiga e retorna a nova
	 * Usado ao editar anuncio (logo, capa)
	 */
	public static function replace_single_image( int $post_id, string $meta_key, int $new_attachment_id ): void {
		$old_id = (int) get_post_meta( $post_id, $meta_key, true );

		if ( $old_id > 0 && $old_id !== $new_attachment_id ) {
			self::safe_delete_attachment( $old_id, $post_id );
		}

		update_post_meta( $post_id, $meta_key, $new_attachment_id );
	}

	/**
	 * Remove attachment do settings (logotipo do guia, hero, etc.)
	 * Busca pelo URL ou pelo attachment_id salvo
	 */
	public static function delete_settings_image( string $url_or_id ): void {
		if ( empty( $url_or_id ) ) {
			return;
		}

		// Se for numerico, e um attachment ID direto
		if ( is_numeric( $url_or_id ) ) {
			wp_delete_attachment( (int) $url_or_id, true );
			return;
		}

		// Buscar attachment pelo URL
		$att_id = self::get_attachment_id_by_url( $url_or_id );
		if ( $att_id > 0 ) {
			wp_delete_attachment( $att_id, true );
		}
	}

	/**
	 * Deleta attachment com seguranca — verifica se nao esta em uso por outro post
	 */
	public static function safe_delete_attachment( int $attachment_id, int $excluding_post_id = 0 ): bool {
		if ( $attachment_id <= 0 ) {
			return false;
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return false;
		}

		// Verificar se o attachment esta vinculado a outro post
		if ( $excluding_post_id > 0 && (int) $attachment->post_parent > 0 && (int) $attachment->post_parent !== $excluding_post_id ) {
			return false;
		}

		// Verificar se nenhum outro anuncio referencia esta imagem
		if ( self::is_used_elsewhere( $attachment_id, $excluding_post_id ) ) {
			return false;
		}

		wp_delete_attachment( $attachment_id, true );
		return true;
	}

	/**
	 * Verifica se um attachment e referenciado por outro anuncio
	 */
	private static function is_used_elsewhere( int $attachment_id, int $excluding_post_id ): bool {
		global $wpdb;

		$all_metas = array_merge( self::SINGLE_IMAGE_METAS, [ self::GALLERY_META ] );
		$placeholders = implode( ',', array_fill( 0, count( $all_metas ), '%s' ) );

		$params = $all_metas;
		$params[] = $excluding_post_id;

		// Buscar em metas de imagens unicas
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ($placeholders)
			 AND meta_value = %s
			 AND post_id != %d",
			...array_merge( self::SINGLE_IMAGE_METAS, [ (string) $attachment_id, $excluding_post_id ] )
		) );

		if ( $count > 0 ) {
			return true;
		}

		// Buscar na galeria (meta_value contem lista de IDs separados por virgula)
		$gallery_results = $wpdb->get_col( $wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta}
			 WHERE meta_key = %s
			 AND post_id != %d
			 AND meta_value != ''",
			self::GALLERY_META,
			$excluding_post_id
		) );

		foreach ( $gallery_results as $gallery_str ) {
			$ids = array_map( 'intval', explode( ',', $gallery_str ) );
			if ( in_array( $attachment_id, $ids, true ) ) {
				return true;
			}
		}

		// Verificar thumbnail
		$thumb_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE meta_key = '_thumbnail_id'
			 AND meta_value = %s
			 AND post_id != %d",
			(string) $attachment_id,
			$excluding_post_id
		) );

		return $thumb_count > 0;
	}

	/**
	 * Encontra attachment ID a partir de URL
	 */
	public static function get_attachment_id_by_url( string $url ): int {
		if ( empty( $url ) ) {
			return 0;
		}

		global $wpdb;

		// Tentar direto pelo guid
		$att_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
			$url
		) );

		if ( $att_id > 0 ) {
			return $att_id;
		}

		// Tentar pelo _wp_attached_file (caminho relativo)
		$upload_dir = wp_upload_dir();
		$base_url   = $upload_dir['baseurl'] . '/';
		if ( str_starts_with( $url, $base_url ) ) {
			$relative = str_replace( $base_url, '', $url );
			$att_id   = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
				$relative
			) );
		}

		return max( 0, $att_id );
	}
}
