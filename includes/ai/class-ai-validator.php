<?php
/**
 * Validação de anúncios via IA (OpenAI / Groq)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 * @modified 1.6.0 - 2026-03-14 - Suporte a provedor Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_AI_Validator {

	// Retorna endpoint, api_key e model do provedor ativo
	private static function get_api_config(): array {
		$provider = GCEP_Settings::get( 'ia_provider', 'openai' );

		if ( 'groq' === $provider ) {
			return [
				'provider' => 'groq',
				'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
				'api_key'  => trim( (string) GCEP_Settings::get( 'groq_api_key', '' ) ),
				'model'    => (string) GCEP_Settings::get( 'groq_model', 'llama-3.3-70b-versatile' ),
			];
		}

		return [
			'provider' => 'openai',
			'endpoint' => 'https://api.openai.com/v1/chat/completions',
			'api_key'  => trim( (string) GCEP_Settings::get( 'openai_api_key', '' ) ),
			'model'    => (string) GCEP_Settings::get( 'openai_model', 'gpt-4o-mini' ),
		];
	}

	// Prompt compartilhado (checa novo campo, fallback para legado)
	private static function get_active_prompt(): string {
		$prompt = trim( (string) GCEP_Settings::get( 'ia_prompt', '' ) );
		if ( '' === $prompt ) {
			$prompt = trim( (string) GCEP_Settings::get( 'openai_prompt', '' ) );
		}
		return '' !== $prompt ? $prompt : self::get_default_prompt();
	}

	// Toggle compartilhado (checa novo campo, fallback para legado)
	private static function get_auto_approve_flag(): bool {
		$flag = GCEP_Settings::get( 'ia_auto_approve', '' );
		if ( '' === $flag || '0' === $flag ) {
			$flag = GCEP_Settings::get( 'openai_auto_approve', '0' );
		}
		return '1' === $flag;
	}

	public static function has_api_key(): bool {
		$config = self::get_api_config();
		return '' !== $config['api_key'];
	}

	public static function is_enabled(): bool {
		return self::has_api_key() && self::get_auto_approve_flag();
	}

	public static function can_generate_content(): bool {
		return self::has_api_key();
	}

	public static function validate( int $post_id ): array {
		$result = [
			'approved'      => false,
			'justificativa' => '',
			'error'         => '',
			'raw_response'  => '',
		];

		if ( ! self::is_enabled() ) {
			$result['error'] = __( 'Validação por IA não está habilitada.', 'guiawp' );
			return $result;
		}

		$config = self::get_api_config();
		$prompt = self::get_active_prompt();

		$anuncio_data = self::build_anuncio_context( $post_id );
		if ( empty( $anuncio_data ) ) {
			$result['error'] = __( 'Anúncio não encontrado.', 'guiawp' );
			return $result;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' => $prompt,
			],
			[
				'role'    => 'user',
				'content' => sprintf(
					"Analise o seguinte anúncio e responda APENAS com um JSON válido no formato {\"approved\": true/false, \"justificativa\": \"texto\"}.\n\nDados do anúncio:\n%s",
					$anuncio_data
				),
			],
		];

		$response = wp_remote_post( $config['endpoint'], [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $config['api_key'],
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'       => $config['model'],
				'messages'    => $messages,
				'temperature' => 0.3,
				'max_tokens'  => 500,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			$result['error'] = $response->get_error_message();
			return $result;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			$result['error'] = __( 'Resposta vazia da API.', 'guiawp' );
			return $result;
		}

		$result['raw_response'] = (string) $body['choices'][0]['message']['content'];

		$parsed = self::parse_ai_response( $result['raw_response'] );
		if ( ! empty( $parsed['error'] ) ) {
			$result['error'] = $parsed['error'];
			return $result;
		}

		$result['approved']      = $parsed['approved'];
		$result['justificativa'] = $parsed['justificativa'];

		// Salvar resultado da IA no post meta (sem alterar status — o handler controla)
		update_post_meta( $post_id, 'GCEP_ai_validado', $result['approved'] ? '1' : '0' );
		update_post_meta( $post_id, 'GCEP_ai_justificativa', $result['justificativa'] );

		return $result;
	}

	// Validar a partir de dados brutos (sem salvar no banco)
	public static function validate_from_data( array $data ): array {
		$result = [
			'approved'      => false,
			'justificativa' => '',
			'error'         => '',
			'raw_response'  => '',
		];

		if ( ! self::is_enabled() ) {
			// IA desabilitada: aprovar automaticamente
			$result['approved'] = true;
			return $result;
		}

		$config = self::get_api_config();
		$prompt = self::get_active_prompt();

		$context = self::build_context_from_data( $data );
		if ( empty( $context ) ) {
			$result['error'] = __( 'Dados insuficientes para validacao.', 'guiawp' );
			return $result;
		}

		$messages = [
			[
				'role'    => 'system',
				'content' => $prompt,
			],
			[
				'role'    => 'user',
				'content' => sprintf(
					"Analise o seguinte anúncio e responda APENAS com um JSON válido no formato {\"approved\": true/false, \"justificativa\": \"texto\"}.\n\nDados do anúncio:\n%s",
					$context
				),
			],
		];

		$response = wp_remote_post( $config['endpoint'], [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $config['api_key'],
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'       => $config['model'],
				'messages'    => $messages,
				'temperature' => 0.3,
				'max_tokens'  => 500,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			$result['error'] = $response->get_error_message();
			return $result;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			$result['error'] = __( 'Resposta vazia da API.', 'guiawp' );
			return $result;
		}

		$result['raw_response'] = (string) $body['choices'][0]['message']['content'];

		$parsed = self::parse_ai_response( $result['raw_response'] );
		if ( ! empty( $parsed['error'] ) ) {
			$result['error'] = $parsed['error'];
			return $result;
		}

		$result['approved']      = $parsed['approved'];
		$result['justificativa'] = $parsed['justificativa'];

		return $result;
	}

	public static function generate_description_html( array $data ): array {
		$result = [
			'html'  => '',
			'error' => '',
		];

		if ( ! self::can_generate_content() ) {
			$result['error'] = __( 'Geração por IA não está habilitada.', 'guiawp' );
			return $result;
		}

		$context = self::build_generation_context( $data );
		if ( '' === $context ) {
			$result['error'] = __( 'Informe um contexto inicial para gerar a descrição.', 'guiawp' );
			return $result;
		}

		$config = self::get_api_config();

		$response = wp_remote_post( $config['endpoint'], [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $config['api_key'],
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'       => $config['model'],
				'messages'    => [
					[
						'role'    => 'system',
						'content' => self::get_description_generation_prompt(),
					],
					[
						'role'    => 'user',
						'content' => "Gere a descrição HTML do anúncio com base neste contexto:\n\n" . $context,
					],
				],
				'temperature' => 0.7,
				'max_tokens'  => 900,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			$result['error'] = $response->get_error_message();
			return $result;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$content = trim( (string) ( $body['choices'][0]['message']['content'] ?? '' ) );

		if ( '' === $content ) {
			$result['error'] = __( 'A IA não retornou conteúdo para a descrição.', 'guiawp' );
			return $result;
		}

		$html = self::normalize_generated_description_html( $content );
		if ( '' === $html ) {
			$result['error'] = __( 'A descrição gerada ficou vazia após a sanitização.', 'guiawp' );
			return $result;
		}

		$result['html'] = $html;
		return $result;
	}

	public static function sanitize_description_html( string $html ): string {
		$html = trim( $html );
		if ( '' === $html ) {
			return '';
		}

		$sanitized = wp_kses( $html, self::get_description_allowed_html() );
		$sanitized = preg_replace( '/<(p|h2|h3|h4|li|th|td)>\s*<\/\1>/i', '', (string) $sanitized );
		$sanitized = preg_replace( '/\n{3,}/', "\n\n", (string) $sanitized );

		return trim( (string) $sanitized );
	}

	private static function build_context_from_data( array $data ): string {
		$context = '';

		$map = [
			'titulo'          => 'Titulo',
			'tipo_anuncio'    => 'Tipo anuncio',
			'tipo_plano'      => 'Tipo plano',
			'descricao_curta' => 'Descricao curta',
			'descricao_longa' => 'Descricao detalhada',
			'telefone'        => 'Telefone',
			'whatsapp'        => 'WhatsApp',
			'email'           => 'Email',
			'site'            => 'Site',
			'cnpj'            => 'CNPJ',
			'endereco'        => 'Endereco',
			'categorias'      => 'Categorias',
		];

		foreach ( $map as $key => $label ) {
			if ( ! empty( $data[ $key ] ) ) {
				$value = wp_strip_all_tags( (string) $data[ $key ], true );
				$context .= $label . ': ' . $value . "\n";
			}
		}

		return $context;
	}

	private static function build_generation_context( array $data ): string {
		$context = '';

		$map = [
			'contexto_inicial' => 'Contexto inicial',
			'titulo'           => 'Título do anúncio',
			'tipo_anuncio'     => 'Tipo de anúncio',
			'tipo_plano'       => 'Plano',
			'categorias'       => 'Categorias',
			'descricao_curta'  => 'Descrição curta atual',
			'cidade'           => 'Cidade',
			'estado'           => 'Estado',
			'site'             => 'Site',
		];

		foreach ( $map as $key => $label ) {
			$value = trim( wp_strip_all_tags( (string) ( $data[ $key ] ?? '' ), true ) );
			if ( '' !== $value ) {
				$context .= $label . ': ' . $value . "\n";
			}
		}

		return trim( $context );
	}

	private static function build_anuncio_context( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$meta_keys = [
			'GCEP_tipo_anuncio', 'GCEP_tipo_plano', 'GCEP_descricao_curta',
			'GCEP_descricao_longa', 'GCEP_telefone', 'GCEP_whatsapp',
			'GCEP_email', 'GCEP_endereco_completo', 'GCEP_site', 'GCEP_cnpj',
		];

		$data  = "Título: " . $post->post_title . "\n";
		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( ! empty( $value ) ) {
				$label = str_replace( [ 'GCEP_', '_' ], [ '', ' ' ], $key );
				$data .= ucfirst( $label ) . ": " . wp_strip_all_tags( (string) $value, true ) . "\n";
			}
		}

		$cats = wp_get_object_terms( $post_id, 'gcep_categoria', [ 'fields' => 'names' ] );
		if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
			$data .= "Categorias: " . implode( ', ', $cats ) . "\n";
		}

		return $data;
	}

	private static function parse_ai_response( string $ai_response ): array {
		$result = [
			'approved'      => false,
			'justificativa' => '',
			'error'         => '',
		];

		$raw = trim( preg_replace( '/^```(?:json)?|```$/mi', '', $ai_response ) ?? '' );
		if ( '' === $raw ) {
			$result['error'] = __( 'Resposta vazia da IA.', 'guiawp' );
			return $result;
		}

		$parsed = json_decode( $raw, true );
		if ( ! is_array( $parsed ) ) {
			preg_match( '/\{.*\}/s', $raw, $matches );
			if ( empty( $matches[0] ) ) {
				$result['error'] = __( 'Nao foi possivel interpretar a resposta da IA.', 'guiawp' );
				return $result;
			}

			$parsed = json_decode( $matches[0], true );
			if ( ! is_array( $parsed ) ) {
				$result['error'] = __( 'Erro ao processar resposta da IA.', 'guiawp' );
				return $result;
			}
		}

		$result['approved']      = self::normalize_approved_value( $parsed['approved'] ?? false );
		$result['justificativa'] = self::extract_justificativa( $parsed, $raw, $result['approved'] );

		return $result;
	}

	private static function normalize_approved_value( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value > 0;
		}

		if ( is_string( $value ) ) {
			$normalized = filter_var( trim( $value ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			return null === $normalized ? false : $normalized;
		}

		return ! empty( $value );
	}

	private static function extract_justificativa( array $parsed, string $raw_response, bool $approved ): string {
		$keys = [
			'justificativa',
			'motivo',
			'motivos',
			'motivos_reprovacao',
			'motivos_reprovação',
			'razao',
			'razão',
			'reason',
			'reasons',
			'feedback',
			'message',
			'mensagem',
			'details',
			'erro',
			'erro_detalhado',
			'error',
			'errors',
			'issues',
			'problem',
			'problems',
			'explanation',
			'observacao',
			'observação',
			'observacoes',
			'observações',
		];

		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $parsed ) ) {
				continue;
			}

			$normalized = self::normalize_reason_value( $parsed[ $key ] );
			if ( '' !== $normalized ) {
				return $normalized;
			}
		}

		if ( ! $approved ) {
			foreach ( $parsed as $key => $value ) {
				if ( 'approved' === $key ) {
					continue;
				}

				$normalized = self::normalize_reason_value( $value );
				if ( '' !== $normalized ) {
					return $normalized;
				}
			}
		}

		if ( ! $approved ) {
			return __( 'A validação automática reprovou o anúncio, mas a IA não informou uma justificativa detalhada. Revise título, descrição e dados de contato antes de tentar novamente.', 'guiawp' );
		}

		return '';
	}

	private static function normalize_reason_value( mixed $value ): string {
		if ( is_array( $value ) ) {
			$lines = [];

			foreach ( $value as $item ) {
				$normalized = self::normalize_reason_value( $item );
				if ( '' !== $normalized ) {
					$lines[] = $normalized;
				}
			}

			return sanitize_textarea_field( implode( "\n", array_unique( $lines ) ) );
		}

		if ( is_object( $value ) ) {
			return self::normalize_reason_value( (array) $value );
		}

		if ( is_scalar( $value ) ) {
			return sanitize_textarea_field( (string) $value );
		}

		return '';
	}

	private static function get_description_allowed_html(): array {
		return [
			'p'          => [],
			'br'         => [],
			'strong'     => [],
			'b'          => [],
			'em'         => [],
			'i'          => [],
			'u'          => [],
			'ul'         => [],
			'ol'         => [],
			'li'         => [],
			'h2'         => [],
			'h3'         => [],
			'h4'         => [],
			'blockquote' => [],
			'table'      => [],
			'thead'      => [],
			'tbody'      => [],
			'tr'         => [],
			'th'         => [ 'scope' => true ],
			'td'         => [ 'colspan' => true, 'rowspan' => true ],
			'a'          => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
		];
	}

	private static function normalize_generated_description_html( string $content ): string {
		$raw = trim( preg_replace( '/^```(?:html)?|```$/mi', '', $content ) ?? '' );
		if ( '' === $raw ) {
			return '';
		}

		if ( ! preg_match( '/<\s*[a-z][^>]*>/i', $raw ) ) {
			$raw = wpautop( esc_html( $raw ) );
		}

		return self::sanitize_description_html( $raw );
	}

	private static function get_description_generation_prompt(): string {
		return "Você é um redator comercial especializado em anúncios profissionais para um guia de empresas e serviços.\n"
			. "Sua tarefa é gerar APENAS um fragmento HTML limpo e pronto para ser usado em um editor visual.\n"
			. "Siga estas regras com rigor:\n"
			. "1. Responda somente com HTML, sem markdown, sem explicações e sem bloco ```.\n"
			. "2. Use apenas estas tags: p, br, strong, b, em, i, u, ul, ol, li, h2, h3, h4, blockquote, table, thead, tbody, tr, th, td, a.\n"
			. "3. Não use img, video, audio, iframe, embed, script, style, svg ou qualquer mídia incorporada.\n"
			. "4. O texto deve soar profissional, claro, confiável e comercial, sem exageros artificiais.\n"
			. "5. Estruture a resposta com título e subtítulos quando fizer sentido, além de listas ou tabela se isso ajudar a explicar serviços e diferenciais.\n"
			. "6. Se citar links, use links externos absolutos (https://...).\n"
			. "7. Não invente informações factuais que não estejam no contexto.\n"
			. "8. Produza uma descrição útil para publicação pública do anúncio, com foco em clareza, diferenciais, atendimento e proposta de valor.";
	}

	public static function get_default_prompt(): string {
		return "Você é um moderador de anúncios de um guia comercial. Analise o anúncio com base nos seguintes critérios:\n"
			. "1. O título deve ser claro e descritivo.\n"
			. "2. A descrição deve conter informações úteis sobre o negócio.\n"
			. "3. Os dados de contato devem ser consistentes.\n"
			. "4. O conteúdo não pode conter linguagem ofensiva, spam ou conteúdo impróprio.\n"
			. "5. O anúncio deve representar um negócio ou serviço legítimo.\n"
			. "6. Se rejeitar, a justificativa é obrigatória e deve apontar objetivamente o campo ou trecho que precisa ser corrigido.\n\n"
			. "Se o anúncio atender os critérios, aprove. Se não, rejeite e justifique de forma clara, objetiva e acionável, sem respostas genéricas.";
	}
}
