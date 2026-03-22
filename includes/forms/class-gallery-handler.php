<?php
/**
 * Handler AJAX de upload de galeria com conversão WebP
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.2.1 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Gallery_Handler {

	// Formatos aceitos para upload
	private const ALLOWED_MIMES = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
		'avif'         => 'image/avif',
		'heic'         => 'image/heic',
		'heif'         => 'image/heif',
	];

	// Máx 15 MB por foto
	private const MAX_FILE_SIZE = 15 * 1024 * 1024;

	// Largura máxima em pixels
	private const MAX_WIDTH = 1000;

	// Qualidade WebP
	private const WEBP_QUALITY = 90;

	// Máximo de fotos na galeria
	private const MAX_GALLERY = 20;

	/**
	 * Cria um rascunho de anúncio ao sair do Step 1
	 */
	public function ajax_create_draft(): void {
		check_ajax_referer( 'gcep_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Não autenticado.', 'guiawp' ) ] );
		}

		$user_id      = get_current_user_id();
		$tipo_anuncio = sanitize_text_field( wp_unslash( $_POST['tipo_anuncio'] ?? 'empresa' ) );
		$tipo_plano   = sanitize_text_field( wp_unslash( $_POST['tipo_plano'] ?? 'gratis' ) );

		$post_id = wp_insert_post( [
			'post_type'   => 'gcep_anuncio',
			'post_status' => 'draft',
			'post_title'  => __( 'Rascunho', 'guiawp' ),
			'post_author' => $user_id,
		] );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
		}

		update_post_meta( $post_id, 'GCEP_tipo_anuncio', $tipo_anuncio );
		update_post_meta( $post_id, 'GCEP_tipo_plano', $tipo_plano );
		update_post_meta( $post_id, 'GCEP_status_anuncio', 'rascunho' );

		wp_send_json_success( [ 'post_id' => $post_id ] );
	}

	/**
	 * Upload individual de foto da galeria com conversão para WebP
	 */
	public function ajax_upload_gallery_photo(): void {
		check_ajax_referer( 'gcep_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Não autenticado.', 'guiawp' ) ] );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'ID do anúncio inválido.', 'guiawp' ) ] );
		}

		// Verificar propriedade ou admin
		$post = get_post( $post_id );
		$user_id = get_current_user_id();
		if ( ! $post || ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Permissão negada.', 'guiawp' ) ] );
		}

		// Verificar limite da galeria
		$ids_existentes = get_post_meta( $post_id, 'GCEP_galeria_fotos', true );
		$ids = ! empty( $ids_existentes ) ? array_filter( explode( ',', $ids_existentes ) ) : [];
		if ( count( $ids ) >= self::MAX_GALLERY ) {
			wp_send_json_error( [ 'message' => sprintf( __( 'Limite de %d fotos atingido.', 'guiawp' ), self::MAX_GALLERY ) ] );
		}

		if ( empty( $_FILES['file'] ) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( [ 'message' => __( 'Nenhum arquivo enviado ou erro no upload.', 'guiawp' ) ] );
		}

		$file = $_FILES['file'];

		// Validar tamanho
		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			wp_send_json_error( [ 'message' => __( 'Arquivo excede o limite de 15 MB.', 'guiawp' ) ] );
		}

		// Validar tipo MIME
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		$allowed_mimes = array_values( self::ALLOWED_MIMES );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Formato não suportado. Use JPG, PNG, GIF, WebP, AVIF ou HEIC.', 'guiawp' ) ] );
		}

		// Converter para WebP
		$webp_path = $this->convert_to_webp( $file['tmp_name'], $mime );
		if ( ! $webp_path ) {
			wp_send_json_error( [ 'message' => __( 'Erro ao converter imagem para WebP.', 'guiawp' ) ] );
		}

		// Criar attachment no WordPress
		$attachment_id = $this->create_attachment( $webp_path, $post_id, $file['name'] );

		// Limpar arquivo temporário WebP
		if ( file_exists( $webp_path ) && strpos( $webp_path, sys_get_temp_dir() ) === 0 ) {
			@unlink( $webp_path );
		}

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
		}

		// Adicionar à galeria
		$ids[] = $attachment_id;
		$ids   = array_slice( array_unique( array_filter( $ids ) ), 0, self::MAX_GALLERY );
		update_post_meta( $post_id, 'GCEP_galeria_fotos', implode( ',', $ids ) );

		$thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		wp_send_json_success( [
			'attachment_id' => $attachment_id,
			'thumb_url'     => $thumb_url,
			'total'         => count( $ids ),
		] );
	}

	/**
	 * Remover foto da galeria via AJAX
	 */
	public function ajax_remove_gallery_photo(): void {
		check_ajax_referer( 'gcep_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Não autenticado.', 'guiawp' ) ] );
		}

		$post_id       = intval( $_POST['post_id'] ?? 0 );
		$attachment_id = intval( $_POST['attachment_id'] ?? 0 );

		$post    = get_post( $post_id );
		$user_id = get_current_user_id();
		if ( ! $post || ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Permissão negada.', 'guiawp' ) ] );
		}

		$ids_existentes = get_post_meta( $post_id, 'GCEP_galeria_fotos', true );
		$ids = ! empty( $ids_existentes ) ? array_filter( explode( ',', $ids_existentes ) ) : [];
		$ids = array_values( array_diff( $ids, [ (string) $attachment_id ] ) );
		update_post_meta( $post_id, 'GCEP_galeria_fotos', implode( ',', $ids ) );

		// Excluir attachment
		wp_delete_attachment( $attachment_id, true );

		wp_send_json_success( [ 'total' => count( $ids ) ] );
	}

	/**
	 * Converte imagem para WebP com redimensionamento
	 */
	private function convert_to_webp( string $source_path, string $mime ): ?string {
		$image = null;

		switch ( $mime ) {
			case 'image/jpeg':
				$image = @imagecreatefromjpeg( $source_path );
				break;
			case 'image/png':
				$image = @imagecreatefrompng( $source_path );
				break;
			case 'image/gif':
				$image = @imagecreatefromgif( $source_path );
				break;
			case 'image/webp':
				$image = @imagecreatefromwebp( $source_path );
				break;
			case 'image/avif':
				if ( function_exists( 'imagecreatefromavif' ) ) {
					$image = @imagecreatefromavif( $source_path );
				}
				break;
			case 'image/heic':
			case 'image/heif':
				// HEIC precisa de ImageMagick ou conversão externa
				$image = $this->convert_heic_to_gd( $source_path );
				break;
		}

		if ( ! $image ) {
			return null;
		}

		// Preservar transparência para PNG/GIF
		imagepalettetotruecolor( $image );
		imagealphablending( $image, true );
		imagesavealpha( $image, true );

		// Redimensionar se necessário
		$width  = imagesx( $image );
		$height = imagesy( $image );

		if ( $width > self::MAX_WIDTH ) {
			$new_width  = self::MAX_WIDTH;
			$new_height = intval( $height * ( self::MAX_WIDTH / $width ) );
			$resized    = imagecreatetruecolor( $new_width, $new_height );
			imagealphablending( $resized, false );
			imagesavealpha( $resized, true );
			imagecopyresampled( $resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
			imagedestroy( $image );
			$image = $resized;
		}

		// Salvar como WebP em arquivo temporário
		$temp_path = tempnam( sys_get_temp_dir(), 'gcep_webp_' ) . '.webp';
		$result    = imagewebp( $image, $temp_path, self::WEBP_QUALITY );
		imagedestroy( $image );

		return $result ? $temp_path : null;
	}

	/**
	 * Tenta converter HEIC para recurso GD via Imagick
	 */
	private function convert_heic_to_gd( string $source_path ) {
		if ( ! class_exists( 'Imagick' ) ) {
			return null;
		}

		try {
			$imagick = new Imagick( $source_path );
			$imagick->setImageFormat( 'png' );
			$temp = tempnam( sys_get_temp_dir(), 'gcep_heic_' ) . '.png';
			$imagick->writeImage( $temp );
			$imagick->clear();
			$imagick->destroy();
			$gd = imagecreatefrompng( $temp );
			@unlink( $temp );
			return $gd;
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Cria attachment WordPress a partir de arquivo WebP convertido
	 */
	private function create_attachment( string $webp_path, int $post_id, string $original_name ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_dir = wp_upload_dir();
		$filename   = pathinfo( $original_name, PATHINFO_FILENAME );
		$filename   = sanitize_file_name( $filename ) . '.webp';

		// Garantir nome único
		$dest_path = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $filename );
		$dest_url  = $upload_dir['url'] . '/' . basename( $dest_path );

		if ( ! copy( $webp_path, $dest_path ) ) {
			return new WP_Error( 'copy_failed', __( 'Erro ao copiar arquivo.', 'guiawp' ) );
		}

		$attachment = [
			'guid'           => $dest_url,
			'post_mime_type' => 'image/webp',
			'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $dest_path, $post_id );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $dest_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $attachment_id;
	}
}
