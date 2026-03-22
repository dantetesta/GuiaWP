<?php
/**
 * Template: Página de Cadastro
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/painel' ) );
	exit;
}

$msg            = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type       = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';
$primary_color  = GCEP_Settings::get( 'cor_primaria', '#0052cc' );
$login_url      = home_url( '/login' );
$terms_url      = GCEP_Helpers::get_page_url_by_path( 'termos-de-uso', home_url() );
$privacy_url    = GCEP_Helpers::get_page_url_by_path( 'privacidade', home_url() );
$register_captcha = GCEP_Auth_Captcha::get_context_config( 'register' );
$button_style   = 'background:linear-gradient(135deg,' . esc_attr( $primary_color ) . ' 0%, #0f172a 100%);';
$hex_to_rgba    = static function ( string $hex, float $opacity ): string {
	$hex = ltrim( trim( $hex ), '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) ) {
		return 'rgba(0, 82, 204, ' . $opacity . ')';
	}

	$red   = hexdec( substr( $hex, 0, 2 ) );
	$green = hexdec( substr( $hex, 2, 2 ) );
	$blue  = hexdec( substr( $hex, 4, 2 ) );

	return sprintf( 'rgba(%d, %d, %d, %.3f)', $red, $green, $blue, $opacity );
};
$primary_glow   = $hex_to_rgba( $primary_color, 0.20 );
$surface_style  = 'background:linear-gradient(180deg,rgba(8,27,52,0.86) 0%, rgba(15,50,92,0.72) 100%);';
$backdrop_style = "background:
	radial-gradient(circle at 14% 18%, {$primary_glow}, transparent 26%),
	radial-gradient(circle at 78% 16%, rgba(255,255,255,0.16), transparent 20%),
	radial-gradient(circle at 72% 76%, rgba(34,197,94,0.10), transparent 22%),
	linear-gradient(135deg, #10233f 0%, #173a69 48%, #0f203d 100%);";
$google_disclosure = sprintf(
	/* translators: 1: privacy url, 2: terms url */
	__( 'Protegido por reCAPTCHA. Aplicam-se a <a href="%1$s" target="_blank" rel="noopener noreferrer">Política de Privacidade</a> e os <a href="%2$s" target="_blank" rel="noopener noreferrer">Termos</a> do Google.', 'guiawp' ),
	esc_url( 'https://policies.google.com/privacy' ),
	esc_url( 'https://policies.google.com/terms' )
);

include GCEP_PLUGIN_DIR . 'templates/partials/header.php';
?>

<?php if ( 'google_v3' === $register_captcha['provider'] ) : ?>
	<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $register_captcha['site_key'] ); ?>"></script>
