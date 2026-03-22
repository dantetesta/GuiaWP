<?php
/**
 * Meta box para relacionar posts do blog com anuncios
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.5.6 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Blog_Metabox {

	private const META_KEY = '_gcep_anuncios_relacionados';
	private const MAX_ANUNCIOS = 2;

	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
		add_action( 'save_post_post', [ $this, 'save_metabox' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_gcep_buscar_anuncios', [ $this, 'ajax_buscar_anuncios' ] );
	}

	public function register_metabox(): void {
		add_meta_box(
			'gcep_anuncios_relacionados',
			__( 'Anuncios Relacionados', 'guiawp' ),
			[ $this, 'render_metabox' ],
			'post',
			'side',
			'default'
		);
	}

	public function render_metabox( \WP_Post $post ): void {
		$saved_ids = (array) get_post_meta( $post->ID, self::META_KEY, true );
		$saved_ids = array_filter( array_map( 'absint', $saved_ids ) );

		wp_nonce_field( 'gcep_anuncios_rel_nonce', '_gcep_anuncios_rel_nonce' );
		?>
		<p class="description" style="margin-bottom:10px;">
			<?php printf( esc_html__( 'Selecione ate %d anuncios para exibir na sidebar do post.', 'guiawp' ), self::MAX_ANUNCIOS ); ?>
		</p>

		<div id="gcep-anuncios-rel-wrap">
			<input type="text" id="gcep-anuncios-busca" placeholder="<?php esc_attr_e( 'Buscar anuncio...', 'guiawp' ); ?>" style="width:100%;margin-bottom:8px;" autocomplete="off">
			<div id="gcep-anuncios-resultados" style="max-height:160px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;display:none;background:#fff;"></div>

			<ul id="gcep-anuncios-selecionados" style="margin:8px 0 0;padding:0;list-style:none;">
				<?php foreach ( $saved_ids as $anuncio_id ) :
					$anuncio = get_post( $anuncio_id );
					if ( ! $anuncio || 'gcep_anuncio' !== $anuncio->post_type ) continue;
				?>
				<li data-id="<?php echo esc_attr( $anuncio_id ); ?>" style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;margin-bottom:4px;background:#f0f0f1;border-radius:4px;font-size:13px;">
					<span><?php echo esc_html( $anuncio->post_title ); ?></span>
					<input type="hidden" name="gcep_anuncios_rel[]" value="<?php echo esc_attr( $anuncio_id ); ?>">
					<button type="button" class="gcep-remover-anuncio" style="background:none;border:none;color:#d63638;cursor:pointer;font-size:16px;line-height:1;" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>">&times;</button>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	public function save_metabox( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['_gcep_anuncios_rel_nonce'] ) || ! wp_verify_nonce( $_POST['_gcep_anuncios_rel_nonce'], 'gcep_anuncios_rel_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$ids = [];
		if ( ! empty( $_POST['gcep_anuncios_rel'] ) && is_array( $_POST['gcep_anuncios_rel'] ) ) {
			$ids = array_slice( array_map( 'absint', $_POST['gcep_anuncios_rel'] ), 0, self::MAX_ANUNCIOS );
			$ids = array_filter( $ids );
		}

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $ids );
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'gcep-blog-metabox',
			GCEP_PLUGIN_URL . 'assets/js/gcep-blog-metabox.js',
			[],
			GCEP_VERSION,
			true
		);

		wp_localize_script( 'gcep-blog-metabox', 'gcepBlogMetabox', [
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'gcep_buscar_anuncios' ),
			'maxAnuncios' => self::MAX_ANUNCIOS,
			'textos'     => [
				'limite'    => sprintf( __( 'Maximo de %d anuncios selecionados.', 'guiawp' ), self::MAX_ANUNCIOS ),
				'nenhum'    => __( 'Nenhum anuncio encontrado.', 'guiawp' ),
				'buscando'  => __( 'Buscando...', 'guiawp' ),
			],
		] );
	}

	public function ajax_buscar_anuncios(): void {
		check_ajax_referer( 'gcep_buscar_anuncios', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		$termo = sanitize_text_field( wp_unslash( $_GET['termo'] ?? '' ) );

		$args = [
			'post_type'      => 'gcep_anuncio',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		];

		if ( '' !== $termo ) {
			$args['s'] = $termo;
		}

		$query   = new WP_Query( $args );
		$results = [];

		foreach ( $query->posts as $p ) {
			$plano = (string) get_post_meta( $p->ID, 'GCEP_tipo_plano', true );
			$results[] = [
				'id'    => $p->ID,
				'title' => $p->post_title,
				'plano' => $plano ?: 'gratis',
			];
		}

		wp_send_json_success( $results );
	}

	/**
	 * Retorna IDs dos anuncios relacionados ao post
	 */
	public static function get_related_anuncios( int $post_id ): array {
		$ids = (array) get_post_meta( $post_id, self::META_KEY, true );
		return array_filter( array_map( 'absint', $ids ) );
	}
}
