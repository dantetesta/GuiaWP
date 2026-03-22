<?php
/**
 * Template: Página de Login
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
$register_url   = home_url( '/cadastro' );
$login_captcha  = GCEP_Auth_Captcha::get_context_config( 'login' );
$reset_captcha  = GCEP_Auth_Captcha::get_context_config( 'reset' );
$forgot_nonce   = wp_create_nonce( 'gcep_forgot_password' );
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

<?php if ( 'google_v3' === $login_captcha['provider'] ) : ?>
	<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $login_captcha['site_key'] ); ?>"></script>
<?php elseif ( 'turnstile' === $login_captcha['provider'] ) : ?>
	<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<main class="relative overflow-hidden pt-24 md:pt-32 pb-16 md:pb-24 min-h-screen">
	<div class="absolute inset-0" style="<?php echo esc_attr( $backdrop_style ); ?>"></div>
	<div class="absolute inset-0 opacity-70 pointer-events-none">
		<div class="absolute top-16 left-[8%] h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
		<div class="absolute bottom-16 right-[10%] h-56 w-56 rounded-full bg-[#c4b5fd]/15 blur-3xl"></div>
	</div>

	<div class="relative max-w-6xl mx-auto px-4 sm:px-6">
		<div class="grid gap-8 lg:grid-cols-[0.92fr_1.08fr] lg:items-stretch">
			<section class="rounded-[2rem] border border-white/10 p-8 md:p-10 text-white shadow-2xl shadow-black/20 backdrop-blur-xl overflow-hidden" style="<?php echo esc_attr( $surface_style ); ?>">
				<div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-white/70">
					<span class="material-symbols-outlined text-sm">bolt</span>
					<?php esc_html_e( 'Acesso rápido', 'guiawp' ); ?>
				</div>

				<h1 class="mt-8 text-4xl md:text-5xl font-black tracking-tight leading-[0.95] max-w-md">
					<?php esc_html_e( 'Entre e siga com seus anúncios.', 'guiawp' ); ?>
				</h1>
				<p class="mt-5 max-w-md text-base md:text-lg leading-8 text-slate-200/90">
					<?php esc_html_e( 'Acesse o painel, acompanhe status e resolva ajustes sem perder tempo.', 'guiawp' ); ?>
				</p>

				<div class="mt-10 grid gap-3 sm:grid-cols-2">
					<div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
						<p class="text-sm font-bold text-white"><?php esc_html_e( 'Editar rápido', 'guiawp' ); ?></p>
						<p class="mt-1 text-sm leading-6 text-slate-300"><?php esc_html_e( 'Conteúdo, mídia e contatos em um só lugar.', 'guiawp' ); ?></p>
					</div>
					<div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
						<p class="text-sm font-bold text-white"><?php esc_html_e( 'Status claro', 'guiawp' ); ?></p>
						<p class="mt-1 text-sm leading-6 text-slate-300"><?php esc_html_e( 'Veja aprovação, pendências e próximos passos.', 'guiawp' ); ?></p>
					</div>
				</div>
			</section>

			<section class="gcep-auth-scene">
				<div id="gcep-auth-flip-card" class="gcep-auth-card relative min-h-[640px]">
					<div class="gcep-auth-face absolute inset-0 rounded-[2rem] border border-white/70 bg-white/95 p-7 md:p-9 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur-xl">
						<div class="mb-8">
							<div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-slate-500">
								<span class="material-symbols-outlined text-sm">login</span>
								<?php esc_html_e( 'Entrar', 'guiawp' ); ?>
							</div>
							<h2 class="mt-5 text-4xl font-black tracking-tight text-slate-950"><?php esc_html_e( 'Seu painel está aqui.', 'guiawp' ); ?></h2>
							<p class="mt-3 text-base leading-7 text-slate-500"><?php esc_html_e( 'Use seu e-mail e senha para acessar anúncios, métricas e pagamentos.', 'guiawp' ); ?></p>
						</div>

						<?php if ( $msg ) : ?>
						<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
						<?php endif; ?>

						<div id="gcep-login-captcha-error" class="hidden mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"></div>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gcep-login-form" class="space-y-5">
							<input type="hidden" name="action" value="gcep_login">
							<?php wp_nonce_field( 'gcep_login', 'gcep_login_nonce' ); ?>
							<?php if ( 'google_v3' === $login_captcha['provider'] ) : ?>
								<input type="hidden" name="gcep_recaptcha_token" value="">
							<?php endif; ?>

							<div>
								<label class="mb-2 block text-sm font-semibold text-slate-700" for="gcep_email"><?php esc_html_e( 'E-mail', 'guiawp' ); ?></label>
								<div class="relative">
									<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">mail</span>
									<input type="email" id="gcep_email" name="gcep_email" required autocomplete="email" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="seu@email.com">
								</div>
							</div>

							<div>
								<div class="mb-2 flex items-center justify-between gap-3">
									<label class="block text-sm font-semibold text-slate-700" for="gcep_senha"><?php esc_html_e( 'Senha', 'guiawp' ); ?></label>
									<button type="button" data-auth-flip-open class="text-sm font-semibold text-slate-500 hover:text-slate-900 transition-colors">
										<?php esc_html_e( 'Esqueci a senha', 'guiawp' ); ?>
									</button>
								</div>
								<div class="relative">
									<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">lock</span>
									<input type="password" id="gcep_senha" name="gcep_senha" required autocomplete="current-password" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-14 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="Sua senha">
									<button type="button" data-password-toggle="#gcep_senha" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors" aria-label="<?php esc_attr_e( 'Mostrar senha', 'guiawp' ); ?>">
										<span class="material-symbols-outlined text-[20px]">visibility</span>
									</button>
								</div>
							</div>

							<?php if ( 'turnstile' === $login_captcha['provider'] ) : ?>
								<div class="pt-1">
									<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $login_captcha['site_key'] ); ?>" data-action="<?php echo esc_attr( $login_captcha['action'] ); ?>" data-theme="light"></div>
								</div>
							<?php elseif ( 'math' === $login_captcha['provider'] && ! empty( $login_captcha['math'] ) ) : ?>
								<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
									<input type="hidden" name="gcep_math_challenge_id" value="<?php echo esc_attr( $login_captcha['math']['id'] ); ?>">
									<label class="block text-sm font-semibold text-slate-700" for="gcep_math_answer_login"><?php echo esc_html( $login_captcha['math']['question'] ); ?></label>
									<input type="text" id="gcep_math_answer_login" name="gcep_math_answer" inputmode="numeric" required class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="<?php esc_attr_e( 'Digite o resultado', 'guiawp' ); ?>">
								</div>
							<?php endif; ?>

							<button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-4 text-base font-black text-white shadow-[0_18px_45px_rgba(15,23,42,0.18)] transition-transform hover:-translate-y-0.5" style="<?php echo esc_attr( $button_style ); ?>">
								<?php esc_html_e( 'Entrar no painel', 'guiawp' ); ?>
								<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
							</button>

							<?php if ( 'google_v3' === $login_captcha['provider'] ) : ?>
								<p class="text-xs leading-5 text-slate-400"><?php echo wp_kses_post( $google_disclosure ); ?></p>
							<?php endif; ?>
						</form>

						<div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
							<p class="text-sm text-slate-600">
								<?php esc_html_e( 'Ainda não tem conta?', 'guiawp' ); ?>
								<a class="ml-1 font-bold text-slate-950 hover:text-primary transition-colors" href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Criar conta agora', 'guiawp' ); ?></a>
							</p>
						</div>
					</div>

					<div class="gcep-auth-face gcep-auth-face--back absolute inset-0 rounded-[2rem] border border-white/70 bg-white/95 p-7 md:p-9 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur-xl">
						<div class="mb-8 flex items-start justify-between gap-4">
							<div>
								<div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-slate-500">
									<span class="material-symbols-outlined text-sm">mark_email_read</span>
									<?php esc_html_e( 'Recuperar acesso', 'guiawp' ); ?>
								</div>
								<h2 class="mt-5 text-4xl font-black tracking-tight text-slate-950"><?php esc_html_e( 'Vamos redefinir sua senha.', 'guiawp' ); ?></h2>
								<p class="mt-3 text-base leading-7 text-slate-500"><?php esc_html_e( 'Informe o e-mail da conta e enviaremos um link para você escolher uma nova senha.', 'guiawp' ); ?></p>
							</div>
							<button type="button" data-auth-flip-close class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors" aria-label="<?php esc_attr_e( 'Voltar para o login', 'guiawp' ); ?>">
								<span class="material-symbols-outlined">arrow_back</span>
							</button>
						</div>

						<div id="gcep-forgot-error" class="hidden mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"></div>
						<div id="gcep-forgot-success" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
							<p class="font-bold"><?php esc_html_e( 'Pedido enviado.', 'guiawp' ); ?></p>
							<p class="mt-2" id="gcep-forgot-success-text"></p>
							<button type="button" data-auth-flip-close class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-emerald-800 hover:text-emerald-900 transition-colors">
								<span class="material-symbols-outlined text-[18px]">arrow_back</span>
								<?php esc_html_e( 'Voltar para entrar', 'guiawp' ); ?>
							</button>
						</div>

						<form id="gcep-forgot-password-form" class="space-y-5">
							<input type="hidden" name="nonce" value="<?php echo esc_attr( $forgot_nonce ); ?>">
							<?php if ( 'google_v3' === $reset_captcha['provider'] ) : ?>
								<input type="hidden" name="gcep_recaptcha_token" value="">
							<?php endif; ?>
							<div>
								<label class="mb-2 block text-sm font-semibold text-slate-700" for="gcep_forgot_email"><?php esc_html_e( 'E-mail da conta', 'guiawp' ); ?></label>
								<div class="relative">
									<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">mail</span>
									<input type="email" id="gcep_forgot_email" name="email" required autocomplete="email" class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 py-4 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="seu@email.com">
								</div>
							</div>

							<?php if ( 'turnstile' === $reset_captcha['provider'] ) : ?>
								<div class="pt-1">
									<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $reset_captcha['site_key'] ); ?>" data-action="<?php echo esc_attr( $reset_captcha['action'] ); ?>" data-theme="light"></div>
								</div>
							<?php elseif ( 'math' === $reset_captcha['provider'] && ! empty( $reset_captcha['math'] ) ) : ?>
								<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
									<input type="hidden" name="gcep_math_challenge_id" value="<?php echo esc_attr( $reset_captcha['math']['id'] ); ?>">
									<label class="block text-sm font-semibold text-slate-700" for="gcep_math_answer_reset"><?php echo esc_html( $reset_captcha['math']['question'] ); ?></label>
									<input type="text" id="gcep_math_answer_reset" name="gcep_math_answer" inputmode="numeric" required class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-200 outline-none transition-all" placeholder="<?php esc_attr_e( 'Digite o resultado', 'guiawp' ); ?>">
								</div>
							<?php endif; ?>

							<button type="submit" id="gcep-forgot-submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-4 text-base font-black text-white shadow-[0_18px_45px_rgba(15,23,42,0.18)] transition-transform hover:-translate-y-0.5" style="<?php echo esc_attr( $button_style ); ?>">
								<?php esc_html_e( 'Enviar link de redefinição', 'guiawp' ); ?>
								<span class="material-symbols-outlined text-[18px]">send</span>
							</button>

							<?php if ( 'google_v3' === $reset_captcha['provider'] ) : ?>
								<p class="text-xs leading-5 text-slate-400"><?php echo wp_kses_post( $google_disclosure ); ?></p>
							<?php endif; ?>
						</form>

						<div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600">
							<?php esc_html_e( 'Se o e-mail informado estiver vinculado a uma conta, você receberá um link para redefinição sem precisar sair desta página.', 'guiawp' ); ?>
						</div>
					</div>
				</div>
			</section>
		</div>
	</div>
</main>

<style>
.gcep-auth-scene {
	perspective: 1600px;
}

.gcep-auth-card {
	transform-style: preserve-3d;
	transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}

.gcep-auth-card.is-flipped {
	transform: rotateY(180deg);
}

.gcep-auth-face {
	backface-visibility: hidden;
	-webkit-backface-visibility: hidden;
}

.gcep-auth-face--back {
	transform: rotateY(180deg);
}

.grecaptcha-badge {
	visibility: hidden !important;
	opacity: 0 !important;
}

@media (max-width: 767px) {
	.gcep-auth-card,
	.gcep-auth-face {
		min-height: 0;
	}

	.gcep-auth-card.is-flipped .gcep-auth-face--back,
	.gcep-auth-card:not(.is-flipped) .gcep-auth-face:first-child {
		position: relative;
	}

	.gcep-auth-card.is-flipped .gcep-auth-face:first-child,
	.gcep-auth-card:not(.is-flipped) .gcep-auth-face--back {
		display: none;
	}
}
</style>

<script>
(function () {
	var flipCard = document.getElementById('gcep-auth-flip-card');
	var openButtons = document.querySelectorAll('[data-auth-flip-open]');
	var closeButtons = document.querySelectorAll('[data-auth-flip-close]');
	var forgotForm = document.getElementById('gcep-forgot-password-form');
	var forgotError = document.getElementById('gcep-forgot-error');
	var forgotSuccess = document.getElementById('gcep-forgot-success');
	var forgotSuccessText = document.getElementById('gcep-forgot-success-text');
	var forgotSubmit = document.getElementById('gcep-forgot-submit');
	var loginForm = document.getElementById('gcep-login-form');
	var loginCaptchaError = document.getElementById('gcep-login-captcha-error');
	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var loginCaptchaConfig = <?php echo wp_json_encode( $login_captcha ); ?>;
	var resetCaptchaConfig = <?php echo wp_json_encode( $reset_captcha ); ?>;

	function showInlineError(box, message) {
		if (!box) return;
		box.textContent = message;
		box.classList.remove('hidden');
	}

	function clearInlineError(box) {
		if (!box) return;
		box.textContent = '';
		box.classList.add('hidden');
	}

	function ensureCaptcha(form, config, errorBox) {
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

	function setForgotMode(enabled) {
		if (!flipCard) return;
		flipCard.classList.toggle('is-flipped', enabled);
		if (!enabled && forgotForm) {
			forgotForm.classList.remove('hidden');
		}
		if (!enabled && forgotSuccess) {
			forgotSuccess.classList.add('hidden');
		}
		clearInlineError(forgotError);
	}

	openButtons.forEach(function (button) {
		button.addEventListener('click', function () {
			setForgotMode(true);
		});
	});

	closeButtons.forEach(function (button) {
		button.addEventListener('click', function () {
			setForgotMode(false);
		});
	});

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

	if (loginForm) {
		loginForm.addEventListener('submit', function (event) {
			if (loginForm.dataset.captchaReady === '1') {
				loginForm.dataset.captchaReady = '';
				return;
			}

			event.preventDefault();
			clearInlineError(loginCaptchaError);

			ensureCaptcha(loginForm, loginCaptchaConfig, loginCaptchaError)
				.then(function () {
					loginForm.dataset.captchaReady = '1';
					loginForm.submit();
				})
				.catch(function (message) {
					showInlineError(loginCaptchaError, message);
				});
		});
	}

	if (!forgotForm) return;

	forgotForm.addEventListener('submit', function (event) {
		event.preventDefault();

		clearInlineError(forgotError);

		var formData = new FormData(forgotForm);
		var email = (formData.get('email') || '').toString().trim();

		if (!email) {
			showInlineError(forgotError, <?php echo wp_json_encode( __( 'Digite seu e-mail para continuar.', 'guiawp' ) ); ?>);
			return;
		}

		if (forgotSubmit) {
			forgotSubmit.disabled = true;
			forgotSubmit.style.opacity = '0.7';
		}

		ensureCaptcha(forgotForm, resetCaptchaConfig, forgotError)
			.then(function () {
				formData = new FormData(forgotForm);
				formData.set('action', 'gcep_request_password_reset');

				return fetch(ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: new URLSearchParams(formData).toString()
				});
			})
			.then(function (response) {
				return response.json();
			})
			.then(function (result) {
				if (!result || !result.success || !result.data) {
					throw new Error((result && result.data && result.data.message) || <?php echo wp_json_encode( __( 'Não foi possível processar seu pedido agora.', 'guiawp' ) ); ?>);
				}

				if (forgotForm) {
					forgotForm.classList.add('hidden');
					forgotForm.reset();
				}

				if (forgotSuccessText) {
					forgotSuccessText.textContent = result.data.message || '';
				}

				if (forgotSuccess) {
					forgotSuccess.classList.remove('hidden');
				}
			})
			.catch(function (error) {
				showInlineError(forgotError, error.message || <?php echo wp_json_encode( __( 'Não foi possível processar seu pedido agora.', 'guiawp' ) ); ?>);
			})
			.finally(function () {
				if (forgotSubmit) {
					forgotSubmit.disabled = false;
					forgotSubmit.style.opacity = '';
				}
			});
	});
})();
</script>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