<?php elseif ( 'turnstile' === $register_captcha['provider'] ) : ?>
	<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<main class="relative overflow-hidden pt-24 md:pt-32 pb-16 md:pb-24 min-h-screen">
	<div class="absolute inset-0" style="<?php echo esc_attr( $backdrop_style ); ?>"></div>
	<div class="absolute inset-0 opacity-70 pointer-events-none">
		<div class="absolute top-10 left-[10%] h-44 w-44 rounded-full bg-white/10 blur-3xl"></div>
		<div class="absolute bottom-16 right-[8%] h-64 w-64 rounded-full bg-[#93c5fd]/15 blur-3xl"></div>
	</div>

	<div class="relative max-w-6xl mx-auto px-4 sm:px-6">
		<div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-stretch">
			<section class="rounded-[2rem] border border-white/10 p-8 md:p-10 text-white shadow-2xl shadow-black/20 backdrop-blur-xl overflow-hidden" style="<?php echo esc_attr( $surface_style ); ?>">
				<div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-white/70">
					<span class="material-symbols-outlined text-sm">rocket_launch</span>
					<?php esc_html_e( 'Primeiros passos', 'guiawp' ); ?>
				</div>

				<h1 class="mt-8 text-4xl md:text-5xl font-black tracking-tight leading-[0.95] max-w-md">
					<?php esc_html_e( 'Crie sua conta e comece a anunciar.', 'guiawp' ); ?>
				</h1>
				<p class="mt-5 max-w-md text-base md:text-lg leading-8 text-slate-200/90">
					<?php esc_html_e( 'Cadastre-se, entre no painel e publique seu negócio com um fluxo direto.', 'guiawp' ); ?>
				</p>

				<div class="mt-10 grid gap-3 sm:grid-cols-2">
					<div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
						<p class="text-sm font-bold text-white"><?php esc_html_e( 'Cadastro rápido', 'guiawp' ); ?></p>
						<p class="mt-1 text-sm leading-6 text-slate-300"><?php esc_html_e( 'Entre no painel sem etapas desnecessárias.', 'guiawp' ); ?></p>
					</div>
					<div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
						<p class="text-sm font-bold text-white"><?php esc_html_e( 'Painel pronto', 'guiawp' ); ?></p>
						<p class="mt-1 text-sm leading-6 text-slate-300"><?php esc_html_e( 'Depois do cadastro você já pode criar o primeiro anúncio.', 'guiawp' ); ?></p>
					</div>
				</div>
			</section>

			<section class="rounded-[2rem] border border-white/70 bg-white/95 p-7 md:p-9 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur-xl">
				<div class="mb-8">
					<div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-slate-500">
						<span class="material-symbols-outlined text-sm">person_add</span>
						<?php esc_html_e( 'Criar conta', 'guiawp' ); ?>
					</div>
					<h2 class="mt-5 text-4xl font-black tracking-tight text-slate-950"><?php esc_html_e( 'Seu cadastro em poucos passos.', 'guiawp' ); ?></h2>
					<p class="mt-3 text-base leading-7 text-slate-500"><?php esc_html_e( 'Preencha os dados abaixo para acessar o painel e começar a divulgar seu negócio.', 'guiawp' ); ?></p>
				</div>

				<?php if ( $msg ) : ?>
				<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
				<?php endif; ?>

				<div id="gcep-register-captcha-error" class="hidden mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"></div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gcep-register-form" class="space-y-5">
					<input type="hidden" name="action" value="gcep_register">
					<?php wp_nonce_field( 'gcep_register', 'gcep_register_nonce' ); ?>
					<?php if ( 'google_v3' === $register_captcha['provider'] ) : ?>
						<input type="hidden" name="gcep_recaptcha_token" value="">
					<?php endif; ?>

					<div>
						<label class="mb-2 block text-sm font-semibold text-slate-700" for="gcep_nome"><?php esc_html_e( 'Nome completo', 'guiawp' ); ?></label>
						<div class="relative">
							<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">person</span>
							<input type="text" id="gcep_nome" name="gcep_nome" required autocomplete="name" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="<?php esc_attr_e( 'Como deseja ser chamado?', 'guiawp' ); ?>">
						</div>
					</div>

					<div>
						<label class="mb-2 block text-sm font-semibold text-slate-700" for="gcep_email"><?php esc_html_e( 'E-mail', 'guiawp' ); ?></label>
						<div class="relative">
							<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">mail</span>
							<input type="email" id="gcep_email" name="gcep_email" required autocomplete="email" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="seu@email.com">
						</div>
					</div>

					<div class="grid gap-5 md:grid-cols-2">
						<div>
							<label class="mb-2 block text-sm font-semibold text-slate-700" for="gcep_telefone"><?php esc_html_e( 'Telefone / WhatsApp', 'guiawp' ); ?></label>
							<div class="gcep-cadastro-tel">
								<input type="tel" id="gcep_telefone" name="gcep_telefone" data-intl-tel required autocomplete="tel" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pr-4 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="(00) 9 9999-9999 ou (00) 9999-9999">
							</div>
						</div>
						<div>
							<label class="mb-2 block text-sm font-semibold text-slate-700" for="gcep_senha"><?php esc_html_e( 'Senha', 'guiawp' ); ?></label>
							<div class="relative">
								<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">lock</span>
								<input type="password" id="gcep_senha" name="gcep_senha" required minlength="6" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-14 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="<?php esc_attr_e( 'Crie uma senha forte', 'guiawp' ); ?>">
								<button type="button" data-password-toggle="#gcep_senha" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors" aria-label="<?php esc_attr_e( 'Mostrar senha', 'guiawp' ); ?>">
									<span class="material-symbols-outlined text-[20px]">visibility</span>
								</button>
							</div>
						</div>
					</div>

					<label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
						<input type="checkbox" id="gcep_termos" required class="mt-1 size-4 rounded border-slate-300 text-primary focus:ring-primary">
						<span class="text-sm leading-6 text-slate-600">
							<?php esc_html_e( 'Ao continuar, você concorda com os nossos', 'guiawp' ); ?>
							<a class="font-bold text-slate-950 hover:text-primary transition-colors" href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Termos de Uso', 'guiawp' ); ?></a>
							<?php esc_html_e( 'e com a nossa', 'guiawp' ); ?>
							<a class="font-bold text-slate-950 hover:text-primary transition-colors" href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Política de Privacidade', 'guiawp' ); ?></a>.
						</span>
					</label>

					<?php if ( 'turnstile' === $register_captcha['provider'] ) : ?>
						<div class="pt-1">
							<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $register_captcha['site_key'] ); ?>" data-action="<?php echo esc_attr( $register_captcha['action'] ); ?>" data-theme="light"></div>
						</div>
					<?php elseif ( 'math' === $register_captcha['provider'] && ! empty( $register_captcha['math'] ) ) : ?>
						<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
							<input type="hidden" name="gcep_math_challenge_id" value="<?php echo esc_attr( $register_captcha['math']['id'] ); ?>">
							<label class="block text-sm font-semibold text-slate-700" for="gcep_math_answer_register"><?php echo esc_html( $register_captcha['math']['question'] ); ?></label>
							<input type="text" id="gcep_math_answer_register" name="gcep_math_answer" inputmode="numeric" required class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="<?php esc_attr_e( 'Digite o resultado', 'guiawp' ); ?>">
						</div>
					<?php endif; ?>

					<button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-4 text-base font-black text-white shadow-[0_18px_45px_rgba(15,23,42,0.18)] transition-transform hover:-translate-y-0.5" style="<?php echo esc_attr( $button_style ); ?>">
						<?php esc_html_e( 'Criar minha conta', 'guiawp' ); ?>
						<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
					</button>

					<?php if ( 'google_v3' === $register_captcha['provider'] ) : ?>
						<p class="text-xs leading-5 text-slate-400"><?php echo wp_kses_post( $google_disclosure ); ?></p>
					<?php endif; ?>
				</form>

				<div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
					<p class="text-sm text-slate-600">
						<?php esc_html_e( 'Já possui uma conta?', 'guiawp' ); ?>
						<a class="ml-1 font-bold text-slate-950 hover:text-primary transition-colors" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Fazer login', 'guiawp' ); ?></a>
					</p>
				</div>
			</section>
		</div>
	</div>
