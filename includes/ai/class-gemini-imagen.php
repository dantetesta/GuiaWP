<?php
/**
 * Geração de imagens via Nanobanana 2 (Gemini 3.1 Flash Image)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.9.0 - 2026-03-21
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Gemini_Imagen {

	// Modelo Nanobanana 2 (Gemini 3.1 Flash Image)
	private const MODEL = 'gemini-3.1-flash-image-preview';

	// Endpoint base da API Gemini
	private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

	// Qualidade WebP para salvamento final
	private const WEBP_QUALITY = 90;

	/**
	 * Verifica se a API key do Gemini esta configurada
	 */
	public static function has_api_key(): bool {
		return '' !== trim( (string) GCEP_Settings::get( 'gemini_imagen_api_key', '' ) );
	}

	/**
	 * Gera imagem via Nanobanana 2 (generateContent com responseModalities IMAGE)
	 *
	 * @param string $prompt     Texto descritivo para gerar a imagem
	 * @param string $aspect     Aspect ratio desejado (incluído no prompt)
	 * @return array{success: bool, image_data?: string, mime_type?: string, error?: string}
	 */
	public static function generate_image( string $prompt, string $aspect = '1:1' ): array {
		$api_key = trim( (string) GCEP_Settings::get( 'gemini_imagen_api_key', '' ) );

		if ( '' === $api_key ) {
			return [
				'success' => false,
				'error'   => __( 'Chave de API do Gemini não configurada.', 'guiawp' ),
			];
		}

		// Incluir instrução de aspect ratio no prompt
		$aspect_instruction = '';
		if ( '1:1' === $aspect ) {
			$aspect_instruction = 'Generate a square (1:1 aspect ratio) image. ';
		} elseif ( '16:9' === $aspect ) {
			$aspect_instruction = 'Generate a wide landscape (16:9 aspect ratio) image. ';
		} elseif ( '4:3' === $aspect ) {
			$aspect_instruction = 'Generate a landscape (4:3 aspect ratio) image. ';
		} elseif ( '3:4' === $aspect ) {
			$aspect_instruction = 'Generate a portrait (3:4 aspect ratio) image. ';
		} elseif ( '9:16' === $aspect ) {
			$aspect_instruction = 'Generate a tall portrait (9:16 aspect ratio) image. ';
		}

		$full_prompt = $aspect_instruction . $prompt;

		$url = self::API_BASE . self::MODEL . ':generateContent?key=' . $api_key;

		$body = [
			'contents' => [
				[
					'parts' => [
						[ 'text' => $full_prompt ],
					],
				],
			],
			'generationConfig' => [
				'responseModalities' => [ 'TEXT', 'IMAGE' ],
			],
		];

		$response = wp_remote_post( $url, [
			'timeout' => 90,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_raw    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body_raw, true );

		if ( 200 !== $status_code ) {
			$error_msg = $data['error']['message'] ?? __( 'Erro na API do Gemini.', 'guiawp' );
			return [
				'success' => false,
				'error'   => sprintf( __( 'API retornou erro %d: %s', 'guiawp' ), $status_code, $error_msg ),
			];
		}

		// Buscar a parte com imagem na resposta
		$parts = $data['candidates'][0]['content']['parts'] ?? [];
		$image_data = null;
		$mime_type  = 'image/png';

		foreach ( $parts as $part ) {
			if ( ! empty( $part['inlineData']['data'] ) ) {
				$image_data = $part['inlineData']['data'];
				$mime_type  = $part['inlineData']['mimeType'] ?? 'image/png';
				break;
			}
		}

		if ( null === $image_data ) {
			return [
				'success' => false,
				'error'   => __( 'A API não retornou dados de imagem. Tente reformular o prompt.', 'guiawp' ),
			];
		}

		return [
			'success'    => true,
			'image_data' => $image_data,
			'mime_type'  => $mime_type,
		];
	}

	/**
	 * Melhora o prompt do usuario usando o provedor de IA ativo (OpenAI/Groq)
	 *
	 * @param string $prompt   Prompt original do usuario
	 * @param string $contexto Contexto da finalidade (categoria, blog)
	 * @return array{success: bool, enhanced_prompt?: string, error?: string}
	 */
	public static function enhance_prompt( string $prompt, string $contexto = 'categoria' ): array {
		if ( ! GCEP_AI_Validator::can_generate_content() ) {
			return [
				'success' => false,
				'error'   => __( 'Nenhum provedor de IA (OpenAI/Groq) configurado para melhorar o prompt.', 'guiawp' ),
			];
		}

		$system_prompt = self::get_enhance_system_prompt( $contexto );

		// Usar o provedor ativo (OpenAI ou Groq)
		$provider = GCEP_Settings::get( 'ia_provider', 'openai' );

		if ( 'groq' === $provider ) {
			$endpoint = 'https://api.groq.com/openai/v1/chat/completions';
			$api_key  = trim( (string) GCEP_Settings::get( 'groq_api_key', '' ) );
			$model    = (string) GCEP_Settings::get( 'groq_model', 'llama-3.3-70b-versatile' );
		} else {
			$endpoint = 'https://api.openai.com/v1/chat/completions';
			$api_key  = trim( (string) GCEP_Settings::get( 'openai_api_key', '' ) );
			$model    = (string) GCEP_Settings::get( 'openai_model', 'gpt-4o-mini' );
		}

		if ( '' === $api_key ) {
			return [
				'success' => false,
				'error'   => __( 'Chave de API do provedor de IA não configurada.', 'guiawp' ),
			];
		}

		$response = wp_remote_post( $endpoint, [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'       => $model,
				'messages'    => [
					[
						'role'    => 'system',
						'content' => $system_prompt,
					],
					[
						'role'    => 'user',
						'content' => $prompt,
					],
				],
				'temperature' => 0.7,
				'max_tokens'  => 300,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$content = trim( (string) ( $body['choices'][0]['message']['content'] ?? '' ) );

		if ( '' === $content ) {
			return [
				'success' => false,
				'error'   => __( 'O provedor de IA não retornou um prompt melhorado.', 'guiawp' ),
			];
		}

		// Limpar aspas ao redor se a IA colocou
		$content = trim( $content, '"\'`' );

		return [
			'success'         => true,
			'enhanced_prompt' => $content,
		];
	}

	/**
	 * Processa imagem gerada: decodifica base64, redimensiona e converte para WebP
	 *
	 * @param string $base64_data Dados base64 da imagem
	 * @param string $tipo        'categoria' (1:1 400px) ou 'blog' (16:9, largura max)
	 * @param string $filename    Nome base do arquivo
	 * @return array{success: bool, attachment_id?: int, url?: string, error?: string}
	 */
	public static function process_and_save( string $base64_data, string $tipo, string $filename = 'ia-imagem' ): array {
		$decoded = base64_decode( $base64_data, true );
		if ( false === $decoded ) {
			return [
				'success' => false,
				'error'   => __( 'Erro ao decodificar dados da imagem.', 'guiawp' ),
			];
		}

		// Salvar temporariamente
		$temp_path = tempnam( sys_get_temp_dir(), 'gcep_imagen_' );
		if ( false === file_put_contents( $temp_path, $decoded ) ) {
			return [
				'success' => false,
				'error'   => __( 'Erro ao salvar arquivo temporário.', 'guiawp' ),
			];
		}

		// Criar recurso GD a partir do conteudo
		$image = @imagecreatefromstring( $decoded );
		if ( ! $image ) {
			@unlink( $temp_path );
			return [
				'success' => false,
				'error'   => __( 'Erro ao processar imagem gerada.', 'guiawp' ),
			];
		}

		$orig_w = imagesx( $image );
		$orig_h = imagesy( $image );

		// Redimensionar conforme tipo
		if ( 'categoria' === $tipo ) {
			// 1:1 crop central + resize para 400x400
			$size    = min( $orig_w, $orig_h );
			$src_x   = intval( ( $orig_w - $size ) / 2 );
			$src_y   = intval( ( $orig_h - $size ) / 2 );
			$resized = imagecreatetruecolor( 400, 400 );
			imagealphablending( $resized, false );
			imagesavealpha( $resized, true );
			imagecopyresampled( $resized, $image, 0, 0, $src_x, $src_y, 400, 400, $size, $size );
			imagedestroy( $image );
			$image = $resized;
		} else {
			// Blog: manter proporção, max 1000px de largura
			if ( $orig_w > 1000 ) {
				$new_w   = 1000;
				$new_h   = intval( $orig_h * ( 1000 / $orig_w ) );
				$resized = imagecreatetruecolor( $new_w, $new_h );
				imagealphablending( $resized, false );
				imagesavealpha( $resized, true );
				imagecopyresampled( $resized, $image, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h );
				imagedestroy( $image );
				$image = $resized;
			}
		}

		// Salvar como WebP
		$webp_temp = tempnam( sys_get_temp_dir(), 'gcep_webp_' ) . '.webp';
		$result    = imagewebp( $image, $webp_temp, self::WEBP_QUALITY );
		imagedestroy( $image );

		// Limpar temp original
		@unlink( $temp_path );

		if ( ! $result ) {
			@unlink( $webp_temp );
			return [
				'success' => false,
				'error'   => __( 'Erro ao converter imagem para WebP.', 'guiawp' ),
			];
		}

		// Criar attachment no WordPress
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_dir = wp_upload_dir();
		$safe_name  = sanitize_file_name( $filename ) . '.webp';
		$dest_path  = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $safe_name );
		$dest_url   = $upload_dir['url'] . '/' . basename( $dest_path );

		if ( ! copy( $webp_temp, $dest_path ) ) {
			@unlink( $webp_temp );
			return [
				'success' => false,
				'error'   => __( 'Erro ao copiar imagem para o diretório de uploads.', 'guiawp' ),
			];
		}

		@unlink( $webp_temp );

		$attachment = [
			'guid'           => $dest_url,
			'post_mime_type' => 'image/webp',
			'post_title'     => pathinfo( $safe_name, PATHINFO_FILENAME ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $dest_path );
		if ( is_wp_error( $attachment_id ) ) {
			return [
				'success' => false,
				'error'   => $attachment_id->get_error_message(),
			];
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $dest_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return [
			'success'       => true,
			'attachment_id' => $attachment_id,
			'url'           => $dest_url,
		];
	}

	/**
	 * Prompt de sistema para melhorar prompts de geração de imagem
	 */
	private static function get_enhance_system_prompt( string $contexto ): string {
		$base = "Você é um especialista em criação de prompts para geração de imagens com IA. "
			. "Sua tarefa é receber um prompt simples do usuário e transformá-lo em um prompt detalhado e otimizado "
			. "para gerar uma imagem de alta qualidade com o Nanobanana 2 (Gemini).\n\n"
			. "Regras:\n"
			. "1. Responda APENAS com o prompt melhorado, sem explicações.\n"
			. "2. Mantenha a intenção original do usuário.\n"
			. "3. Adicione detalhes de iluminação, composição, estilo e qualidade.\n"
			. "4. O prompt final deve estar em inglês (melhores resultados na API).\n"
			. "5. Evite texto na imagem (logos, nomes, marcas).\n"
			. "6. Máximo de 200 palavras.\n";

		if ( 'categoria' === $contexto ) {
			$base .= "\nContexto: a imagem será usada como thumb de uma categoria em um guia comercial. "
				. "OBRIGATÓRIO: a imagem deve ser fullcover (edge-to-edge), sem bordas, sem paddings internos, sem margens brancas. "
				. "A representação visual deve preencher 100% da área da imagem. "
				. "Deve ser quadrada (1:1), com cores vibrantes, profissional e de alta qualidade. "
				. "Estilo: fotografia profissional ou ilustração clean moderna. Nunca gerar imagem com bordas ou espaços vazios ao redor.";
		} else {
			$base .= "\nContexto: a imagem será usada como capa de um post de blog em um guia comercial. "
				. "Deve ser no formato paisagem (16:9), editorial, atraente e profissional. "
				. "Estilo: fotografia editorial, boa composição, cores harmoniosas.";
		}

		return $base;
	}

	/**
	 * Registra os hooks AJAX
	 */
	public function init(): void {
		add_action( 'wp_ajax_gcep_imagen_generate', [ $this, 'ajax_generate' ] );
		add_action( 'wp_ajax_gcep_imagen_enhance', [ $this, 'ajax_enhance_prompt' ] );
	}

	/**
	 * AJAX: gerar imagem
	 */
	public function ajax_generate(): void {
		check_ajax_referer( 'gcep_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permissão negada.', 'guiawp' ) ] );
		}

		$prompt = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );
		$tipo   = sanitize_text_field( wp_unslash( $_POST['tipo'] ?? 'categoria' ) );

		if ( '' === trim( $prompt ) ) {
			wp_send_json_error( [ 'message' => __( 'Informe um prompt para gerar a imagem.', 'guiawp' ) ] );
		}

		$aspect = 'categoria' === $tipo ? '1:1' : '16:9';

		// Gerar imagem via API
		$gen_result = self::generate_image( $prompt, $aspect );
		if ( ! $gen_result['success'] ) {
			wp_send_json_error( [ 'message' => $gen_result['error'] ] );
		}

		// Processar e salvar como WebP otimizado
		$filename   = 'ia-' . $tipo . '-' . wp_generate_uuid4();
		$save_result = self::process_and_save( $gen_result['image_data'], $tipo, $filename );
		if ( ! $save_result['success'] ) {
			wp_send_json_error( [ 'message' => $save_result['error'] ] );
		}

		wp_send_json_success( [
			'attachment_id' => $save_result['attachment_id'],
			'url'           => $save_result['url'],
		] );
	}

	/**
	 * AJAX: melhorar prompt
	 */
	public function ajax_enhance_prompt(): void {
		check_ajax_referer( 'gcep_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permissão negada.', 'guiawp' ) ] );
		}

		$prompt   = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );
		$contexto = sanitize_text_field( wp_unslash( $_POST['contexto'] ?? 'categoria' ) );

		if ( '' === trim( $prompt ) ) {
			wp_send_json_error( [ 'message' => __( 'Informe um prompt para melhorar.', 'guiawp' ) ] );
		}

		$result = self::enhance_prompt( $prompt, $contexto );
		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		wp_send_json_success( [ 'enhanced_prompt' => $result['enhanced_prompt'] ] );
	}
}
