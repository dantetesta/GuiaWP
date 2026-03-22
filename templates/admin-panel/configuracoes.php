<?php
/**
 * Template: Admin - Configurações
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.2.0 - 2026-03-11 - Upload de logotipo do guia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$settings = GCEP_Settings::get_all();
$gateway_ativo = $settings['gateway_ativo'] ?? '';
$captcha_provider = $settings['auth_captcha_provider'] ?? 'none';
$msg      = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="lg:ml-64 flex-1 p-4 lg:p-8 min-w-0 overflow-x-hidden">
	<header class="mb-8">
		<h2 class="text-3xl font-black tracking-tight mb-2"><?php esc_html_e( 'Configurações', 'guiawp' ); ?></h2>
		<p class="text-slate-500"><?php esc_html_e( 'Gerencie os dados gerais da plataforma.', 'guiawp' ); ?></p>
	</header>

	<?php if ( $msg ) : ?>
	<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="space-y-8 max-w-[1040px]">
		<input type="hidden" name="action" value="gcep_admin_save_settings">
		<?php wp_nonce_field( 'gcep_save_settings', 'gcep_nonce' ); ?>

		<section class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
			<div class="space-y-3">
					<div class="w-full overflow-x-auto pb-1">
						<div class="inline-flex min-w-max flex-nowrap gap-2">
						<button type="button" data-settings-tab="plataforma" class="gcep-settings-tab shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-colors bg-slate-900 text-white shadow-sm">
						<span class="material-symbols-outlined text-[18px]">dashboard_customize</span>
						<?php esc_html_e( 'Plataforma', 'guiawp' ); ?>
					</button>
						<button type="button" data-settings-tab="aparencia" class="gcep-settings-tab shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-colors text-slate-500 hover:bg-slate-50 hover:text-slate-800">
						<span class="material-symbols-outlined text-[18px]">palette</span>
						<?php esc_html_e( 'Aparência', 'guiawp' ); ?>
					</button>
						<button type="button" data-settings-tab="seguranca" class="gcep-settings-tab shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-colors text-slate-500 hover:bg-slate-50 hover:text-slate-800">
						<span class="material-symbols-outlined text-[18px]">shield</span>
						<?php esc_html_e( 'Segurança', 'guiawp' ); ?>
					</button>
						<button type="button" data-settings-tab="pagamento" class="gcep-settings-tab shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-colors text-slate-500 hover:bg-slate-50 hover:text-slate-800">
						<span class="material-symbols-outlined text-[18px]">payments</span>
						<?php esc_html_e( 'Pagamento', 'guiawp' ); ?>
					</button>
						<button type="button" data-settings-tab="ia" class="gcep-settings-tab shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-colors text-slate-500 hover:bg-slate-50 hover:text-slate-800">
						<span class="material-symbols-outlined text-[18px]">smart_toy</span>
						<?php esc_html_e( 'IA', 'guiawp' ); ?>
					</button>
						<button type="button" data-settings-tab="monetizacao" class="gcep-settings-tab shrink-0 whitespace-nowrap inline-flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-colors text-slate-500 hover:bg-slate-50 hover:text-slate-800">
						<span class="material-symbols-outlined text-[18px]">monetization_on</span>
						<?php esc_html_e( 'Monetização', 'guiawp' ); ?>
					</button>
				</div>
				</div>
				<p class="text-xs text-slate-400 px-1"><?php esc_html_e( 'As alterações de todas as tabs são salvas juntas.', 'guiawp' ); ?></p>
			</div>
		</section>

		<!-- Identidade -->
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm" data-settings-panel="plataforma">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">badge</span>
				<?php esc_html_e( 'Identidade do Guia', 'guiawp' ); ?>
			</h3>

			<!-- Logotipo -->
			<div class="mb-6">
				<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Logotipo do Guia', 'guiawp' ); ?></span>
				<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Se informado, substitui o nome do guia no menu e rodapé.', 'guiawp' ); ?></p>

				<div class="flex flex-wrap items-start gap-6">
					<div class="flex-1 min-w-[200px]">
						<label for="gcep-logo-input" class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-5 bg-slate-50 hover:bg-[#0052cc]/5 transition-colors cursor-pointer">
							<span class="material-symbols-outlined text-2xl text-slate-400 mb-1">cloud_upload</span>
							<p class="text-xs text-slate-500"><?php esc_html_e( 'Clique para enviar', 'guiawp' ); ?></p>
							<p class="text-[10px] text-slate-400 mt-1">PNG, JPG, SVG, WebP</p>
						</label>
						<input type="file" name="gcep_logo_guia" id="gcep-logo-input" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="sr-only">
						<p id="gcep-logo-filename" class="text-xs text-slate-500 mt-2 hidden"></p>
					</div>

					<div id="gcep-logo-existing-wrap" class="flex flex-col items-center gap-3 <?php echo empty( $settings['logo_url'] ) ? 'hidden' : ''; ?>">
						<img id="gcep-logo-existing" src="<?php echo esc_url( $settings['logo_url'] ?? '' ); ?>" alt="Logo" class="max-w-[200px] max-h-[80px] object-contain rounded-lg border border-slate-200 bg-white p-2">
						<label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer">
							<input type="checkbox" name="gcep_remove_logo" value="1" class="rounded border-slate-300 text-rose-500 focus:ring-rose-500/20">
							<?php esc_html_e( 'Remover logo', 'guiawp' ); ?>
						</label>
					</div>
				</div>
			</div>

			<!-- Largura do logotipo no menu -->
			<?php
			$logo_largura = intval( $settings['logo_largura'] ?? 150 );
			if ( $logo_largura < 60 ) $logo_largura = 60;
			if ( $logo_largura > 300 ) $logo_largura = 300;
			?>
			<div class="mb-6">
				<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Largura do Logo no Menu', 'guiawp' ); ?></span>
				<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Define a largura em pixels do logo no header. A altura se ajusta proporcionalmente.', 'guiawp' ); ?></p>

				<div class="flex items-center gap-4 mb-4">
					<input type="range" id="gcep-logo-range" name="logo_largura" min="60" max="300" step="5" value="<?php echo esc_attr( $logo_largura ); ?>" class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#0052cc]">
					<div class="flex items-center gap-1">
						<input type="number" id="gcep-logo-number" min="60" max="300" step="5" value="<?php echo esc_attr( $logo_largura ); ?>" class="w-20 text-center text-sm font-semibold border-slate-200 rounded-lg bg-slate-50 p-2 focus:ring-2 focus:ring-primary/20 outline-none">
						<span class="text-xs text-slate-400">px</span>
					</div>
				</div>

				<!-- Preview do menu (sempre renderiza, oculto se sem logo) -->
				<div id="gcep-logo-preview-wrap" class="border border-slate-200 rounded-xl overflow-hidden <?php echo empty( $settings['logo_url'] ) ? 'hidden' : ''; ?>">
					<div class="bg-white px-4 py-3 flex items-center gap-3 border-b border-slate-100">
						<span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?php esc_html_e( 'Preview do Menu', 'guiawp' ); ?></span>
					</div>
					<div class="bg-white px-6 py-4 flex items-center justify-between">
						<img id="gcep-logo-preview" src="<?php echo esc_url( $settings['logo_url'] ?? '' ); ?>" alt="Preview" style="width: <?php echo esc_attr( $logo_largura ); ?>px;" class="object-contain">
						<div class="flex items-center gap-4">
							<span class="text-xs text-slate-400"><?php esc_html_e( 'Explorar', 'guiawp' ); ?></span>
							<span class="text-xs text-slate-400"><?php esc_html_e( 'Categorias', 'guiawp' ); ?></span>
							<span class="text-xs bg-[#0052cc] text-white px-3 py-1 rounded"><?php esc_html_e( 'Meu Painel', 'guiawp' ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<script>
			(function(){
				var range = document.getElementById('gcep-logo-range');
				var number = document.getElementById('gcep-logo-number');
				var preview = document.getElementById('gcep-logo-preview');
				var fileInput = document.getElementById('gcep-logo-input');
				var fileLabel = document.getElementById('gcep-logo-filename');
				var existingImg = document.getElementById('gcep-logo-existing');
				var previewWrap = document.getElementById('gcep-logo-preview-wrap');

				if (range && number) {
					range.addEventListener('input', function() {
						number.value = this.value;
						if (preview) preview.style.width = this.value + 'px';
					});
					number.addEventListener('input', function() {
						var v = Math.min(300, Math.max(60, parseInt(this.value) || 60));
						range.value = v;
						if (preview) preview.style.width = v + 'px';
					});
				}

				if (fileInput) {
					fileInput.addEventListener('change', function() {
						if (!this.files || !this.files[0]) return;
						var file = this.files[0];

						// Mostrar nome do arquivo
						if (fileLabel) {
							fileLabel.textContent = file.name;
							fileLabel.classList.remove('hidden');
						}

						// Preview em tempo real via FileReader
						var reader = new FileReader();
						reader.onload = function(e) {
							var existingWrap = document.getElementById('gcep-logo-existing-wrap');

							// Mostrar e atualizar imagem existente
							if (existingImg) {
								existingImg.src = e.target.result;
							}
							if (existingWrap) {
								existingWrap.classList.remove('hidden');
							}

							// Mostrar e atualizar preview do menu
							if (preview) {
								preview.src = e.target.result;
							}
							if (previewWrap) {
								previewWrap.classList.remove('hidden');
							}
						};
						reader.readAsDataURL(file);
					});
				}
			})();
			</script>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nome do Guia', 'guiawp' ); ?></span>
					<input type="text" name="nome_guia" value="<?php echo esc_attr( $settings['nome_guia'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'E-mail Principal', 'guiawp' ); ?></span>
					<input type="email" name="email_principal" value="<?php echo esc_attr( $settings['email_principal'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Telefone Principal', 'guiawp' ); ?></span>
					<input type="tel" name="telefone_principal" data-intl-tel value="<?php echo esc_attr( GCEP_Helpers::format_phone( (string) $settings['telefone_principal'] ) ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="(00) 9 9999-9999 ou (00) 9999-9999">
				</label>
			</div>

			<div class="mt-8 pt-8 border-t border-slate-100">
				<div class="mb-5">
					<h4 class="text-lg font-bold text-slate-900"><?php esc_html_e( 'Canais do Rodapé', 'guiawp' ); ?></h4>
					<p class="text-sm text-slate-500 mt-1"><?php esc_html_e( 'Esses links aparecem no rodapé público do guia com ícones reais de contato e redes sociais.', 'guiawp' ); ?></p>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'WhatsApp Principal', 'guiawp' ); ?></span>
						<input type="tel" name="whatsapp_principal" data-intl-tel value="<?php echo esc_attr( GCEP_Helpers::format_phone( (string) $settings['whatsapp_principal'] ) ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="(00) 9 9999-9999 ou (00) 9999-9999">
						<span class="text-xs text-slate-400"><?php esc_html_e( 'Será convertido automaticamente para link wa.me/55.', 'guiawp' ); ?></span>
					</label>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Instagram', 'guiawp' ); ?></span>
						<input type="url" name="instagram_url" value="<?php echo esc_attr( $settings['instagram_url'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="https://instagram.com/seuperfil">
					</label>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Facebook', 'guiawp' ); ?></span>
						<input type="url" name="facebook_url" value="<?php echo esc_attr( $settings['facebook_url'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="https://facebook.com/suapagina">
					</label>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'X', 'guiawp' ); ?></span>
						<input type="url" name="x_url" value="<?php echo esc_attr( $settings['x_url'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="https://x.com/seuperfil">
					</label>
				</div>
			</div>
		</section>

		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="seguranca">
			<div class="mb-6">
				<h3 class="text-xl font-bold text-slate-900 mb-2 flex items-center gap-2">
					<span class="material-symbols-outlined text-[#0052cc]">verified_user</span>
					<?php esc_html_e( 'Proteção dos Formulários Públicos', 'guiawp' ); ?>
				</h3>
				<p class="text-sm text-slate-500"><?php esc_html_e( 'Defina como login, cadastro e recuperação de senha serão protegidos contra abuso.', 'guiawp' ); ?></p>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
				<?php
				$captcha_options = [
					'google_v3' => [
						'title' => __( 'Google reCAPTCHA v3', 'guiawp' ),
						'desc'  => __( 'Proteção invisível por score. O badge será ocultado e exibiremos apenas aviso textual.', 'guiawp' ),
						'icon'  => 'shield_with_house',
					],
					'turnstile' => [
						'title' => __( 'Cloudflare Turnstile', 'guiawp' ),
						'desc'  => __( 'Widget moderno com menor fricção para login, cadastro e reset.', 'guiawp' ),
						'icon'  => 'cloud',
					],
					'math' => [
						'title' => __( 'Cálculo simples nativo', 'guiawp' ),
						'desc'  => __( 'Pergunta matemática leve, sem depender de serviço externo.', 'guiawp' ),
						'icon'  => 'calculate',
					],
					'none' => [
						'title' => __( 'Nenhum (perigoso)', 'guiawp' ),
						'desc'  => __( 'Desativa a barreira extra. Só use se realmente souber o risco.', 'guiawp' ),
						'icon'  => 'warning',
					],
				];
				foreach ( $captcha_options as $value => $option ) :
					$is_checked = $captcha_provider === $value;
				?>
				<label class="block cursor-pointer">
					<input type="radio" name="auth_captcha_provider" value="<?php echo esc_attr( $value ); ?>" class="sr-only" <?php checked( $is_checked ); ?>>
					<span class="gcep-captcha-provider-card block rounded-2xl border px-5 py-5 transition-all <?php echo $is_checked ? 'border-slate-900 bg-slate-900 text-white shadow-lg' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300'; ?>">
						<span class="material-symbols-outlined text-[26px] mb-4 <?php echo $is_checked ? 'text-white' : 'text-[#0052cc]'; ?>"><?php echo esc_html( $option['icon'] ); ?></span>
						<span class="block text-sm font-bold"><?php echo esc_html( $option['title'] ); ?></span>
						<span class="mt-2 block text-sm leading-6 <?php echo $is_checked ? 'text-white/80' : 'text-slate-500'; ?>"><?php echo esc_html( $option['desc'] ); ?></span>
					</span>
				</label>
				<?php endforeach; ?>
			</div>

			<div class="space-y-6">
				<div data-captcha-panel="google_v3" class="<?php echo 'google_v3' === $captcha_provider ? '' : 'hidden'; ?>">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Google Site Key', 'guiawp' ); ?></span>
							<input type="text" name="auth_google_site_key" value="<?php echo esc_attr( $settings['auth_google_site_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
						</label>
						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Google Secret Key', 'guiawp' ); ?></span>
							<input type="text" name="auth_google_secret_key" value="<?php echo esc_attr( $settings['auth_google_secret_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
						</label>
					</div>
					<label class="mt-6 flex flex-col gap-2 max-w-xs">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Score mínimo', 'guiawp' ); ?></span>
						<input type="number" name="auth_google_min_score" min="0.1" max="0.9" step="0.1" value="<?php echo esc_attr( $settings['auth_google_min_score'] ?? '0.5' ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
						<span class="text-xs text-slate-400"><?php esc_html_e( '0.5 é um ponto de partida seguro para login e cadastro.', 'guiawp' ); ?></span>
					</label>
				</div>

				<div data-captcha-panel="turnstile" class="<?php echo 'turnstile' === $captcha_provider ? '' : 'hidden'; ?>">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Turnstile Site Key', 'guiawp' ); ?></span>
							<input type="text" name="auth_turnstile_site_key" value="<?php echo esc_attr( $settings['auth_turnstile_site_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
						</label>
						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Turnstile Secret Key', 'guiawp' ); ?></span>
							<input type="text" name="auth_turnstile_secret_key" value="<?php echo esc_attr( $settings['auth_turnstile_secret_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
						</label>
					</div>
				</div>

				<div data-captcha-panel="math" class="<?php echo 'math' === $captcha_provider ? '' : 'hidden'; ?>">
					<div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600">
						<?php esc_html_e( 'Os formulários vão exibir uma conta rápida para o visitante resolver antes de continuar. Não depende de Google nem Cloudflare.', 'guiawp' ); ?>
					</div>
				</div>

				<div data-captcha-panel="none" class="<?php echo 'none' === $captcha_provider ? '' : 'hidden'; ?>">
					<div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm leading-6 text-rose-700">
						<?php esc_html_e( 'Sem captcha você fica mais exposto a brute force, spam de cadastro e abuso no reset de senha.', 'guiawp' ); ?>
					</div>
				</div>
			</div>
		</section>

		<!-- Cores do Tema -->
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="aparencia">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">palette</span>
				<?php esc_html_e( 'Cores do Tema', 'guiawp' ); ?>
			</h3>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
				<?php
				$color_fields = [
					'cor_primaria'   => __( 'Cor Primária', 'guiawp' ),
					'cor_secundaria' => __( 'Cor Secundária', 'guiawp' ),
					'cor_destaque'   => __( 'Cor de Destaque', 'guiawp' ),
					'cor_fundo'      => __( 'Cor de Fundo', 'guiawp' ),
					'cor_texto'      => __( 'Cor do Texto', 'guiawp' ),
					'cor_fundo_categorias' => __( 'Cor de Fundo Categorias', 'guiawp' ),
				];
				foreach ( $color_fields as $key => $label ) :
				?>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php echo esc_html( $label ); ?></span>
					<div class="flex items-center gap-3">
						<input type="color" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $settings[ $key ] ); ?>" class="rounded border-slate-200 cursor-pointer" style="width:48px;height:40px;padding:2px;">
						<input type="text" value="<?php echo esc_attr( $settings[ $key ] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-2 text-sm flex-1 min-w-0" readonly>
					</div>
				</label>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- Rodapé -->
		<?php
		$rodape_tipo     = $settings['cor_rodape_tipo'] ?? 'solido';
		$rodape_cor1     = $settings['cor_rodape'] ?? '#1e293b';
		$rodape_cor2     = $settings['cor_rodape_cor2'] ?? '#0f172a';
		$rodape_dir      = $settings['cor_rodape_direcao'] ?? 'to bottom';
		$rodape_opac     = intval( $settings['cor_rodape_opacidade'] ?? 100 );
		$rodape_titulo   = $settings['cor_rodape_titulo'] ?? '#f1f5f9';
		$rodape_texto    = $settings['cor_rodape_texto'] ?? '#94a3b8';
		$rodape_link     = $settings['cor_rodape_link'] ?? '#cbd5e1';
		$rodape_link_hov = $settings['cor_rodape_link_hover'] ?? '#ffffff';
		?>
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="aparencia">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">bottom_navigation</span>
				<?php esc_html_e( 'Rodapé', 'guiawp' ); ?>
			</h3>

			<!-- Preview do rodapé -->
			<div id="gcep-rodape-preview" class="rounded-xl overflow-hidden mb-6 p-6" style="background:<?php echo esc_attr( $rodape_cor1 ); ?>">
				<div class="flex items-center gap-6">
					<div class="flex-1">
						<p id="gcep-rp-titulo" class="font-bold text-sm mb-1" style="color:<?php echo esc_attr( $rodape_titulo ); ?>"><?php esc_html_e( 'Título de exemplo', 'guiawp' ); ?></p>
						<p id="gcep-rp-texto" class="text-xs mb-2" style="color:<?php echo esc_attr( $rodape_texto ); ?>"><?php esc_html_e( 'Texto descritivo do rodapé com informações sobre a plataforma.', 'guiawp' ); ?></p>
						<div class="flex gap-3">
							<a id="gcep-rp-link1" href="#" onclick="return false" class="text-xs underline" style="color:<?php echo esc_attr( $rodape_link ); ?>"><?php esc_html_e( 'Link normal', 'guiawp' ); ?></a>
							<a id="gcep-rp-link2" href="#" onclick="return false" class="text-xs underline" style="color:<?php echo esc_attr( $rodape_link_hov ); ?>"><?php esc_html_e( 'Link hover', 'guiawp' ); ?></a>
						</div>
					</div>
				</div>
			</div>

			<!-- Tipo de fundo -->
			<div class="mb-6">
				<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Tipo de Fundo', 'guiawp' ); ?></span>
				<div class="flex gap-3">
					<label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition-all <?php echo 'solido' === $rodape_tipo ? 'border-[#0052cc] bg-[#0052cc]/5 text-[#0052cc]' : 'border-slate-200 text-slate-600 hover:border-slate-300'; ?>">
						<input type="radio" name="cor_rodape_tipo" value="solido" <?php checked( $rodape_tipo, 'solido' ); ?> class="sr-only" onchange="gcepRodapeToggleTipo()">
						<span class="material-symbols-outlined text-lg">format_color_fill</span>
						<span class="text-sm font-semibold"><?php esc_html_e( 'Sólido', 'guiawp' ); ?></span>
					</label>
					<label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition-all <?php echo 'gradiente' === $rodape_tipo ? 'border-[#0052cc] bg-[#0052cc]/5 text-[#0052cc]' : 'border-slate-200 text-slate-600 hover:border-slate-300'; ?>">
						<input type="radio" name="cor_rodape_tipo" value="gradiente" <?php checked( $rodape_tipo, 'gradiente' ); ?> class="sr-only" onchange="gcepRodapeToggleTipo()">
						<span class="material-symbols-outlined text-lg">gradient</span>
						<span class="text-sm font-semibold"><?php esc_html_e( 'Gradiente', 'guiawp' ); ?></span>
					</label>
				</div>
			</div>

			<!-- Cores do fundo -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Cor Principal', 'guiawp' ); ?></span>
					<div class="flex items-center gap-3">
						<input type="color" name="cor_rodape" id="gcep-rodape-cor1" value="<?php echo esc_attr( $rodape_cor1 ); ?>" class="rounded border-slate-200 cursor-pointer" style="width:48px;height:40px;padding:2px;" onchange="gcepRodapeUpdatePreview()">
						<input type="text" value="<?php echo esc_attr( $rodape_cor1 ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-2 text-sm flex-1 min-w-0" readonly>
					</div>
				</label>
				<label class="flex flex-col gap-2 gcep-rodape-gradiente-field <?php echo 'gradiente' !== $rodape_tipo ? 'hidden' : ''; ?>">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Cor Secundária', 'guiawp' ); ?></span>
					<div class="flex items-center gap-3">
						<input type="color" name="cor_rodape_cor2" id="gcep-rodape-cor2" value="<?php echo esc_attr( $rodape_cor2 ); ?>" class="rounded border-slate-200 cursor-pointer" style="width:48px;height:40px;padding:2px;" onchange="gcepRodapeUpdatePreview()">
						<input type="text" value="<?php echo esc_attr( $rodape_cor2 ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-2 text-sm flex-1 min-w-0" readonly>
					</div>
				</label>
				<label class="flex flex-col gap-2 gcep-rodape-gradiente-field <?php echo 'gradiente' !== $rodape_tipo ? 'hidden' : ''; ?>">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Direção', 'guiawp' ); ?></span>
					<select name="cor_rodape_direcao" id="gcep-rodape-dir" class="rounded-lg border-slate-200 bg-slate-50 p-2.5 text-sm" onchange="gcepRodapeUpdatePreview()">
						<option value="to bottom" <?php selected( $rodape_dir, 'to bottom' ); ?>><?php esc_html_e( '↓ Para baixo', 'guiawp' ); ?></option>
						<option value="to top" <?php selected( $rodape_dir, 'to top' ); ?>><?php esc_html_e( '↑ Para cima', 'guiawp' ); ?></option>
						<option value="to right" <?php selected( $rodape_dir, 'to right' ); ?>><?php esc_html_e( '→ Para direita', 'guiawp' ); ?></option>
						<option value="to left" <?php selected( $rodape_dir, 'to left' ); ?>><?php esc_html_e( '← Para esquerda', 'guiawp' ); ?></option>
						<option value="to bottom right" <?php selected( $rodape_dir, 'to bottom right' ); ?>><?php esc_html_e( '↘ Diagonal', 'guiawp' ); ?></option>
						<option value="to bottom left" <?php selected( $rodape_dir, 'to bottom left' ); ?>><?php esc_html_e( '↙ Diagonal inversa', 'guiawp' ); ?></option>
					</select>
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Opacidade', 'guiawp' ); ?></span>
					<div class="flex items-center gap-3">
						<input type="range" name="cor_rodape_opacidade" id="gcep-rodape-opac" value="<?php echo esc_attr( $rodape_opac ); ?>" min="0" max="100" step="5" class="flex-1" oninput="document.getElementById('gcep-rodape-opac-val').textContent=this.value+'%';gcepRodapeUpdatePreview()">
						<span id="gcep-rodape-opac-val" class="text-sm font-semibold text-slate-600 w-10 text-right"><?php echo esc_html( $rodape_opac ); ?>%</span>
					</div>
				</label>
			</div>

			<!-- Cores de texto e links -->
			<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Textos e Links', 'guiawp' ); ?></span>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
				<?php
				$rodape_text_fields = [
					'cor_rodape_titulo'     => __( 'Títulos', 'guiawp' ),
					'cor_rodape_texto'      => __( 'Texto', 'guiawp' ),
					'cor_rodape_link'       => __( 'Links', 'guiawp' ),
					'cor_rodape_link_hover' => __( 'Links (hover)', 'guiawp' ),
				];
				foreach ( $rodape_text_fields as $rtk => $rtl ) :
				?>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php echo esc_html( $rtl ); ?></span>
					<div class="flex items-center gap-3">
						<input type="color" name="<?php echo esc_attr( $rtk ); ?>" id="gcep-<?php echo esc_attr( $rtk ); ?>" value="<?php echo esc_attr( $settings[ $rtk ] ); ?>" class="rounded border-slate-200 cursor-pointer" style="width:48px;height:40px;padding:2px;" onchange="gcepRodapeUpdatePreview()">
						<input type="text" value="<?php echo esc_attr( $settings[ $rtk ] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-2 text-sm flex-1 min-w-0" readonly>
					</div>
				</label>
				<?php endforeach; ?>
			</div>
		</section>

		<script>
		function gcepRodapeToggleTipo() {
			var tipo = document.querySelector('input[name="cor_rodape_tipo"]:checked').value;
			var fields = document.querySelectorAll('.gcep-rodape-gradiente-field');
			fields.forEach(function(f) { f.classList.toggle('hidden', tipo !== 'gradiente'); });
			// Atualizar visual dos radio labels
			document.querySelectorAll('input[name="cor_rodape_tipo"]').forEach(function(r) {
				var lbl = r.closest('label');
				if (r.checked) {
					lbl.className = lbl.className.replace(/border-slate-200 text-slate-600 hover:border-slate-300/g, '').replace(/border-\[#0052cc\] bg-\[#0052cc\]\/5 text-\[#0052cc\]/g, '') + ' border-[#0052cc] bg-[#0052cc]/5 text-[#0052cc]';
				} else {
					lbl.className = lbl.className.replace(/border-\[#0052cc\] bg-\[#0052cc\]\/5 text-\[#0052cc\]/g, '') + ' border-slate-200 text-slate-600 hover:border-slate-300';
				}
			});
			gcepRodapeUpdatePreview();
		}
		function gcepRodapeUpdatePreview() {
			var tipo = document.querySelector('input[name="cor_rodape_tipo"]:checked').value;
			var cor1 = document.getElementById('gcep-rodape-cor1').value;
			var cor2 = document.getElementById('gcep-rodape-cor2').value;
			var dir = document.getElementById('gcep-rodape-dir').value;
			var opac = document.getElementById('gcep-rodape-opac').value / 100;
			var preview = document.getElementById('gcep-rodape-preview');
			if (tipo === 'gradiente') {
				preview.style.background = 'linear-gradient(' + dir + ',' + cor1 + ',' + cor2 + ')';
			} else {
				preview.style.background = cor1;
			}
			preview.style.opacity = opac;
			// Textos
			var tit = document.getElementById('gcep-cor_rodape_titulo');
			var txt = document.getElementById('gcep-cor_rodape_texto');
			var lnk = document.getElementById('gcep-cor_rodape_link');
			var lnkh = document.getElementById('gcep-cor_rodape_link_hover');
			if (tit) document.getElementById('gcep-rp-titulo').style.color = tit.value;
			if (txt) document.getElementById('gcep-rp-texto').style.color = txt.value;
			if (lnk) document.getElementById('gcep-rp-link1').style.color = lnk.value;
			if (lnkh) document.getElementById('gcep-rp-link2').style.color = lnkh.value;
			// Sincronizar campos readonly
			document.querySelectorAll('#gcep-rodape-preview').forEach(function(){});
			['cor_rodape','cor_rodape_cor2','cor_rodape_titulo','cor_rodape_texto','cor_rodape_link','cor_rodape_link_hover'].forEach(function(k){
				var inp = document.querySelector('input[name="'+k+'"][type="color"]');
				if(inp){var ro=inp.closest('.flex').querySelector('input[readonly]');if(ro)ro.value=inp.value;}
			});
		}
		</script>

		<!-- Hero da Home -->
		<?php
		$hero_overlay_cor1   = $settings['hero_overlay_cor1'] ?? '#0f172a';
		$hero_overlay_cor2   = $settings['hero_overlay_cor2'] ?? '#0f172a';
		$hero_overlay_dir    = $settings['hero_overlay_direcao'] ?? 'to bottom';
		$hero_overlay_op1    = intval( $settings['hero_overlay_opacidade1'] ?? 40 );
		$hero_overlay_op2    = intval( $settings['hero_overlay_opacidade2'] ?? 80 );
		$hero_img_url        = $settings['hero_imagem'] ?? '';
		?>
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="aparencia">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">image</span>
				<?php esc_html_e( 'Hero da Página Inicial', 'guiawp' ); ?>
			</h3>

			<!-- Imagem de fundo com crop -->
			<div class="mb-8 gcep-crop-wrapper">
				<span class="text-sm font-semibold text-slate-700 mb-2 block"><?php esc_html_e( 'Imagem de Fundo', 'guiawp' ); ?></span>
				<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'A imagem sera recortada para 1920x800px em WebP otimizado.', 'guiawp' ); ?></p>

				<!-- Preview da imagem existente -->
				<div id="gcep-hero-img-wrap" class="<?php echo empty( $hero_img_url ) ? 'hidden' : ''; ?> mb-4">
					<img id="gcep-hero-img-thumb" src="<?php echo esc_url( $hero_img_url ); ?>" alt="Hero" class="w-full max-w-lg h-auto rounded-xl border-2 border-slate-200 object-cover">
					<label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer mt-3">
						<input type="checkbox" name="gcep_remove_hero_imagem" value="1" class="rounded border-slate-300 text-rose-500 focus:ring-rose-500/20">
						<?php esc_html_e( 'Remover imagem', 'guiawp' ); ?>
					</label>
				</div>

				<!-- Preview do crop realizado -->
				<div class="gcep-crop-result-wrap hidden mb-4">
					<img src="" alt="Preview" class="gcep-crop-result w-full max-w-lg h-auto rounded-xl object-cover border-2 border-slate-200">
					<p class="text-xs text-emerald-600 font-medium mt-2"><?php esc_html_e( 'Imagem recortada com sucesso! Salve as configuracoes para aplicar.', 'guiawp' ); ?></p>
				</div>

				<!-- Botao de upload -->
				<label for="gcep-hero-img-input" class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-6 bg-slate-50 hover:bg-[#0052cc]/5 transition-colors cursor-pointer max-w-lg">
					<span class="material-symbols-outlined text-3xl text-slate-400 mb-2">cloud_upload</span>
					<p class="text-sm text-slate-500"><?php esc_html_e( 'Clique para enviar e recortar', 'guiawp' ); ?></p>
					<p class="text-[10px] text-slate-400 mt-1">PNG, JPG, WebP</p>
				</label>
				<input type="file" name="gcep_hero_imagem" id="gcep-hero-img-input" accept="image/png,image/jpeg,image/webp" class="hidden">

				<!-- Area de crop -->
				<div class="gcep-crop-area hidden mt-4">
					<div class="gcep-crop-preview mx-auto overflow-hidden rounded-lg bg-slate-100" style="max-width:100%;"></div>
					<div class="flex gap-3 mt-4 justify-center">
						<button type="button" class="gcep-crop-confirm px-6 py-2.5 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-lg font-bold text-sm transition-all flex items-center gap-2">
							<span class="material-symbols-outlined text-lg">crop</span>
							<?php esc_html_e( 'Recortar', 'guiawp' ); ?>
						</button>
						<button type="button" class="gcep-crop-cancel px-6 py-2.5 border border-slate-200 text-slate-600 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-all">
							<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Overlay -->
			<div class="mb-8">
				<span class="text-sm font-semibold text-slate-700 mb-2 block"><?php esc_html_e( 'Overlay (Gradiente)', 'guiawp' ); ?></span>
				<p class="text-xs text-slate-400 mb-4"><?php esc_html_e( 'Camada de cor sobre a imagem. Define duas cores com opacidades independentes.', 'guiawp' ); ?></p>

				<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
					<!-- Cor 1 -->
					<div>
						<span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block"><?php esc_html_e( 'Cor Inicial', 'guiawp' ); ?></span>
						<div class="flex items-center gap-3 mb-3">
							<input type="color" name="hero_overlay_cor1" id="gcep-hero-cor1" value="<?php echo esc_attr( $hero_overlay_cor1 ); ?>" class="rounded border-slate-200 cursor-pointer" style="width:48px;height:40px;padding:2px;">
							<span id="gcep-hero-cor1-hex" class="text-sm text-slate-600 font-mono"><?php echo esc_html( $hero_overlay_cor1 ); ?></span>
						</div>
						<label class="text-xs text-slate-500 mb-1 block"><?php esc_html_e( 'Opacidade', 'guiawp' ); ?>: <strong id="gcep-hero-op1-val"><?php echo esc_html( $hero_overlay_op1 ); ?>%</strong></label>
						<input type="range" name="hero_overlay_opacidade1" id="gcep-hero-op1" min="0" max="100" step="5" value="<?php echo esc_attr( $hero_overlay_op1 ); ?>" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#0052cc]">
					</div>
					<!-- Cor 2 -->
					<div>
						<span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block"><?php esc_html_e( 'Cor Final', 'guiawp' ); ?></span>
						<div class="flex items-center gap-3 mb-3">
							<input type="color" name="hero_overlay_cor2" id="gcep-hero-cor2" value="<?php echo esc_attr( $hero_overlay_cor2 ); ?>" class="rounded border-slate-200 cursor-pointer" style="width:48px;height:40px;padding:2px;">
							<span id="gcep-hero-cor2-hex" class="text-sm text-slate-600 font-mono"><?php echo esc_html( $hero_overlay_cor2 ); ?></span>
						</div>
						<label class="text-xs text-slate-500 mb-1 block"><?php esc_html_e( 'Opacidade', 'guiawp' ); ?>: <strong id="gcep-hero-op2-val"><?php echo esc_html( $hero_overlay_op2 ); ?>%</strong></label>
						<input type="range" name="hero_overlay_opacidade2" id="gcep-hero-op2" min="0" max="100" step="5" value="<?php echo esc_attr( $hero_overlay_op2 ); ?>" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#0052cc]">
					</div>
				</div>

				<!-- Direção -->
				<div class="mb-6">
					<span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block"><?php esc_html_e( 'Direção do Gradiente', 'guiawp' ); ?></span>
					<div class="flex flex-wrap gap-2">
						<?php
						$direcoes = [
							'to bottom'       => __( '↓ Cima → Baixo', 'guiawp' ),
							'to top'          => __( '↑ Baixo → Cima', 'guiawp' ),
							'to right'        => __( '→ Esquerda → Direita', 'guiawp' ),
							'to left'         => __( '← Direita → Esquerda', 'guiawp' ),
							'to bottom right' => __( '↘ Diagonal', 'guiawp' ),
							'to bottom left'  => __( '↙ Diagonal', 'guiawp' ),
						];
						foreach ( $direcoes as $val => $lbl ) :
						?>
						<label class="relative cursor-pointer">
							<input type="radio" name="hero_overlay_direcao" value="<?php echo esc_attr( $val ); ?>" class="peer sr-only gcep-hero-dir" <?php checked( $hero_overlay_dir, $val ); ?>>
							<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-blue-50 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 peer-checked:text-[#0052cc] transition-all">
								<?php echo esc_html( $lbl ); ?>
							</div>
						</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Preview -->
			<div>
				<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Preview do Hero', 'guiawp' ); ?></span>
				<div id="gcep-hero-preview" class="relative h-[200px] rounded-xl overflow-hidden border border-slate-200">
					<div id="gcep-hero-preview-bg" class="absolute inset-0 bg-cover bg-center" style="<?php echo $hero_img_url ? 'background-image:url(' . esc_url( $hero_img_url ) . ')' : 'background-color:#64748b'; ?>"></div>
					<div id="gcep-hero-preview-overlay" class="absolute inset-0" style="background:linear-gradient(<?php echo esc_attr( $hero_overlay_dir ); ?>, <?php echo esc_attr( $hero_overlay_cor1 ); ?><?php echo dechex( (int) round( $hero_overlay_op1 * 2.55 ) ); ?>, <?php echo esc_attr( $hero_overlay_cor2 ); ?><?php echo dechex( (int) round( $hero_overlay_op2 * 2.55 ) ); ?>);"></div>
					<div class="relative z-10 flex items-center justify-center h-full">
						<div class="text-center">
							<p class="text-white font-black text-xl md:text-2xl"><?php echo esc_html( $settings['hero_titulo'] ); ?></p>
							<p class="text-white/80 text-sm mt-2"><?php echo esc_html( $settings['hero_subtitulo'] ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<script>
			(function(){
				var cor1 = document.getElementById('gcep-hero-cor1');
				var cor2 = document.getElementById('gcep-hero-cor2');
				var op1 = document.getElementById('gcep-hero-op1');
				var op2 = document.getElementById('gcep-hero-op2');
				var cor1Hex = document.getElementById('gcep-hero-cor1-hex');
				var cor2Hex = document.getElementById('gcep-hero-cor2-hex');
				var op1Val = document.getElementById('gcep-hero-op1-val');
				var op2Val = document.getElementById('gcep-hero-op2-val');
				var overlay = document.getElementById('gcep-hero-preview-overlay');
				var bgEl = document.getElementById('gcep-hero-preview-bg');
				var dirs = document.querySelectorAll('.gcep-hero-dir');
				var fileInput = document.getElementById('gcep-hero-img-input');
				var imgWrap = document.getElementById('gcep-hero-img-wrap');
				var imgThumb = document.getElementById('gcep-hero-img-thumb');

				function toHex2(n){ var h = Math.round(n).toString(16); return h.length < 2 ? '0'+h : h; }

				function getDir(){
					var checked = document.querySelector('.gcep-hero-dir:checked');
					return checked ? checked.value : 'to bottom';
				}

				function updatePreview(){
					var c1 = cor1.value, c2 = cor2.value;
					var a1 = toHex2(parseInt(op1.value) * 2.55);
					var a2 = toHex2(parseInt(op2.value) * 2.55);
					overlay.style.background = 'linear-gradient(' + getDir() + ',' + c1 + a1 + ',' + c2 + a2 + ')';
					cor1Hex.textContent = c1;
					cor2Hex.textContent = c2;
					op1Val.textContent = op1.value + '%';
					op2Val.textContent = op2.value + '%';
				}

				[cor1, cor2, op1, op2].forEach(function(el){ el.addEventListener('input', updatePreview); });
				dirs.forEach(function(d){ d.addEventListener('change', updatePreview); });

				// Atualizar preview do overlay apos o crop concluir
				var cropResult = document.querySelector('.gcep-crop-wrapper .gcep-crop-result');
				if (cropResult) {
					var observer = new MutationObserver(function(mutations){
						mutations.forEach(function(m){
							if (m.type === 'attributes' && m.attributeName === 'src' && cropResult.src) {
								bgEl.style.backgroundImage = 'url(' + cropResult.src + ')';
								if (imgWrap) imgWrap.classList.add('hidden');
							}
						});
					});
					observer.observe(cropResult, { attributes: true, attributeFilter: ['src'] });
				}
			})();
			</script>
		</section>

		<!-- Imagem CTA -->
		<?php $cta_img_url = $settings['cta_imagem'] ?? ''; ?>
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="aparencia">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">campaign</span>
				<?php esc_html_e( 'Bloco CTA — Imagem', 'guiawp' ); ?>
			</h3>
			<p class="text-sm text-slate-500 mb-6"><?php esc_html_e( 'Imagem exibida à direita do bloco "Divulgue seu negócio" na página inicial. Recomendado: 800×600px ou maior, proporção paisagem.', 'guiawp' ); ?></p>

			<div class="gcep-crop-wrapper">
				<!-- Preview da imagem existente -->
				<div id="gcep-cta-img-wrap" class="<?php echo empty( $cta_img_url ) ? 'hidden' : ''; ?> mb-4">
					<img id="gcep-cta-img-thumb" src="<?php echo esc_url( $cta_img_url ); ?>" alt="CTA" class="w-full max-w-sm h-auto rounded-xl border-2 border-slate-200 object-cover">
					<label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer mt-3">
						<input type="checkbox" name="gcep_remove_cta_imagem" value="1" class="rounded border-slate-300 text-rose-500 focus:ring-rose-500/20">
						<?php esc_html_e( 'Remover imagem', 'guiawp' ); ?>
					</label>
				</div>

				<!-- Preview do crop realizado -->
				<div class="gcep-crop-result-wrap hidden mb-4">
					<img src="" alt="Preview" class="gcep-crop-result w-full max-w-sm h-auto rounded-xl object-cover border-2 border-slate-200">
					<p class="text-xs text-emerald-600 font-medium mt-2"><?php esc_html_e( 'Imagem recortada com sucesso! Salve as configurações para aplicar.', 'guiawp' ); ?></p>
				</div>

				<!-- Botão de upload -->
				<label for="gcep-cta-img-input" class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-6 bg-slate-50 hover:bg-[#0052cc]/5 transition-colors cursor-pointer max-w-sm">
					<span class="material-symbols-outlined text-3xl text-slate-400 mb-2">cloud_upload</span>
					<p class="text-sm text-slate-500"><?php esc_html_e( 'Clique para enviar e recortar', 'guiawp' ); ?></p>
					<p class="text-[10px] text-slate-400 mt-1">PNG, JPG, WebP — <?php esc_html_e( 'Será recortado para 800×600px', 'guiawp' ); ?></p>
				</label>
				<input type="file" name="gcep_cta_imagem" id="gcep-cta-img-input" accept="image/png,image/jpeg,image/webp" class="hidden">

				<!-- Área de crop -->
				<div class="gcep-crop-area hidden mt-4">
					<div class="gcep-crop-preview mx-auto overflow-hidden rounded-lg bg-slate-100" style="max-width:100%;"></div>
					<div class="flex gap-3 mt-4 justify-center">
						<button type="button" class="gcep-crop-confirm px-6 py-2.5 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-lg font-bold text-sm transition-all flex items-center gap-2">
							<span class="material-symbols-outlined text-lg">crop</span>
							<?php esc_html_e( 'Recortar', 'guiawp' ); ?>
						</button>
						<button type="button" class="gcep-crop-cancel px-6 py-2.5 border border-slate-200 text-slate-600 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-all">
							<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
						</button>
					</div>
				</div>
			</div>
		</section>

		<!-- Pagamento -->
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="pagamento">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">payments</span>
				<?php esc_html_e( 'Dados de Pagamento', 'guiawp' ); ?>
			</h3>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Chave PIX', 'guiawp' ); ?></span>
					<input type="text" name="chave_pix" value="<?php echo esc_attr( $settings['chave_pix'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nome do Recebedor', 'guiawp' ); ?></span>
					<input type="text" name="nome_recebedor" value="<?php echo esc_attr( $settings['nome_recebedor'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Cidade do Recebedor', 'guiawp' ); ?></span>
					<input type="text" name="cidade_recebedor" value="<?php echo esc_attr( $settings['cidade_recebedor'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'WhatsApp para Comprovante', 'guiawp' ); ?></span>
					<input type="tel" name="whatsapp_comprovante" data-intl-tel value="<?php echo esc_attr( GCEP_Helpers::format_phone( (string) $settings['whatsapp_comprovante'] ) ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="(00) 9 9999-9999 ou (00) 9999-9999">
				</label>
				<label class="flex flex-col gap-2 md:col-span-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Instruções de Pagamento', 'guiawp' ); ?></span>
					<textarea name="texto_instrucoes_pagamento" rows="3" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none"><?php echo esc_textarea( $settings['texto_instrucoes_pagamento'] ); ?></textarea>
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Prazo de Aprovação (horas)', 'guiawp' ); ?></span>
					<input type="number" name="prazo_aprovacao_horas" value="<?php echo esc_attr( $settings['prazo_aprovacao_horas'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
			</div>
		</section>

		<!-- Inteligência Artificial -->
		<?php
		$ia_provider       = $settings['ia_provider'] ?? 'openai';
		$ia_auto_flag      = $settings['ia_auto_approve'] ?? ( $settings['openai_auto_approve'] ?? '0' );
		$ia_prompt_value   = $settings['ia_prompt'] ?? ( $settings['openai_prompt'] ?? '' );
		$openai_cur_model  = $settings['openai_model'] ?? 'gpt-4o-mini';
		$groq_cur_model    = $settings['groq_model'] ?? 'llama-3.3-70b-versatile';
		?>
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="ia">
			<h3 class="text-xl font-bold text-slate-900 mb-2 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">smart_toy</span>
				<?php esc_html_e( 'Inteligência Artificial', 'guiawp' ); ?>
			</h3>
			<p class="text-sm text-slate-500 mb-6"><?php esc_html_e( 'Quando habilitada, os anúncios premium são analisados automaticamente pela IA antes de serem publicados.', 'guiawp' ); ?></p>

			<div class="grid grid-cols-1 gap-6">

				<!-- Toggle auto-aprovação -->
				<div class="flex items-center gap-3 p-4 rounded-lg bg-slate-50 border border-slate-200">
					<label class="relative inline-flex items-center cursor-pointer">
						<input type="hidden" name="ia_auto_approve" value="0">
						<input type="checkbox" name="ia_auto_approve" value="1" class="sr-only peer" <?php checked( $ia_auto_flag, '1' ); ?>>
						<div class="w-11 h-6 bg-slate-300 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0052cc]"></div>
					</label>
					<div>
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Habilitar auto-aprovação por IA', 'guiawp' ); ?></span>
						<p class="text-xs text-slate-400"><?php esc_html_e( 'Se desabilitado, todos os anúncios passam por aprovação manual.', 'guiawp' ); ?></p>
					</div>
				</div>

				<!-- Seletor de provedor -->
				<div class="flex flex-col gap-3">
					<div>
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Provedor de IA', 'guiawp' ); ?></span>
						<p class="text-xs text-slate-400 mt-0.5"><?php esc_html_e( 'Selecione qual serviço de IA será utilizado para validação e geração de conteúdo.', 'guiawp' ); ?></p>
					</div>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
						<label class="relative cursor-pointer">
							<input type="radio" name="ia_provider" value="openai" <?php checked( $ia_provider, 'openai' ); ?> class="peer sr-only gcep-ia-provider-radio">
							<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-4 transition-all flex items-center gap-4">
								<div class="flex items-center justify-center w-10 h-10 rounded-lg bg-slate-900 text-white shrink-0">
									<span class="text-sm font-black">AI</span>
								</div>
								<div>
									<span class="font-bold text-slate-900 text-sm">OpenAI</span>
									<p class="text-[11px] text-slate-500"><?php esc_html_e( 'GPT-4o Mini, GPT-4o e GPT-3.5 Turbo. API paga por uso.', 'guiawp' ); ?></p>
								</div>
							</div>
						</label>
						<label class="relative cursor-pointer">
							<input type="radio" name="ia_provider" value="groq" <?php checked( $ia_provider, 'groq' ); ?> class="peer sr-only gcep-ia-provider-radio">
							<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-4 transition-all flex items-center gap-4">
								<div class="flex items-center justify-center w-10 h-10 rounded-lg bg-orange-500 text-white shrink-0">
									<span class="text-sm font-black">G</span>
								</div>
								<div>
									<span class="font-bold text-slate-900 text-sm">Groq</span>
									<p class="text-[11px] text-slate-500"><?php esc_html_e( 'Llama 3.3, Mixtral e Gemma 2. Gratuito com rate limits generosos.', 'guiawp' ); ?></p>
								</div>
							</div>
						</label>
					</div>
				</div>

				<!-- Painel OpenAI -->
				<div id="gcep-ia-panel-openai" class="p-6 rounded-xl bg-slate-50 border border-slate-200 space-y-5 <?php echo 'openai' !== $ia_provider ? 'hidden' : ''; ?>">
					<h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
						<span class="material-symbols-outlined text-slate-600 text-[18px]">key</span>
						<?php esc_html_e( 'Configuração OpenAI', 'guiawp' ); ?>
					</h4>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Chave de API', 'guiawp' ); ?></span>
						<input type="password" name="openai_api_key" value="<?php echo esc_attr( $settings['openai_api_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="sk-...">
					</label>
					<div class="flex flex-col gap-3">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Modelo', 'guiawp' ); ?></span>
						<?php
						$openai_models = [
							'gpt-4o-mini' => [
								'label'       => 'GPT-4o Mini',
								'badge'       => __( 'Recomendado', 'guiawp' ),
								'badge_class' => 'bg-emerald-100 text-emerald-700',
								'desc'        => __( 'Melhor custo-benefício. Raciocínio excelente. ~$0,15/1M tokens.', 'guiawp' ),
							],
							'gpt-4o' => [
								'label'       => 'GPT-4o',
								'badge'       => __( 'Premium', 'guiawp' ),
								'badge_class' => 'bg-amber-100 text-amber-700',
								'desc'        => __( 'Máxima qualidade e precisão. ~$2,50/1M tokens.', 'guiawp' ),
							],
							'gpt-3.5-turbo' => [
								'label'       => 'GPT-3.5 Turbo',
								'badge'       => __( 'Econômico', 'guiawp' ),
								'badge_class' => 'bg-sky-100 text-sky-700',
								'desc'        => __( 'Mais acessível, validações simples. ~$0,50/1M tokens.', 'guiawp' ),
							],
						];
						?>
						<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
							<?php foreach ( $openai_models as $m_id => $m ) : ?>
							<label class="relative cursor-pointer">
								<input type="radio" name="openai_model" value="<?php echo esc_attr( $m_id ); ?>" <?php checked( $openai_cur_model, $m_id ); ?> class="peer sr-only">
								<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-3 transition-all h-full flex flex-col gap-1.5">
									<div class="flex items-center justify-between gap-2 flex-wrap">
										<span class="font-bold text-slate-900 text-xs"><?php echo esc_html( $m['label'] ); ?></span>
										<span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full <?php echo esc_attr( $m['badge_class'] ); ?>"><?php echo esc_html( $m['badge'] ); ?></span>
									</div>
									<p class="text-[10px] text-slate-500 leading-relaxed"><?php echo esc_html( $m['desc'] ); ?></p>
								</div>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Painel Groq -->
				<div id="gcep-ia-panel-groq" class="p-6 rounded-xl bg-orange-50/50 border border-orange-100 space-y-5 <?php echo 'groq' !== $ia_provider ? 'hidden' : ''; ?>">
					<h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
						<span class="material-symbols-outlined text-orange-500 text-[18px]">key</span>
						<?php esc_html_e( 'Configuração Groq', 'guiawp' ); ?>
					</h4>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Chave de API', 'guiawp' ); ?></span>
						<input type="password" name="groq_api_key" value="<?php echo esc_attr( $settings['groq_api_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="gsk_...">
						<p class="text-[11px] text-slate-400"><?php
							/* translators: %s = URL do console Groq */
							printf(
								esc_html__( 'Crie sua chave em %s', 'guiawp' ),
								'<a href="https://console.groq.com/keys" target="_blank" rel="noopener" class="text-[#0052cc] underline underline-offset-2">console.groq.com/keys</a>'
							);
						?></p>
					</label>
					<div class="flex flex-col gap-3">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Modelo', 'guiawp' ); ?></span>
						<?php
						$groq_models = [
							'llama-3.3-70b-versatile' => [
								'label'       => 'Llama 3.3 70B',
								'badge'       => __( 'Recomendado', 'guiawp' ),
								'badge_class' => 'bg-emerald-100 text-emerald-700',
								'desc'        => __( 'Melhor equilíbrio qualidade e velocidade. Ideal para validação e geração de conteúdo.', 'guiawp' ),
							],
							'llama-3.1-8b-instant' => [
								'label'       => 'Llama 3.1 8B',
								'badge'       => __( 'Rápido', 'guiawp' ),
								'badge_class' => 'bg-violet-100 text-violet-700',
								'desc'        => __( 'Ultra-rápido, baixa latência. Bom para validações simples.', 'guiawp' ),
							],
							'mixtral-8x7b-32768' => [
								'label'       => 'Mixtral 8x7B',
								'badge'       => __( 'Contexto longo', 'guiawp' ),
								'badge_class' => 'bg-sky-100 text-sky-700',
								'desc'        => __( 'Janela de 32k tokens. Bom para descrições extensas.', 'guiawp' ),
							],
							'gemma2-9b-it' => [
								'label'       => 'Gemma 2 9B',
								'badge'       => __( 'Alternativo', 'guiawp' ),
								'badge_class' => 'bg-amber-100 text-amber-700',
								'desc'        => __( 'Google Gemma 2. Leve, eficiente e preciso.', 'guiawp' ),
							],
						];
						?>
						<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
							<?php foreach ( $groq_models as $m_id => $m ) : ?>
							<label class="relative cursor-pointer">
								<input type="radio" name="groq_model" value="<?php echo esc_attr( $m_id ); ?>" <?php checked( $groq_cur_model, $m_id ); ?> class="peer sr-only">
								<div class="border-2 border-slate-200 peer-checked:border-[#0052cc] peer-checked:bg-[#0052cc]/5 rounded-xl p-3 transition-all h-full flex flex-col gap-1.5">
									<div class="flex items-center justify-between gap-2 flex-wrap">
										<span class="font-bold text-slate-900 text-xs"><?php echo esc_html( $m['label'] ); ?></span>
										<span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full <?php echo esc_attr( $m['badge_class'] ); ?>"><?php echo esc_html( $m['badge'] ); ?></span>
									</div>
									<p class="text-[10px] text-slate-500 leading-relaxed"><?php echo esc_html( $m['desc'] ); ?></p>
								</div>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Separador visual -->
				<div class="border-t border-slate-200 pt-2">
					<h4 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-1">
						<span class="material-symbols-outlined text-violet-500">auto_awesome</span>
						<?php esc_html_e( 'Geração de Imagens com IA', 'guiawp' ); ?>
					</h4>
					<p class="text-xs text-slate-400"><?php esc_html_e( 'Gemini 3.1 Flash Image para gerar imagens de categorias e capas de blog.', 'guiawp' ); ?></p>
				</div>

				<!-- Painel Gemini 3.1 Flash Image -->
				<div class="p-6 rounded-xl bg-violet-50/50 border border-violet-100 space-y-5">
					<h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
						<span class="material-symbols-outlined text-violet-500 text-[18px]">key</span>
						<?php esc_html_e( 'Configuração Gemini 3.1 Flash Image', 'guiawp' ); ?>
					</h4>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Chave de API', 'guiawp' ); ?></span>
						<input type="password" name="gemini_imagen_api_key" value="<?php echo esc_attr( $settings['gemini_imagen_api_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="AIza...">
						<p class="text-[11px] text-slate-400"><?php
							printf(
								esc_html__( 'Crie sua chave em %s', 'guiawp' ),
								'<a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="text-[#0052cc] underline underline-offset-2">Google AI Studio</a>'
							);
						?></p>
					</label>
					<div class="flex items-center gap-3 p-3 rounded-lg bg-white border border-violet-100">
						<span class="material-symbols-outlined text-violet-400 text-lg shrink-0">info</span>
						<p class="text-[11px] text-slate-500 leading-relaxed"><?php esc_html_e( 'A melhoria de prompts utiliza o provedor de IA selecionado acima (OpenAI/Groq). A geração de imagem utiliza o Gemini 3.1 Flash Image (gemini-3.1-flash-image-preview).', 'guiawp' ); ?></p>
					</div>
				</div>

				<!-- Separador visual -->
				<div class="border-t border-slate-200 pt-2">
					<h4 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-1">
						<span class="material-symbols-outlined text-[#0052cc]">verified</span>
						<?php esc_html_e( 'Validação de Anúncios', 'guiawp' ); ?>
					</h4>
				</div>

				<!-- Prompt compartilhado -->
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Prompt de Validação', 'guiawp' ); ?></span>
					<p class="text-xs text-slate-400 -mt-1"><?php esc_html_e( 'Defina as regras que a IA usará para aprovar ou rejeitar anúncios. Deixe vazio para usar o prompt padrão.', 'guiawp' ); ?></p>
					<textarea name="ia_prompt" rows="6" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm font-mono" placeholder="<?php echo esc_attr( GCEP_AI_Validator::get_default_prompt() ); ?>"><?php echo esc_textarea( $ia_prompt_value ); ?></textarea>
				</label>
			</div>
		</section>

		<!-- JS: alternar painéis OpenAI / Groq -->
		<script>
		(function(){
			var radios = document.querySelectorAll('.gcep-ia-provider-radio');
			var panels = {
				openai: document.getElementById('gcep-ia-panel-openai'),
				groq:   document.getElementById('gcep-ia-panel-groq')
			};
			radios.forEach(function(r){
				r.addEventListener('change', function(){
					Object.keys(panels).forEach(function(k){
						if(panels[k]) panels[k].classList.toggle('hidden', k !== r.value);
					});
				});
			});
		})();
		</script>

		<!-- Integrações de Pagamento -->
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="pagamento">
			<h3 class="text-xl font-bold text-slate-900 mb-2 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">account_balance</span>
				<?php esc_html_e( 'Integrações de Pagamento', 'guiawp' ); ?>
			</h3>
			<p class="text-sm text-slate-500 mb-6"><?php esc_html_e( 'Configure os gateways de pagamento para cobranças dos planos premium.', 'guiawp' ); ?></p>

			<!-- Gateway ativo -->
			<div class="mb-8">
				<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Gateway Ativo', 'guiawp' ); ?></span>
				<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
					<label class="relative cursor-pointer">
						<input type="radio" name="gateway_ativo" value="" class="peer sr-only" <?php checked( $settings['gateway_ativo'] ?? '', '' ); ?>>
						<div class="border-2 border-slate-200 peer-checked:border-slate-500 peer-checked:bg-slate-50 rounded-xl p-4 transition-all text-center">
							<span class="material-symbols-outlined text-2xl text-slate-400 mb-1 block">block</span>
							<span class="text-sm font-bold text-slate-700"><?php esc_html_e( 'Nenhum', 'guiawp' ); ?></span>
							<p class="text-[10px] text-slate-400 mt-1"><?php esc_html_e( 'Pagamento manual', 'guiawp' ); ?></p>
						</div>
					</label>
					<label class="relative cursor-pointer">
						<input type="radio" name="gateway_ativo" value="mercadopago" class="peer sr-only" <?php checked( $settings['gateway_ativo'] ?? '', 'mercadopago' ); ?>>
						<div class="border-2 border-slate-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 rounded-xl p-4 transition-all text-center">
							<span class="material-symbols-outlined text-2xl text-blue-500 mb-1 block">credit_card</span>
							<span class="text-sm font-bold text-slate-700"><?php esc_html_e( 'Mercado Pago', 'guiawp' ); ?></span>
							<p class="text-[10px] text-slate-400 mt-1"><?php esc_html_e( 'PIX e Cartão', 'guiawp' ); ?></p>
						</div>
					</label>
					<label class="relative cursor-pointer">
						<input type="radio" name="gateway_ativo" value="pagou" class="peer sr-only" <?php checked( $settings['gateway_ativo'] ?? '', 'pagou' ); ?>>
						<div class="border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 rounded-xl p-4 transition-all text-center">
							<span class="material-symbols-outlined text-2xl text-emerald-500 mb-1 block">payments</span>
							<span class="text-sm font-bold text-slate-700"><?php esc_html_e( 'Pagou.com.br', 'guiawp' ); ?></span>
							<p class="text-[10px] text-slate-400 mt-1"><?php esc_html_e( 'Chave de API', 'guiawp' ); ?></p>
						</div>
					</label>
				</div>
			</div>

			<!-- Mercado Pago -->
			<div
				class="p-6 rounded-xl bg-blue-50/50 border border-blue-100 mb-6 <?php echo 'mercadopago' === $gateway_ativo ? '' : 'hidden'; ?>"
				data-gateway-panel="mercadopago"
			>
				<h4 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
					<span class="material-symbols-outlined text-blue-500">credit_card</span>
					<?php esc_html_e( 'Mercado Pago', 'guiawp' ); ?>
				</h4>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Access Token', 'guiawp' ); ?></span>
						<input type="password" name="mercadopago_access_token" value="<?php echo esc_attr( $settings['mercadopago_access_token'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm" placeholder="APP_USR-...">
					</label>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Public Key', 'guiawp' ); ?></span>
						<input type="password" name="mercadopago_public_key" value="<?php echo esc_attr( $settings['mercadopago_public_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm" placeholder="APP_USR-...">
					</label>
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Webhook Secret', 'guiawp' ); ?></span>
						<input type="password" name="mercadopago_webhook_secret" value="<?php echo esc_attr( $settings['mercadopago_webhook_secret'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm" placeholder="abcdef1234...">
					</label>
					<div class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'URL do Webhook', 'guiawp' ); ?></span>
						<code class="rounded-lg border-slate-200 bg-slate-100 p-3 text-xs text-slate-600 break-all select-all"><?php echo esc_html( rest_url( 'gcep/v1/webhook/mercadopago' ) ); ?></code>
						<p class="text-xs text-slate-500"><?php esc_html_e( 'Configure esta URL no painel do Mercado Pago em Webhooks.', 'guiawp' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Pagou.com.br -->
			<div
				class="p-6 rounded-xl bg-emerald-50/50 border border-emerald-100 <?php echo 'pagou' === $gateway_ativo ? '' : 'hidden'; ?>"
				data-gateway-panel="pagou"
			>
				<h4 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
					<span class="material-symbols-outlined text-emerald-500">payments</span>
					<?php esc_html_e( 'Pagou.com.br', 'guiawp' ); ?>
				</h4>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Chave de API', 'guiawp' ); ?></span>
					<input type="password" name="pagou_api_key" value="<?php echo esc_attr( $settings['pagou_api_key'] ?? '' ); ?>" class="rounded-lg border-slate-200 bg-white p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm max-w-md" placeholder="pk_...">
				</label>
			</div>
		</section>

		<!-- Monetizacao / AdSense -->
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm hidden" data-settings-panel="monetizacao">
			<h3 class="text-xl font-bold text-slate-900 mb-2 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">monetization_on</span>
				<?php esc_html_e( 'Google AdSense / Monetização', 'guiawp' ); ?>
			</h3>
			<p class="text-sm text-slate-500 mb-6"><?php esc_html_e( 'Configure os scripts de anúncios para exibição automática no blog e nos anúncios grátis.', 'guiawp' ); ?></p>

			<div class="grid grid-cols-1 gap-6">
				<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
					<input type="hidden" name="adsense_enabled" value="0">
					<label class="flex items-start gap-3 cursor-pointer">
						<input type="checkbox" name="adsense_enabled" value="1" class="mt-1 rounded border-slate-300 text-[#0052cc] focus:ring-[#0052cc]/20" <?php checked( $settings['adsense_enabled'] ?? '0', '1' ); ?>>
						<span class="space-y-1">
							<span class="block text-sm font-bold text-slate-900"><?php esc_html_e( 'Habilitar Google AdSense', 'guiawp' ); ?></span>
							<span class="block text-xs text-slate-500"><?php esc_html_e( 'Quando ativo, o script global do AdSense é carregado e os blocos configurados podem aparecer no blog e nos anúncios grátis.', 'guiawp' ); ?></span>
						</span>
					</label>
				</div>

				<div class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Script do AdSense (Head)', 'guiawp' ); ?></span>
					<p class="text-xs text-slate-400 -mt-1"><?php esc_html_e( 'Cole aqui o script principal do Google AdSense. Será inserido no <head> de todas as páginas.', 'guiawp' ); ?></p>
					<textarea name="adsense_script" rows="4" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm font-mono" placeholder='<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXX" crossorigin="anonymous"></script>'><?php echo esc_textarea( $settings['adsense_script'] ?? '' ); ?></textarea>
				</div>

				<div class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Bloco de Anúncio In-Article', 'guiawp' ); ?></span>
					<p class="text-xs text-slate-400 -mt-1"><?php esc_html_e( 'Código do bloco de anúncio que será inserido automaticamente dentro do conteúdo dos posts. Use o formato in-article ou display horizontal.', 'guiawp' ); ?></p>
					<textarea name="adsense_in_article" rows="6" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none text-sm font-mono" placeholder='<ins class="adsbygoogle" style="display:block; text-align:center;" data-ad-layout="in-article" data-ad-format="fluid" data-ad-client="ca-pub-XXXX" data-ad-slot="YYYY"></ins>
<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>'><?php echo esc_textarea( $settings['adsense_in_article'] ?? '' ); ?></textarea>
				</div>

				<div class="flex flex-col gap-2 max-w-xs">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Intervalo de Palavras', 'guiawp' ); ?></span>
					<p class="text-xs text-slate-400 -mt-1"><?php esc_html_e( 'A cada quantas palavras um anúncio será inserido no conteúdo. Se o post for menor que esse valor, o anúncio aparece apenas no final.', 'guiawp' ); ?></p>
					<input type="number" name="adsense_intervalo_palavras" value="<?php echo esc_attr( $settings['adsense_intervalo_palavras'] ?? '600' ); ?>" min="200" max="3000" step="50" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</div>

				<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
					<p class="text-sm text-amber-800 flex items-start gap-2">
						<span class="material-symbols-outlined text-base mt-0.5">info</span>
						<?php esc_html_e( 'Os anúncios podem ser inseridos automaticamente nos posts do blog e também nos anúncios grátis. Em anúncios grátis, o bloco aparece antes da seção Sobre e outro após o mapa quando o AdSense estiver habilitado.', 'guiawp' ); ?>
					</p>
				</div>
			</div>
		</section>

		<!-- Home -->
		<section class="bg-white p-4 sm:p-6 lg:p-8 rounded-xl border border-slate-200 shadow-sm" data-settings-panel="plataforma">
			<h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
				<span class="material-symbols-outlined text-[#0052cc]">home</span>
				<?php esc_html_e( 'Conteúdo da Home', 'guiawp' ); ?>
			</h3>
			<div class="grid grid-cols-1 gap-6">
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Título do Hero', 'guiawp' ); ?></span>
					<input type="text" name="hero_titulo" value="<?php echo esc_attr( $settings['hero_titulo'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Subtítulo do Hero', 'guiawp' ); ?></span>
					<input type="text" name="hero_subtitulo" value="<?php echo esc_attr( $settings['hero_subtitulo'] ); ?>" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
				</label>
			</div>
		</section>

		<div class="flex justify-end">
			<button type="submit" class="px-10 py-3 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-xl font-bold shadow-lg shadow-primary/20 transition-all">
				<?php esc_html_e( 'Salvar Configurações', 'guiawp' ); ?>
			</button>
		</div>
	</form>
</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
