<?php
/**
 * Template: Admin - Gestão de Usuários
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.2 - 2026-03-21 - Botoes de acao com tamanho fixo 34px, gap corrigido com inline style, hover com cores de contexto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$current_page = max( 1, absint( $_GET['pagina'] ?? 1 ) );
$per_page     = 15;
$usuarios_url   = home_url( '/painel-admin/usuarios' );
$search_digits  = GCEP_Helpers::sanitize_phone( $search_query );
$count_users    = count_users();
$total_all_users = (int) ( $count_users['avail_roles']['gcep_anunciante'] ?? 0 );

$query_args = [
	'role'        => 'gcep_anunciante',
	'orderby'     => 'registered',
	'order'       => 'DESC',
	'number'      => $per_page,
	'paged'       => $current_page,
	'count_total' => true,
];

$search_is_phone = '' !== $search_digits && strlen( $search_digits ) >= 8 && 1 === preg_match( '/^[0-9\-\+\(\)\s]+$/', $search_query );

if ( '' !== $search_query ) {
	if ( $search_is_phone ) {
		$query_args['meta_query'] = [
			[
				'key'     => 'gcep_telefone',
				'value'   => $search_digits,
				'compare' => 'LIKE',
			],
		];
	} else {
		$query_args['search']         = '*' . $search_query . '*';
		$query_args['search_columns'] = [ 'user_login', 'user_nicename', 'user_email', 'display_name' ];
	}
}

$user_query     = new WP_User_Query( $query_args );
$users          = $user_query->get_results();
$total_filtered = (int) $user_query->get_total();
$total_pages    = max( 1, (int) ceil( $total_filtered / $per_page ) );
$normalized_page = min( $current_page, $total_pages );

if ( $normalized_page !== $current_page ) {
	$current_page         = $normalized_page;
	$query_args['paged']  = $current_page;
	$user_query           = new WP_User_Query( $query_args );
	$users                = $user_query->get_results();
} else {
	$current_page = $normalized_page;
}

$anuncio_counts = [];
$user_ids       = array_values( array_filter( array_map( static fn( $user ) => (int) $user->ID, $users ) ) );

if ( ! empty( $user_ids ) ) {
	global $wpdb;
	$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_author, COUNT(*) AS total
			FROM {$wpdb->posts}
			WHERE post_type = 'gcep_anuncio'
			AND post_status IN (%s, %s)
			AND post_author IN ($placeholders)
			GROUP BY post_author",
			...array_merge( GCEP_Helpers::get_manageable_anuncio_post_statuses(), $user_ids )
		),
		ARRAY_A
	);

	foreach ( $rows as $row ) {
		$anuncio_counts[ (int) $row['post_author'] ] = (int) $row['total'];
	}
}

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="lg:ml-64 flex-1 p-4 lg:p-8 min-w-0 overflow-x-hidden">
	<header class="mb-8 flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
		<div>
			<h2 class="text-3xl font-black tracking-tight mb-2"><?php esc_html_e( 'Usuários Anunciantes', 'guiawp' ); ?></h2>
			<p class="text-slate-500">
				<?php
				if ( '' !== $search_query ) {
					printf(
						esc_html__( '%1$d resultado(s) de %2$d anunciantes', 'guiawp' ),
						$total_filtered,
						$total_all_users
					);
				} else {
					printf( esc_html__( '%d anunciantes cadastrados', 'guiawp' ), $total_all_users );
				}
				?>
			</p>
		</div>
	</header>


	<section class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
		<form method="get" action="<?php echo esc_url( $usuarios_url ); ?>" class="flex flex-col lg:flex-row lg:items-end gap-4">
			<label class="flex-1 flex flex-col gap-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Pesquisar Usuários', 'guiawp' ); ?></span>
				<div class="relative">
					<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
					<input type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Buscar por nome, e-mail ou telefone...', 'guiawp' ); ?>">
				</div>
			</label>
			<div class="flex gap-3">
				<button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-700 transition-colors">
					<?php esc_html_e( 'Pesquisar', 'guiawp' ); ?>
				</button>
				<a href="<?php echo esc_url( $usuarios_url ); ?>" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
					<?php esc_html_e( 'Limpar', 'guiawp' ); ?>
				</a>
			</div>
		</form>
	</section>

	<section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
		<div class="overflow-x-auto">
			<table class="w-full text-left">
				<thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
					<tr>
						<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Usuário', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 hidden md:table-cell"><?php esc_html_e( 'Telefone', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Anúncios', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 hidden lg:table-cell"><?php esc_html_e( 'Cadastro', 'guiawp' ); ?></th>
						<th class="px-4 lg:px-6 py-4 text-right"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					<?php if ( ! empty( $users ) ) : ?>
						<?php foreach ( $users as $u ) : ?>
							<?php
							$total_anuncios = (int) ( $anuncio_counts[ $u->ID ] ?? 0 );
							$telefone       = get_user_meta( $u->ID, 'gcep_telefone', true );
							$avatar_url     = GCEP_Helpers::get_user_gravatar_url( $u, 40 );
							$user_initials  = GCEP_Helpers::get_user_initials( $u );
							?>
							<tr class="hover:bg-slate-50/60 transition-colors">
								<td class="px-4 lg:px-6 py-4">
									<div class="flex items-center gap-3">
										<div class="relative w-10 h-10 flex-shrink-0">
											<?php if ( $avatar_url ) : ?>
												<img
													src="<?php echo esc_url( $avatar_url ); ?>"
													alt="<?php echo esc_attr( $u->display_name ); ?>"
													class="w-10 h-10 rounded-full object-cover"
													loading="lazy"
													onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
												>
											<?php endif; ?>
											<div class="w-10 h-10 rounded-full bg-[#0052cc]/20 flex items-center justify-center text-[#0052cc] text-sm font-bold <?php echo $avatar_url ? 'hidden' : ''; ?>">
												<?php echo esc_html( $user_initials ); ?>
											</div>
										</div>
										<div>
											<p class="text-sm font-bold text-slate-900 truncate"><?php echo esc_html( $u->display_name ); ?></p>
											<p class="text-xs text-slate-500 truncate"><?php echo esc_html( $u->user_email ); ?></p>
										</div>
									</div>
								</td>
								<td class="px-4 lg:px-6 py-4 text-sm text-slate-600 hidden md:table-cell"><?php echo esc_html( $telefone ? GCEP_Helpers::format_phone( $telefone ) : '—' ); ?></td>
								<td class="px-4 lg:px-6 py-4 text-sm font-bold text-slate-900"><?php echo esc_html( $total_anuncios ); ?></td>
								<td class="px-4 lg:px-6 py-4 text-sm text-slate-500 hidden lg:table-cell"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $u->user_registered ) ) ); ?></td>
								<td class="px-4 lg:px-6 py-4 text-right">
									<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
										<button
											type="button"
											class="gcep-open-password-modal inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors"
											data-user-id="<?php echo esc_attr( $u->ID ); ?>"
											data-user-name="<?php echo esc_attr( $u->display_name ); ?>"
											data-user-email="<?php echo esc_attr( $u->user_email ); ?>"
											title="<?php esc_attr_e( 'Alterar Senha', 'guiawp' ); ?>"
										>
											<span class="material-symbols-outlined" style="font-size:18px;">key</span>
										</button>
										<button
											type="button"
											class="gcep-delete-user-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors"
											data-user-id="<?php echo esc_attr( $u->ID ); ?>"
											data-user-name="<?php echo esc_attr( $u->display_name ); ?>"
											data-user-anuncios="<?php echo esc_attr( $total_anuncios ); ?>"
											title="<?php esc_attr_e( 'Excluir Usuário', 'guiawp' ); ?>"
										>
											<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" class="px-6 py-16 text-center text-slate-400">
								<span class="material-symbols-outlined text-5xl block mb-3">group</span>
								<?php echo '' !== $search_query ? esc_html__( 'Nenhum usuário encontrado para essa pesquisa.', 'guiawp' ) : esc_html__( 'Nenhum usuário anunciante cadastrado.', 'guiawp' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<?php
		$paged    = $current_page;
		$pages    = $total_pages;
		$total    = $total_filtered;
		$base_url = $usuarios_url . ( '' !== $search_query ? '?s=' . urlencode( $search_query ) : '' );
		// Usa 'pg' como param para manter consistência com os outros templates
		$pg_param = 'pagina';
		include GCEP_PLUGIN_DIR . 'templates/partials/pagination-usuarios.php';
		?>
	</section>

	<div id="gcep-user-password-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
		<div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
			<div class="px-6 py-5 border-b border-slate-200 flex items-start justify-between gap-4">
				<div>
					<p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-2"><?php esc_html_e( 'Senha do Usuário', 'guiawp' ); ?></p>
					<h3 id="gcep-user-password-title" class="text-xl font-black tracking-tight text-slate-900"></h3>
					<p id="gcep-user-password-email" class="text-sm text-slate-500 mt-1"></p>
				</div>
				<button type="button" id="gcep-user-password-close" class="w-10 h-10 inline-flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<div class="px-6 py-6 space-y-6">
				<div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
					<?php esc_html_e( 'Defina uma nova senha, copie se necessário e envie ao usuário um aviso com link seguro usando o SMTP já configurado no WordPress.', 'guiawp' ); ?>
				</div>

				<input type="hidden" id="gcep-user-password-id" value="0">

				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nova Senha', 'guiawp' ); ?></span>
					<div class="relative">
						<input type="password" id="gcep-user-password-value" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 pr-24 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Digite ou gere uma senha', 'guiawp' ); ?>">
						<button type="button" class="gcep-toggle-password absolute inset-y-0 right-3 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors" data-target="gcep-user-password-value">
							<span class="material-symbols-outlined">visibility</span>
						</button>
					</div>
				</label>

				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Confirmar Senha', 'guiawp' ); ?></span>
					<div class="relative">
						<input type="password" id="gcep-user-password-confirm" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 pr-24 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Repita a nova senha', 'guiawp' ); ?>">
						<button type="button" class="gcep-toggle-password absolute inset-y-0 right-3 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors" data-target="gcep-user-password-confirm">
							<span class="material-symbols-outlined">visibility</span>
						</button>
					</div>
				</label>

				<div class="flex flex-wrap gap-3">
					<button type="button" id="gcep-user-password-generate" class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-colors">
						<span class="material-symbols-outlined text-[18px]">key</span>
						<?php esc_html_e( 'Gerar Senha', 'guiawp' ); ?>
					</button>
					<button type="button" id="gcep-user-password-copy" class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-colors">
						<span class="material-symbols-outlined text-[18px]">content_copy</span>
						<?php esc_html_e( 'Copiar Senha', 'guiawp' ); ?>
					</button>
				</div>
			</div>

			<div class="px-6 py-5 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
				<button type="button" id="gcep-user-password-cancel" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
					<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
				</button>
				<div class="flex flex-wrap justify-end gap-3">
					<button type="button" id="gcep-user-password-save" class="px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-700 transition-colors">
						<?php esc_html_e( 'Salvar Senha', 'guiawp' ); ?>
					</button>
					<button type="button" id="gcep-user-password-save-email" class="px-6 py-3 rounded-xl bg-[#0052cc] text-white text-sm font-bold hover:bg-[#003d99] transition-colors">
						<?php esc_html_e( 'Salvar e Enviar por E-mail', 'guiawp' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<!-- Modal: Confirmar exclusao de usuario -->
	<div id="gcep-delete-user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
		<div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
			<div class="px-6 py-5 border-b border-slate-200">
				<div class="flex items-center gap-3 mb-3">
					<div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
						<span class="material-symbols-outlined text-rose-600 text-xl">warning</span>
					</div>
					<h3 class="text-lg font-black text-slate-900"><?php esc_html_e( 'Excluir Usuário', 'guiawp' ); ?></h3>
				</div>
				<p class="text-sm text-slate-600"><?php esc_html_e( 'Esta ação é irreversível. Todos os dados do usuário serão removidos permanentemente.', 'guiawp' ); ?></p>
			</div>

			<div class="px-6 py-5 space-y-3">
				<div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
					<p class="text-sm font-bold text-slate-900" id="gcep-delete-user-name"></p>
					<p class="text-xs text-slate-500 mt-1" id="gcep-delete-user-detail"></p>
				</div>

				<div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700 space-y-1">
					<p class="font-bold"><?php esc_html_e( 'Serão removidos:', 'guiawp' ); ?></p>
					<ul class="list-disc list-inside text-xs space-y-0.5">
						<li><?php esc_html_e( 'Todos os anúncios do usuário', 'guiawp' ); ?></li>
						<li><?php esc_html_e( 'Todas as imagens e mídias vinculadas', 'guiawp' ); ?></li>
						<li><?php esc_html_e( 'Metadados e registros de analytics', 'guiawp' ); ?></li>
						<li><?php esc_html_e( 'A conta WordPress do usuário', 'guiawp' ); ?></li>
					</ul>
				</div>

				<input type="hidden" id="gcep-delete-user-id" value="0">
			</div>

			<div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-3">
				<button type="button" id="gcep-delete-user-cancel" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
					<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
				</button>
				<button type="button" id="gcep-delete-user-confirm" class="px-5 py-2.5 rounded-xl bg-rose-600 text-white text-sm font-bold hover:bg-rose-700 transition-colors">
					<?php esc_html_e( 'Excluir Permanentemente', 'guiawp' ); ?>
				</button>
			</div>
		</div>
	</div>
</main>
</div>

<script>
(function() {
	var modal = document.getElementById('gcep-user-password-modal');
	var titleEl = document.getElementById('gcep-user-password-title');
	var emailEl = document.getElementById('gcep-user-password-email');
	var userIdInput = document.getElementById('gcep-user-password-id');
	var passwordInput = document.getElementById('gcep-user-password-value');
	var confirmInput = document.getElementById('gcep-user-password-confirm');
	var copyButton = document.getElementById('gcep-user-password-copy');
	var activeTrigger = null;
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';


		feedback.textContent = message;
		feedback.className = classes;

		setTimeout(function() {
			feedback.className = 'hidden mb-6 p-4 rounded-lg text-sm font-medium';
		}, 5000);
	}

	function resetModal() {
		userIdInput.value = '0';
		passwordInput.value = '';
		confirmInput.value = '';
		document.querySelectorAll('.gcep-toggle-password').forEach(function(button) {
			var target = document.getElementById(button.dataset.target);
			var icon = button.querySelector('.material-symbols-outlined');
			if (target) target.type = 'password';
			if (icon) icon.textContent = 'visibility';
		});
	}

	function openModal(button) {
		activeTrigger = button;
		resetModal();
		userIdInput.value = button.dataset.userId || '0';
		titleEl.textContent = button.dataset.userName || '';
		emailEl.textContent = button.dataset.userEmail || '';
		modal.classList.remove('hidden');
		document.body.classList.add('overflow-hidden');
		passwordInput.focus();
	}

	function closeModal() {
		modal.classList.add('hidden');
		document.body.classList.remove('overflow-hidden');
		if (activeTrigger) {
			activeTrigger.focus();
		}
	}

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

	function parseAjaxResponse(response) {
		return response.text().then(function(text) {
			var data;
			try {
				data = JSON.parse(text);
			} catch (err) {
				throw new Error(text || 'Resposta inválida do servidor.');
			}
			return data;
		});
	}

	function flashCopySuccess() {
		var original = copyButton.innerHTML;
		copyButton.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span><?php echo esc_js( __( 'Copiada', 'guiawp' ) ); ?>';
		setTimeout(function() {
			copyButton.innerHTML = original;
		}, 1600);
	}

	function fallbackCopyText(text) {
		var helper = document.createElement('textarea');

		helper.value = text;
		helper.setAttribute('readonly', 'readonly');
		helper.style.position = 'fixed';
		helper.style.opacity = '0';
		helper.style.pointerEvents = 'none';

		document.body.appendChild(helper);
		helper.focus();
		helper.select();
		helper.setSelectionRange(0, helper.value.length);

		try {
			return document.execCommand('copy');
		} catch (error) {
			return false;
		} finally {
			document.body.removeChild(helper);
		}
	}

	function submitPassword(sendEmail, button) {
		var password = passwordInput.value;
		var confirm = confirmInput.value;
		var originalText = button.textContent;
		var body = new URLSearchParams();

		if (!password || password.length < 6) {
			gcepToast('<?php echo esc_js( __( 'A senha deve ter pelo menos 6 caracteres.', 'guiawp' ) ); ?>', 'error');
			passwordInput.focus();
			return;
		}

		if (password !== confirm) {
			gcepToast('<?php echo esc_js( __( 'A confirmação da senha não confere.', 'guiawp' ) ); ?>', 'error');
			confirmInput.focus();
			return;
		}

		button.disabled = true;
		button.textContent = sendEmail ? '<?php echo esc_js( __( 'Salvando e enviando...', 'guiawp' ) ); ?>' : '<?php echo esc_js( __( 'Salvando...', 'guiawp' ) ); ?>';

		body.append('action', 'gcep_admin_update_user_password');
		body.append('nonce', nonce);
		body.append('user_id', userIdInput.value);
		body.append('password', password);
		body.append('send_email', sendEmail ? '1' : '0');

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
		.then(parseAjaxResponse)
		.then(function(res) {
			button.disabled = false;
			button.textContent = originalText;

			if (!res.success) {
				throw new Error(res.data && res.data.message ? res.data.message : 'Erro ao atualizar senha.');
			}

			gcepToast(res.data.message, res.data.message_type || 'success');
			if (res.data.message_type !== 'warning') {
				closeModal();
			}
		})
		.catch(function(error) {
			button.disabled = false;
			button.textContent = originalText;
			gcepToast(error.message || 'Erro ao atualizar senha.', 'error');
		});
	}

	document.addEventListener('click', function(event) {
		var openButton = event.target.closest('.gcep-open-password-modal');
		if (openButton) {
			event.preventDefault();
			openModal(openButton);
			return;
		}

		if (event.target === modal) {
			closeModal();
		}
	});

	document.getElementById('gcep-user-password-close').addEventListener('click', closeModal);
	document.getElementById('gcep-user-password-cancel').addEventListener('click', closeModal);

	document.getElementById('gcep-user-password-generate').addEventListener('click', function() {
		var generated = generatePassword(14);
		passwordInput.value = generated;
		confirmInput.value = generated;
		passwordInput.type = 'text';
		confirmInput.type = 'text';

		document.querySelectorAll('.gcep-toggle-password').forEach(function(button) {
			var icon = button.querySelector('.material-symbols-outlined');
			if (icon) icon.textContent = 'visibility_off';
		});

		passwordInput.focus();
		passwordInput.select();
	});

	copyButton.addEventListener('click', function() {
		var password = passwordInput.value;
		if (!password) {
			gcepToast('<?php echo esc_js( __( 'Gere ou digite uma senha antes de copiar.', 'guiawp' ) ); ?>', 'error');
			return;
		}

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(password).then(function() {
				flashCopySuccess();
			}).catch(function() {
				if (fallbackCopyText(password)) {
					flashCopySuccess();
					return;
				}

				gcepToast('<?php echo esc_js( __( 'Não foi possível copiar a senha automaticamente.', 'guiawp' ) ); ?>', 'error');
			});
			return;
		}

		if (fallbackCopyText(password)) {
			flashCopySuccess();
			return;
		}

		gcepToast('<?php echo esc_js( __( 'Não foi possível copiar a senha automaticamente.', 'guiawp' ) ); ?>', 'error');
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

	document.getElementById('gcep-user-password-save').addEventListener('click', function() {
		submitPassword(false, this);
	});

	document.getElementById('gcep-user-password-save-email').addEventListener('click', function() {
		submitPassword(true, this);
	});

	document.addEventListener('keydown', function(event) {
		if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
			closeModal();
		}
	});
})();

// === Exclusao de usuario ===
(function() {
	var delModal    = document.getElementById('gcep-delete-user-modal');
	var delNameEl   = document.getElementById('gcep-delete-user-name');
	var delDetailEl = document.getElementById('gcep-delete-user-detail');
	var delIdInput  = document.getElementById('gcep-delete-user-id');
	var delConfirm  = document.getElementById('gcep-delete-user-confirm');
	var delCancel   = document.getElementById('gcep-delete-user-cancel');
	var ajaxUrl     = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce       = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';

	if (!delModal) return;

	function openDeleteModal(btn) {
		var userId   = btn.dataset.userId;
		var userName = btn.dataset.userName;
		var anuncios = btn.dataset.userAnuncios || '0';
		delIdInput.value = userId;
		delNameEl.textContent = userName;
		delDetailEl.textContent = anuncios + ' anúncio(s) serão removidos junto com todas as mídias';
		delModal.classList.remove('hidden');
		document.body.classList.add('overflow-hidden');
	}

	function closeDeleteModal() {
		delModal.classList.add('hidden');
		document.body.classList.remove('overflow-hidden');
	}

	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.gcep-delete-user-btn');
		if (btn) { openDeleteModal(btn); return; }
		if (e.target === delModal) { closeDeleteModal(); }
	});

	delCancel.addEventListener('click', closeDeleteModal);

	delConfirm.addEventListener('click', function() {
		var userId = delIdInput.value;
		var originalText = delConfirm.textContent;
		delConfirm.disabled = true;
		delConfirm.textContent = '<?php echo esc_js( __( 'Excluindo...', 'guiawp' ) ); ?>';

		var body = new URLSearchParams();
		body.append('action', 'gcep_admin_delete_user');
		body.append('nonce', nonce);
		body.append('user_id', userId);

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			delConfirm.disabled = false;
			delConfirm.textContent = originalText;

			if (!res.success) {
				gcepToast(res.data && res.data.message ? res.data.message : 'Erro ao excluir.', 'error');
				return;
			}

			closeDeleteModal();
			gcepToast(res.data.message, 'success');

			// Remover a linha da tabela
			var row = document.querySelector('tr .gcep-delete-user-btn[data-user-id="' + userId + '"]');
			if (row) {
				var tr = row.closest('tr');
				if (tr) tr.remove();
			}
		})
		.catch(function(err) {
			delConfirm.disabled = false;
			delConfirm.textContent = originalText;
			gcepToast(err.message || 'Erro ao excluir.', 'error');
		});
	});

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && !delModal.classList.contains('hidden')) {
			closeDeleteModal();
		}
	});
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
