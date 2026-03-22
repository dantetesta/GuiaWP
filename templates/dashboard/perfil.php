<?php
/**
 * Template: Perfil do Anunciante
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$user            = wp_get_current_user();
$avatar_url      = GCEP_Helpers::get_user_gravatar_url( $user, 96 );
$user_initials   = GCEP_Helpers::get_user_initials( $user );
$telefone        = get_user_meta( $user->ID, 'gcep_telefone', true );
$has_custom_avatar = (int) get_user_meta( $user->ID, 'gcep_avatar_id', true ) > 0;
$avatar_nonce    = wp_create_nonce( 'gcep_avatar_nonce' );
$msg             = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type        = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-sidebar.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<header class="h-14 lg:h-16 bg-white/80 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200 px-4 lg:px-8 flex items-center">
		<h2 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Perfil', 'guiawp' ); ?></h2>
	</header>

	<div class="p-4 lg:p-8 max-w-[980px] w-full mx-auto space-y-8">
		<div>
			<h1 class="text-3xl font-black tracking-tight text-slate-900"><?php esc_html_e( 'Meu Perfil', 'guiawp' ); ?></h1>
			<p class="text-slate-500 mt-2"><?php esc_html_e( 'Atualize seus dados de acesso sem precisar informar a senha atual.', 'guiawp' ); ?></p>
		</div>

		<?php if ( $msg ) : ?>
		<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
		<?php endif; ?>

		<section class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
			<div class="flex flex-col md:flex-row md:items-center gap-6">
				<div class="relative w-24 h-24 flex-shrink-0 group">
					<input type="file" id="gcep-avatar-input" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
					<div id="gcep-avatar-preview" class="w-24 h-24 rounded-full overflow-hidden border-2 border-slate-200 cursor-pointer" title="<?php esc_attr_e( 'Alterar foto', 'guiawp' ); ?>">
						<?php if ( $avatar_url ) : ?>
							<img
								id="gcep-avatar-img"
								src="<?php echo esc_url( $avatar_url ); ?>"
								alt="<?php echo esc_attr( $user->display_name ); ?>"
								class="w-full h-full object-cover"
								loading="lazy"
								onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
							>
						<?php endif; ?>
						<div id="gcep-avatar-initials" class="w-full h-full bg-primary/15 flex items-center justify-center text-primary text-3xl font-black <?php echo $avatar_url ? 'hidden' : ''; ?>">
							<?php echo esc_html( $user_initials ); ?>
						</div>
					</div>
					<button type="button" id="gcep-avatar-trigger" class="absolute inset-0 w-24 h-24 rounded-full bg-black/0 group-hover:bg-black/40 flex items-center justify-center transition-all cursor-pointer" title="<?php esc_attr_e( 'Alterar foto', 'guiawp' ); ?>">
						<span class="material-symbols-outlined text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity">photo_camera</span>
					</button>
					<div id="gcep-avatar-loading" class="absolute inset-0 w-24 h-24 rounded-full bg-black/50 items-center justify-center hidden">
						<span class="material-symbols-outlined text-white text-2xl animate-spin">progress_activity</span>
					</div>
				</div>

				<div class="min-w-0 flex-1">
					<h2 class="text-2xl font-black tracking-tight text-slate-900"><?php echo esc_html( $user->display_name ); ?></h2>
					<p class="text-slate-500 mt-1"><?php echo esc_html( $user->user_email ); ?></p>
					<div class="mt-3 flex flex-wrap items-center gap-3">
						<button type="button" id="gcep-avatar-upload-btn" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold hover:bg-primary/20 transition-colors cursor-pointer">
							<span class="material-symbols-outlined text-[14px]">upload</span>
							<?php esc_html_e( 'Enviar foto', 'guiawp' ); ?>
						</button>
						<button type="button" id="gcep-avatar-remove-btn" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-50 text-rose-600 text-xs font-bold hover:bg-rose-100 transition-colors cursor-pointer <?php echo $has_custom_avatar ? '' : 'hidden'; ?>">
							<span class="material-symbols-outlined text-[14px]">delete</span>
							<?php esc_html_e( 'Remover foto', 'guiawp' ); ?>
						</button>
					</div>
					<div class="mt-4 flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
						<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100">
							<span class="material-symbols-outlined text-[16px]">calendar_month</span>
							<?php printf( esc_html__( 'Membro desde %s', 'guiawp' ), esc_html( date_i18n( 'd/m/Y', strtotime( $user->user_registered ) ) ) ); ?>
						</span>
						<?php if ( ! empty( $telefone ) ) : ?>
							<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100">
								<span class="material-symbols-outlined text-[16px]">call</span>
								<?php echo esc_html( GCEP_Helpers::format_phone( $telefone ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
			<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-8">
				<div>
					<h2 class="text-xl font-black tracking-tight text-slate-900"><?php esc_html_e( 'Editar Perfil', 'guiawp' ); ?></h2>
					<p class="text-slate-500 mt-1"><?php esc_html_e( 'Altere nome, e-mail, telefone e senha pelo painel frontend.', 'guiawp' ); ?></p>
				</div>
				<div class="inline-flex items-start gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
					<span class="material-symbols-outlined text-[18px]">info</span>
					<span><?php esc_html_e( 'Você não precisa digitar a senha atual para trocar o acesso.', 'guiawp' ); ?></span>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-8">
				<input type="hidden" name="action" value="gcep_save_profile">
				<?php wp_nonce_field( 'gcep_save_profile', 'gcep_nonce' ); ?>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nome Completo', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
						<input type="text" name="gcep_nome" value="<?php echo esc_attr( $user->display_name ); ?>" class="rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Seu nome', 'guiawp' ); ?>">
					</label>

					<label class="flex flex-col gap-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'E-mail', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
						<input type="email" name="gcep_email" value="<?php echo esc_attr( $user->user_email ); ?>" class="rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'voce@exemplo.com', 'guiawp' ); ?>">
					</label>

					<label class="flex flex-col gap-2 md:col-span-2">
						<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Telefone', 'guiawp' ); ?></span>
						<input type="tel" name="gcep_telefone" data-intl-tel value="<?php echo esc_attr( GCEP_Helpers::format_phone( (string) $telefone ) ); ?>" class="rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( '(00) 9 9999-9999 ou (00) 9999-9999', 'guiawp' ); ?>">
					</label>
				</div>

				<div class="border-t border-slate-100 pt-8">
					<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
						<div>
							<h3 class="text-lg font-black tracking-tight text-slate-900"><?php esc_html_e( 'Alterar Senha', 'guiawp' ); ?></h3>
							<p class="text-slate-500 mt-1"><?php esc_html_e( 'Preencha apenas se quiser trocar sua senha atual.', 'guiawp' ); ?></p>
						</div>
						<button type="button" id="gcep-generate-password" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-colors">
							<span class="material-symbols-outlined text-[18px]">key</span>
							<?php esc_html_e( 'Gerar Senha', 'guiawp' ); ?>
						</button>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nova Senha', 'guiawp' ); ?></span>
							<div class="relative">
								<input type="password" id="gcep-new-password" name="gcep_nova_senha" class="w-full rounded-xl border-slate-200 bg-slate-50 p-4 pr-24 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Digite ou gere uma senha', 'guiawp' ); ?>" autocomplete="new-password">
								<button type="button" class="gcep-toggle-password absolute inset-y-0 right-3 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors" data-target="gcep-new-password" aria-label="<?php esc_attr_e( 'Mostrar senha', 'guiawp' ); ?>">
									<span class="material-symbols-outlined">visibility</span>
								</button>
							</div>
						</label>

						<label class="flex flex-col gap-2">
							<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Confirmar Nova Senha', 'guiawp' ); ?></span>
							<div class="relative">
								<input type="password" id="gcep-confirm-password" name="gcep_confirmar_senha" class="w-full rounded-xl border-slate-200 bg-slate-50 p-4 pr-24 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Repita a nova senha', 'guiawp' ); ?>" autocomplete="new-password">
								<button type="button" class="gcep-toggle-password absolute inset-y-0 right-3 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors" data-target="gcep-confirm-password" aria-label="<?php esc_attr_e( 'Mostrar senha', 'guiawp' ); ?>">
									<span class="material-symbols-outlined">visibility</span>
								</button>
							</div>
						</label>
					</div>

					<p class="mt-4 text-xs text-slate-400"><?php esc_html_e( 'Se os campos de senha ficarem em branco, sua senha atual será mantida.', 'guiawp' ); ?></p>
				</div>

				<div class="flex items-center justify-end gap-3">
					<button type="submit" class="inline-flex items-center justify-center gap-2 bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all">
						<span class="material-symbols-outlined text-[18px]">save</span>
						<?php esc_html_e( 'Salvar Alterações', 'guiawp' ); ?>
					</button>
				</div>
			</form>
		</section>

	<?php if ( ! current_user_can( 'manage_options' ) ) : ?>
	<section class="bg-white p-8 rounded-2xl shadow-sm border-2 border-rose-200">
		<div class="flex items-start gap-4 mb-6">
			<div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
				<span class="material-symbols-outlined text-2xl text-rose-500">warning</span>
			</div>
			<div>
				<h2 class="text-xl font-black tracking-tight text-rose-700"><?php esc_html_e( 'Zona de Perigo', 'guiawp' ); ?></h2>
				<p class="text-slate-500 mt-1"><?php esc_html_e( 'A exclusao da conta e irreversivel. Todos os seus anuncios, imagens e dados serao removidos permanentemente.', 'guiawp' ); ?></p>
			</div>
		</div>

		<div id="gcep-delete-account-container">
			<!-- Etapa 1: Solicitar senha -->
			<div id="gcep-delete-step-password" class="space-y-4">
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Confirme sua senha para continuar', 'guiawp' ); ?></span>
					<div class="relative">
						<input type="password" id="gcep-delete-password" class="w-full rounded-xl border-slate-200 bg-slate-50 p-4 pr-14 focus:ring-2 focus:ring-rose-200 outline-none" placeholder="<?php esc_attr_e( 'Digite sua senha atual', 'guiawp' ); ?>" autocomplete="current-password">
						<button type="button" class="gcep-toggle-delete-pw absolute inset-y-0 right-3 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors" aria-label="<?php esc_attr_e( 'Mostrar senha', 'guiawp' ); ?>">
							<span class="material-symbols-outlined">visibility</span>
						</button>
					</div>
				</label>
				<div id="gcep-delete-pw-error" class="hidden p-3 rounded-lg bg-rose-50 text-rose-700 text-sm font-medium border border-rose-200"></div>
				<button type="button" id="gcep-delete-request-code" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold transition-all">
					<span class="material-symbols-outlined text-[18px]">mail</span>
					<?php esc_html_e( 'Enviar codigo de confirmacao', 'guiawp' ); ?>
				</button>
			</div>

			<!-- Etapa 2: Codigo de email -->
			<div id="gcep-delete-step-code" class="hidden space-y-4">
				<div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
					<div class="flex items-center gap-2 font-bold mb-1">
						<span class="material-symbols-outlined text-[18px]">schedule</span>
						<?php esc_html_e( 'Codigo enviado!', 'guiawp' ); ?>
					</div>
					<p id="gcep-delete-code-msg"><?php esc_html_e( 'Verifique seu email e insira o codigo de 6 digitos abaixo.', 'guiawp' ); ?></p>
				</div>
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Codigo de confirmacao', 'guiawp' ); ?></span>
					<input type="text" id="gcep-delete-code" class="w-full max-w-[200px] rounded-xl border-slate-200 bg-slate-50 p-4 text-center text-2xl font-black tracking-[0.3em] focus:ring-2 focus:ring-rose-200 outline-none" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000" autocomplete="one-time-code">
				</label>
				<div id="gcep-delete-code-error" class="hidden p-3 rounded-lg bg-rose-50 text-rose-700 text-sm font-medium border border-rose-200"></div>
				<div class="flex items-center gap-3">
					<button type="button" id="gcep-delete-confirm" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold transition-all">
						<span class="material-symbols-outlined text-[18px]">delete_forever</span>
						<?php esc_html_e( 'Excluir minha conta', 'guiawp' ); ?>
					</button>
					<button type="button" id="gcep-delete-cancel" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-all">
						<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
					</button>
				</div>
			</div>

			<!-- Etapa 3: Processando / Concluido -->
			<div id="gcep-delete-step-done" class="hidden">
				<div class="p-6 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
					<span class="material-symbols-outlined text-4xl text-emerald-500 mb-2 block">check_circle</span>
					<p class="text-sm font-bold text-emerald-800"><?php esc_html_e( 'Conta excluida com sucesso. Redirecionando...', 'guiawp' ); ?></p>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	</div><!-- /.max-w-[980px] -->

	<script>
	(function() {
		var generateButton = document.getElementById('gcep-generate-password');
		var newPasswordInput = document.getElementById('gcep-new-password');
		var confirmPasswordInput = document.getElementById('gcep-confirm-password');

		function generatePassword(length) {
			var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*';
			var password = '';
			var values;
			var i;

			if (window.crypto && window.crypto.getRandomValues) {
				values = new Uint32Array(length);
				window.crypto.getRandomValues(values);
				for (i = 0; i < length; i += 1) {
					password += chars.charAt(values[i] % chars.length);
				}
				return password;
			}

			for (i = 0; i < length; i += 1) {
				password += chars.charAt(Math.floor(Math.random() * chars.length));
			}

			return password;
		}

		generateButton.addEventListener('click', function() {
			var generated = generatePassword(14);
			newPasswordInput.value = generated;
			confirmPasswordInput.value = generated;
			newPasswordInput.type = 'text';
			confirmPasswordInput.type = 'text';

			document.querySelectorAll('.gcep-toggle-password').forEach(function(button) {
				var icon = button.querySelector('.material-symbols-outlined');
				if (icon) {
					icon.textContent = 'visibility_off';
				}
			});

			newPasswordInput.focus();
			newPasswordInput.select();
		});

		document.querySelectorAll('.gcep-toggle-password').forEach(function(button) {
			button.addEventListener('click', function() {
				var target = document.getElementById(button.dataset.target);
				var icon = button.querySelector('.material-symbols-outlined');
				var isPassword = target.type === 'password';

				target.type = isPassword ? 'text' : 'password';

				if (icon) {
					icon.textContent = isPassword ? 'visibility_off' : 'visibility';
				}
			});
		});

		// Toggle senha do campo de exclusao
		var toggleDeletePw = document.querySelector('.gcep-toggle-delete-pw');
		if (toggleDeletePw) {
			toggleDeletePw.addEventListener('click', function() {
				var input = document.getElementById('gcep-delete-password');
				var icon  = toggleDeletePw.querySelector('.material-symbols-outlined');
				if (!input) return;
				var isPassword = input.type === 'password';
				input.type = isPassword ? 'text' : 'password';
				if (icon) icon.textContent = isPassword ? 'visibility_off' : 'visibility';
			});
		}

		// Zona de Perigo: fluxo de exclusao de conta
		var stepPassword = document.getElementById('gcep-delete-step-password');
		var stepCode     = document.getElementById('gcep-delete-step-code');
		var stepDone     = document.getElementById('gcep-delete-step-done');
		var btnRequest   = document.getElementById('gcep-delete-request-code');
		var btnConfirm   = document.getElementById('gcep-delete-confirm');
		var btnCancel    = document.getElementById('gcep-delete-cancel');
		var pwError      = document.getElementById('gcep-delete-pw-error');
		var codeError    = document.getElementById('gcep-delete-code-error');
		var codeMsg      = document.getElementById('gcep-delete-code-msg');

		function showError(el, msg) {
			if (!el) return;
			el.textContent = msg;
			el.classList.remove('hidden');
		}
		function hideError(el) {
			if (!el) return;
			el.classList.add('hidden');
			el.textContent = '';
		}
		function ajaxPost(action, data, onSuccess, onError) {
			var fd = new FormData();
			fd.append('action', action);
			fd.append('nonce', (window.gcepData && gcepData.nonce) || '');
			Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });

			var xhr = new XMLHttpRequest();
			xhr.open('POST', (window.gcepData && gcepData.ajaxUrl) || '/wp-admin/admin-ajax.php', true);
			xhr.onload = function() {
				try {
					var res = JSON.parse(xhr.responseText);
					if (res.success) { onSuccess(res.data); }
					else { onError(res.data && res.data.message ? res.data.message : 'Erro inesperado.'); }
				} catch(e) { onError('Erro inesperado.'); }
			};
			xhr.onerror = function() { onError('Erro de rede.'); };
			xhr.send(fd);
		}

		// Etapa 1: Enviar codigo
		if (btnRequest) {
			btnRequest.addEventListener('click', function() {
				hideError(pwError);
				var pw = (document.getElementById('gcep-delete-password') || {}).value || '';
				if (!pw.trim()) { showError(pwError, 'Informe sua senha.'); return; }

				btnRequest.disabled = true;
				btnRequest.querySelector('.material-symbols-outlined').textContent = 'hourglass_top';

				ajaxPost('gcep_request_delete_code', { password: pw }, function(data) {
					if (codeMsg) codeMsg.textContent = data.message || '';
					stepPassword.classList.add('hidden');
					stepCode.classList.remove('hidden');
					document.getElementById('gcep-delete-code').focus();
					btnRequest.disabled = false;
					btnRequest.querySelector('.material-symbols-outlined').textContent = 'mail';
				}, function(msg) {
					showError(pwError, msg);
					btnRequest.disabled = false;
					btnRequest.querySelector('.material-symbols-outlined').textContent = 'mail';
				});
			});
		}

		// Etapa 2: Confirmar exclusao
		if (btnConfirm) {
			btnConfirm.addEventListener('click', function() {
				hideError(codeError);
				var code = (document.getElementById('gcep-delete-code') || {}).value || '';
				if (!code.trim() || code.length < 6) { showError(codeError, 'Informe o codigo de 6 digitos.'); return; }

				var confirmMsg = 'ULTIMA CHANCE!\n\nSua conta, todos os anuncios e imagens serao excluidos permanentemente.\n\nDeseja realmente continuar?';
				if (!confirm(confirmMsg)) return;

				btnConfirm.disabled = true;
				btnConfirm.querySelector('.material-symbols-outlined').textContent = 'hourglass_top';

				ajaxPost('gcep_confirm_delete_account', { code: code }, function(data) {
					stepCode.classList.add('hidden');
					stepDone.classList.remove('hidden');
					setTimeout(function() {
						window.location.href = data.redirect || '<?php echo esc_url( home_url( '/' ) ); ?>';
					}, 2000);
				}, function(msg) {
					showError(codeError, msg);
					btnConfirm.disabled = false;
					btnConfirm.querySelector('.material-symbols-outlined').textContent = 'delete_forever';
				});
			});
		}

		// Cancelar: voltar para etapa 1
		if (btnCancel) {
			btnCancel.addEventListener('click', function() {
				stepCode.classList.add('hidden');
				stepPassword.classList.remove('hidden');
				hideError(pwError);
				hideError(codeError);
				var pwInput = document.getElementById('gcep-delete-password');
				if (pwInput) pwInput.value = '';
				var codeInput = document.getElementById('gcep-delete-code');
				if (codeInput) codeInput.value = '';
			});
		}
	})();

	// Avatar upload/remover
	(function() {
		var ajaxUrl    = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
		var nonce      = '<?php echo esc_attr( $avatar_nonce ); ?>';
		var fileInput  = document.getElementById('gcep-avatar-input');
		var trigger    = document.getElementById('gcep-avatar-trigger');
		var uploadBtn  = document.getElementById('gcep-avatar-upload-btn');
		var removeBtn  = document.getElementById('gcep-avatar-remove-btn');
		var preview    = document.getElementById('gcep-avatar-preview');
		var loading    = document.getElementById('gcep-avatar-loading');
		var imgEl      = document.getElementById('gcep-avatar-img');
		var initialsEl = document.getElementById('gcep-avatar-initials');

		if (!fileInput || !trigger) return;

		function openFilePicker() { fileInput.click(); }
		trigger.addEventListener('click', openFilePicker);
		if (uploadBtn) uploadBtn.addEventListener('click', openFilePicker);

		fileInput.addEventListener('change', function() {
			var file = fileInput.files[0];
			if (!file) return;

			if (file.size > 5 * 1024 * 1024) {
				gcepToast('<?php echo esc_js( __( 'Arquivo muito grande. Máximo 5MB.', 'guiawp' ) ); ?>', 'warning');
				fileInput.value = '';
				return;
			}

			loading.classList.remove('hidden');
			loading.classList.add('flex');

			var fd = new FormData();
			fd.append('action', 'gcep_upload_avatar');
			fd.append('nonce', nonce);
			fd.append('gcep_avatar', file);

			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, true);
			xhr.onload = function() {
				loading.classList.add('hidden');
				loading.classList.remove('flex');
				fileInput.value = '';
				try {
					var res = JSON.parse(xhr.responseText);
					if (res.success && res.data && res.data.url) {
						if (!imgEl) {
							imgEl = document.createElement('img');
							imgEl.id = 'gcep-avatar-img';
							imgEl.className = 'w-full h-full object-cover';
							preview.insertBefore(imgEl, initialsEl);
						}
						imgEl.src = res.data.url;
						imgEl.classList.remove('hidden');
						if (initialsEl) initialsEl.classList.add('hidden');
						if (removeBtn) removeBtn.classList.remove('hidden');
					} else {
						gcepToast(res.data && res.data.message ? res.data.message : <?php echo esc_js( __( 'Erro ao enviar a foto.', 'guiawp' ) ); ?>, 'error');
					}
				} catch(e) {
					gcepToast(<?php echo esc_js( __( 'Erro inesperado.', 'guiawp' ) ); ?>, 'error');
				}
			};
			xhr.onerror = function() {
				loading.classList.add('hidden');
				loading.classList.remove('flex');
				fileInput.value = '';
				gcepToast(<?php echo esc_js( __( 'Erro de rede.', 'guiawp' ) ); ?>, 'error');
			};
			xhr.send(fd);
		});

		if (removeBtn) {
			removeBtn.addEventListener('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Remover sua foto de perfil?', 'guiawp' ) ); ?>')) return;

				loading.classList.remove('hidden');
				loading.classList.add('flex');

				var fd = new FormData();
				fd.append('action', 'gcep_remove_avatar');
				fd.append('nonce', nonce);

				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxUrl, true);
				xhr.onload = function() {
					loading.classList.add('hidden');
					loading.classList.remove('flex');
					try {
						var res = JSON.parse(xhr.responseText);
						if (res.success) {
							if (imgEl) imgEl.classList.add('hidden');
							if (initialsEl) initialsEl.classList.remove('hidden');
							removeBtn.classList.add('hidden');
						}
					} catch(e) {}
				};
				xhr.onerror = function() {
					loading.classList.add('hidden');
					loading.classList.remove('flex');
				};
				xhr.send(fd);
			});
		}
	})();
	</script>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-footer.php'; ?>