</main>

<script>
(function () {
	var registerForm = document.getElementById('gcep-register-form');
	var registerError = document.getElementById('gcep-register-captcha-error');
	var registerCaptchaConfig = <?php echo wp_json_encode( $register_captcha ); ?>;

	document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
		button.addEventListener('click', function () {
			var selector = button.getAttribute('data-password-toggle');
			var input = selector ? document.querySelector(selector) : null;
			var icon = button.querySelector('.material-symbols-outlined');
			if (!input) return;
			var visible = input.type === 'text';
			input.type = visible ? 'password' : 'text';
			if (icon) {
				icon.textContent = visible ? 'visibility' : 'visibility_off';
			}
		});
	});

	function showInlineError(message) {
		if (!registerError) return;
		registerError.textContent = message;
		registerError.classList.remove('hidden');
	}

	function clearInlineError() {
		if (!registerError) return;
		registerError.textContent = '';
		registerError.classList.add('hidden');
	}

	function ensureCaptcha(form, config) {
		return new Promise(function (resolve, reject) {
			if (!config || !config.provider || config.provider === 'none' || config.provider === 'math') {
				resolve();
				return;
			}

			if (config.provider === 'turnstile') {
				var turnstileToken = form.querySelector('input[name="cf-turnstile-response"]');
				if (turnstileToken && turnstileToken.value) {
					resolve();
					return;
				}

				reject(<?php echo wp_json_encode( __( 'Conclua a verificação antes de continuar.', 'guiawp' ) ); ?>);
				return;
			}

			if (config.provider === 'google_v3') {
				if (!window.grecaptcha || !window.grecaptcha.execute) {
					reject(<?php echo wp_json_encode( __( 'Não foi possível carregar o reCAPTCHA agora. Tente novamente.', 'guiawp' ) ); ?>);
					return;
				}

				window.grecaptcha.ready(function () {
					window.grecaptcha.execute(config.site_key, { action: config.action })
						.then(function (token) {
							var tokenField = form.querySelector('input[name="gcep_recaptcha_token"]');
							if (!token || !tokenField) {
								reject(<?php echo wp_json_encode( __( 'Não foi possível validar o reCAPTCHA agora. Tente novamente.', 'guiawp' ) ); ?>);
								return;
							}

							tokenField.value = token;
							resolve();
						})
						.catch(function () {
							reject(<?php echo wp_json_encode( __( 'Não foi possível validar o reCAPTCHA agora. Tente novamente.', 'guiawp' ) ); ?>);
						});
				});
				return;
			}

			resolve();
		});
	}

	if (!registerForm) return;

	registerForm.addEventListener('submit', function (event) {
		if (registerForm.dataset.captchaReady === '1') {
			registerForm.dataset.captchaReady = '';
			return;
		}

		event.preventDefault();
		clearInlineError();

		ensureCaptcha(registerForm, registerCaptchaConfig)
			.then(function () {
				registerForm.dataset.captchaReady = '1';
				registerForm.submit();
			})
			.catch(function (message) {
				showInlineError(message);
			});
	});
})();
</script>

<style>
.grecaptcha-badge {
	visibility: hidden !important;
	opacity: 0 !important;
}
</style>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
