<?php
/**
 * Dashboard administrativo externo
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.2.0 - 2026-03-11 - CRUD categorias via AJAX, upload logotipo
 * @modified 1.10.0 - 2026-03-22 - Correcao: sincronizacao array $allowed com $defaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Dashboard_Admin {

	public function init(): void {
		add_action( 'admin_post_gcep_admin_approve', [ $this, 'handle_approve' ] );
		add_action( 'admin_post_gcep_admin_reject', [ $this, 'handle_reject' ] );
		add_action( 'admin_post_gcep_admin_save_settings', [ $this, 'handle_save_settings' ] );
		add_action( 'admin_post_gcep_admin_save_blog_post', [ $this, 'handle_save_blog_post' ] );
		add_action( 'admin_post_gcep_admin_delete_blog_post', [ $this, 'handle_delete_blog_post' ] );

		// AJAX: Alterar status de anúncio
		add_action( 'wp_ajax_gcep_change_status', [ $this, 'ajax_change_status' ] );
		add_action( 'wp_ajax_gcep_admin_update_user_password', [ $this, 'ajax_admin_update_user_password' ] );
		add_action( 'wp_ajax_gcep_admin_delete_user', [ $this, 'ajax_admin_delete_user' ] );

		// AJAX: CRUD de categorias
		add_action( 'wp_ajax_gcep_create_category', [ $this, 'ajax_create_category' ] );
		add_action( 'wp_ajax_gcep_update_category', [ $this, 'ajax_update_category' ] );
		add_action( 'wp_ajax_gcep_delete_category', [ $this, 'ajax_delete_category' ] );
		add_action( 'wp_ajax_gcep_upload_category_image', [ $this, 'ajax_upload_category_image' ] );
		add_action( 'wp_ajax_gcep_set_category_ai_image', [ $this, 'ajax_set_category_ai_image' ] );
		add_action( 'wp_ajax_gcep_remove_category_image', [ $this, 'ajax_remove_category_image' ] );
		add_action( 'wp_ajax_gcep_create_blog_category', [ $this, 'ajax_create_blog_category' ] );
		add_action( 'wp_ajax_gcep_update_blog_category', [ $this, 'ajax_update_blog_category' ] );
		add_action( 'wp_ajax_gcep_delete_blog_category', [ $this, 'ajax_delete_blog_category' ] );

		// AJAX: CRUD de planos
		add_action( 'wp_ajax_gcep_create_plan', [ $this, 'ajax_create_plan' ] );
		add_action( 'wp_ajax_gcep_update_plan', [ $this, 'ajax_update_plan' ] );
		add_action( 'wp_ajax_gcep_delete_plan', [ $this, 'ajax_delete_plan' ] );

		// AJAX: Validação IA
		add_action( 'wp_ajax_gcep_validate_ai', [ $this, 'ajax_validate_ai' ] );

		// AJAX: Filtro período admin dashboard
		add_action( 'wp_ajax_gcep_filter_admin_dashboard', [ $this, 'ajax_filter_admin_dashboard' ] );

		// AJAX: Exclusao em massa de blog posts
		add_action( 'wp_ajax_gcep_bulk_delete_blog_posts', [ $this, 'ajax_bulk_delete_blog_posts' ] );
	}

	public function handle_approve(): void {
		$this->verify_admin_action( 'gcep_admin_action' );
		$post_id = intval( $_POST['anuncio_id'] ?? 0 );
		if ( $post_id > 0 ) {
			$plano = get_post_meta( $post_id, 'GCEP_tipo_plano', true );
			if ( 'premium' === $plano ) {
				// Premium: aprovar conteúdo → aguardar pagamento
				$status_pagamento = get_post_meta( $post_id, 'GCEP_status_pagamento', true );
				if ( 'pago' === $status_pagamento ) {
					// Já pago: publicar e setar vigência
					$days = (int) get_post_meta( $post_id, 'GCEP_vigencia_dias', true );
					if ( $days > 0 ) {
						GCEP_Expiration::set_expiration( $post_id, $days );
					}
					update_post_meta( $post_id, 'GCEP_status_anuncio', 'publicado' );
					GCEP_Helpers::sync_anuncio_post_status( $post_id, 'publicado' );
				} else {
					update_post_meta( $post_id, 'GCEP_status_anuncio', 'aguardando_pagamento' );
					GCEP_Helpers::sync_anuncio_post_status( $post_id, 'aguardando_pagamento' );
				}
			} else {
				// Grátis: publicar direto
				update_post_meta( $post_id, 'GCEP_status_anuncio', 'publicado' );
				GCEP_Helpers::sync_anuncio_post_status( $post_id, 'publicado' );
			}
		}
		GCEP_Helpers::redirect_with_message( home_url( '/painel-admin/anuncios' ), 'success', __( 'Anúncio aprovado.', 'guiawp' ) );
	}

	public function handle_reject(): void {
		$this->verify_admin_action( 'gcep_admin_action' );
		$post_id = intval( $_POST['anuncio_id'] ?? 0 );
		if ( $post_id > 0 ) {
			update_post_meta( $post_id, 'GCEP_status_anuncio', 'rejeitado' );
			GCEP_Helpers::sync_anuncio_post_status( $post_id, 'rejeitado' );
			// Salvar motivo da rejeição manual
			$existing_reason = trim( (string) get_post_meta( $post_id, 'GCEP_ai_justificativa', true ) );
			if ( '' === $existing_reason ) {
				update_post_meta( $post_id, 'GCEP_ai_justificativa', __( 'Rejeitado manualmente pelo administrador.', 'guiawp' ) );
			}
		}
		GCEP_Helpers::redirect_with_message( home_url( '/painel-admin/anuncios' ), 'success', __( 'Anúncio rejeitado.', 'guiawp' ) );
	}

	public function handle_save_settings(): void {
		$this->verify_admin_action( 'gcep_save_settings' );
		$allowed = [
			// Informacoes basicas
			'nome_guia', 'telefone_principal', 'email_principal', 'whatsapp_principal',
			// Redes sociais
			'instagram_url', 'facebook_url', 'x_url',
			// Logotipo e favicon
			'logo_url', 'logo_attachment_id', 'logo_largura', 'favicon_url',
			// Cores primarias
			'cor_primaria', 'cor_secundaria', 'cor_destaque', 'cor_fundo', 'cor_texto',
			// Cores do rodape
			'cor_rodape', 'cor_rodape_cor2', 'cor_rodape_tipo', 'cor_rodape_direcao', 'cor_rodape_opacidade',
			'cor_rodape_titulo', 'cor_rodape_texto', 'cor_rodape_link', 'cor_rodape_link_hover',
			'cor_fundo_categorias',
			// Estilos gerais
			'borda_raio',
			// Autenticacao
			'auth_captcha_provider', 'auth_google_site_key', 'auth_google_secret_key',
			'auth_google_min_score', 'auth_turnstile_site_key', 'auth_turnstile_secret_key',
			// Pagamento e PIX
			'chave_pix', 'nome_recebedor', 'cidade_recebedor', 'link_pagamento',
			'texto_instrucoes_pagamento', 'whatsapp_comprovante', 'prazo_aprovacao_horas',
			// Hero section
			'hero_titulo', 'hero_subtitulo', 'hero_imagem', 'hero_imagem_id',
			'hero_overlay_cor1', 'hero_overlay_cor2', 'hero_overlay_direcao',
			'hero_overlay_opacidade1', 'hero_overlay_opacidade2',
			// CTA section
			'cta_imagem', 'cta_imagem_id',
			// Banner e premiums
			'banner_principal', 'texto_premium', 'imagem_premium',
			// IA
			'ia_provider', 'ia_auto_approve', 'ia_prompt',
			// OpenAI
			'openai_api_key', 'openai_auto_approve', 'openai_model', 'openai_prompt',
			// Groq
			'groq_api_key', 'groq_model',
			// Gemini Imagen
			'gemini_imagen_api_key',
			// Mercado Pago
			'mercadopago_access_token', 'mercadopago_public_key', 'mercadopago_webhook_secret',
			// Pagou
			'pagou_api_key', 'gateway_ativo',
			// AdSense
			'adsense_enabled', 'adsense_script', 'adsense_in_article', 'adsense_intervalo_palavras',
		];
		// Campos que aceitam HTML (scripts do AdSense)
		$campos_html = [ 'adsense_script', 'adsense_in_article' ];

		// Campos de prompt/texto longo
		$campos_textarea = [ 'ia_prompt', 'openai_prompt' ];

		$data = [];
		foreach ( $allowed as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = wp_unslash( $_POST[ $key ] );
				if ( in_array( $key, [ 'telefone_principal', 'whatsapp_principal', 'whatsapp_comprovante' ], true ) ) {
					$value = GCEP_Helpers::sanitize_phone( $value );
				} elseif ( in_array( $key, $campos_html, true ) ) {
					// AdSense scripts: permitir tags de script especificas
					$value = wp_kses( $value, [
						'script' => [ 'async' => [], 'src' => [], 'crossorigin' => [], 'data-ad-client' => [] ],
						'ins'    => [ 'class' => [], 'style' => [], 'data-ad-client' => [], 'data-ad-slot' => [], 'data-ad-format' => [], 'data-full-width-responsive' => [] ],
					] );
				} elseif ( in_array( $key, $campos_textarea, true ) ) {
					$value = sanitize_textarea_field( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
				$data[ $key ] = $value;
			}
		}

		// Upload de logotipo — deletar antigo antes de salvar novo
		if ( ! empty( $_FILES['gcep_logo_guia']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Deletar logo antigo
			$old_att_id = GCEP_Settings::get( 'logo_attachment_id' );
			$old_url    = GCEP_Settings::get( 'logo_url' );
			if ( $old_att_id ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_att_id );
			} elseif ( $old_url ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_url );
			}

			$attachment_id = media_handle_upload( 'gcep_logo_guia', 0 );
			if ( ! is_wp_error( $attachment_id ) ) {
				$data['logo_url']           = wp_get_attachment_url( $attachment_id );
				$data['logo_attachment_id'] = $attachment_id;
			}
		}

		// Upload de imagem hero
		if ( ! empty( $_FILES['gcep_hero_imagem']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$old_hero_id = GCEP_Settings::get( 'hero_imagem_id' );
			if ( $old_hero_id ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_hero_id );
			}

			$hero_att_id = media_handle_upload( 'gcep_hero_imagem', 0 );
			if ( ! is_wp_error( $hero_att_id ) ) {
				$data['hero_imagem']    = wp_get_attachment_url( $hero_att_id );
				$data['hero_imagem_id'] = $hero_att_id;
			}
		}

		// Remover imagem hero
		if ( ! empty( $_POST['gcep_remove_hero_imagem'] ) ) {
			$old_hero_id = GCEP_Settings::get( 'hero_imagem_id' );
			if ( $old_hero_id ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_hero_id );
			}
			$data['hero_imagem']    = '';
			$data['hero_imagem_id'] = '';
		}

		// Upload imagem CTA
		if ( ! empty( $_FILES['gcep_cta_imagem']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$old_cta_id = GCEP_Settings::get( 'cta_imagem_id' );
			if ( $old_cta_id ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_cta_id );
			}

			$cta_att_id = media_handle_upload( 'gcep_cta_imagem', 0 );
			if ( ! is_wp_error( $cta_att_id ) ) {
				$data['cta_imagem']    = wp_get_attachment_url( $cta_att_id );
				$data['cta_imagem_id'] = $cta_att_id;
			}
		}

		// Remover imagem CTA
		if ( ! empty( $_POST['gcep_remove_cta_imagem'] ) ) {
			$old_cta_id = GCEP_Settings::get( 'cta_imagem_id' );
			if ( $old_cta_id ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_cta_id );
			}
			$data['cta_imagem']    = '';
			$data['cta_imagem_id'] = '';
		}

		// Remover logotipo — deletar arquivo fisico
		if ( ! empty( $_POST['gcep_remove_logo'] ) ) {
			$old_att_id = GCEP_Settings::get( 'logo_attachment_id' );
			$old_url    = GCEP_Settings::get( 'logo_url' );
			if ( $old_att_id ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_att_id );
			} elseif ( $old_url ) {
				GCEP_Image_Cleanup::delete_settings_image( $old_url );
			}
			$data['logo_url']           = '';
			$data['logo_attachment_id'] = '';
		}

		GCEP_Settings::save_all( $data );
		GCEP_Helpers::redirect_with_message( home_url( '/painel-admin/configuracoes' ), 'success', __( 'Configurações salvas.', 'guiawp' ) );
	}

	public function handle_save_blog_post(): void {
		$this->verify_admin_action( 'gcep_admin_save_blog_post' );

		$post_id    = absint( $_POST['gcep_blog_post_id'] ?? 0 );
		$title      = sanitize_text_field( wp_unslash( $_POST['gcep_blog_title'] ?? '' ) );
		$excerpt    = sanitize_textarea_field( wp_unslash( $_POST['gcep_blog_excerpt'] ?? '' ) );
		$content    = wp_kses_post( wp_unslash( $_POST['gcep_blog_content'] ?? '' ) );
		$status     = sanitize_key( wp_unslash( $_POST['gcep_blog_status'] ?? 'draft' ) );
		$categories = array_filter( array_map( 'absint', (array) ( $_POST['gcep_blog_categories'] ?? [] ) ) );
		$allowed    = [ 'publish', 'draft', 'pending', 'private' ];

		if ( '' === $title ) {
			$this->redirect_blog_admin_with_message(
				'error',
				__( 'O titulo do post e obrigatorio.', 'guiawp' ),
				$this->get_blog_admin_return_args( $post_id )
			);
		}

		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'draft';
		}

		$post_data = [
			'post_type'    => 'post',
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
			'post_status'  => $status,
			'post_category'=> $categories,
		];

		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( ! $post || 'post' !== $post->post_type ) {
				$this->redirect_blog_admin_with_message( 'error', __( 'Post nao encontrado.', 'guiawp' ) );
			}
			$post_data['ID'] = $post_id;
		} else {
			$post_data['post_author'] = get_current_user_id();
		}

		$saved_post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $saved_post_id ) ) {
			$this->redirect_blog_admin_with_message(
				'error',
				$saved_post_id->get_error_message(),
				$this->get_blog_admin_return_args( $post_id )
			);
		}

		$thumb_id = (int) get_post_thumbnail_id( $saved_post_id );

		// Imagem gerada por IA (attachment já existe)
		$ai_thumb_id = absint( $_POST['gcep_blog_ai_thumbnail_id'] ?? 0 );

		if ( $ai_thumb_id > 0 && wp_attachment_is_image( $ai_thumb_id ) ) {
			set_post_thumbnail( $saved_post_id, $ai_thumb_id );

			if ( $thumb_id > 0 && $thumb_id !== $ai_thumb_id ) {
				GCEP_Image_Cleanup::safe_delete_attachment( $thumb_id, $saved_post_id );
			}
		} elseif ( ! empty( $_FILES['gcep_blog_thumbnail']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = media_handle_upload( 'gcep_blog_thumbnail', $saved_post_id );
			if ( is_wp_error( $attachment_id ) ) {
				$this->redirect_blog_admin_with_message(
					'error',
					$attachment_id->get_error_message(),
					[ 'edit' => $saved_post_id ]
				);
			}

			set_post_thumbnail( $saved_post_id, $attachment_id );

			if ( $thumb_id > 0 && $thumb_id !== $attachment_id ) {
				GCEP_Image_Cleanup::safe_delete_attachment( $thumb_id, $saved_post_id );
			}
		} elseif ( ! empty( $_POST['gcep_blog_remove_thumbnail'] ) && $thumb_id > 0 ) {
			delete_post_thumbnail( $saved_post_id );
			GCEP_Image_Cleanup::safe_delete_attachment( $thumb_id, $saved_post_id );
		}

		// Salvar video de destaque
		$video_url = esc_url_raw( wp_unslash( $_POST['gcep_blog_video'] ?? '' ) );
		if ( '' !== $video_url ) {
			update_post_meta( $saved_post_id, '_gcep_video_destaque', $video_url );
		} else {
			delete_post_meta( $saved_post_id, '_gcep_video_destaque' );
		}

		// Salvar anuncios relacionados (max 2)
		$anuncios_rel = [];
		if ( ! empty( $_POST['gcep_anuncios_rel'] ) && is_array( $_POST['gcep_anuncios_rel'] ) ) {
			$anuncios_rel = array_slice( array_filter( array_map( 'absint', $_POST['gcep_anuncios_rel'] ) ), 0, 2 );
		}
		if ( empty( $anuncios_rel ) ) {
			delete_post_meta( $saved_post_id, '_gcep_anuncios_relacionados' );
		} else {
			update_post_meta( $saved_post_id, '_gcep_anuncios_relacionados', $anuncios_rel );
		}

		$this->redirect_blog_admin_with_message(
			'success',
			$post_id > 0 ? __( 'Post atualizado com sucesso.', 'guiawp' ) : __( 'Post criado com sucesso.', 'guiawp' )
		);
	}

	public function handle_delete_blog_post(): void {
		$this->verify_admin_action( 'gcep_admin_delete_blog_post' );

		$post_id = absint( $_POST['gcep_blog_post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			$this->redirect_blog_admin_with_message( 'error', __( 'Post invalido.', 'guiawp' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'post' !== $post->post_type ) {
			$this->redirect_blog_admin_with_message( 'error', __( 'Post nao encontrado.', 'guiawp' ) );
		}

		$thumb_id = (int) get_post_thumbnail_id( $post_id );
		$deleted  = wp_delete_post( $post_id, true );

		if ( ! $deleted ) {
			$this->redirect_blog_admin_with_message( 'error', __( 'Nao foi possivel remover o post.', 'guiawp' ) );
		}

		if ( $thumb_id > 0 ) {
			GCEP_Image_Cleanup::safe_delete_attachment( $thumb_id, $post_id );
		}

		$this->redirect_blog_admin_with_message( 'success', __( 'Post removido com sucesso.', 'guiawp' ) );
	}

	/**
	 * AJAX: Exclusao em massa de blog posts com remocao de midias
	 *
	 * @author Dante Testa - https://dantetesta.com.br
	 * @since 1.9.3 - 2026-03-21
	 */
	public function ajax_bulk_delete_blog_posts(): void {
		check_ajax_referer( 'gcep_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permissao negada.', 'guiawp' ) ] );
		}

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'absint', (array) $_POST['post_ids'] ) : [];
		$post_ids = array_filter( $post_ids );

		if ( empty( $post_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'Nenhum post selecionado.', 'guiawp' ) ] );
		}

		$removidos = 0;

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || 'post' !== $post->post_type ) {
				continue;
			}

			// Remover thumbnail
			$thumb_id = (int) get_post_thumbnail_id( $post_id );
			if ( $thumb_id > 0 ) {
				GCEP_Image_Cleanup::safe_delete_attachment( $thumb_id, $post_id );
			}

			// Remover todas as midias anexadas ao post
			$attachments = get_posts( [
				'post_type'      => 'attachment',
				'post_parent'    => $post_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			] );
			foreach ( $attachments as $att_id ) {
				wp_delete_attachment( $att_id, true );
			}

			// Remover post permanentemente
			$deleted = wp_delete_post( $post_id, true );
			if ( $deleted ) {
				$removidos++;
			}
		}

		wp_send_json_success( [
			'message'   => sprintf( __( '%d post(s) removido(s) com sucesso.', 'guiawp' ), $removidos ),
			'removidos' => $removidos,
		] );
	}

	private function verify_admin_action( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Acesso negado.', 'guiawp' ) );
		}
		if ( ! isset( $_POST['gcep_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_nonce'], $action ) ) {
			wp_die( __( 'Erro de segurança.', 'guiawp' ) );
		}
	}

	private function get_blog_admin_url( array $args = [] ): string {
		$url = home_url( '/painel-admin/blog' );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	private function get_blog_admin_return_args( int $post_id = 0 ): array {
		$args = [];

		if ( $post_id > 0 ) {
			$args['edit'] = $post_id;
		} else {
			$args['novo'] = 1;
		}

		return $args;
	}

	private function redirect_blog_admin_with_message( string $type, string $message, array $args = [] ): void {
		$args['gcep_msg']  = $message;
		$args['gcep_type'] = $type;

		wp_safe_redirect( $this->get_blog_admin_url( $args ) );
		exit;
	}

	public static function get_stats(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT COALESCE(pm.meta_value, 'rascunho') AS status, COUNT(*) AS total
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id
				AND pm.meta_key = 'GCEP_status_anuncio'
			WHERE p.post_type = 'gcep_anuncio'
			AND p.post_status IN ('publish', 'draft')
			GROUP BY COALESCE(pm.meta_value, 'rascunho')",
			ARRAY_A
		);

		$total      = 0;
		$pendentes  = 0;
		$publicados = 0;

		foreach ( $rows as $row ) {
			$count  = (int) ( $row['total'] ?? 0 );
			$status = (string) ( $row['status'] ?? 'rascunho' );

			$total += $count;

			if ( in_array( $status, [ 'aguardando_aprovacao', 'aguardando_pagamento' ], true ) ) {
				$pendentes += $count;
			}

			if ( 'publicado' === $status ) {
				$publicados += $count;
			}
		}

		$user_counts = count_users();
		$usuarios    = (int) ( $user_counts['avail_roles']['gcep_anunciante'] ?? 0 );

		return [
			'total_anuncios'  => $total,
			'pendentes'       => $pendentes,
			'publicados'      => $publicados,
			'total_usuarios'  => $usuarios,
		];
	}

	// ===== Admin Dashboard: stats com filtro de periodo =====

	public static function get_admin_stats( int $year = 0, int $month = 0 ): array {
		global $wpdb;

		$date_clause = '';
		$params      = [];

		if ( $year > 0 ) {
			$date_clause .= ' AND YEAR(p.post_date) = %d';
			$params[]     = $year;
		}
		if ( $month > 0 ) {
			$date_clause .= ' AND MONTH(p.post_date) = %d';
			$params[]     = $month;
		}

		$query = "SELECT COALESCE(pm.meta_value, 'rascunho') AS status, COUNT(*) AS total
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id AND pm.meta_key = 'GCEP_status_anuncio'
			WHERE p.post_type = 'gcep_anuncio'
			AND p.post_status IN ('publish', 'draft')
			$date_clause
			GROUP BY COALESCE(pm.meta_value, 'rascunho')";

		$rows = empty( $params )
			? $wpdb->get_results( $query, ARRAY_A )
			: $wpdb->get_results( $wpdb->prepare( $query, ...$params ), ARRAY_A );

		$total      = 0;
		$pendentes  = 0;
		$publicados = 0;
		$rejeitados = 0;

		foreach ( $rows as $row ) {
			$count  = (int) ( $row['total'] ?? 0 );
			$status = (string) ( $row['status'] ?? 'rascunho' );
			$total += $count;

			if ( 'publicado' === $status ) {
				$publicados += $count;
			} elseif ( in_array( $status, [ 'aguardando_aprovacao', 'aguardando_pagamento' ], true ) ) {
				$pendentes += $count;
			} elseif ( 'rejeitado' === $status ) {
				$rejeitados += $count;
			}
		}

		$user_counts = count_users();
		$usuarios    = (int) ( $user_counts['avail_roles']['gcep_anunciante'] ?? 0 );

		$visitas = 0;
		if ( class_exists( 'GCEP_Analytics' ) ) {
			$visitas = GCEP_Analytics::get_global_total_views( $year, $month );
		}

		return [
			'total_anuncios' => $total,
			'publicados'     => $publicados,
			'pendentes'      => $pendentes,
			'rejeitados'     => $rejeitados,
			'total_usuarios' => $usuarios,
			'visitas'        => $visitas,
		];
	}

	public static function get_admin_available_periods(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT YEAR(post_date) AS period_year, MONTH(post_date) AS period_month
			FROM {$wpdb->posts}
			WHERE post_type = 'gcep_anuncio'
			AND post_status IN ('publish', 'draft')
			GROUP BY YEAR(post_date), MONTH(post_date)
			ORDER BY YEAR(post_date) DESC, MONTH(post_date) DESC",
			ARRAY_A
		);

		$years          = [];
		$months_by_year = [];

		foreach ( $rows as $row ) {
			$year  = (int) ( $row['period_year'] ?? 0 );
			$month = (int) ( $row['period_month'] ?? 0 );
			if ( $year <= 0 || $month < 1 || $month > 12 ) {
				continue;
			}
			if ( ! in_array( $year, $years, true ) ) {
				$years[] = $year;
			}
			if ( ! isset( $months_by_year[ $year ] ) ) {
				$months_by_year[ $year ] = [];
			}
			if ( ! in_array( $month, $months_by_year[ $year ], true ) ) {
				$months_by_year[ $year ][] = $month;
			}
		}

		$cy = (int) current_time( 'Y' );
		$cm = (int) current_time( 'm' );
		if ( ! in_array( $cy, $years, true ) ) {
			array_unshift( $years, $cy );
		}
		if ( ! isset( $months_by_year[ $cy ] ) ) {
			$months_by_year[ $cy ] = [];
		}
		if ( ! in_array( $cm, $months_by_year[ $cy ], true ) ) {
			array_unshift( $months_by_year[ $cy ], $cm );
		}

		rsort( $years );
		foreach ( $months_by_year as &$months ) {
			rsort( $months );
		}

		return [
			'years'          => $years,
			'months_by_year' => $months_by_year,
			'latest_year'    => $years[0] ?? $cy,
			'latest_month'   => ! empty( $months_by_year[ $years[0] ?? $cy ] ) ? $months_by_year[ $years[0] ?? $cy ][0] : $cm,
		];
	}

	public static function resolve_admin_dashboard_period( int $requested_year = 0, int $requested_month = 0 ): array {
		$available         = self::get_admin_available_periods();
		$available_years   = $available['years'] ?? [];
		$months_by_year    = $available['months_by_year'] ?? [];
		$has_periods       = ! empty( $available_years );
		$default_year      = $has_periods ? (int) ( $available['latest_year'] ?? 0 ) : (int) current_time( 'Y' );
		$filter_year       = $requested_year > 0 ? $requested_year : $default_year;

		if ( $has_periods && ! in_array( $filter_year, $available_years, true ) ) {
			$filter_year = $default_year;
		}

		$available_months  = $has_periods ? ( $months_by_year[ $filter_year ] ?? [] ) : [];
		$default_month     = ! empty( $available_months ) ? (int) $available_months[0] : (int) current_time( 'm' );
		$filter_month      = $requested_month > 0 ? $requested_month : $default_month;

		if ( $has_periods && ! in_array( $filter_month, $available_months, true ) ) {
			$filter_month = $default_month;
		}

		return [
			'available_years'  => $available_years,
			'months_by_year'   => $months_by_year,
			'available_months' => $available_months,
			'has_periods'      => $has_periods,
			'year'             => $filter_year,
			'month'            => $filter_month,
		];
	}

	public static function get_admin_chart_data( int $year, int $month ): array {
		$days = (int) date( 't', strtotime( "$year-$month-01" ) );
		$data = array_fill( 1, $days, 0 );

		if ( ! class_exists( 'GCEP_Analytics' ) ) {
			return $data;
		}

		return GCEP_Analytics::get_global_views_by_month( $year, $month );
	}

	public function ajax_filter_admin_dashboard(): void {
		$this->verify_ajax_admin();

		$period       = self::resolve_admin_dashboard_period(
			absint( $_POST['year'] ?? 0 ),
			absint( $_POST['month'] ?? 0 )
		);
		$filter_year  = (int) $period['year'];
		$filter_month = (int) $period['month'];

		$stats       = self::get_admin_stats( $filter_year, $filter_month );
		$chart_data  = self::get_admin_chart_data( $filter_year, $filter_month );

		$top_anuncios = class_exists( 'GCEP_Analytics' )
			? GCEP_Analytics::get_top_anuncios( 20, $filter_year, $filter_month )
			: [];
		$top_posts = class_exists( 'GCEP_Analytics' )
			? GCEP_Analytics::get_top_blog_posts( 20, $filter_year, $filter_month )
			: [];

		wp_send_json_success( [
			'period' => [
				'year'  => $filter_year,
				'month' => $filter_month,
				'label' => sprintf(
					__( 'Visitas por Dia - %1$s/%2$s', 'guiawp' ),
					date_i18n( 'F', mktime( 0, 0, 0, $filter_month, 1 ) ),
					$filter_year
				),
			],
			'stats' => $stats,
			'chart' => [
				'labels' => array_values( array_keys( $chart_data ) ),
				'data'   => array_values( array_map( 'intval', $chart_data ) ),
			],
			'top_anuncios' => array_map( function ( $r ) {
				return [
					'title' => $r['post_title'] ?? '',
					'views' => (int) ( $r['views'] ?? 0 ),
				];
			}, $top_anuncios ),
			'top_posts' => array_map( function ( $r ) {
				return [
					'title' => $r['post_title'] ?? '',
					'views' => (int) ( $r['views'] ?? 0 ),
				];
			}, $top_posts ),
		] );
	}

	// ===== AJAX: Alterar status de anúncio =====
	public function ajax_change_status(): void {
		$this->verify_ajax_admin();
		$post_id = intval( $_POST['post_id'] ?? 0 );
		$status  = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );

		$allowed = [ 'publicado', 'rejeitado', 'aguardando_aprovacao', 'aguardando_pagamento', 'rascunho' ];
		if ( $post_id <= 0 || ! in_array( $status, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Dados invalidos.', 'guiawp' ) ] );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'gcep_anuncio' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Anuncio nao encontrado.', 'guiawp' ) ] );
		}

		// Premium sem pagamento nao pode ser publicado — redireciona para aguardando_pagamento
		if ( 'publicado' === $status ) {
			$plano            = get_post_meta( $post_id, 'GCEP_tipo_plano', true );
			$status_pagamento = get_post_meta( $post_id, 'GCEP_status_pagamento', true );
			if ( 'premium' === $plano && 'pago' !== $status_pagamento ) {
				$status = 'aguardando_pagamento';
			} elseif ( 'premium' === $plano && 'pago' === $status_pagamento ) {
				// Premium pago: setar vigencia ao publicar
				$days = (int) get_post_meta( $post_id, 'GCEP_vigencia_dias', true );
				if ( $days > 0 ) {
					GCEP_Expiration::set_expiration( $post_id, $days );
				}
			}
		}

		update_post_meta( $post_id, 'GCEP_status_anuncio', $status );
		GCEP_Helpers::sync_anuncio_post_status( $post_id, $status );

		// Salvar motivo padrão ao rejeitar manualmente
		if ( 'rejeitado' === $status ) {
			$existing_reason = trim( (string) get_post_meta( $post_id, 'GCEP_ai_justificativa', true ) );
			if ( '' === $existing_reason ) {
				update_post_meta( $post_id, 'GCEP_ai_justificativa', __( 'Rejeitado manualmente pelo administrador.', 'guiawp' ) );
			}
		}

		$color = GCEP_Helpers::get_status_color( $status );
		$label = GCEP_Helpers::get_status_label( $status );

		wp_send_json_success( [
			'status' => $status,
			'color'  => $color,
			'label'  => $label,
		] );
	}

	// ===== AJAX: Atualizar senha de usuário =====
	public function ajax_admin_update_user_password(): void {
		$this->verify_ajax_admin();

		$user_id    = absint( $_POST['user_id'] ?? 0 );
		$password   = (string) ( wp_unslash( $_POST['password'] ?? '' ) );
		$send_email = ! empty( $_POST['send_email'] );

		if ( $user_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Usuário inválido.', 'guiawp' ) ] );
		}

		if ( strlen( $password ) < 6 ) {
			wp_send_json_error( [ 'message' => __( 'A senha deve ter pelo menos 6 caracteres.', 'guiawp' ) ] );
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User || ! in_array( 'gcep_anunciante', (array) $user->roles, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Usuário anunciante não encontrado.', 'guiawp' ) ] );
		}

		$updated = wp_update_user(
			[
				'ID'        => $user_id,
				'user_pass' => $password,
			]
		);

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( [ 'message' => $updated->get_error_message() ] );
		}

		$response = [
			'message'    => __( 'Senha atualizada com sucesso.', 'guiawp' ),
			'message_type' => 'success',
			'email_sent' => false,
		];

		if ( $send_email ) {
			$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
			$subject   = sprintf( __( '[%s] Nova senha de acesso', 'guiawp' ), $site_name );
			$body      = implode(
				"\n\n",
				[
					sprintf( __( 'Olá, %s.', 'guiawp' ), $user->display_name ?: $user->user_login ),
					__( 'Sua senha de acesso foi redefinida por um administrador.', 'guiawp' ),
					__( 'Por segurança, a nova senha não é enviada por e-mail.', 'guiawp' ),
					sprintf( __( 'Acesse seu painel em: %s', 'guiawp' ), home_url( '/login' ) ),
				]
			);

			$reset_key = get_password_reset_key( $user );
			if ( ! is_wp_error( $reset_key ) ) {
				$reset_url = network_site_url( 'wp-login.php?action=rp&key=' . rawurlencode( $reset_key ) . '&login=' . rawurlencode( $user->user_login ), 'login' );
				$body     .= "\n\n" . sprintf( __( 'Se preferir, você pode definir uma nova senha por este link seguro: %s', 'guiawp' ), $reset_url );
			}

			$mail_sent = wp_mail( $user->user_email, $subject, $body );

			if ( $mail_sent ) {
				$response['message']    = __( 'Senha atualizada e e-mail enviado com aviso e link seguro.', 'guiawp' );
				$response['email_sent'] = true;
			} else {
				$response['message']      = __( 'Senha atualizada, mas o e-mail não pôde ser enviado. Verifique o plugin SMTP configurado no WordPress.', 'guiawp' );
				$response['message_type'] = 'warning';
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * AJAX: Excluir usuario anunciante com limpeza completa de dados
	 *
	 * @author Dante Testa <https://dantetesta.com.br>
	 * @since 1.8.8 - 2026-03-21
	 */
	public function ajax_admin_delete_user(): void {
		$this->verify_ajax_admin();

		$user_id = absint( $_POST['user_id'] ?? 0 );
		if ( $user_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Usuário inválido.', 'guiawp' ) ] );
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User || ! in_array( 'gcep_anunciante', (array) $user->roles, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Usuário anunciante não encontrado.', 'guiawp' ) ] );
		}

		// Nao permitir exclusao de administradores
		if ( user_can( $user_id, 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Não é permitido excluir administradores.', 'guiawp' ) ] );
		}

		// Contar anuncios para o log
		$anuncios = get_posts( [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => 'any',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		$total_anuncios = count( $anuncios );

		// Deletar cada anuncio com limpeza de midias (dispara hooks)
		foreach ( $anuncios as $anuncio_id ) {
			wp_delete_post( $anuncio_id, true );
		}

		// Limpar attachments orfaos do usuario (midias sem post pai)
		$orphan_attachments = get_posts( [
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		foreach ( $orphan_attachments as $att_id ) {
			wp_delete_attachment( $att_id, true );
		}

		// Limpar analytics
		global $wpdb;
		$analytics_table = $wpdb->prefix . 'gcep_analytics';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $analytics_table ) ) === $analytics_table ) {
			$wpdb->delete( $analytics_table, [ 'user_id' => $user_id ], [ '%d' ] );
		}

		// Limpar transients do usuario
		delete_transient( 'gcep_delete_code_' . $user_id );

		// Deletar conta WP (sem reassign)
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );

		wp_send_json_success( [
			'message' => sprintf(
				__( 'Usuário excluído com sucesso. %d anúncio(s) e todas as mídias foram removidos.', 'guiawp' ),
				$total_anuncios
			),
		] );
	}

	// ===== AJAX: Criar categoria =====
	public function ajax_create_category(): void {
		$this->verify_ajax_admin();
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$icon = sanitize_text_field( wp_unslash( $_POST['icon'] ?? 'category' ) );

		if ( empty( $name ) ) {
			wp_send_json_error( [ 'message' => __( 'Nome é obrigatório.', 'guiawp' ) ] );
		}

		$result = wp_insert_term( $name, 'gcep_categoria' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$term_id = $result['term_id'];
		update_term_meta( $term_id, 'gcep_icon', $icon );

		$term = get_term( $term_id, 'gcep_categoria' );
		wp_send_json_success( [
			'term_id' => $term_id,
			'name'    => $term->name,
			'icon'    => $icon,
			'count'   => 0,
			'message' => __( 'Categoria criada com sucesso.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Editar categoria =====
	public function ajax_update_category(): void {
		$this->verify_ajax_admin();
		$term_id = intval( $_POST['term_id'] ?? 0 );
		$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$icon    = sanitize_text_field( wp_unslash( $_POST['icon'] ?? 'category' ) );

		if ( $term_id <= 0 || empty( $name ) ) {
			wp_send_json_error( [ 'message' => __( 'Dados inválidos.', 'guiawp' ) ] );
		}

		$result = wp_update_term( $term_id, 'gcep_categoria', [ 'name' => $name ] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		update_term_meta( $term_id, 'gcep_icon', $icon );

		wp_send_json_success( [
			'term_id' => $term_id,
			'name'    => $name,
			'icon'    => $icon,
			'message' => __( 'Categoria atualizada.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Excluir categoria =====
	public function ajax_delete_category(): void {
		$this->verify_ajax_admin();
		$term_id = intval( $_POST['term_id'] ?? 0 );

		if ( $term_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'ID inválido.', 'guiawp' ) ] );
		}

		// Limpar attachment da imagem da categoria antes de deletar o termo
		$img_id = absint( get_term_meta( $term_id, 'gcep_image', true ) );
		if ( $img_id > 0 ) {
			wp_delete_attachment( $img_id, true );
		}

		$result = wp_delete_term( $term_id, 'gcep_categoria' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [
			'term_id' => $term_id,
			'message' => __( 'Categoria excluída.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Upload imagem da categoria (crop quadrado 400x400 WebP) =====
	public function ajax_upload_category_image(): void {
		$this->verify_ajax_admin();

		$term_id = absint( $_POST['term_id'] ?? 0 );
		if ( $term_id <= 0 || ! term_exists( $term_id, 'gcep_categoria' ) ) {
			wp_send_json_error( [ 'message' => __( 'Categoria inválida.', 'guiawp' ) ] );
		}

		if ( empty( $_FILES['category_image'] ) || ! empty( $_FILES['category_image']['error'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Nenhuma imagem recebida.', 'guiawp' ) ] );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'category_image', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
		}

		// Remover imagem anterior se existir
		$old_id = absint( get_term_meta( $term_id, 'gcep_image', true ) );
		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		update_term_meta( $term_id, 'gcep_image', $attachment_id );

		$url = wp_get_attachment_image_url( $attachment_id, 'medium' );

		wp_send_json_success( [
			'term_id'       => $term_id,
			'attachment_id' => $attachment_id,
			'url'           => $url,
			'message'       => __( 'Imagem da categoria salva com sucesso.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Vincular imagem gerada por IA à categoria =====
	public function ajax_set_category_ai_image(): void {
		$this->verify_ajax_admin();

		$term_id       = absint( $_POST['term_id'] ?? 0 );
		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );

		if ( $term_id <= 0 || ! term_exists( $term_id, 'gcep_categoria' ) ) {
			wp_send_json_error( [ 'message' => __( 'Categoria inválida.', 'guiawp' ) ] );
		}

		if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Imagem inválida.', 'guiawp' ) ] );
		}

		// Remover imagem anterior se existir
		$old_id = absint( get_term_meta( $term_id, 'gcep_image', true ) );
		if ( $old_id > 0 && $old_id !== $attachment_id ) {
			wp_delete_attachment( $old_id, true );
		}

		update_term_meta( $term_id, 'gcep_image', $attachment_id );

		// Usar 'full' — imagem IA já é 400x400 otimizada, 'medium' pode falhar para WebP
		$url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $url ) {
			$url = wp_get_attachment_url( $attachment_id );
		}

		wp_send_json_success( [
			'term_id'       => $term_id,
			'attachment_id' => $attachment_id,
			'url'           => $url,
			'message'       => __( 'Imagem gerada por IA vinculada com sucesso.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Remover imagem da categoria =====
	public function ajax_remove_category_image(): void {
		$this->verify_ajax_admin();

		$term_id = absint( $_POST['term_id'] ?? 0 );
		if ( $term_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'ID inválido.', 'guiawp' ) ] );
		}

		$old_id = absint( get_term_meta( $term_id, 'gcep_image', true ) );
		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		delete_term_meta( $term_id, 'gcep_image' );

		wp_send_json_success( [
			'term_id' => $term_id,
			'message' => __( 'Imagem removida.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Criar categoria do blog =====
	public function ajax_create_blog_category(): void {
		$this->verify_ajax_admin();
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

		if ( '' === $name ) {
			wp_send_json_error( [ 'message' => __( 'Nome da categoria é obrigatório.', 'guiawp' ) ] );
		}

		$result = wp_insert_term( $name, 'category' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$term = get_term( (int) $result['term_id'], 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( [ 'message' => __( 'Categoria criada, mas não foi possível carregá-la.', 'guiawp' ) ] );
		}

		wp_send_json_success( [
			'term_id' => (int) $term->term_id,
			'name'    => $term->name,
			'slug'    => $term->slug,
			'count'   => (int) $term->count,
			'message' => __( 'Categoria do blog criada com sucesso.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Editar categoria do blog =====
	public function ajax_update_blog_category(): void {
		$this->verify_ajax_admin();
		$term_id = absint( $_POST['term_id'] ?? 0 );
		$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

		if ( $term_id <= 0 || '' === $name ) {
			wp_send_json_error( [ 'message' => __( 'Dados inválidos.', 'guiawp' ) ] );
		}

		$result = wp_update_term(
			$term_id,
			'category',
			[
				'name' => $name,
			]
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$term = get_term( $term_id, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( [ 'message' => __( 'Categoria atualizada, mas não foi possível carregá-la.', 'guiawp' ) ] );
		}

		wp_send_json_success( [
			'term_id' => (int) $term->term_id,
			'name'    => $term->name,
			'slug'    => $term->slug,
			'count'   => (int) $term->count,
			'message' => __( 'Categoria do blog atualizada.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Excluir categoria do blog =====
	public function ajax_delete_blog_category(): void {
		$this->verify_ajax_admin();
		$term_id            = absint( $_POST['term_id'] ?? 0 );
		$default_category_id = (int) get_option( 'default_category' );

		if ( $term_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'ID inválido.', 'guiawp' ) ] );
		}

		if ( $default_category_id === $term_id ) {
			wp_send_json_error( [ 'message' => __( 'A categoria padrão do WordPress não pode ser removida.', 'guiawp' ) ] );
		}

		$result = wp_delete_term( $term_id, 'category' );
		if ( is_wp_error( $result ) || false === $result ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Não foi possível excluir a categoria.', 'guiawp' );
			wp_send_json_error( [ 'message' => $message ] );
		}

		wp_send_json_success( [
			'term_id' => $term_id,
			'message' => __( 'Categoria do blog excluída.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Criar plano =====
	public function ajax_create_plan(): void {
		$this->verify_ajax_admin();
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$days   = absint( $_POST['days'] ?? 30 );
		$price  = floatval( $_POST['price'] ?? 0 );
		$desc   = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );

		if ( empty( $name ) || $days <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Nome e dias são obrigatórios.', 'guiawp' ) ] );
		}

		$id = GCEP_Plans::create( [
			'name'        => $name,
			'days'        => $days,
			'price'       => $price,
			'description' => $desc,
			'status'      => $status,
		] );

		if ( $id <= 0 ) {
			wp_send_json_error( [ 'message' => $this->get_plan_error_message( __( 'Erro ao criar plano.', 'guiawp' ) ) ] );
		}

		$plan = GCEP_Plans::get( $id );
		wp_send_json_success( [
			'plan'    => $plan,
			'message' => __( 'Plano criado com sucesso.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Atualizar plano =====
	public function ajax_update_plan(): void {
		$this->verify_ajax_admin();
		$id     = absint( $_POST['plan_id'] ?? 0 );
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$days   = absint( $_POST['days'] ?? 30 );
		$price  = floatval( $_POST['price'] ?? 0 );
		$desc   = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );

		if ( $id <= 0 || empty( $name ) || $days <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Dados inválidos.', 'guiawp' ) ] );
		}

		$updated = GCEP_Plans::update( $id, [
			'name'        => $name,
			'days'        => $days,
			'price'       => $price,
			'description' => $desc,
			'status'      => $status,
		] );

		if ( ! $updated ) {
			wp_send_json_error( [ 'message' => $this->get_plan_error_message( __( 'Erro ao atualizar plano.', 'guiawp' ) ) ] );
		}

		wp_send_json_success( [
			'plan'    => GCEP_Plans::get( $id ),
			'message' => __( 'Plano atualizado.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Excluir plano =====
	public function ajax_delete_plan(): void {
		$this->verify_ajax_admin();
		$id = absint( $_POST['plan_id'] ?? 0 );

		if ( $id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'ID inválido.', 'guiawp' ) ] );
		}

		if ( ! GCEP_Plans::delete( $id ) ) {
			wp_send_json_error( [ 'message' => $this->get_plan_error_message( __( 'Erro ao excluir plano.', 'guiawp' ) ) ] );
		}

		wp_send_json_success( [
			'plan_id' => $id,
			'message' => __( 'Plano excluído.', 'guiawp' ),
		] );
	}

	// ===== AJAX: Validação IA =====
	public function ajax_validate_ai(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Acesso negado.', 'guiawp' ) ] );
		}
		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Erro de segurança.', 'guiawp' ) ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Anúncio não informado.', 'guiawp' ) ] );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'gcep_anuncio' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Anúncio não encontrado.', 'guiawp' ) ] );
		}

		// Verificar se o autor é o usuário logado ou admin
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Acesso negado.', 'guiawp' ) ] );
		}

		$result = GCEP_AI_Validator::validate( $post_id );

		if ( ! empty( $result['error'] ) ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		wp_send_json_success( [
			'approved'      => $result['approved'],
			'justificativa' => $result['justificativa'],
			'message'       => $result['approved']
				? __( 'Anúncio aprovado pela IA!', 'guiawp' )
				: __( 'Anúncio rejeitado pela IA.', 'guiawp' ),
		] );
	}

	// Verificação de segurança AJAX
	private function verify_ajax_admin(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Acesso negado.', 'guiawp' ) ] );
		}
		if ( ! check_ajax_referer( 'gcep_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Erro de segurança.', 'guiawp' ) ] );
		}
	}

	private function get_plan_error_message( string $fallback ): string {
		$error = trim( GCEP_Plans::get_last_error() );

		if ( '' !== $error && 'local' === wp_get_environment_type() ) {
			return $fallback . ' ' . $error;
		}

		return $fallback;
	}

	public static function get_anuncios( array $args = [] ): array {
		$defaults = [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => GCEP_Helpers::get_manageable_anuncio_post_statuses(),
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( ! empty( $args['status'] ) ) {
			$defaults['meta_query'] = [
				[
					'key'   => 'GCEP_status_anuncio',
					'value' => sanitize_text_field( $args['status'] ),
				],
			];
		}

		return get_posts( wp_parse_args( $args, $defaults ) );
	}

	// Retorna posts + dados de paginacao
	private static function build_admin_anuncios_meta_query( array $args ): array {
		$meta_query = [];

		if ( ! empty( $args['status'] ) ) {
			$meta_query[] = [
				'key'   => 'GCEP_status_anuncio',
				'value' => sanitize_text_field( (string) $args['status'] ),
			];
		}

		return $meta_query;
	}

	private static function build_admin_anuncios_date_query( array $args ): array {
		$date_from = sanitize_text_field( (string) ( $args['date_from'] ?? '' ) );
		$date_to   = sanitize_text_field( (string) ( $args['date_to'] ?? '' ) );

		$is_valid_from = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from );
		$is_valid_to   = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to );

		if ( ! $is_valid_from && ! $is_valid_to ) {
			return [];
		}

		$date_clause = [
			'inclusive' => true,
		];

		if ( $is_valid_from ) {
			$date_clause['after'] = $date_from;
		}

		if ( $is_valid_to ) {
			$date_clause['before'] = $date_to;
		}

		return [ $date_clause ];
	}

	private static function run_admin_anuncios_id_query( array $query_args ): array {
		$query = new WP_Query(
			wp_parse_args(
				$query_args,
				[
					'post_type'              => 'gcep_anuncio',
					'post_status'            => GCEP_Helpers::get_manageable_anuncio_post_statuses(),
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			)
		);

		return array_values( array_unique( array_map( 'intval', (array) $query->posts ) ) );
	}

	private static function get_admin_anuncios_search_ids( array $args, string $search_term ): array {
		$search_term = trim( $search_term );
		if ( '' === $search_term ) {
			return [];
		}

		$base_query = [
			'post_type'              => 'gcep_anuncio',
			'post_status'            => GCEP_Helpers::get_manageable_anuncio_post_statuses(),
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		$base_meta_query = self::build_admin_anuncios_meta_query( $args );
		$date_query      = self::build_admin_anuncios_date_query( $args );

		if ( ! empty( $base_meta_query ) ) {
			$base_query['meta_query'] = $base_meta_query;
		}

		if ( ! empty( $date_query ) ) {
			$base_query['date_query'] = $date_query;
		}

		$post_ids = self::run_admin_anuncios_id_query(
			array_merge(
				$base_query,
				[
					's' => $search_term,
				]
			)
		);

		$meta_search_group = [
			'relation' => 'OR',
			[
				'key'     => 'GCEP_email',
				'value'   => $search_term,
				'compare' => 'LIKE',
			],
		];

		$digits = GCEP_Helpers::sanitize_phone( $search_term );
		if ( '' !== $digits ) {
			$meta_search_group[] = [
				'key'     => 'GCEP_telefone',
				'value'   => $digits,
				'compare' => 'LIKE',
			];
			$meta_search_group[] = [
				'key'     => 'GCEP_whatsapp',
				'value'   => $digits,
				'compare' => 'LIKE',
			];
		}

		$post_ids = array_merge(
			$post_ids,
			self::run_admin_anuncios_id_query(
				array_merge(
					$base_query,
					[
						'meta_query' => ! empty( $base_meta_query )
							? array_merge( [ 'relation' => 'AND' ], $base_meta_query, [ $meta_search_group ] )
							: $meta_search_group,
					]
				)
			)
		);

		$term_ids = get_terms(
			[
				'taxonomy'   => 'gcep_categoria',
				'hide_empty' => false,
				'fields'     => 'ids',
				'search'     => $search_term,
			]
		);

		if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) {
			$post_ids = array_merge(
				$post_ids,
				self::run_admin_anuncios_id_query(
					array_merge(
						$base_query,
						[
							'tax_query' => [
								[
									'taxonomy' => 'gcep_categoria',
									'field'    => 'term_id',
									'terms'    => array_map( 'intval', $term_ids ),
								],
							],
						]
					)
				)
			);
		}

		$user_ids = get_users(
			[
				'fields'         => 'ID',
				'number'         => 100,
				'search'         => '*' . $search_term . '*',
				'search_columns' => [ 'display_name', 'user_email', 'user_login', 'user_nicename' ],
			]
		);

		if ( '' !== $digits ) {
			$user_ids = array_merge(
				(array) $user_ids,
				(array) get_users(
					[
						'fields'     => 'ID',
						'number'     => 100,
						'meta_query' => [
							[
								'key'     => 'gcep_telefone',
								'value'   => $digits,
								'compare' => 'LIKE',
							],
						],
					]
				)
			);
		}

		$user_ids = array_values( array_unique( array_map( 'intval', (array) $user_ids ) ) );

		if ( ! empty( $user_ids ) ) {
			$post_ids = array_merge(
				$post_ids,
				self::run_admin_anuncios_id_query(
					array_merge(
						$base_query,
						[
							'author__in' => $user_ids,
						]
					)
				)
			);
		}

		return array_values( array_unique( array_map( 'intval', $post_ids ) ) );
	}

	public static function order_admin_anuncios_by_status_priority( array $clauses, WP_Query $query ): array {
		if ( ! $query->get( 'gcep_status_priority_order' ) ) {
			return $clauses;
		}

		global $wpdb;

		if ( false === strpos( $clauses['join'], 'gcep_status_pm' ) ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS gcep_status_pm ON ({$wpdb->posts}.ID = gcep_status_pm.post_id AND gcep_status_pm.meta_key = 'GCEP_status_anuncio')";
		}

		$status_order = "CASE COALESCE(gcep_status_pm.meta_value, 'rascunho')
			WHEN 'aguardando_aprovacao' THEN 0
			WHEN 'aguardando_pagamento' THEN 1
			WHEN 'rejeitado' THEN 2
			WHEN 'publicado' THEN 3
			WHEN 'expirado' THEN 4
			ELSE 5
		END";

		$clauses['orderby'] = $status_order . " ASC, {$wpdb->posts}.post_date DESC";

		return $clauses;
	}

	public static function get_anuncios_paged( array $args = [] ): array {
		$defaults = [
			'post_type'                => 'gcep_anuncio',
			'post_status'              => GCEP_Helpers::get_manageable_anuncio_post_statuses(),
			'posts_per_page'           => 15,
			'orderby'                  => 'date',
			'order'                    => 'DESC',
			'paged'                    => 1,
			'ignore_sticky_posts'      => true,
			'update_post_meta_cache'   => false,
			'update_post_term_cache'   => false,
			'gcep_status_priority_order' => empty( $args['status'] ),
		];

		$meta_query = self::build_admin_anuncios_meta_query( $args );
		$date_query = self::build_admin_anuncios_date_query( $args );
		$search     = sanitize_text_field( (string) ( $args['search'] ?? '' ) );

		unset( $args['status'], $args['date_from'], $args['date_to'], $args['search'] );

		if ( ! empty( $meta_query ) ) {
			$defaults['meta_query'] = $meta_query;
		}

		if ( ! empty( $date_query ) ) {
			$defaults['date_query'] = $date_query;
		}

		if ( '' !== $search ) {
			$search_ids = self::get_admin_anuncios_search_ids(
				[
					'status'    => $defaults['meta_query'][0]['value'] ?? '',
					'date_from' => $date_query[0]['after'] ?? '',
					'date_to'   => $date_query[0]['before'] ?? '',
				],
				$search
			);

			$defaults['post__in'] = ! empty( $search_ids ) ? $search_ids : [ 0 ];
		}

		add_filter( 'posts_clauses', [ __CLASS__, 'order_admin_anuncios_by_status_priority' ], 10, 2 );
		$query = new WP_Query( wp_parse_args( $args, $defaults ) );
		remove_filter( 'posts_clauses', [ __CLASS__, 'order_admin_anuncios_by_status_priority' ], 10 );

		return [
			'posts' => $query->posts,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
			'paged' => (int) $query->get( 'paged' ),
		];
	}
}
