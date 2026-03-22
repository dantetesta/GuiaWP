<?php
/**
 * Template: Admin - Blog frontend
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.4.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$blog_admin_url  = home_url( '/painel-admin/blog' );
$blog_public_url = home_url( '/blog' );
$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$status_filter   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$current_page    = max( 1, absint( $_GET['pagina'] ?? 1 ) );
$edit_post_id    = absint( $_GET['edit'] ?? 0 );
$show_new_form   = ! empty( $_GET['novo'] );
$msg             = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type        = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';
$current_view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'posts';
$current_view    = in_array( $current_view, [ 'posts', 'categorias' ], true ) ? $current_view : 'posts';

$is_category_view = 'categorias' === $current_view;
$is_form_view     = ! $is_category_view && ( $show_new_form || $edit_post_id > 0 );
$is_posts_view    = ! $is_category_view && ! $is_form_view;

$status_labels = [
	'publish' => __( 'Publicado', 'guiawp' ),
	'draft'   => __( 'Rascunho', 'guiawp' ),
	'pending' => __( 'Pendente', 'guiawp' ),
	'future'  => __( 'Agendado', 'guiawp' ),
	'private' => __( 'Privado', 'guiawp' ),
];

$status_styles = [
	'publish' => 'bg-emerald-100 text-emerald-700',
	'draft'   => 'bg-slate-100 text-slate-600',
	'pending' => 'bg-amber-100 text-amber-700',
	'future'  => 'bg-sky-100 text-sky-700',
	'private' => 'bg-violet-100 text-violet-700',
];

$editing_post            = null;
$editing_post_categories = [];
$editing_thumbnail_id    = 0;
$editing_thumbnail_url   = '';
$editing_video_url       = '';
$blog_posts              = null;

$blog_categories = get_terms(
	[
		'taxonomy'   => 'category',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	]
);

$editing_anuncios_rel = [];

if ( $is_form_view ) {
	$editing_post = $edit_post_id > 0 ? get_post( $edit_post_id ) : null;

	if ( $editing_post && 'post' !== $editing_post->post_type ) {
		$editing_post = null;
	}

	if ( $editing_post ) {
		$editing_post_categories = wp_get_post_categories( $editing_post->ID );
		$editing_thumbnail_id    = (int) get_post_thumbnail_id( $editing_post->ID );
		$editing_thumbnail_url   = $editing_thumbnail_id ? wp_get_attachment_image_url( $editing_thumbnail_id, 'medium' ) : '';
		$editing_video_url       = (string) get_post_meta( $editing_post->ID, '_gcep_video_destaque', true );

		// Anuncios relacionados ao post
		if ( class_exists( 'GCEP_Blog_Metabox' ) ) {
			$saved_rel_ids = GCEP_Blog_Metabox::get_related_anuncios( $editing_post->ID );
			foreach ( $saved_rel_ids as $rel_id ) {
				$rel_post = get_post( $rel_id );
				if ( $rel_post && 'gcep_anuncio' === $rel_post->post_type ) {
					$editing_anuncios_rel[] = [
						'id'    => $rel_post->ID,
						'title' => $rel_post->post_title,
					];
				}
			}
		}
	}

	wp_enqueue_editor();
}

if ( $is_posts_view ) {
	$query_args = [
		'post_type'           => 'post',
		'post_status'         => [ 'publish', 'draft', 'pending', 'future', 'private' ],
		'posts_per_page'      => 12,
		'paged'               => $current_page,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	];

	if ( '' !== $search_query ) {
		$query_args['s'] = $search_query;
	}

	if ( in_array( $status_filter, [ 'publish', 'draft', 'pending', 'future', 'private' ], true ) ) {
		$query_args['post_status'] = $status_filter;
	}

	$blog_posts = new WP_Query( $query_args );
}

$posts_count_object = wp_count_posts( 'post' );
$posts_total_count  = 0;

if ( $posts_count_object ) {
	foreach ( get_object_vars( $posts_count_object ) as $count_value ) {
		$posts_total_count += (int) $count_value;
	}
}

$blog_terms_count   = is_array( $blog_categories ) ? count( $blog_categories ) : 0;
$default_category   = (int) get_option( 'default_category' );
$form_title         = $editing_post ? __( 'Editar Post', 'guiawp' ) : __( 'Novo Post', 'guiawp' );
$form_submit        = $editing_post ? __( 'Atualizar Post', 'guiawp' ) : __( 'Publicar Post', 'guiawp' );

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="lg:ml-64 flex-1 p-4 lg:p-8 min-w-0 overflow-x-hidden">
	<header class="mb-8 flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
		<div>
			<h2 class="text-3xl font-black tracking-tight mb-2"><?php esc_html_e( 'Blog', 'guiawp' ); ?></h2>
			<p class="text-slate-500">
				<?php
				if ( $is_category_view ) {
					printf( esc_html__( '%d categorias nativas do WordPress', 'guiawp' ), $blog_terms_count );
				} elseif ( $is_form_view ) {
					esc_html_e( 'Crie ou edite posts sem entrar no wp-admin.', 'guiawp' );
				} else {
					printf( esc_html__( '%d posts nativos cadastrados', 'guiawp' ), $posts_total_count );
				}
				?>
			</p>
		</div>

		<div class="flex flex-wrap gap-3">
			<a href="<?php echo esc_url( add_query_arg( 'view', 'posts', $blog_admin_url ) ); ?>" class="px-5 py-3 rounded-xl text-sm font-semibold transition-colors <?php echo $is_category_view ? 'border border-slate-200 text-slate-600 hover:bg-white' : 'bg-slate-900 text-white'; ?>">
				<?php esc_html_e( 'Posts', 'guiawp' ); ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'view', 'categorias', $blog_admin_url ) ); ?>" class="px-5 py-3 rounded-xl text-sm font-semibold transition-colors <?php echo $is_category_view ? 'bg-slate-900 text-white' : 'border border-slate-200 text-slate-600 hover:bg-white'; ?>">
				<?php esc_html_e( 'Categorias', 'guiawp' ); ?>
			</a>
			<a href="<?php echo esc_url( $blog_public_url ); ?>" target="_blank" class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-white transition-colors">
				<?php esc_html_e( 'Ver blog publico', 'guiawp' ); ?>
			</a>
			<?php if ( $is_category_view ) : ?>
				<button type="button" id="gcep-blog-add-category" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all">
					<?php esc_html_e( 'Nova Categoria', 'guiawp' ); ?>
				</button>
			<?php elseif ( $is_form_view ) : ?>
				<a href="<?php echo esc_url( $blog_admin_url ); ?>" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all">
					<?php esc_html_e( 'Voltar para Lista', 'guiawp' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( add_query_arg( 'novo', 1, $blog_admin_url ) ); ?>" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all">
					<?php esc_html_e( 'Novo Post', 'guiawp' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( $msg ) : ?>
	<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
	<?php endif; ?>

	<?php if ( $is_posts_view ) : ?>
		<section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8">
			<form method="get" action="<?php echo esc_url( $blog_admin_url ); ?>" class="flex flex-col lg:flex-row lg:items-end gap-4">
				<input type="hidden" name="view" value="posts">
				<label class="flex-1 flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Pesquisar', 'guiawp' ); ?></span>
					<input type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" class="rounded-xl border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Buscar por titulo, resumo ou conteudo...', 'guiawp' ); ?>">
				</label>
				<label class="w-full lg:w-56 flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Status', 'guiawp' ); ?></span>
					<select name="status" class="rounded-xl border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
						<option value=""><?php esc_html_e( 'Todos', 'guiawp' ); ?></option>
						<option value="publish" <?php selected( $status_filter, 'publish' ); ?>><?php esc_html_e( 'Publicados', 'guiawp' ); ?></option>
						<option value="draft" <?php selected( $status_filter, 'draft' ); ?>><?php esc_html_e( 'Rascunhos', 'guiawp' ); ?></option>
						<option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pendentes', 'guiawp' ); ?></option>
						<option value="future" <?php selected( $status_filter, 'future' ); ?>><?php esc_html_e( 'Agendados', 'guiawp' ); ?></option>
						<option value="private" <?php selected( $status_filter, 'private' ); ?>><?php esc_html_e( 'Privados', 'guiawp' ); ?></option>
					</select>
				</label>
				<div class="flex gap-3">
					<button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-700 transition-colors">
						<?php esc_html_e( 'Filtrar', 'guiawp' ); ?>
					</button>
					<a href="<?php echo esc_url( $blog_admin_url ); ?>" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
						<?php esc_html_e( 'Limpar', 'guiawp' ); ?>
					</a>
				</div>
			</form>
		</section>

		<!-- Barra de acoes em massa -->
		<div id="gcep-blog-bulk-bar" class="hidden mb-4 bg-rose-50 border border-rose-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
			<span class="text-sm font-semibold text-rose-700">
				<span id="gcep-blog-bulk-count">0</span> <?php esc_html_e( 'post(s) selecionado(s)', 'guiawp' ); ?>
			</span>
			<div class="flex items-center gap-3">
				<button type="button" id="gcep-blog-bulk-cancel" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-white transition-colors">
					<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
				</button>
				<button type="button" id="gcep-blog-bulk-delete" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-sm font-bold hover:bg-rose-700 transition-colors flex items-center gap-2">
					<span class="material-symbols-outlined text-[18px]">delete_sweep</span>
					<?php esc_html_e( 'Excluir Selecionados', 'guiawp' ); ?>
				</button>
			</div>
		</div>

		<section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-left min-w-[520px]">
					<thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
						<tr>
							<th class="px-3 py-4 w-10">
								<input type="checkbox" id="gcep-blog-select-all" class="rounded border-slate-300 text-primary focus:ring-primary/20" title="<?php esc_attr_e( 'Selecionar todos', 'guiawp' ); ?>">
							</th>
							<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Post', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 hidden md:table-cell"><?php esc_html_e( 'Categorias', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 whitespace-nowrap"><?php esc_html_e( 'Status', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 hidden lg:table-cell whitespace-nowrap"><?php esc_html_e( 'Atualizado', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-right whitespace-nowrap"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						<?php if ( $blog_posts && $blog_posts->have_posts() ) : ?>
							<?php while ( $blog_posts->have_posts() ) : $blog_posts->the_post(); ?>
								<?php
								$post_categories = get_the_category();
								$post_status     = get_post_status();
								$post_status_css = $status_styles[ $post_status ] ?? 'bg-slate-100 text-slate-600';
								$post_status_lbl = $status_labels[ $post_status ] ?? ucfirst( $post_status );
								$preview_url     = 'publish' === $post_status ? get_permalink() : get_preview_post_link( get_the_ID() );
								?>
								<tr class="hover:bg-slate-50/70 transition-colors gcep-blog-row" data-post-id="<?php echo esc_attr( get_the_ID() ); ?>">
									<td class="px-3 py-4 w-10">
										<input type="checkbox" class="gcep-blog-select-item rounded border-slate-300 text-primary focus:ring-primary/20" value="<?php echo esc_attr( get_the_ID() ); ?>">
									</td>
									<td class="px-4 lg:px-6 py-4" style="max-width:320px;">
										<div class="flex items-start gap-3">
											<div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl lg:rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0">
												<?php if ( has_post_thumbnail() ) : ?>
													<?php the_post_thumbnail( 'thumbnail', [ 'class' => 'w-full h-full object-cover' ] ); ?>
												<?php else : ?>
													<div class="w-full h-full flex items-center justify-center text-slate-300">
														<span class="material-symbols-outlined text-2xl">article</span>
													</div>
												<?php endif; ?>
											</div>
											<div class="min-w-0 flex-1">
												<p class="text-sm font-bold text-slate-900 truncate"><?php the_title(); ?></p>
												<p class="text-xs text-slate-500 mt-1 truncate"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 8 ) ); ?></p>
											</div>
										</div>
									</td>
									<td class="px-4 lg:px-6 py-4 hidden md:table-cell">
										<div class="flex flex-wrap gap-1.5">
											<?php if ( ! empty( $post_categories ) ) : ?>
												<?php foreach ( array_slice( $post_categories, 0, 2 ) as $post_category ) : ?>
													<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium truncate max-w-[120px]"><?php echo esc_html( $post_category->name ); ?></span>
												<?php endforeach; ?>
												<?php if ( count( $post_categories ) > 2 ) : ?>
													<span class="text-xs text-slate-400">+<?php echo count( $post_categories ) - 2; ?></span>
												<?php endif; ?>
											<?php else : ?>
												<span class="text-sm text-slate-400">-</span>
											<?php endif; ?>
										</div>
									</td>
									<td class="px-4 lg:px-6 py-4">
										<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] lg:text-xs font-bold <?php echo esc_attr( $post_status_css ); ?>">
											<?php echo esc_html( $post_status_lbl ); ?>
										</span>
									</td>
									<td class="px-4 lg:px-6 py-4 text-sm text-slate-500 whitespace-nowrap hidden lg:table-cell">
										<?php echo esc_html( get_the_modified_date( 'd/m/Y H:i' ) ); ?>
									</td>
									<td class="px-4 lg:px-6 py-4 text-right">
										<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
											<?php if ( $preview_url ) : ?>
												<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Visualizar', 'guiawp' ); ?>">
													<span class="material-symbols-outlined" style="font-size:18px;">visibility</span>
												</a>
											<?php endif; ?>
											<a href="<?php echo esc_url( add_query_arg( 'edit', get_the_ID(), $blog_admin_url ) ); ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>">
												<span class="material-symbols-outlined" style="font-size:18px;">edit</span>
											</a>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Remover este post permanentemente?', 'guiawp' ) ); ?>');">
												<input type="hidden" name="action" value="gcep_admin_delete_blog_post">
												<input type="hidden" name="gcep_blog_post_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
												<?php wp_nonce_field( 'gcep_admin_delete_blog_post', 'gcep_nonce' ); ?>
												<button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" title="<?php esc_attr_e( 'Excluir', 'guiawp' ); ?>">
													<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endwhile; ?>
						<?php else : ?>
							<tr>
								<td colspan="6" class="px-6 py-16 text-center text-slate-400">
									<span class="material-symbols-outlined text-5xl block mb-3">article</span>
									<p class="mb-4"><?php esc_html_e( 'Nenhum post encontrado com os filtros atuais.', 'guiawp' ); ?></p>
									<a href="<?php echo esc_url( add_query_arg( 'novo', 1, $blog_admin_url ) ); ?>" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#0052cc] text-white font-bold text-sm">
										<?php esc_html_e( 'Criar Primeiro Post', 'guiawp' ); ?>
									</a>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php
			if ( $blog_posts && $blog_posts->max_num_pages > 1 ) :
				$pages    = (int) $blog_posts->max_num_pages;
				$paged    = $current_page;
				$base_url = add_query_arg( array_filter( [ 'view' => 'posts', 's' => $search_query, 'status' => $status_filter ] ), $blog_admin_url );
				include GCEP_PLUGIN_DIR . 'templates/partials/pagination.php';
			endif;
			?>
		</section>

		<!-- Script de exclusao em massa -->
		<script>
		(function() {
			var selectAll = document.getElementById('gcep-blog-select-all');
			var bulkBar = document.getElementById('gcep-blog-bulk-bar');
			var bulkCount = document.getElementById('gcep-blog-bulk-count');
			var bulkDelete = document.getElementById('gcep-blog-bulk-delete');
			var bulkCancel = document.getElementById('gcep-blog-bulk-cancel');
			if (!selectAll || !bulkBar) return;

			var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';

			function getCheckboxes() {
				return document.querySelectorAll('.gcep-blog-select-item');
			}

			function getSelected() {
				var ids = [];
				getCheckboxes().forEach(function(cb) {
					if (cb.checked) ids.push(parseInt(cb.value, 10));
				});
				return ids;
			}

			function updateBulkBar() {
				var ids = getSelected();
				if (ids.length > 0) {
					bulkBar.classList.remove('hidden');
					bulkCount.textContent = ids.length;
				} else {
					bulkBar.classList.add('hidden');
				}
				selectAll.checked = ids.length > 0 && ids.length === getCheckboxes().length;
			}

			selectAll.addEventListener('change', function() {
				getCheckboxes().forEach(function(cb) {
					cb.checked = selectAll.checked;
				});
				updateBulkBar();
			});

			document.addEventListener('change', function(e) {
				if (e.target.classList.contains('gcep-blog-select-item')) {
					updateBulkBar();
				}
			});

			bulkCancel.addEventListener('click', function() {
				selectAll.checked = false;
				getCheckboxes().forEach(function(cb) { cb.checked = false; });
				updateBulkBar();
			});

			bulkDelete.addEventListener('click', function() {
				var ids = getSelected();
				if (!ids.length) return;

				var msg = '<?php echo esc_js( __( 'Tem certeza que deseja excluir permanentemente', 'guiawp' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'post(s)? As midias associadas tambem serao removidas. Esta acao nao pode ser desfeita.', 'guiawp' ) ); ?>';
				if (!confirm(msg)) return;

				bulkDelete.disabled = true;
				bulkDelete.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">sync</span> <?php echo esc_js( __( 'Removendo...', 'guiawp' ) ); ?>';

				var body = new URLSearchParams();
				body.append('action', 'gcep_bulk_delete_blog_posts');
				body.append('nonce', nonce);
				ids.forEach(function(id) {
					body.append('post_ids[]', id);
				});

				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				})
				.then(function(r) { return r.json(); })
				.then(function(res) {
					if (res.success) {
						// Remover linhas da tabela
						ids.forEach(function(id) {
							var row = document.querySelector('.gcep-blog-row[data-post-id="' + id + '"]');
							if (row) row.remove();
						});
						bulkBar.classList.add('hidden');
						selectAll.checked = false;
						if (typeof gcepToast === 'function') gcepToast(res.data.message, 'success');

						// Se nao sobrou nenhuma linha, recarregar
						if (!document.querySelectorAll('.gcep-blog-row').length) {
							setTimeout(function() { location.reload(); }, 1500);
						}
					} else {
						if (typeof gcepToast === 'function') gcepToast(res.data.message || 'Erro ao excluir posts.', 'error');
					}
				})
				.catch(function(err) {
					if (typeof gcepToast === 'function') gcepToast('Erro na requisicao: ' + err.message, 'error');
				})
				.finally(function() {
					bulkDelete.disabled = false;
					bulkDelete.innerHTML = '<span class="material-symbols-outlined text-[18px]">delete_sweep</span> <?php echo esc_js( __( 'Excluir Selecionados', 'guiawp' ) ); ?>';
				});
			});
		})();
		</script>
	<?php endif; ?>

	<?php if ( $is_form_view ) : ?>
		<section class="bg-white p-4 lg:p-8 rounded-2xl border border-slate-200 shadow-sm">
			<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-8">
				<div>
					<h3 class="text-2xl font-black tracking-tight mb-2"><?php echo esc_html( $form_title ); ?></h3>
					<p class="text-slate-500"><?php esc_html_e( 'Editor de posts nativos do WordPress no painel frontend.', 'guiawp' ); ?></p>
				</div>
				<?php if ( $editing_post ) : ?>
					<a href="<?php echo esc_url( get_permalink( $editing_post->ID ) ); ?>" target="_blank" class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
						<?php esc_html_e( 'Abrir Post', 'guiawp' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="space-y-8">
				<input type="hidden" name="action" value="gcep_admin_save_blog_post">
				<input type="hidden" name="gcep_blog_post_id" value="<?php echo esc_attr( $editing_post ? $editing_post->ID : 0 ); ?>">
				<?php wp_nonce_field( 'gcep_admin_save_blog_post', 'gcep_nonce' ); ?>

				<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,2fr)_340px] gap-8">
					<div class="space-y-6">
						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Titulo do Post', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
							<input type="text" name="gcep_blog_title" value="<?php echo esc_attr( $editing_post ? $editing_post->post_title : '' ); ?>" class="rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Ex: Tendencias de marketing para negocios locais', 'guiawp' ); ?>">
						</label>

						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Resumo', 'guiawp' ); ?></span>
							<textarea name="gcep_blog_excerpt" rows="3" class="rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Pequeno resumo para cards e listagens...', 'guiawp' ); ?>"><?php echo esc_textarea( $editing_post ? $editing_post->post_excerpt : '' ); ?></textarea>
						</label>

						<div class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Conteudo', 'guiawp' ); ?></span>
							<div class="rounded-2xl border border-slate-200 overflow-hidden">
								<?php
								wp_editor(
									$editing_post ? $editing_post->post_content : '',
									'gcep_blog_content',
									[
										'textarea_name' => 'gcep_blog_content',
										'textarea_rows' => 16,
										'media_buttons' => false,
										'teeny'         => false,
										'quicktags'     => true,
									]
								);
								?>
							</div>
						</div>
					</div>

					<aside class="space-y-6">
						<div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4">
							<h4 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Publicacao', 'guiawp' ); ?></h4>
							<label class="flex flex-col gap-2">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Status do Post', 'guiawp' ); ?></span>
								<select name="gcep_blog_status" class="rounded-xl border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none">
									<?php
									$current_status = $editing_post ? $editing_post->post_status : 'publish';
									foreach ( $status_labels as $status_key => $status_label ) :
									?>
										<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $current_status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>

						<div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4">
							<div class="flex items-center justify-between gap-3">
								<h4 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Categorias', 'guiawp' ); ?></h4>
								<a href="<?php echo esc_url( add_query_arg( 'view', 'categorias', $blog_admin_url ) ); ?>" class="text-xs font-semibold text-primary hover:underline">
									<?php esc_html_e( 'Gerenciar', 'guiawp' ); ?>
								</a>
							</div>
							<?php if ( ! empty( $blog_categories ) && ! is_wp_error( $blog_categories ) ) : ?>
								<div class="space-y-2 max-h-72 overflow-y-auto pr-1">
									<?php foreach ( $blog_categories as $category ) : ?>
										<label class="flex items-start gap-3 rounded-xl bg-white border border-slate-200 p-3">
											<input type="checkbox" name="gcep_blog_categories[]" value="<?php echo esc_attr( $category->term_id ); ?>" class="mt-1 rounded border-slate-300 text-primary focus:ring-primary/20" <?php checked( in_array( $category->term_id, $editing_post_categories, true ) ); ?>>
											<span class="min-w-0">
												<span class="block text-sm font-semibold text-slate-800"><?php echo esc_html( $category->name ); ?></span>
												<span class="block text-xs text-slate-400"><?php printf( esc_html__( '%d posts', 'guiawp' ), (int) $category->count ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<p class="text-sm text-slate-500"><?php esc_html_e( 'Nenhuma categoria nativa encontrada.', 'guiawp' ); ?></p>
							<?php endif; ?>
						</div>

						<div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4">
							<h4 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Capa', 'guiawp' ); ?></h4>
							<div id="gcep-blog-thumb-preview">
								<?php if ( $editing_thumbnail_url ) : ?>
									<img src="<?php echo esc_url( $editing_thumbnail_url ); ?>" alt="<?php echo esc_attr( $editing_post ? $editing_post->post_title : '' ); ?>" class="w-full h-44 rounded-2xl object-cover border border-slate-200 bg-white">
								<?php else : ?>
									<div class="w-full h-44 rounded-2xl border border-dashed border-slate-300 bg-white flex items-center justify-center text-slate-400">
										<span class="material-symbols-outlined text-4xl">image</span>
									</div>
								<?php endif; ?>
							</div>
							<label class="flex flex-col gap-2">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Enviar nova imagem', 'guiawp' ); ?></span>
								<input type="file" name="gcep_blog_thumbnail" accept="image/*" class="rounded-xl border-slate-200 bg-white p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none">
							</label>
							<?php if ( GCEP_Gemini_Imagen::has_api_key() ) : ?>
							<button type="button" id="gcep-blog-img-ai" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all" style="background:<?php echo esc_attr( GCEP_Settings::get( 'cor_primaria', '#0052cc' ) ); ?>10;border:1px solid <?php echo esc_attr( GCEP_Settings::get( 'cor_primaria', '#0052cc' ) ); ?>30;color:<?php echo esc_attr( GCEP_Settings::get( 'cor_primaria', '#0052cc' ) ); ?>;">
								<span class="material-symbols-outlined text-base">auto_awesome</span>
								<?php esc_html_e( 'Gerar capa com IA', 'guiawp' ); ?>
							</button>
							<input type="hidden" name="gcep_blog_ai_thumbnail_id" id="gcep-blog-ai-thumb-id" value="">
							<?php endif; ?>
							<?php if ( $editing_thumbnail_id ) : ?>
								<label class="inline-flex items-center gap-2 text-sm text-slate-600">
									<input type="checkbox" name="gcep_blog_remove_thumbnail" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-200">
									<?php esc_html_e( 'Remover imagem destacada atual', 'guiawp' ); ?>
								</label>
							<?php endif; ?>
						</div>
						<!-- Vídeo de Destaque -->
						<div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4">
							<h4 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Vídeo de Destaque', 'guiawp' ); ?></h4>
							<p class="text-xs text-slate-400 leading-relaxed"><?php esc_html_e( 'Cole a URL do YouTube ou Vimeo. O vídeo será exibido sobre a imagem de capa.', 'guiawp' ); ?></p>
							<label class="flex flex-col gap-2">
								<input type="url" name="gcep_blog_video" value="<?php echo esc_attr( $editing_video_url ); ?>" class="rounded-xl border-slate-200 bg-white p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'https://www.youtube.com/watch?v=...', 'guiawp' ); ?>">
							</label>
							<?php if ( $editing_video_url ) : ?>
							<div class="rounded-xl overflow-hidden border border-slate-200 bg-black aspect-video">
								<?php echo wp_kses( GCEP_Helpers::get_video_embed( $editing_video_url ), [ 'div' => [ 'style' => true, 'class' => true ], 'iframe' => [ 'src' => true, 'class' => true, 'style' => true, 'allow' => true, 'allowfullscreen' => true, 'loading' => true, 'title' => true ] ] ); ?>
							</div>
							<?php endif; ?>
						</div>

						<!-- Anuncios relacionados -->
						<div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4">
							<h4 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Anúncios Relacionados', 'guiawp' ); ?></h4>
							<p class="text-xs text-slate-400 leading-relaxed"><?php esc_html_e( 'Selecione até 2 anúncios para exibir na sidebar do post.', 'guiawp' ); ?></p>

							<div id="gcep-anuncios-rel-wrap" class="relative">
								<input type="text" id="gcep-anuncios-busca" placeholder="<?php esc_attr_e( 'Buscar anúncio pelo nome...', 'guiawp' ); ?>" class="w-full rounded-xl border-slate-200 bg-white p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none" autocomplete="off">
								<div id="gcep-anuncios-resultados" class="hidden absolute left-0 right-0 top-full mt-1 z-20 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>

								<ul id="gcep-anuncios-selecionados" class="space-y-2 mt-3">
									<?php foreach ( $editing_anuncios_rel as $rel_item ) : ?>
									<li data-id="<?php echo esc_attr( $rel_item['id'] ); ?>" class="flex items-center justify-between gap-2 px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm">
										<span class="truncate font-medium text-slate-800"><?php echo esc_html( $rel_item['title'] ); ?></span>
										<input type="hidden" name="gcep_anuncios_rel[]" value="<?php echo esc_attr( $rel_item['id'] ); ?>">
										<button type="button" class="gcep-remover-anuncio flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>">
											<span class="material-symbols-outlined text-[16px]">close</span>
										</button>
									</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>

					</aside>
				</div>

				<div class="flex flex-wrap items-center justify-end gap-3">
					<a href="<?php echo esc_url( $blog_admin_url ); ?>" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
						<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
					</a>
					<button type="submit" class="px-8 py-3 rounded-xl bg-[#0052cc] text-white font-bold shadow-lg shadow-primary/20 hover:bg-[#003d99] transition-colors">
						<?php echo esc_html( $form_submit ); ?>
					</button>
				</div>
			</form>
		</section>

		<script>
		(function(){
			var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'gcep_buscar_anuncios' ) ); ?>';
			var maxAnuncios = 2;
			var input = document.getElementById('gcep-anuncios-busca');
			var resultados = document.getElementById('gcep-anuncios-resultados');
			var lista = document.getElementById('gcep-anuncios-selecionados');
			if (!input || !resultados || !lista) return;

			var debounce = null;

			function getIds() {
				var ids = [];
				lista.querySelectorAll('input[name="gcep_anuncios_rel[]"]').forEach(function(h){ ids.push(parseInt(h.value,10)); });
				return ids;
			}

			function criarItem(id, title) {
				var li = document.createElement('li');
				li.setAttribute('data-id', id);
				li.className = 'flex items-center justify-between gap-2 px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm';
				li.innerHTML = '<span class="truncate font-medium text-slate-800">' + title.replace(/</g,'&lt;') + '</span>'
					+ '<input type="hidden" name="gcep_anuncios_rel[]" value="' + id + '">'
					+ '<button type="button" class="gcep-remover-anuncio flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors" title="Remover">'
					+ '<span class="material-symbols-outlined text-[16px]">close</span></button>';
				return li;
			}

			function addAnuncio(id, title) {
				if (getIds().length >= maxAnuncios) {
					if (typeof gcepToast === 'function') gcepToast('Máximo de ' + maxAnuncios + ' anúncios.', 'warning');
					return;
				}
				if (getIds().indexOf(id) !== -1) return;
				lista.appendChild(criarItem(id, title));
				resultados.classList.add('hidden');
				input.value = '';
			}

			function buscar(termo) {
				resultados.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Buscando...</div>';
				resultados.classList.remove('hidden');
				fetch(ajaxUrl + '?action=gcep_buscar_anuncios&nonce=' + encodeURIComponent(nonce) + '&termo=' + encodeURIComponent(termo))
					.then(function(r){ return r.json(); })
					.then(function(res){
						if (!res.success || !res.data || !res.data.length) {
							resultados.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Nenhum anúncio encontrado.</div>';
							return;
						}
						var sIds = getIds();
						var html = '';
						res.data.forEach(function(item){
							var disabled = sIds.indexOf(item.id) !== -1;
							var badge = item.plano === 'premium' ? ' <span class="ml-1 px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded">Premium</span>' : '';
							html += '<div class="gcep-anuncio-resultado px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0' + (disabled ? ' opacity-40 pointer-events-none' : ' cursor-pointer') + '" data-id="' + item.id + '" data-title="' + item.title.replace(/"/g,'&quot;') + '">';
							html += item.title.replace(/</g,'&lt;') + badge + '</div>';
						});
						resultados.innerHTML = html;
						resultados.querySelectorAll('.gcep-anuncio-resultado').forEach(function(el){
							el.addEventListener('click', function(){
								addAnuncio(parseInt(el.dataset.id,10), el.dataset.title);
							});
						});
					})
					.catch(function(){ resultados.innerHTML = '<div class="px-3 py-2 text-xs text-rose-500">Erro na busca.</div>'; });
			}

			input.addEventListener('input', function(){
				clearTimeout(debounce);
				var v = input.value.trim();
				if (v.length < 2) { resultados.classList.add('hidden'); return; }
				debounce = setTimeout(function(){ buscar(v); }, 300);
			});

			input.addEventListener('focus', function(){
				if (input.value.trim().length >= 2) buscar(input.value.trim());
			});

			document.addEventListener('click', function(e){
				if (!e.target.closest('#gcep-anuncios-rel-wrap')) resultados.classList.add('hidden');
			});

			lista.addEventListener('click', function(e){
				var btn = e.target.closest('.gcep-remover-anuncio');
				if (btn) btn.closest('li').remove();
			});
		})();
		</script>
	<?php endif; ?>

	<?php if ( $is_category_view ) : ?>
		<section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-left">
					<thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
						<tr>
							<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Categoria', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 hidden sm:table-cell"><?php esc_html_e( 'Slug', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Posts', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-right"><?php esc_html_e( 'Acoes', 'guiawp' ); ?></th>
						</tr>
					</thead>
					<tbody id="gcep-blog-categories-list" class="divide-y divide-slate-100">
						<?php if ( ! empty( $blog_categories ) && ! is_wp_error( $blog_categories ) ) : ?>
							<?php foreach ( $blog_categories as $category ) : ?>
								<tr data-term-id="<?php echo esc_attr( $category->term_id ); ?>" class="hover:bg-slate-50/70 transition-colors">
									<td class="px-4 lg:px-6 py-4">
										<div class="font-semibold text-slate-900 gcep-blog-category-name"><?php echo esc_html( $category->name ); ?></div>
										<?php if ( $default_category === (int) $category->term_id ) : ?>
											<div class="mt-1 text-xs font-medium text-sky-600"><?php esc_html_e( 'Categoria padrão do WordPress', 'guiawp' ); ?></div>
										<?php endif; ?>
									</td>
									<td class="px-4 lg:px-6 py-4 text-sm text-slate-500 gcep-blog-category-slug hidden sm:table-cell"><?php echo esc_html( $category->slug ); ?></td>
									<td class="px-4 lg:px-6 py-4 text-sm text-slate-500 gcep-blog-category-count"><?php echo esc_html( (string) $category->count ); ?></td>
									<td class="px-4 lg:px-6 py-4 text-right">
										<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
											<button type="button" class="gcep-blog-edit-category inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" data-id="<?php echo esc_attr( $category->term_id ); ?>" data-name="<?php echo esc_attr( $category->name ); ?>" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>">
												<span class="material-symbols-outlined" style="font-size:18px;">edit</span>
											</button>
											<?php if ( $default_category !== (int) $category->term_id ) : ?>
												<button type="button" class="gcep-blog-delete-category inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" data-id="<?php echo esc_attr( $category->term_id ); ?>" title="<?php esc_attr_e( 'Excluir', 'guiawp' ); ?>">
													<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
												</button>
											<?php endif; ?>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr id="gcep-blog-categories-empty">
								<td colspan="4" class="px-6 py-16 text-center text-slate-400">
									<span class="material-symbols-outlined text-5xl block mb-3">folder</span>
									<?php esc_html_e( 'Nenhuma categoria encontrada.', 'guiawp' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>


		<div id="gcep-blog-category-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
			<div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 sm:p-8">
				<h3 id="gcep-blog-category-modal-title" class="text-xl font-bold text-slate-900 mb-6"></h3>
				<input type="hidden" id="gcep-blog-category-id" value="0">

				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nome da Categoria', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
					<input type="text" id="gcep-blog-category-name" class="rounded-lg border-slate-200 bg-slate-50 p-4 text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Ex: Marketing Local', 'guiawp' ); ?>">
				</label>

				<div class="flex gap-3 mt-8">
					<button type="button" id="gcep-blog-category-save" class="flex-1 px-6 py-3 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-xl font-bold text-sm transition-all">
						<?php esc_html_e( 'Salvar', 'guiawp' ); ?>
					</button>
					<button type="button" id="gcep-blog-category-cancel" class="px-6 py-3 border border-slate-200 text-slate-600 rounded-xl font-semibold text-sm hover:bg-slate-100 transition-all">
						<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
					</button>
				</div>
			</div>
		</div>

		<script>
		(function() {
			var modal = document.getElementById('gcep-blog-category-modal');
			var modalTitle = document.getElementById('gcep-blog-category-modal-title');
			var inputId = document.getElementById('gcep-blog-category-id');
			var inputName = document.getElementById('gcep-blog-category-name');
			var list = document.getElementById('gcep-blog-categories-list');
			var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';


			function openModal(title, termId, name) {
				modalTitle.textContent = title;
				inputId.value = termId || '0';
				inputName.value = name || '';
				modal.classList.remove('hidden');
				setTimeout(function() {
					inputName.focus();
				}, 50);
			}

			function closeModal() {
				modal.classList.add('hidden');
			}

			function parseAjaxResponse(response) {
				return response.text().then(function(text) {
					var data;
					try {
						data = JSON.parse(text);
					} catch (err) {
						throw new Error(text || 'Resposta invalida do servidor.');
					}
					return data;
				});
			}

			function escapeHtml(value) {
				return String(value)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;');
			}

			function buildRow(term) {
				var row = document.createElement('tr');
				var safeName = escapeHtml(term.name);
				var safeSlug = escapeHtml(term.slug);
				row.dataset.termId = term.term_id;
				row.className = 'hover:bg-slate-50/70 transition-colors';
				row.innerHTML =
					'<td class="px-6 py-4"><div class="font-semibold text-slate-900 gcep-blog-category-name">' + safeName + '</div></td>' +
					'<td class="px-6 py-4 text-sm text-slate-500 gcep-blog-category-slug">' + safeSlug + '</td>' +
					'<td class="px-6 py-4 text-sm text-slate-500 gcep-blog-category-count">' + term.count + '</td>' +
					'<td class="px-6 py-4"><div class="flex items-center justify-end gap-2">' +
					'<button type="button" class="gcep-blog-edit-category p-2 rounded-xl text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors" data-id="' + term.term_id + '" data-name="' + safeName + '">' +
					'<span class="material-symbols-outlined text-[20px]">edit</span></button>' +
					'<button type="button" class="gcep-blog-delete-category p-2 rounded-xl text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors" data-id="' + term.term_id + '">' +
					'<span class="material-symbols-outlined text-[20px]">delete</span></button>' +
					'</div></td>';
				return row;
			}

			document.getElementById('gcep-blog-add-category').addEventListener('click', function() {
				openModal('<?php echo esc_js( __( 'Nova Categoria', 'guiawp' ) ); ?>', '0', '');
			});

			document.getElementById('gcep-blog-category-cancel').addEventListener('click', closeModal);

			modal.addEventListener('click', function(event) {
				if (event.target === modal) {
					closeModal();
				}
			});

			document.getElementById('gcep-blog-category-save').addEventListener('click', function() {
				var termId = inputId.value;
				var name = inputName.value.trim();
				var action = termId === '0' ? 'gcep_create_blog_category' : 'gcep_update_blog_category';
				var body = new URLSearchParams();

				if (!name) {
					inputName.focus();
					return;
				}

				body.append('action', action);
				body.append('nonce', nonce);
				body.append('name', name);

				if (termId !== '0') {
					body.append('term_id', termId);
				}

				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				})
				.then(parseAjaxResponse)
				.then(function(res) {
					if (!res.success) {
						throw new Error(res.data && res.data.message ? res.data.message : 'Erro ao salvar categoria.');
					}

					var emptyRow = document.getElementById('gcep-blog-categories-empty');
					if (emptyRow) {
						emptyRow.remove();
					}

					if (termId === '0') {
						list.appendChild(buildRow(res.data));
					} else {
						var row = list.querySelector('[data-term-id="' + termId + '"]');
						if (row) {
							row.querySelector('.gcep-blog-category-name').textContent = res.data.name;
							row.querySelector('.gcep-blog-category-slug').textContent = res.data.slug;
							row.querySelector('.gcep-blog-category-count').textContent = res.data.count;
							row.querySelector('.gcep-blog-edit-category').dataset.name = res.data.name;
						}
					}

					closeModal();
					gcepToast(res.data.message, 'success');
				})
				.catch(function(error) {
					gcepToast(error.message || 'Erro ao salvar categoria.', 'error');
				});
			});

			document.addEventListener('click', function(event) {
				var editButton = event.target.closest('.gcep-blog-edit-category');
				var deleteButton = event.target.closest('.gcep-blog-delete-category');

				if (editButton) {
					openModal('<?php echo esc_js( __( 'Editar Categoria', 'guiawp' ) ); ?>', editButton.dataset.id, editButton.dataset.name);
					return;
				}

				if (!deleteButton) {
					return;
				}

				if (!window.confirm('<?php echo esc_js( __( 'Excluir esta categoria do blog?', 'guiawp' ) ); ?>')) {
					return;
				}

				var body = new URLSearchParams();
				body.append('action', 'gcep_delete_blog_category');
				body.append('nonce', nonce);
				body.append('term_id', deleteButton.dataset.id);

				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				})
				.then(parseAjaxResponse)
				.then(function(res) {
					if (!res.success) {
						throw new Error(res.data && res.data.message ? res.data.message : 'Erro ao excluir categoria.');
					}

					var row = list.querySelector('[data-term-id="' + deleteButton.dataset.id + '"]');
					if (row) {
						row.remove();
					}

					if (!list.children.length) {
						var empty = document.createElement('tr');
						empty.id = 'gcep-blog-categories-empty';
						empty.innerHTML = '<td colspan="4" class="px-6 py-16 text-center text-slate-400"><span class="material-symbols-outlined text-5xl block mb-3">folder</span><?php echo esc_js( __( 'Nenhuma categoria encontrada.', 'guiawp' ) ); ?></td>';
						list.appendChild(empty);
					}

					gcepToast(res.data.message, 'success');
				})
				.catch(function(error) {
					gcepToast(error.message || 'Erro ao excluir categoria.', 'error');
				});
			});
		})();
		</script>
	<?php endif; ?>
</main>
<?php if ( $blog_posts instanceof WP_Query ) : ?>
	<?php wp_reset_postdata(); ?>
<?php endif; ?>
</div>
<?php include __DIR__ . '/partial-modal-imagen.php'; ?>

<script>
(function(){
	var aiBtn = document.getElementById('gcep-blog-img-ai');
	if (!aiBtn || !window.gcepImagenModal) return;

	aiBtn.addEventListener('click', function(){
		window.gcepImagenModal.open('blog', function(attachmentId, url){
			// Atualizar preview
			var previewWrap = document.getElementById('gcep-blog-thumb-preview');
			previewWrap.innerHTML = '<img src="' + url + '" alt="" class="w-full h-44 rounded-2xl object-cover border border-slate-200 bg-white">';

			// Setar hidden input com o attachment ID
			var hiddenInput = document.getElementById('gcep-blog-ai-thumb-id');
			if (hiddenInput) hiddenInput.value = attachmentId;
		});
	});
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
