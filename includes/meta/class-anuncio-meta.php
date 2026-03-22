<?php
/**
 * Meta fields do anúncio
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.1.0 - 2026-03-11 - Adicionados campos de endereço, CNPJ, galeria, vídeos e novas redes sociais
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Anuncio_Meta {

	private array $fields = [
		'GCEP_tipo_anuncio'           => 'select',
		'GCEP_tipo_plano'             => 'select',
		'GCEP_cnpj'                   => 'text',
		'GCEP_descricao_curta'        => 'textarea',
		'GCEP_descricao_longa'        => 'wysiwyg',
		'GCEP_telefone'               => 'text',
		'GCEP_whatsapp'               => 'text',
		'GCEP_email'                  => 'email',
		'GCEP_cep'                    => 'text',
		'GCEP_logradouro'             => 'text',
		'GCEP_numero'                 => 'text',
		'GCEP_complemento'            => 'text',
		'GCEP_bairro'                 => 'text',
		'GCEP_cidade'                 => 'text',
		'GCEP_estado'                 => 'text',
		'GCEP_endereco_completo'      => 'textarea',
		'GCEP_latitude'               => 'text',
		'GCEP_longitude'              => 'text',
		'GCEP_site'                   => 'url',
		'GCEP_instagram'              => 'url',
		'GCEP_facebook'               => 'url',
		'GCEP_linkedin'               => 'url',
		'GCEP_youtube'                => 'url',
		'GCEP_x_twitter'              => 'url',
		'GCEP_tiktok'                 => 'url',
		'GCEP_threads'                => 'url',
		'GCEP_galeria_fotos'          => 'gallery',
		'GCEP_galeria_videos'         => 'repeater',
		'GCEP_status_anuncio'         => 'select',
		'GCEP_logo_ou_foto_principal' => 'image',
		'GCEP_foto_capa'              => 'image',
	];

	public function register_meta_boxes(): void {
		add_meta_box(
			'gcep_anuncio_dados',
			__( 'Dados do Anúncio', 'guiawp' ),
			[ $this, 'render_meta_box' ],
			'gcep_anuncio',
			'normal',
			'high'
		);

		add_meta_box(
			'gcep_anuncio_status',
			__( 'Status e Moderação', 'guiawp' ),
			[ $this, 'render_status_box' ],
			'gcep_anuncio',
			'side',
			'high'
		);
	}

	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'gcep_save_anuncio_meta', 'gcep_meta_nonce' );

		$tipo_anuncio = get_post_meta( $post->ID, 'GCEP_tipo_anuncio', true );
		$tipo_plano   = get_post_meta( $post->ID, 'GCEP_tipo_plano', true );
		?>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Tipo de Anúncio', 'guiawp' ); ?></label></th>
				<td>
					<select name="GCEP_tipo_anuncio">
						<option value="empresa" <?php selected( $tipo_anuncio, 'empresa' ); ?>><?php esc_html_e( 'Empresa', 'guiawp' ); ?></option>
						<option value="profissional_liberal" <?php selected( $tipo_anuncio, 'profissional_liberal' ); ?>><?php esc_html_e( 'Profissional Liberal', 'guiawp' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Plano', 'guiawp' ); ?></label></th>
				<td>
					<select name="GCEP_tipo_plano">
						<option value="gratis" <?php selected( $tipo_plano, 'gratis' ); ?>><?php esc_html_e( 'Grátis', 'guiawp' ); ?></option>
						<option value="premium" <?php selected( $tipo_plano, 'premium' ); ?>><?php esc_html_e( 'Premium', 'guiawp' ); ?></option>
					</select>
				</td>
			</tr>
			<?php
			$text_fields = [
				'GCEP_cnpj'              => __( 'CNPJ', 'guiawp' ),
				'GCEP_descricao_curta'   => __( 'Descrição Curta', 'guiawp' ),
				'GCEP_telefone'          => __( 'Telefone', 'guiawp' ),
				'GCEP_whatsapp'          => __( 'WhatsApp', 'guiawp' ),
				'GCEP_email'             => __( 'E-mail', 'guiawp' ),
				'GCEP_cep'               => __( 'CEP', 'guiawp' ),
				'GCEP_logradouro'        => __( 'Logradouro', 'guiawp' ),
				'GCEP_numero'            => __( 'Número', 'guiawp' ),
				'GCEP_complemento'       => __( 'Complemento', 'guiawp' ),
				'GCEP_bairro'            => __( 'Bairro', 'guiawp' ),
				'GCEP_cidade'            => __( 'Cidade', 'guiawp' ),
				'GCEP_estado'            => __( 'Estado', 'guiawp' ),
				'GCEP_endereco_completo' => __( 'Endereço Completo', 'guiawp' ),
				'GCEP_latitude'          => __( 'Latitude', 'guiawp' ),
				'GCEP_longitude'         => __( 'Longitude', 'guiawp' ),
				'GCEP_site'              => __( 'Site', 'guiawp' ),
				'GCEP_instagram'         => __( 'Instagram', 'guiawp' ),
				'GCEP_facebook'          => __( 'Facebook', 'guiawp' ),
				'GCEP_linkedin'          => __( 'LinkedIn', 'guiawp' ),
				'GCEP_youtube'           => __( 'YouTube', 'guiawp' ),
				'GCEP_x_twitter'         => __( 'X (Twitter)', 'guiawp' ),
				'GCEP_tiktok'            => __( 'TikTok', 'guiawp' ),
				'GCEP_threads'           => __( 'Threads', 'guiawp' ),
			];
			foreach ( $text_fields as $key => $label ) :
				$value = get_post_meta( $post->ID, $key, true );
				?>
				<tr>
					<th><label><?php echo esc_html( $label ); ?></label></th>
					<td>
						<?php if ( in_array( $key, [ 'GCEP_descricao_curta', 'GCEP_endereco_completo' ], true ) ) : ?>
							<textarea name="<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
						<?php else : ?>
							<input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th><label><?php esc_html_e( 'Galeria de Fotos (IDs)', 'guiawp' ); ?></label></th>
				<td>
					<?php $galeria = get_post_meta( $post->ID, 'GCEP_galeria_fotos', true ); ?>
					<input type="text" name="GCEP_galeria_fotos" value="<?php echo esc_attr( $galeria ); ?>" class="large-text" />
					<p class="description"><?php esc_html_e( 'IDs dos anexos separados por vírgula.', 'guiawp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Galeria de Vídeos (JSON)', 'guiawp' ); ?></label></th>
				<td>
					<?php $videos = get_post_meta( $post->ID, 'GCEP_galeria_videos', true ); ?>
					<textarea name="GCEP_galeria_videos" rows="4" class="large-text"><?php echo esc_textarea( is_array( $videos ) ? wp_json_encode( $videos ) : $videos ); ?></textarea>
					<p class="description"><?php esc_html_e( 'JSON com array de objetos {titulo, url}.', 'guiawp' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function render_status_box( \WP_Post $post ): void {
		$status = get_post_meta( $post->ID, 'GCEP_status_anuncio', true ) ?: 'rascunho';
		?>
		<p>
			<label><strong><?php esc_html_e( 'Status do Anúncio', 'guiawp' ); ?></strong></label><br>
			<select name="GCEP_status_anuncio" style="width:100%">
				<?php
				$statuses = [ 'rascunho', 'aguardando_pagamento', 'aguardando_aprovacao', 'publicado', 'rejeitado', 'expirado' ];
				foreach ( $statuses as $s ) :
					?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>>
						<?php echo esc_html( GCEP_Helpers::get_status_label( $s ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['gcep_meta_nonce'] ) || ! wp_verify_nonce( $_POST['gcep_meta_nonce'], 'gcep_save_anuncio_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Campos de texto simples
		$text_keys = [
			'GCEP_tipo_anuncio', 'GCEP_tipo_plano', 'GCEP_cnpj',
			'GCEP_descricao_curta', 'GCEP_telefone', 'GCEP_whatsapp', 'GCEP_email',
			'GCEP_cep', 'GCEP_logradouro', 'GCEP_numero', 'GCEP_complemento',
			'GCEP_bairro', 'GCEP_cidade', 'GCEP_estado', 'GCEP_endereco_completo',
			'GCEP_latitude', 'GCEP_longitude',
			'GCEP_site', 'GCEP_instagram', 'GCEP_facebook', 'GCEP_linkedin',
			'GCEP_youtube', 'GCEP_x_twitter', 'GCEP_tiktok', 'GCEP_threads',
			'GCEP_status_anuncio',
		];

		foreach ( $text_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				if ( in_array( $key, [ 'GCEP_telefone', 'GCEP_whatsapp' ], true ) ) {
					$value = GCEP_Helpers::sanitize_phone( $value );
				}
				update_post_meta( $post_id, $key, $value );
			}
		}

		if ( isset( $_POST['GCEP_status_anuncio'] ) ) {
			GCEP_Helpers::sync_anuncio_post_status( $post_id, sanitize_text_field( wp_unslash( $_POST['GCEP_status_anuncio'] ) ) );
		}

		// Galeria de fotos (string de IDs separados por vírgula)
		if ( isset( $_POST['GCEP_galeria_fotos'] ) ) {
			$galeria = sanitize_text_field( wp_unslash( $_POST['GCEP_galeria_fotos'] ) );
			update_post_meta( $post_id, 'GCEP_galeria_fotos', $galeria );
		}

		// Galeria de vídeos (JSON)
		if ( isset( $_POST['GCEP_galeria_videos'] ) ) {
			$raw = wp_unslash( $_POST['GCEP_galeria_videos'] );
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$sanitized = [];
				foreach ( $decoded as $video ) {
					if ( ! empty( $video['url'] ) ) {
						$sanitized[] = [
							'titulo' => sanitize_text_field( $video['titulo'] ?? '' ),
							'url'    => esc_url_raw( $video['url'] ),
						];
					}
				}
				update_post_meta( $post_id, 'GCEP_galeria_videos', $sanitized );
			}
		}

		// Descrição longa (permite HTML)
		if ( isset( $_POST['GCEP_descricao_longa'] ) ) {
			$value = GCEP_AI_Validator::sanitize_description_html( (string) wp_unslash( $_POST['GCEP_descricao_longa'] ) );
			update_post_meta( $post_id, 'GCEP_descricao_longa', $value );
		}
	}
}
