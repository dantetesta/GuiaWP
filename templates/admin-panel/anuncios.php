<?php
/**
 * Template: Admin - Gestão de Anúncios
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.2 - 2026-03-21 - Botoes de acao com bordas e tamanho fixo 32px, botao conferencia rapida com modal completo, link Ver Anuncio (target blank)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$search_query  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$date_from     = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to       = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
$date_from     = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ? $date_from : '';
$date_to       = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ? $date_to : '';
$paged         = max( 1, intval( $_GET['pg'] ?? 1 ) );
$args          = [
	'posts_per_page' => 15,
	'paged'          => $paged,
	'search'         => $search_query,
	'date_from'      => $date_from,
	'date_to'        => $date_to,
];
if ( $status_filter ) {
	$args['status'] = $status_filter;
}
$result   = GCEP_Dashboard_Admin::get_anuncios_paged( $args );
$anuncios = $result['posts'];
$pages    = $result['pages'];
$total    = $result['total'];
$msg      = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';

$status_tabs = [
	''                      => [ 'label' => __( 'Todos', 'guiawp' ), 'icon' => 'apps' ],
	'aguardando_aprovacao'  => [ 'label' => __( 'Aguardando Aprovação', 'guiawp' ), 'icon' => 'hourglass_top' ],
	'publicado'             => [ 'label' => __( 'Publicados', 'guiawp' ), 'icon' => 'check_circle' ],
	'rejeitado'             => [ 'label' => __( 'Rejeitados', 'guiawp' ), 'icon' => 'cancel' ],
	'aguardando_pagamento'  => [ 'label' => __( 'Aguardando Pagamento', 'guiawp' ), 'icon' => 'payments' ],
	'expirado'              => [ 'label' => __( 'Expirados', 'guiawp' ), 'icon' => 'schedule' ],
];

$build_admin_anuncios_url = static function ( array $overrides = [] ) use ( $status_filter, $search_query, $date_from, $date_to ) {
	$params = [
		'status'    => $status_filter,
		'q'         => $search_query,
		'date_from' => $date_from,
		'date_to'   => $date_to,
	];

	foreach ( $params as $key => $value ) {
		if ( '' === $value || null === $value ) {
			unset( $params[ $key ] );
		}
	}

	foreach ( $overrides as $key => $value ) {
		if ( null === $value || '' === $value ) {
			unset( $params[ $key ] );
			continue;
		}

		$params[ $key ] = $value;
	}

	unset( $params['pg'] );

	return add_query_arg( $params, home_url( '/painel-admin/anuncios' ) );
};

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="lg:ml-64 flex-1 p-4 lg:p-8 min-w-0 overflow-x-hidden">
	<header class="mb-8">
		<div>
			<h2 class="text-3xl font-black tracking-tight mb-2"><?php esc_html_e( 'Gestão de Anúncios', 'guiawp' ); ?></h2>
			<p class="text-slate-500"><?php printf( esc_html__( '%d anúncios encontrados', 'guiawp' ), $total ); ?></p>
		</div>
	</header>

	<?php if ( $msg ) : ?>
	<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
	<?php endif; ?>

	<section class="mb-6 rounded-xl border border-slate-200 bg-white p-3 lg:p-4 shadow-sm space-y-3">
		<div class="overflow-x-auto pb-1">
			<div class="inline-flex min-w-max gap-2">
				<?php foreach ( $status_tabs as $status_key => $status_data ) :
					$is_active  = $status_filter === $status_key;
					$status_url = $build_admin_anuncios_url( [ 'status' => $status_key ] );
				?>
				<a href="<?php echo esc_url( $status_url ); ?>" class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-bold transition-colors <?php echo $is_active ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700'; ?>">
					<span class="material-symbols-outlined text-[16px]"><?php echo esc_html( $status_data['icon'] ); ?></span>
					<?php echo esc_html( $status_data['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</div>
		</div>

		<form method="get" action="<?php echo esc_url( home_url( '/painel-admin/anuncios' ) ); ?>" class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_148px_148px_auto_auto] gap-2 items-center">
			<?php if ( '' !== $status_filter ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
			<?php endif; ?>

			<label class="sr-only" for="gcep-admin-anuncios-search"><?php esc_html_e( 'Pesquisar anúncios', 'guiawp' ); ?></label>
			<div class="relative">
				<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
				<input id="gcep-admin-anuncios-search" type="search" name="q" value="<?php echo esc_attr( $search_query ); ?>" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Título, usuário, e-mail, telefone ou categoria', 'guiawp' ); ?>">
			</div>

			<label class="sr-only" for="gcep-admin-anuncios-date-from"><?php esc_html_e( 'Data inicial', 'guiawp' ); ?></label>
			<input id="gcep-admin-anuncios-date-from" type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#0052cc]/20 outline-none">

			<label class="sr-only" for="gcep-admin-anuncios-date-to"><?php esc_html_e( 'Data final', 'guiawp' ); ?></label>
			<input id="gcep-admin-anuncios-date-to" type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#0052cc]/20 outline-none">

			<button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0052cc] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#003d99] transition-colors">
				<span class="material-symbols-outlined text-[18px]">filter_alt</span>
				<?php esc_html_e( 'Filtrar', 'guiawp' ); ?>
			</button>

			<a href="<?php echo esc_url( home_url( '/painel-admin/anuncios' ) ); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
				<span class="material-symbols-outlined text-[18px]">close_small</span>
				<?php esc_html_e( 'Limpar', 'guiawp' ); ?>
			</a>
		</form>
	</section>

	<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
		<div class="overflow-x-auto">
			<table class="w-full text-left">
				<thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
					<tr>
						<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Anúncio', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 hidden lg:table-cell"><?php esc_html_e( 'Categoria', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 hidden md:table-cell"><?php esc_html_e( 'Usuário', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 hidden sm:table-cell"><?php esc_html_e( 'Plano', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Status', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 text-right"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
					</tr>
				</thead>
					<tbody class="divide-y divide-slate-100">
						<?php if ( empty( $anuncios ) ) : ?>
						<tr>
							<td colspan="6" class="px-6 py-14 text-center">
								<div class="mx-auto max-w-md space-y-3">
									<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
										<span class="material-symbols-outlined text-[26px]">inventory_2</span>
									</div>
									<h3 class="text-lg font-bold text-slate-900"><?php esc_html_e( 'Nenhum anúncio encontrado', 'guiawp' ); ?></h3>
									<p class="text-sm text-slate-500"><?php esc_html_e( 'Ajuste os filtros ou a pesquisa para localizar anúncios neste painel.', 'guiawp' ); ?></p>
								</div>
							</td>
						</tr>
						<?php endif; ?>
						<?php foreach ( $anuncios as $anuncio ) :
							$status        = get_post_meta( $anuncio->ID, 'GCEP_status_anuncio', true ) ?: 'rascunho';
							$plano         = get_post_meta( $anuncio->ID, 'GCEP_tipo_plano', true ) ?: 'gratis';
							$color         = GCEP_Helpers::get_status_color( $status );
							$author        = get_userdata( $anuncio->post_author );
							$author_phone  = $author ? get_user_meta( $author->ID, 'gcep_telefone', true ) : '';
							$cats          = wp_get_object_terms( $anuncio->ID, 'gcep_categoria', [ 'fields' => 'names' ] );
							$locs          = wp_get_object_terms( $anuncio->ID, 'gcep_localizacao', [ 'fields' => 'names' ] );
							$justificativa = get_post_meta( $anuncio->ID, 'GCEP_ai_justificativa', true );
							// Dados completos para modal de conferencia
							$_qv_keys = [ 'GCEP_tipo_anuncio', 'GCEP_cnpj', 'GCEP_descricao_curta', 'GCEP_telefone', 'GCEP_whatsapp', 'GCEP_email', 'GCEP_endereco_completo', 'GCEP_site', 'GCEP_instagram', 'GCEP_facebook', 'GCEP_linkedin', 'GCEP_youtube', 'GCEP_x_twitter', 'GCEP_tiktok', 'GCEP_threads' ];
							$_qv_data = [
								'titulo'    => $anuncio->post_title,
								'id'        => $anuncio->ID,
								'status'    => GCEP_Helpers::get_status_label( $status ),
								'plano'     => ucfirst( $plano ),
								'categoria' => ! empty( $cats ) ? implode( ', ', $cats ) : '—',
								'local'     => ! empty( $locs ) ? implode( ', ', $locs ) : '—',
								'autor'     => $author ? $author->display_name : '—',
								'email_autor' => $author ? $author->user_email : '—',
								'tel_autor' => $author_phone ? GCEP_Helpers::format_phone( (string) $author_phone ) : '—',
								'criado_em' => get_the_date( 'd/m/Y H:i', $anuncio->ID ),
							];
							foreach ( $_qv_keys as $_k ) {
								$_qv_data[ $_k ] = (string) get_post_meta( $anuncio->ID, $_k, true );
							}
							$_qv_thumb = has_post_thumbnail( $anuncio->ID ) ? get_the_post_thumbnail_url( $anuncio->ID, 'medium' ) : '';
						?>
					<tr class="hover:bg-slate-50">
						<td class="px-4 lg:px-6 py-4">
							<div class="flex items-center gap-3">
								<?php if ( has_post_thumbnail( $anuncio->ID ) ) : ?>
								<div class="w-10 h-10 rounded bg-slate-100 overflow-hidden flex-shrink-0">
									<?php echo get_the_post_thumbnail( $anuncio->ID, 'thumbnail', [ 'class' => 'w-full h-full object-cover' ] ); ?>
								</div>
								<?php endif; ?>
								<div class="min-w-0">
									<p class="text-sm font-semibold truncate"><?php echo esc_html( $anuncio->post_title ); ?></p>
									<p class="text-xs text-slate-500">ID: #<?php echo esc_html( $anuncio->ID ); ?></p>
								</div>
							</div>
						</td>
						<td class="px-4 lg:px-6 py-4 text-sm hidden lg:table-cell"><?php echo esc_html( ! empty( $cats ) ? implode( ', ', $cats ) : '—' ); ?></td>
							<td class="px-4 lg:px-6 py-4 hidden md:table-cell">
								<div class="flex flex-col">
									<span class="text-sm font-medium"><?php echo esc_html( $author ? $author->display_name : '—' ); ?></span>
									<span class="text-xs text-slate-500"><?php echo esc_html( $author ? $author->user_email : '' ); ?></span>
									<?php if ( ! empty( $author_phone ) ) : ?>
									<span class="text-xs text-slate-400"><?php echo esc_html( GCEP_Helpers::format_phone( (string) $author_phone ) ); ?></span>
									<?php endif; ?>
								</div>
							</td>
						<td class="px-4 lg:px-6 py-4 hidden sm:table-cell">
							<span class="text-sm"><?php echo esc_html( ucfirst( $plano ) ); ?></span>
						</td>
						<td class="px-4 lg:px-6 py-4">
							<span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo esc_attr( GCEP_Helpers::get_status_badge_classes( $status ) ); ?>">
								<?php echo esc_html( GCEP_Helpers::get_status_label_short( $status ) ); ?>
							</span>
						</td>
						<td class="px-4 lg:px-6 py-4 text-right">
							<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
								<?php if ( 'rejeitado' === $status && ! empty( $justificativa ) ) : ?>
								<button type="button" class="gcep-show-rejection-reason inline-flex items-center justify-center w-8 h-8 rounded-lg border border-rose-200 bg-rose-50 text-rose-500 hover:bg-rose-100 transition-colors" data-title="<?php echo esc_attr( $anuncio->post_title ); ?>" data-reason="<?php echo esc_attr( $justificativa ); ?>" title="<?php esc_attr_e( 'Ver motivo da rejeição', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">info</span>
								</button>
								<?php endif; ?>
								<?php if ( 'publicado' !== $status ) : ?>
								<button class="gcep-status-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-status="publicado" title="<?php esc_attr_e( 'Publicar', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">check_circle</span>
								</button>
								<?php endif; ?>
								<?php if ( 'rejeitado' !== $status ) : ?>
								<button class="gcep-status-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-status="rejeitado" title="<?php esc_attr_e( 'Rejeitar', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">cancel</span>
								</button>
								<?php endif; ?>
								<?php if ( 'aguardando_aprovacao' !== $status ) : ?>
								<button class="gcep-status-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600 transition-colors hidden lg:inline-flex" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-status="aguardando_aprovacao" title="<?php esc_attr_e( 'Pendente', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">hourglass_top</span>
								</button>
								<?php endif; ?>
								<button type="button" class="gcep-quickview-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" data-anuncio='<?php echo esc_attr( wp_json_encode( $_qv_data, JSON_UNESCAPED_UNICODE ) ); ?>' data-thumb="<?php echo esc_attr( $_qv_thumb ); ?>" title="<?php esc_attr_e( 'Conferência Rápida', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">quick_reference_all</span>
								</button>
								<a href="<?php echo esc_url( get_permalink( $anuncio->ID ) ); ?>" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Ver Anúncio', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">visibility</span>
								</a>
								<a href="<?php echo esc_url( home_url( '/painel-admin/editar-anuncio?id=' . $anuncio->ID ) ); ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">edit</span>
								</a>
								<button type="button" class="gcep-delete-anuncio-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-title="<?php echo esc_attr( $anuncio->post_title ); ?>" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>">
									<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
								</button>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		$base_url = $build_admin_anuncios_url();
		include GCEP_PLUGIN_DIR . 'templates/partials/pagination.php';
		?>
	</div>
</main>
</div>
<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-rejection-modal.php'; ?>

<!-- Modal: Conferência Rápida do Anúncio -->
<div id="gcep-quickview-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4" style="overflow-y:auto;">
	<div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto" style="max-height:90vh;display:flex;flex-direction:column;">
		<!-- Header -->
		<div class="px-6 py-5 border-b border-slate-200 flex items-start justify-between gap-4" style="flex-shrink:0;">
			<div class="min-w-0">
				<p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1"><?php esc_html_e( 'Conferência Rápida', 'guiawp' ); ?></p>
				<h3 id="gcep-qv-titulo" class="text-xl font-black tracking-tight text-slate-900 truncate"></h3>
				<p id="gcep-qv-id" class="text-xs text-slate-400 mt-0.5"></p>
			</div>
			<button type="button" id="gcep-qv-close" class="w-10 h-10 inline-flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors" style="flex-shrink:0;">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>
		<!-- Conteúdo scrollável -->
		<div id="gcep-qv-body" class="px-6 py-5 space-y-5" style="overflow-y:auto;flex:1;min-height:0;">
			<!-- Thumb + Info básica -->
			<div class="flex gap-4 items-start">
				<div id="gcep-qv-thumb-wrap" class="hidden w-24 h-24 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0">
					<img id="gcep-qv-thumb" src="" alt="" class="w-full h-full object-cover">
				</div>
				<div class="flex-1 min-w-0 space-y-2">
					<div id="gcep-qv-badges" class="flex flex-wrap gap-2"></div>
					<div id="gcep-qv-meta-basic" class="text-sm text-slate-600 space-y-1"></div>
				</div>
			</div>
			<!-- Dados detalhados -->
			<div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="gcep-qv-fields"></div>
			<!-- Redes sociais -->
			<div id="gcep-qv-socials-wrap" class="hidden">
				<p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2"><?php esc_html_e( 'Redes Sociais', 'guiawp' ); ?></p>
				<div id="gcep-qv-socials" class="flex flex-wrap gap-2"></div>
			</div>
			<!-- Descrição curta -->
			<div id="gcep-qv-desc-wrap" class="hidden">
				<p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2"><?php esc_html_e( 'Descrição Curta', 'guiawp' ); ?></p>
				<p id="gcep-qv-desc" class="text-sm text-slate-600 leading-relaxed bg-slate-50 rounded-xl p-4 border border-slate-100"></p>
			</div>
		</div>
		<!-- Footer -->
		<div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between gap-3" style="flex-shrink:0;">
			<button type="button" id="gcep-qv-close-bottom" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
				<?php esc_html_e( 'Fechar', 'guiawp' ); ?>
			</button>
			<div class="flex gap-2">
				<a id="gcep-qv-link-view" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-colors">
					<span class="material-symbols-outlined" style="font-size:16px;">visibility</span>
					<?php esc_html_e( 'Ver Anúncio', 'guiawp' ); ?>
				</a>
				<a id="gcep-qv-link-edit" href="#" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#0052cc] text-white text-sm font-bold hover:bg-[#003d99] transition-colors">
					<span class="material-symbols-outlined" style="font-size:16px;">edit</span>
					<?php esc_html_e( 'Editar', 'guiawp' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<script>
// Conferência rápida do anúncio
(function(){
	var modal = document.getElementById('gcep-quickview-modal');
	if (!modal) return;

	var tituloEl   = document.getElementById('gcep-qv-titulo');
	var idEl       = document.getElementById('gcep-qv-id');
	var thumbWrap  = document.getElementById('gcep-qv-thumb-wrap');
	var thumbImg   = document.getElementById('gcep-qv-thumb');
	var badgesEl   = document.getElementById('gcep-qv-badges');
	var metaBasicEl = document.getElementById('gcep-qv-meta-basic');
	var fieldsEl   = document.getElementById('gcep-qv-fields');
	var socialsWrap = document.getElementById('gcep-qv-socials-wrap');
	var socialsEl  = document.getElementById('gcep-qv-socials');
	var descWrap   = document.getElementById('gcep-qv-desc-wrap');
	var descEl     = document.getElementById('gcep-qv-desc');
	var linkView   = document.getElementById('gcep-qv-link-view');
	var linkEdit   = document.getElementById('gcep-qv-link-edit');

	var fieldLabels = {
		'GCEP_tipo_anuncio': '<?php echo esc_js( __( 'Tipo', 'guiawp' ) ); ?>',
		'GCEP_cnpj': 'CNPJ',
		'GCEP_telefone': '<?php echo esc_js( __( 'Telefone', 'guiawp' ) ); ?>',
		'GCEP_whatsapp': 'WhatsApp',
		'GCEP_email': 'E-mail',
		'GCEP_endereco_completo': '<?php echo esc_js( __( 'Endereço', 'guiawp' ) ); ?>',
		'GCEP_site': 'Site'
	};

	var socialLabels = {
		'GCEP_instagram': 'Instagram',
		'GCEP_facebook': 'Facebook',
		'GCEP_linkedin': 'LinkedIn',
		'GCEP_youtube': 'YouTube',
		'GCEP_x_twitter': 'X',
		'GCEP_tiktok': 'TikTok',
		'GCEP_threads': 'Threads'
	};

	function esc(str) {
		var d = document.createElement('div');
		d.textContent = str;
		return d.innerHTML;
	}

	function openQuickview(data, thumb) {
		tituloEl.textContent = data.titulo || '';
		idEl.textContent = 'ID: #' + (data.id || '');

		// Thumb
		if (thumb) {
			thumbImg.src = thumb;
			thumbWrap.classList.remove('hidden');
		} else {
			thumbWrap.classList.add('hidden');
		}

		// Badges: status, plano, categoria
		badgesEl.innerHTML = '';
		var badges = [
			{ label: data.status || '', color: '#64748b' },
			{ label: data.plano || '', color: '#0052cc' },
			{ label: data.categoria || '', color: '#8b5cf6' }
		];
		badges.forEach(function(b) {
			if (!b.label || b.label === '—') return;
			var s = document.createElement('span');
			s.textContent = b.label;
			s.style.cssText = 'display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:700;border:1px solid ' + b.color + '30;color:' + b.color + ';background:' + b.color + '08;';
			badgesEl.appendChild(s);
		});

		// Meta básica
		metaBasicEl.innerHTML = '';
		var metaItems = [
			{ icon: 'person', val: data.autor },
			{ icon: 'mail', val: data.email_autor },
			{ icon: 'phone', val: data.tel_autor },
			{ icon: 'location_on', val: data.local },
			{ icon: 'calendar_today', val: data.criado_em }
		];
		metaItems.forEach(function(m) {
			if (!m.val || m.val === '—') return;
			var d = document.createElement('div');
			d.style.cssText = 'display:flex;align-items:center;gap:6px;';
			d.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;color:#94a3b8;">' + m.icon + '</span><span>' + esc(m.val) + '</span>';
			metaBasicEl.appendChild(d);
		});

		// Campos detalhados
		fieldsEl.innerHTML = '';
		Object.keys(fieldLabels).forEach(function(key) {
			var val = data[key] || '';
			if (!val) return;
			var card = document.createElement('div');
			card.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;';
			var lbl = document.createElement('p');
			lbl.style.cssText = 'font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;margin-bottom:4px;';
			lbl.textContent = fieldLabels[key];
			var v = document.createElement('p');
			v.style.cssText = 'font-size:14px;font-weight:600;color:#1e293b;word-break:break-word;';
			if (key === 'GCEP_site' && val) {
				v.innerHTML = '<a href="' + esc(val) + '" target="_blank" style="color:#0052cc;text-decoration:underline;">' + esc(val.replace(/https?:\/\//, '')) + '</a>';
			} else if (key === 'GCEP_tipo_anuncio') {
				v.textContent = val === 'empresa' ? '<?php echo esc_js( __( 'Empresa', 'guiawp' ) ); ?>' : '<?php echo esc_js( __( 'Profissional Liberal', 'guiawp' ) ); ?>';
			} else {
				v.textContent = val;
			}
			card.appendChild(lbl);
			card.appendChild(v);
			fieldsEl.appendChild(card);
		});

		// Redes sociais
		socialsEl.innerHTML = '';
		var hasSocials = false;
		Object.keys(socialLabels).forEach(function(key) {
			var val = data[key] || '';
			if (!val) return;
			hasSocials = true;
			var a = document.createElement('a');
			a.href = val;
			a.target = '_blank';
			a.rel = 'noopener';
			a.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:9999px;font-size:12px;font-weight:600;border:1px solid #e2e8f0;color:#475569;background:#fff;text-decoration:none;transition:all .2s;';
			a.textContent = socialLabels[key];
			a.addEventListener('mouseenter', function() { this.style.borderColor = '#0052cc'; this.style.color = '#0052cc'; });
			a.addEventListener('mouseleave', function() { this.style.borderColor = '#e2e8f0'; this.style.color = '#475569'; });
			socialsEl.appendChild(a);
		});
		socialsWrap.classList.toggle('hidden', !hasSocials);

		// Descrição curta
		var desc = data.GCEP_descricao_curta || '';
		if (desc) {
			descEl.textContent = desc;
			descWrap.classList.remove('hidden');
		} else {
			descWrap.classList.add('hidden');
		}

		// Links
		// O permalink é injetado via data-permalink no botão
		var triggerBtn = document.querySelector('.gcep-quickview-btn[data-anuncio*=\'"id":' + data.id + '\']');
		linkView.href = triggerBtn ? triggerBtn.closest('tr').querySelector('a[target="_blank"]').href : '<?php echo esc_url( home_url( '/?p=' ) ); ?>' + data.id;
		linkEdit.href = '<?php echo esc_url( home_url( '/painel-admin/editar-anuncio?id=' ) ); ?>' + data.id;

		modal.classList.remove('hidden');
		document.body.classList.add('overflow-hidden');
	}

	function closeQuickview() {
		modal.classList.add('hidden');
		document.body.classList.remove('overflow-hidden');
	}

	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.gcep-quickview-btn');
		if (btn) {
			e.preventDefault();
			try {
				var data = JSON.parse(btn.dataset.anuncio);
				openQuickview(data, btn.dataset.thumb || '');
			} catch(err) {}
			return;
		}
		if (e.target === modal) closeQuickview();
	});

	document.getElementById('gcep-qv-close').addEventListener('click', closeQuickview);
	document.getElementById('gcep-qv-close-bottom').addEventListener('click', closeQuickview);
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeQuickview();
	});
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
