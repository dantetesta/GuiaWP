<?php
/**
 * Template: Editar Anúncio (Multi-Step Redesign)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.1.0 - 2026-03-11 - Multi-step, CNPJ, ViaCEP, galeria, vídeos, crop, redes sociais
 * @modified 1.9.6 - 2026-03-21 - Redesign: 5 steps, barra linear no header, nav sticky bottom, redes sociais step proprio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$anuncio_id = intval( $_GET['id'] ?? 0 );
$user_id    = get_current_user_id();
$post       = get_post( $anuncio_id );

$current_route      = (string) get_query_var( 'gcep_route', 'painel/editar-anuncio' );
$is_admin_context   = str_starts_with( $current_route, 'painel-admin' );
$is_admin_user      = current_user_can( 'manage_options' );
$is_owner           = $post && (int) $post->post_author === $user_id;
$anuncios_list_url  = home_url( $is_admin_context ? '/painel-admin/anuncios' : '/painel/anuncios' );

if ( ! $post || 'gcep_anuncio' !== $post->post_type || ( ! $is_owner && ! $is_admin_user ) ) {
	wp_safe_redirect( $anuncios_list_url );
	exit;
}

// Carregar metas existentes
$meta_keys = [
	'GCEP_tipo_anuncio', 'GCEP_tipo_plano', 'GCEP_cnpj',
	'GCEP_descricao_curta', 'GCEP_descricao_longa',
	'GCEP_telefone', 'GCEP_whatsapp', 'GCEP_email',
	'GCEP_cep', 'GCEP_logradouro', 'GCEP_numero', 'GCEP_complemento',
	'GCEP_bairro', 'GCEP_cidade', 'GCEP_estado',
	'GCEP_latitude', 'GCEP_longitude',
	'GCEP_site', 'GCEP_instagram', 'GCEP_facebook', 'GCEP_linkedin',
	'GCEP_youtube', 'GCEP_x_twitter', 'GCEP_tiktok', 'GCEP_threads',
	'GCEP_status_anuncio', 'GCEP_logo_ou_foto_principal', 'GCEP_foto_capa',
	'GCEP_galeria_fotos', 'GCEP_galeria_videos',
];
$meta = [];
foreach ( $meta_keys as $k ) {
	$meta[ $k ] = get_post_meta( $anuncio_id, $k, true );
}

$categorias     = get_terms( [ 'taxonomy' => 'gcep_categoria', 'hide_empty' => false, 'parent' => 0 ] );
$post_cats      = wp_get_object_terms( $anuncio_id, 'gcep_categoria', [ 'fields' => 'ids' ] );
$planos_premium = GCEP_Plans::get_active();

$tipo_anuncio     = $meta['GCEP_tipo_anuncio'] ?: 'empresa';
$tipo_plano       = $meta['GCEP_tipo_plano'] ?: 'gratis';
$is_premium       = 'premium' === $tipo_plano;
$status_anuncio   = $meta['GCEP_status_anuncio'] ?: 'rascunho';
$status_pagamento = get_post_meta( $anuncio_id, 'GCEP_status_pagamento', true );
$plano_id_atual   = get_post_meta( $anuncio_id, 'GCEP_plano_id', true );
$plano_preco      = get_post_meta( $anuncio_id, 'GCEP_plano_preco', true );
$vigencia_dias    = get_post_meta( $anuncio_id, 'GCEP_vigencia_dias', true );
$plano_data       = $plano_id_atual ? GCEP_Plans::get( (int) $plano_id_atual ) : null;
$saved_ai_reason  = trim( (string) get_post_meta( $anuncio_id, 'GCEP_ai_justificativa', true ) );
$ai_enabled       = GCEP_AI_Validator::can_generate_content();

// Galeria de vídeos
$videos = $meta['GCEP_galeria_videos'];
if ( ! is_array( $videos ) ) {
	$videos = [];
}

// Galeria de fotos (IDs)
$galeria_ids = [];
if ( ! empty( $meta['GCEP_galeria_fotos'] ) ) {
	$galeria_ids = array_filter( explode( ',', $meta['GCEP_galeria_fotos'] ) );
}

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/' . ( $is_admin_context ? 'admin-sidebar' : 'dashboard-sidebar' ) . '.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<!-- Header com barra de progresso -->
	<header id="gcep-form-header" class="bg-white/95 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200">
		<div class="px-4 lg:px-8 flex items-center justify-between" style="height:56px">
			<div class="flex items-center gap-3 min-w-0">
				<a href="<?php echo esc_url( $anuncios_list_url ); ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors flex-shrink-0" aria-label="<?php esc_attr_e( 'Voltar para anúncios', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-xl">arrow_back</span>
				</a>
				<h2 class="text-sm font-bold text-slate-900 truncate"><?php esc_html_e( 'Editar Anúncio', 'guiawp' ); ?></h2>
			</div>
			<!-- Contador mobile -->
			<span id="gcep-step-counter" class="text-xs font-semibold text-slate-400 sm:hidden">1 / 7</span>
		</div>

		<!-- Barra de progresso linear -->
		<div class="px-4 lg:px-8 pb-3">
			<!-- Labels desktop -->
			<div class="hidden sm:flex items-center justify-between mb-1.5">
				<?php
				$step_labels = [
					1 => __( 'Tipo', 'guiawp' ),
					2 => __( 'Informações', 'guiawp' ),
					3 => __( 'Contato', 'guiawp' ),
					4 => __( 'Endereço', 'guiawp' ),
					5 => __( 'Redes', 'guiawp' ),
					6 => __( 'Mídia', 'guiawp' ),
					7 => __( 'Galeria', 'guiawp' ),
				];
				foreach ( $step_labels as $n => $label ) :
				?>
				<span data-step-label="<?php echo esc_attr( $n ); ?>" class="text-xs font-semibold transition-colors <?php echo 1 === $n ? 'text-[#0052cc]' : 'text-slate-400'; ?>"><?php echo esc_html( $label ); ?></span>
				<?php endforeach; ?>
			</div>
			<!-- Barra -->
			<div class="w-full bg-slate-200 rounded-full overflow-hidden" style="height:4px">
				<div id="gcep-progress-bar" class="h-full bg-[#0052cc] rounded-full transition-all duration-300" style="width:14.28%"></div>
			</div>
		</div>
	</header>

	<div class="p-4 sm:p-6 lg:p-8 max-w-[900px] w-full mx-auto flex-1">

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="gcep-multistep-form" data-edit-mode="1">
			<input type="hidden" name="action" value="gcep_save_anuncio">
			<input type="hidden" name="gcep_anuncio_id" value="<?php echo esc_attr( $anuncio_id ); ?>">
			<input type="hidden" name="gcep_edit_context" value="<?php echo esc_attr( $is_admin_context ? 'admin' : 'user' ); ?>">
			<input type="hidden" name="gcep_tipo_plano" id="gcep_tipo_plano_hidden" value="<?php echo esc_attr( $tipo_plano ); ?>">
			<?php if ( $is_premium ) : ?>
			<input type="hidden" name="gcep_plano_id" value="<?php echo esc_attr( $plano_id_atual ); ?>">
			<?php endif; ?>
			<?php wp_nonce_field( 'gcep_save_anuncio', 'gcep_anuncio_nonce' ); ?>

			<!-- ==================== STEP 1: Tipo e Plano ==================== -->
			<div data-step="1">
				<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200">
					<h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
						<span class="material-symbols-outlined text-[#0052cc] text-lg">tune</span>
						<?php esc_html_e( 'Tipo e Plano do Anuncio', 'guiawp' ); ?>
					</h2>
					<p class="text-xs text-slate-500 mb-5"><?php echo $is_premium ? esc_html__( 'O tipo de anuncio pode ser alterado. O plano premium esta travado na vigencia contratada.', 'guiawp' ) : esc_html__( 'Altere o tipo de anuncio ou faca upgrade para o plano premium.', 'guiawp' ); ?></p>

					<div class="grid grid-cols-1 gap-5">
						<!-- Tipo de anuncio: editavel -->
						<div>
							<span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block"><?php esc_html_e( 'Tipo de Anuncio', 'guiawp' ); ?></span>
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
								<label class="relative cursor-pointer">
									<input type="radio" name="gcep_tipo_anuncio" value="empresa" <?php checked( $tipo_anuncio, 'empresa' ); ?> class="peer sr-only">
									<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-4 transition-all flex items-center gap-3">
										<div class="w-10 h-10 rounded-lg bg-[#0052cc]/10 flex items-center justify-center flex-shrink-0">
											<span class="material-symbols-outlined text-xl text-[#0052cc]">business</span>
										</div>
										<div>
											<h3 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Empresa', 'guiawp' ); ?></h3>
											<p class="text-[11px] text-slate-500 leading-tight mt-0.5"><?php esc_html_e( 'Para empresas com CNPJ.', 'guiawp' ); ?></p>
										</div>
									</div>
								</label>
								<label class="relative cursor-pointer">
									<input type="radio" name="gcep_tipo_anuncio" value="profissional_liberal" <?php checked( $tipo_anuncio, 'profissional_liberal' ); ?> class="peer sr-only">
									<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-4 transition-all flex items-center gap-3">
										<div class="w-10 h-10 rounded-lg bg-[#0052cc]/10 flex items-center justify-center flex-shrink-0">
											<span class="material-symbols-outlined text-xl text-[#0052cc]">person</span>
										</div>
										<div>
											<h3 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Profissional Liberal', 'guiawp' ); ?></h3>
											<p class="text-[11px] text-slate-500 leading-tight mt-0.5"><?php esc_html_e( 'Para autonomos e freelancers.', 'guiawp' ); ?></p>
										</div>
									</div>
								</label>
							</div>
						</div>

						<!-- Plano -->
						<div>
							<span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block"><?php esc_html_e( 'Plano', 'guiawp' ); ?></span>

							<?php if ( $is_premium ) : ?>
							<!-- Premium: travado na vigencia contratada -->
							<div class="border-2 border-amber-500 bg-amber-50 rounded-xl p-4">
								<div class="flex items-center gap-3">
									<span class="material-symbols-outlined text-xl text-amber-600 flex-shrink-0">workspace_premium</span>
									<div class="flex-1 min-w-0">
										<div class="flex items-center gap-2">
											<h3 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Premium', 'guiawp' ); ?></h3>
											<span class="text-[10px] bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full font-bold uppercase"><?php echo esc_html( GCEP_Helpers::get_status_label( $status_anuncio ) ); ?></span>
										</div>
										<?php if ( $plano_data ) : ?>
										<p class="text-[11px] text-slate-500 leading-tight"><?php echo esc_html( $plano_data['name'] ); ?>
											<?php if ( (int) $vigencia_dias > 0 ) : ?> · <?php echo esc_html( GCEP_Plans::format_duration( (int) $vigencia_dias ) ); ?><?php endif; ?>
											<?php if ( (float) $plano_preco > 0 ) : ?> · R$ <?php echo esc_html( number_format( (float) $plano_preco, 2, ',', '.' ) ); ?><?php endif; ?>
											<?php if ( 'pago' === $status_pagamento ) : ?> · <span class="text-emerald-600 font-bold"><?php esc_html_e( 'Pago', 'guiawp' ); ?></span><?php endif; ?>
										</p>
										<?php else : ?>
										<p class="text-[11px] text-slate-500 leading-tight"><?php esc_html_e( 'Plano premium vinculado a este anuncio.', 'guiawp' ); ?></p>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<p class="text-[11px] text-slate-400 mt-2 flex items-center gap-1">
								<span class="material-symbols-outlined text-xs">lock</span>
								<?php esc_html_e( 'O plano premium esta travado na vigencia contratada. Apos expirar, utilize o sistema de renovacao.', 'guiawp' ); ?>
							</p>

							<?php else : ?>
							<!-- Gratuito: permite upgrade para premium -->
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="gcep-edit-plan-selector">
								<label class="relative cursor-pointer">
									<input type="radio" name="gcep_edit_plano" value="gratis" checked class="peer sr-only">
									<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-4 transition-all flex items-center gap-3">
										<span class="material-symbols-outlined text-xl text-emerald-500">volunteer_activism</span>
										<div>
											<h3 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Manter Gratuito', 'guiawp' ); ?></h3>
											<p class="text-[11px] text-slate-500 leading-tight"><?php esc_html_e( 'Informacoes basicas e contato.', 'guiawp' ); ?></p>
										</div>
									</div>
								</label>
								<label class="relative cursor-pointer">
									<input type="radio" name="gcep_edit_plano" value="premium" class="peer sr-only">
									<div class="border-2 border-slate-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 rounded-xl p-4 transition-all flex items-center gap-3">
										<span class="material-symbols-outlined text-xl text-amber-500">workspace_premium</span>
										<div>
											<h3 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Upgrade Premium', 'guiawp' ); ?></h3>
											<p class="text-[11px] text-slate-500 leading-tight"><?php esc_html_e( 'Galeria, videos, descricao e selo.', 'guiawp' ); ?></p>
										</div>
									</div>
								</label>
							</div>

							<!-- Selecao de vigencia (aparece ao escolher premium) -->
							<div id="gcep-edit-premium-plans" class="hidden mt-5">
								<input type="hidden" name="gcep_plano_id" id="gcep_plano_id" value="0">
								<span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block"><?php esc_html_e( 'Escolha a Vigencia', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
								<?php if ( ! empty( $planos_premium ) ) : ?>
								<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
									<?php foreach ( $planos_premium as $plano_item ) : ?>
									<label class="relative cursor-pointer gcep-plan-option">
										<input type="radio" name="gcep_plano_radio" value="<?php echo esc_attr( $plano_item['id'] ); ?>" class="peer sr-only" data-days="<?php echo esc_attr( $plano_item['days'] ); ?>">
										<div class="border-2 border-slate-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 rounded-lg px-3 py-3 transition-all text-center">
											<h3 class="font-bold text-slate-900 text-xs"><?php echo esc_html( $plano_item['name'] ); ?></h3>
											<p class="text-base font-black text-amber-600 mt-0.5">
												<?php echo esc_html( GCEP_Plans::format_duration( (int) $plano_item['days'] ) ); ?>
											</p>
											<?php if ( (float) $plano_item['price'] > 0 ) : ?>
											<p class="text-xs font-bold text-slate-600">R$ <?php echo esc_html( number_format( (float) $plano_item['price'], 2, ',', '.' ) ); ?></p>
											<?php endif; ?>
										</div>
									</label>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
									<span class="material-symbols-outlined text-xl text-amber-400 mb-1 block">info</span>
									<p class="text-xs text-amber-700 font-medium"><?php esc_html_e( 'Nenhum plano premium disponivel no momento.', 'guiawp' ); ?></p>
								</div>
								<?php endif; ?>
							</div>

							<div id="gcep-edit-upgrade-info" class="hidden mt-3 bg-amber-50 border border-amber-200 rounded-xl p-4">
								<p class="text-xs text-amber-700 font-medium flex items-center gap-2">
									<span class="material-symbols-outlined text-sm">info</span>
									<?php esc_html_e( 'Ao fazer upgrade, o anuncio sera validado e voce sera redirecionado para o pagamento.', 'guiawp' ); ?>
								</p>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</section>
			</div>

			<!-- ==================== STEP 2: Informações ==================== -->
			<div data-step="2" class="hidden">
				<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200">
					<h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
						<span class="material-symbols-outlined text-[#0052cc] text-lg">info</span>
						<?php esc_html_e( 'Informações do Anúncio', 'guiawp' ); ?>
					</h2>
					<p class="text-xs text-slate-500 mb-5"><?php esc_html_e( 'Preencha os dados do seu negócio.', 'guiawp' ); ?></p>

					<div class="grid grid-cols-1 gap-4">
						<label class="flex flex-col gap-1.5">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Título do Anúncio', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
							<input type="text" name="gcep_titulo" required value="<?php echo esc_attr( $post->post_title ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none transition-all" placeholder="<?php esc_attr_e( 'Ex: Agência Digital Pro', 'guiawp' ); ?>">
						</label>

						<!-- CNPJ + Categoria lado a lado no desktop -->
						<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
							<div data-show-tipo="empresa">
								<label class="flex flex-col gap-1.5">
									<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'CNPJ', 'guiawp' ); ?></span>
									<input type="text" name="gcep_cnpj" id="gcep_cnpj" value="<?php echo esc_attr( $meta['GCEP_cnpj'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="00.000.000/0000-00" maxlength="18">
								</label>
							</div>

							<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Categoria', 'guiawp' ); ?></span>
								<select name="gcep_categoria[]" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none">
									<option value=""><?php esc_html_e( 'Selecione uma categoria', 'guiawp' ); ?></option>
									<?php foreach ( $categorias as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( $cat->term_id, $post_cats, true ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<?php endif; ?>
						</div>

						<!-- Descrição Curta (somente grátis) -->
						<div data-gratis-only class="<?php echo $is_premium ? 'hidden' : ''; ?>">
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Descrição Curta', 'guiawp' ); ?></span>
								<textarea name="gcep_descricao_curta" rows="3" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Uma breve frase que resume seu serviço...', 'guiawp' ); ?>"><?php echo esc_textarea( $meta['GCEP_descricao_curta'] ); ?></textarea>
							</label>
						</div>

						<!-- Descrição Longa (somente premium) -->
						<div data-premium-only class="<?php echo $is_premium ? '' : 'hidden'; ?>">
							<div class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700 flex items-center gap-2">
									<?php esc_html_e( 'Descrição Detalhada', 'guiawp' ); ?>
									<span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">PREMIUM</span>
								</span>
								<p class="text-xs text-slate-400 -mt-0.5"><?php esc_html_e( 'Esta descrição substitui a curta no seu perfil público.', 'guiawp' ); ?></p>
								<?php
								$editor_name        = 'gcep_descricao_longa';
								$editor_value       = (string) $meta['GCEP_descricao_longa'];
								$editor_placeholder = __( 'Conte mais sobre sua empresa, serviços, diferenciais e para quem você atende...', 'guiawp' );
								include GCEP_PLUGIN_DIR . 'templates/partials/descricao-rich-editor.php';
								?>
							</div>
						</div>
					</div>
				</section>
			</div>

			<!-- ==================== STEP 3: Contato ==================== -->
			<div data-step="3" class="hidden">
				<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200">
					<h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
						<span class="material-symbols-outlined text-[#0052cc] text-lg">call</span>
						<?php esc_html_e( 'Contato', 'guiawp' ); ?>
					</h2>
					<p class="text-xs text-slate-500 mb-4"><?php esc_html_e( 'Como os clientes vão te encontrar.', 'guiawp' ); ?></p>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<label class="flex flex-col gap-1.5">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Telefone', 'guiawp' ); ?></span>
							<input type="tel" name="gcep_telefone" data-intl-tel value="<?php echo esc_attr( GCEP_Helpers::format_phone( (string) $meta['GCEP_telefone'] ) ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="(00) 9 9999-9999">
						</label>
						<label class="flex flex-col gap-1.5">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'WhatsApp', 'guiawp' ); ?></span>
							<input type="tel" name="gcep_whatsapp" data-intl-tel value="<?php echo esc_attr( GCEP_Helpers::format_phone( (string) $meta['GCEP_whatsapp'] ) ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="(00) 9 9999-9999">
						</label>
						<label class="flex flex-col gap-1.5">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'E-mail', 'guiawp' ); ?></span>
							<input type="email" name="gcep_email" value="<?php echo esc_attr( $meta['GCEP_email'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="contato@empresa.com">
						</label>
						<label class="flex flex-col gap-1.5">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Site', 'guiawp' ); ?></span>
							<input type="url" name="gcep_site" value="<?php echo esc_attr( $meta['GCEP_site'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="https://seusite.com.br">
						</label>
					</div>
				</section>
			</div>

			<!-- ==================== STEP 4: Endereço ==================== -->
			<div data-step="4" class="hidden">
				<!-- Endereço com ViaCEP -->
					<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200">
						<h3 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
							<span class="material-symbols-outlined text-[#0052cc] text-lg">location_on</span>
							<?php esc_html_e( 'Endereço', 'guiawp' ); ?>
						</h3>
						<p class="text-xs text-slate-500 mb-4"><?php esc_html_e( 'Digite o CEP para preenchimento automático.', 'guiawp' ); ?></p>
						<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'CEP', 'guiawp' ); ?></span>
								<input type="text" name="gcep_cep" id="gcep_cep" value="<?php echo esc_attr( $meta['GCEP_cep'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="00000-000" maxlength="9">
								<span id="gcep-cep-feedback" class="text-xs mt-0.5 font-medium"></span>
							</label>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Logradouro', 'guiawp' ); ?></span>
								<input type="text" name="gcep_logradouro" id="gcep_logradouro" value="<?php echo esc_attr( $meta['GCEP_logradouro'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Rua, Avenida...', 'guiawp' ); ?>">
							</label>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Número', 'guiawp' ); ?></span>
								<input type="text" name="gcep_numero" id="gcep_numero" value="<?php echo esc_attr( $meta['GCEP_numero'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="123">
							</label>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Complemento', 'guiawp' ); ?></span>
								<input type="text" name="gcep_complemento" value="<?php echo esc_attr( $meta['GCEP_complemento'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Sala, Andar...', 'guiawp' ); ?>">
							</label>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Bairro', 'guiawp' ); ?></span>
								<input type="text" name="gcep_bairro" id="gcep_bairro" value="<?php echo esc_attr( $meta['GCEP_bairro'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none">
							</label>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Cidade', 'guiawp' ); ?></span>
								<input type="text" name="gcep_cidade" id="gcep_cidade" value="<?php echo esc_attr( $meta['GCEP_cidade'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" readonly>
							</label>
							<label class="flex flex-col gap-1.5">
								<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Estado (UF)', 'guiawp' ); ?></span>
								<input type="text" name="gcep_estado" id="gcep_estado" value="<?php echo esc_attr( $meta['GCEP_estado'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" maxlength="2" readonly>
							</label>
						</div>

						<!-- Mapa -->
						<div class="mt-4">
							<h4 class="text-sm font-semibold text-slate-700 mb-1.5 flex items-center gap-2">
								<span class="material-symbols-outlined text-[#0052cc] text-base">my_location</span>
								<?php esc_html_e( 'Localização no Mapa', 'guiawp' ); ?>
							</h4>
							<p class="text-xs text-slate-400 mb-2"><?php esc_html_e( 'Ajuste o pin arrastando-o ou clicando no mapa para definir a posição exata.', 'guiawp' ); ?></p>
							<div id="gcep-address-map" class="<?php echo ( ! empty( $meta['GCEP_latitude'] ) && ! empty( $meta['GCEP_longitude'] ) ) ? '' : 'hidden'; ?> rounded-xl border border-slate-200 overflow-hidden z-0" style="height:300px"></div>
							<input type="hidden" name="gcep_latitude" id="gcep_latitude" value="<?php echo esc_attr( $meta['GCEP_latitude'] ?? '' ); ?>">
							<input type="hidden" name="gcep_longitude" id="gcep_longitude" value="<?php echo esc_attr( $meta['GCEP_longitude'] ?? '' ); ?>">
						</div>
					</section>
			</div>

			<!-- ==================== STEP 5: Redes Sociais ==================== -->
			<div data-step="5" class="hidden">
				<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200">
					<h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
						<span class="material-symbols-outlined text-[#0052cc] text-lg">share</span>
						<?php esc_html_e( 'Redes Sociais', 'guiawp' ); ?>
					</h2>
					<p class="text-xs text-slate-500 mb-5"><?php esc_html_e( 'Conecte seus perfis para que seus clientes te encontrem nas redes.', 'guiawp' ); ?></p>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php
						require_once GCEP_PLUGIN_DIR . 'templates/partials/social-icons.php';
						$svg_icons = gcep_social_svg_icons( 'w-4 h-4' );
						$redes = [
							[ 'gcep_instagram', 'Instagram', 'https://instagram.com/...', 'instagram', 'GCEP_instagram' ],
							[ 'gcep_facebook',  'Facebook',  'https://facebook.com/...',  'facebook',  'GCEP_facebook' ],
							[ 'gcep_linkedin',  'LinkedIn',  'https://linkedin.com/...',  'linkedin',  'GCEP_linkedin' ],
							[ 'gcep_youtube',   'YouTube',   'https://youtube.com/...',   'youtube',   'GCEP_youtube' ],
							[ 'gcep_x_twitter', 'X (Twitter)', 'https://x.com/...',       'x_twitter', 'GCEP_x_twitter' ],
							[ 'gcep_tiktok',    'TikTok',    'https://tiktok.com/@...',   'tiktok',    'GCEP_tiktok' ],
							[ 'gcep_threads',   'Threads',   'https://threads.net/@...',  'threads',   'GCEP_threads' ],
						];
						foreach ( $redes as $rede ) :
						?>
						<label class="flex flex-col gap-1.5">
							<span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
								<span class="text-slate-400"><?php echo $svg_icons[ $rede[3] ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php echo esc_html( $rede[1] ); ?>
							</span>
							<input type="url" name="<?php echo esc_attr( $rede[0] ); ?>" value="<?php echo esc_attr( $meta[ $rede[4] ] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php echo esc_attr( $rede[2] ); ?>">
						</label>
						<?php endforeach; ?>
					</div>
					<p class="text-xs text-slate-400 mt-4 flex items-center gap-1.5">
						<span class="material-symbols-outlined text-sm">info</span>
						<?php esc_html_e( 'Todos os campos são opcionais. Preencha apenas as redes que você utiliza.', 'guiawp' ); ?>
					</p>
				</section>
			</div>

			<!-- ==================== STEP 6: Mídia ==================== -->
			<div data-step="6" class="hidden">
				<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
					<div>
						<h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
							<span class="material-symbols-outlined text-[#0052cc] text-lg">image</span>
							<?php esc_html_e( 'Mídia', 'guiawp' ); ?>
						</h2>
						<p class="text-xs text-slate-500"><?php esc_html_e( 'Adicione imagens para deixar seu anúncio mais atrativo.', 'guiawp' ); ?></p>
					</div>

					<!-- Logo / Foto Principal -->
					<div class="gcep-crop-wrapper">
						<span class="text-sm font-semibold text-slate-700 mb-2 block"><?php esc_html_e( 'Logo / Foto Principal', 'guiawp' ); ?></span>
						<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Formato quadrado 500x500px. A imagem será recortada automaticamente.', 'guiawp' ); ?></p>

						<?php if ( $meta['GCEP_logo_ou_foto_principal'] ) : ?>
						<div class="mb-3 flex items-center gap-3">
							<?php echo wp_get_attachment_image( $meta['GCEP_logo_ou_foto_principal'], 'thumbnail', false, [ 'class' => 'w-16 h-16 rounded-xl object-cover border-2 border-slate-200' ] ); ?>
							<span class="text-xs text-slate-500"><?php esc_html_e( 'Imagem atual. Envie uma nova para substituir.', 'guiawp' ); ?></span>
						</div>
						<?php endif; ?>

						<div class="gcep-crop-result-wrap hidden mb-3">
							<img src="" alt="Preview" class="gcep-crop-result w-20 h-20 sm:w-24 sm:h-24 rounded-xl object-cover border-2 border-slate-200">
							<p class="text-xs text-emerald-600 font-medium mt-1.5"><?php esc_html_e( 'Imagem recortada com sucesso!', 'guiawp' ); ?></p>
						</div>

						<label class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-5 bg-slate-50 hover:bg-[#0052cc]/5 transition-colors cursor-pointer">
							<span class="material-symbols-outlined text-2xl text-slate-400 mb-1.5">add_a_photo</span>
							<p class="text-sm text-slate-500 text-center"><?php esc_html_e( 'Toque para tirar foto ou escolher da galeria', 'guiawp' ); ?></p>
							<p class="text-xs text-slate-400 mt-0.5">PNG, JPG, WebP</p>
							<input type="file" name="gcep_logo" id="gcep_logo_input" accept="image/*" capture="environment" class="hidden">
						</label>

						<div class="gcep-crop-area hidden mt-4">
							<div class="gcep-crop-preview mx-auto overflow-hidden rounded-lg bg-slate-100" style="max-width:100%;"></div>
							<div class="flex gap-3 mt-3 justify-center">
								<button type="button" class="gcep-crop-confirm px-5 py-2 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-lg font-bold text-sm transition-all flex items-center gap-2">
									<span class="material-symbols-outlined text-base">crop</span>
									<?php esc_html_e( 'Recortar', 'guiawp' ); ?>
								</button>
								<button type="button" class="gcep-crop-cancel px-5 py-2 border border-slate-200 text-slate-600 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-all">
									<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
								</button>
							</div>
						</div>
					</div>

					<!-- Foto de Capa -->
					<div class="gcep-crop-wrapper">
						<span class="text-sm font-semibold text-slate-700 mb-2 block"><?php esc_html_e( 'Foto de Capa', 'guiawp' ); ?></span>
						<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Formato panorâmico 1400x400px. A imagem será recortada automaticamente.', 'guiawp' ); ?></p>

						<?php if ( $meta['GCEP_foto_capa'] ) : ?>
						<div class="mb-3">
							<?php echo wp_get_attachment_image( $meta['GCEP_foto_capa'], 'medium', false, [ 'class' => 'w-full max-w-md h-16 rounded-xl object-cover border-2 border-slate-200' ] ); ?>
							<span class="text-xs text-slate-500 mt-1 block"><?php esc_html_e( 'Capa atual. Envie uma nova para substituir.', 'guiawp' ); ?></span>
						</div>
						<?php endif; ?>

						<div class="gcep-crop-result-wrap hidden mb-3">
							<img src="" alt="Preview" class="gcep-crop-result w-full max-w-md h-16 sm:h-20 rounded-xl object-cover border-2 border-slate-200">
							<p class="text-xs text-emerald-600 font-medium mt-1.5"><?php esc_html_e( 'Capa recortada com sucesso!', 'guiawp' ); ?></p>
						</div>

						<label class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-5 bg-slate-50 hover:bg-[#0052cc]/5 transition-colors cursor-pointer">
							<span class="material-symbols-outlined text-2xl text-slate-400 mb-1.5">panorama</span>
							<p class="text-sm text-slate-500 text-center"><?php esc_html_e( 'Toque para tirar foto ou escolher da galeria', 'guiawp' ); ?></p>
							<p class="text-xs text-slate-400 mt-0.5">PNG, JPG, WebP</p>
							<input type="file" name="gcep_capa" id="gcep_capa_input" accept="image/*" capture="environment" class="hidden">
						</label>

						<div class="gcep-crop-area hidden mt-4">
							<div class="gcep-crop-preview mx-auto overflow-hidden rounded-lg bg-slate-100" style="max-width:100%;"></div>
							<div class="flex gap-3 mt-3 justify-center">
								<button type="button" class="gcep-crop-confirm px-5 py-2 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-lg font-bold text-sm transition-all flex items-center gap-2">
									<span class="material-symbols-outlined text-base">crop</span>
									<?php esc_html_e( 'Recortar', 'guiawp' ); ?>
								</button>
								<button type="button" class="gcep-crop-cancel px-5 py-2 border border-slate-200 text-slate-600 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-all">
									<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
								</button>
							</div>
						</div>
					</div>
				</section>
			</div>

			<!-- ==================== STEP 7: Galeria (Premium) ==================== -->
			<div data-step="7" class="hidden">
				<section class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-slate-200 space-y-6">
					<div>
						<h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
							<span class="material-symbols-outlined text-[#0052cc] text-lg">photo_library</span>
							<?php esc_html_e( 'Galeria', 'guiawp' ); ?>
							<span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">PREMIUM</span>
						</h2>
						<p class="text-xs text-slate-500"><?php esc_html_e( 'Fotos e vídeos extras para destacar seu anúncio.', 'guiawp' ); ?></p>
					</div>

					<!-- Galeria de Fotos (premium) -->
					<div data-premium-only class="<?php echo $is_premium ? '' : 'hidden'; ?>" id="gcep-gallery-section">
						<span class="text-sm font-semibold text-slate-700 mb-2 block flex items-center gap-2">
							<?php esc_html_e( 'Galeria de Fotos', 'guiawp' ); ?>
							<span id="gcep-gallery-count" class="text-xs text-slate-400 ml-auto"><?php echo count( $galeria_ids ); ?> / 20</span>
						</span>
						<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Até 20 fotos. JPG, PNG, GIF, WebP, AVIF ou HEIC. Máx 15 MB cada.', 'guiawp' ); ?></p>

						<div id="gcep-gallery-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-3">
							<?php foreach ( $galeria_ids as $gid ) :
								$thumb = wp_get_attachment_image_url( (int) $gid, 'thumbnail' );
								if ( ! $thumb ) continue;
							?>
							<div class="gcep-gallery-item relative group" data-id="<?php echo esc_attr( $gid ); ?>">
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="w-full aspect-square object-cover rounded-xl border border-slate-200">
								<button type="button" class="gcep-gallery-remove absolute top-1 right-1 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>">
									<span class="material-symbols-outlined text-sm">close</span>
								</button>
							</div>
							<?php endforeach; ?>
						</div>

						<label id="gcep-gallery-dropzone" class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-5 bg-slate-50 hover:bg-[#0052cc]/5 transition-colors cursor-pointer">
							<span class="material-symbols-outlined text-2xl text-slate-400 mb-1.5">photo_library</span>
							<p class="text-sm text-slate-500 text-center"><?php esc_html_e( 'Toque para selecionar fotos', 'guiawp' ); ?></p>
							<p class="text-xs text-slate-400 mt-0.5">JPG, PNG, GIF, WebP, AVIF, HEIC</p>
							<input type="file" id="gcep-gallery-input" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/heic,image/heif" multiple class="hidden">
						</label>

						<div id="gcep-gallery-error" class="text-xs text-rose-500 font-medium mt-2 hidden"></div>
					</div>

					<!-- Galeria de Vídeos (premium) -->
					<div data-premium-only class="<?php echo $is_premium ? '' : 'hidden'; ?>">
						<span class="text-sm font-semibold text-slate-700 mb-2 block flex items-center gap-2">
							<?php esc_html_e( 'Galeria de Vídeos', 'guiawp' ); ?>
						</span>
						<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Até 10 vídeos do YouTube ou Vimeo.', 'guiawp' ); ?></p>
						<div id="gcep-videos-container" class="space-y-3 mb-3">
							<?php foreach ( $videos as $video ) : ?>
							<div class="gcep-video-item flex flex-col sm:flex-row gap-3 items-start bg-slate-50 p-3 rounded-xl border border-slate-200">
								<div class="flex-1 w-full space-y-2">
									<input type="text" name="gcep_video_titulo[]" value="<?php echo esc_attr( $video['titulo'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Título do vídeo', 'guiawp' ); ?>" class="w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#0052cc]/20 outline-none">
									<input type="url" name="gcep_video_url[]" value="<?php echo esc_attr( $video['url'] ?? '' ); ?>" placeholder="https://youtube.com/watch?v=..." class="w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#0052cc]/20 outline-none">
								</div>
								<button type="button" class="gcep-remove-video p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>">
									<span class="material-symbols-outlined text-lg">delete</span>
								</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" id="gcep-add-video" class="w-full py-2.5 border-2 border-dashed border-slate-200 rounded-xl text-slate-500 font-semibold text-sm hover:border-[#0052cc] hover:text-[#0052cc] transition-all flex items-center justify-center gap-2">
							<span class="material-symbols-outlined text-base">add</span>
							<?php esc_html_e( 'Adicionar Vídeo', 'guiawp' ); ?>
						</button>
					</div>

					<!-- Aviso para plano grátis -->
					<?php if ( ! $is_premium ) : ?>
					<div>
						<div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-center">
							<span class="material-symbols-outlined text-2xl text-amber-400 mb-2 block">lock</span>
							<p class="text-sm font-semibold text-amber-800 mb-1"><?php esc_html_e( 'Recurso Premium', 'guiawp' ); ?></p>
							<p class="text-xs text-amber-600"><?php esc_html_e( 'Galeria de fotos e vídeos disponível apenas no plano premium.', 'guiawp' ); ?></p>
						</div>
					</div>
					<?php endif; ?>
				</section>
			</div>

			</form>

		<!-- Overlay de resultado da validacao IA -->
		<div id="gcep-result-overlay" class="hidden">
			<div id="gcep-result-loading" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-8 sm:p-10 text-center">
				<div class="w-14 h-14 border-4 border-slate-200 border-t-[#0052cc] rounded-full animate-spin mx-auto mb-5"></div>
				<h3 class="text-lg font-bold text-slate-900 mb-1"><?php esc_html_e( 'Validando alteracoes...', 'guiawp' ); ?></h3>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'Estamos analisando as informacoes. Aguarde um momento.', 'guiawp' ); ?></p>
			</div>

			<div id="gcep-result-approved" class="hidden bg-white rounded-2xl border border-emerald-200 shadow-sm p-8 sm:p-10 text-center">
				<div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-5">
					<span class="material-symbols-outlined text-3xl text-emerald-600">check_circle</span>
				</div>
				<h3 class="text-xl font-bold text-slate-900 mb-1" id="gcep-result-approved-title"></h3>
				<p class="text-sm text-slate-500 mb-6" id="gcep-result-approved-msg"></p>
				<a id="gcep-result-approved-btn" href="#" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold shadow-lg shadow-emerald-500/20 transition-all text-sm">
					<span class="material-symbols-outlined text-base">arrow_forward</span>
					<span id="gcep-result-approved-btn-label"><?php esc_html_e( 'Continuar', 'guiawp' ); ?></span>
				</a>
			</div>

			<div id="gcep-result-rejected" class="hidden bg-white rounded-2xl border border-rose-200 shadow-sm p-8 sm:p-10">
				<div class="text-center mb-6">
					<div class="w-16 h-16 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-5">
						<span class="material-symbols-outlined text-3xl text-rose-600">cancel</span>
					</div>
					<h3 class="text-xl font-bold text-slate-900 mb-1"><?php esc_html_e( 'Alterações não salvas', 'guiawp' ); ?></h3>
					<p class="text-sm text-slate-500"><?php esc_html_e( 'A validação automática encontrou problemas.', 'guiawp' ); ?></p>
				</div>

				<div class="flex flex-col sm:flex-row gap-3 justify-center">
					<button type="button" id="gcep-result-fix-btn" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-[#0052cc] hover:bg-[#003d99] text-white font-bold shadow-lg shadow-[#0052cc]/20 transition-all text-sm">
						<span class="material-symbols-outlined text-base">edit</span>
						<?php esc_html_e( 'Corrigir Anúncio', 'guiawp' ); ?>
					</button>
					<a href="<?php echo esc_url( $anuncios_list_url ); ?>" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-100 transition-all text-sm">
						<span class="material-symbols-outlined text-base">arrow_back</span>
						<?php esc_html_e( 'Ir para Meus Anúncios', 'guiawp' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<!-- Navegação Sticky Bottom (dentro da main, fora do container max-w) -->
	<div id="gcep-nav-bottom" class="sticky bottom-0 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 sm:px-6 z-20" style="padding-top:12px;padding-bottom:max(12px,env(safe-area-inset-bottom))">
		<div class="max-w-[900px] mx-auto flex justify-between items-center">
			<button type="button" id="gcep-step-prev" class="invisible px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-100 transition-all flex items-center gap-2 text-sm">
				<span class="material-symbols-outlined text-base">arrow_back</span>
				<?php esc_html_e( 'Voltar', 'guiawp' ); ?>
			</button>

			<button type="button" id="gcep-step-next" class="px-6 py-2.5 rounded-xl bg-[#0052cc] hover:bg-[#003d99] text-white font-bold shadow-lg shadow-[#0052cc]/20 transition-all flex items-center gap-2 text-sm">
				<?php esc_html_e( 'Próximo', 'guiawp' ); ?>
				<span class="material-symbols-outlined text-base">arrow_forward</span>
			</button>

			<button type="button" id="gcep-step-submit" class="hidden px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2 text-sm">
				<span class="material-symbols-outlined text-base" id="gcep-submit-icon">check_circle</span>
				<span id="gcep-submit-label"><?php esc_html_e( 'Salvar Alterações', 'guiawp' ); ?></span>
			</button>
		</div>
	</div>

	<script>
	(function () {
		var rejected = document.getElementById('gcep-result-rejected');
		var postId = <?php echo (int) $anuncio_id; ?>;
		var fallbackReason = <?php echo wp_json_encode( $saved_ai_reason ?: __( 'A validação automática reprovou o anúncio, mas não retornou uma justificativa detalhada. Revise título, descrição e dados de contato antes de tentar novamente.', 'guiawp' ) ); ?>;

		if (!rejected) return;

		function ensureReasonBox() {
			var box = document.getElementById('gcep-inline-reason-box');
			if (box) return box;
			box = document.createElement('div');
			box.id = 'gcep-inline-reason-box';
			box.style.cssText = 'display:block;background:#fff1f2;border:1px solid #fecdd3;border-radius:12px;padding:16px;margin-bottom:20px;text-align:left;';
			var inner = document.createElement('div');
			inner.style.cssText = 'display:flex;align-items:flex-start;gap:10px;';
			var icon = document.createElement('span');
			icon.className = 'material-symbols-outlined';
			icon.style.cssText = 'color:#f43f5e;margin-top:2px;flex-shrink:0;font-size:18px;';
			icon.textContent = 'report';
			var wrap = document.createElement('div');
			wrap.style.cssText = 'flex:1;min-width:0;';
			var title = document.createElement('strong');
			title.style.cssText = 'display:block;color:#9f1239;font-size:13px;margin-bottom:4px;';
			title.textContent = <?php echo wp_json_encode( __( 'Motivo da rejeição:', 'guiawp' ) ); ?>;
			var text = document.createElement('p');
			text.id = 'gcep-inline-reason-text';
			text.style.cssText = 'color:#be123c;font-size:13px;line-height:1.5;margin:0;white-space:pre-wrap;word-break:break-word;';
			text.textContent = '...';
			wrap.appendChild(title);
			wrap.appendChild(text);
			inner.appendChild(icon);
			inner.appendChild(wrap);
			box.appendChild(inner);
			var buttons = rejected.querySelector('.flex.flex-col');
			if (buttons) { rejected.insertBefore(box, buttons); } else { rejected.appendChild(box); }
			return box;
		}

		function renderReason(reason) {
			var text = String(reason || '').trim() || fallbackReason;
			ensureReasonBox();
			var el = document.getElementById('gcep-inline-reason-text');
			if (el) el.textContent = text;
		}

		function fetchReason() {
			var form = document.getElementById('gcep-multistep-form');
			var nonceField = form ? form.querySelector('input[name="gcep_anuncio_nonce"]') : null;
			if (!window.gcepData || !gcepData.ajaxUrl || !nonceField || !postId) { renderReason(''); return; }
			var fd = new FormData();
			fd.append('action', 'gcep_get_validation_reason');
			fd.append('post_id', String(postId));
			fd.append('gcep_anuncio_nonce', nonceField.value);
			fetch(gcepData.ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (res) { renderReason(res && res.success && res.data ? res.data.justificativa || '' : ''); })
				.catch(function () { renderReason(''); });
		}

		function syncRejectedState() {
			if (rejected.classList.contains('hidden')) return;
			renderReason('');
			fetchReason();
		}

		new MutationObserver(syncRejectedState).observe(rejected, { attributes: true, attributeFilter: ['class'] });
		syncRejectedState();

	}());
	</script>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-footer.php'; ?>
