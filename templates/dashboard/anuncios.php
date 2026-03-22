<?php
/**
 * Template: Dashboard Anunciante - Meus Anúncios
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.8.9 - 2026-03-21
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$user_id  = get_current_user_id();
$paged    = max( 1, intval( $_GET['pg'] ?? 1 ) );
$result   = GCEP_Dashboard_Advertiser::get_user_anuncios_paged( $user_id, $paged, 10 );
$anuncios = $result['posts'];
$pages    = $result['pages'];
$total    = $result['total'];
$views_by_post = $result['views'] ?? [];
$msg      = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-sidebar.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<header class="h-14 lg:h-16 bg-white/80 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-3">
		<h2 class="text-base lg:text-lg font-bold text-slate-900 truncate"><?php esc_html_e( 'Meus Anúncios', 'guiawp' ); ?></h2>
		<a href="<?php echo esc_url( home_url( '/painel/criar-anuncio' ) ); ?>" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-3 lg:px-4 py-1.5 lg:py-2 rounded-lg font-bold text-xs flex items-center gap-1.5 shadow-sm hover:shadow-md transition-all flex-shrink-0">
			<span class="material-symbols-outlined text-base">add</span>
			<span class="hidden sm:inline"><?php esc_html_e( 'Criar Anúncio', 'guiawp' ); ?></span>
		</a>
	</header>

	<div class="p-4 lg:p-8 max-w-[1400px] w-full mx-auto space-y-8">

		<?php if ( $msg ) : ?>
		<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
		<?php endif; ?>

		<?php if ( ! empty( $anuncios ) ) : ?>
		<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full text-left border-collapse">
					<thead>
						<tr class="bg-slate-50">
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Anúncio', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell"><?php esc_html_e( 'Tipo', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:table-cell"><?php esc_html_e( 'Plano', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Status', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell"><?php esc_html_e( 'Views', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider hidden lg:table-cell"><?php esc_html_e( 'Data', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						<?php foreach ( $anuncios as $anuncio ) :
							$status = get_post_meta( $anuncio->ID, 'GCEP_status_anuncio', true ) ?: 'rascunho';
							$plano  = get_post_meta( $anuncio->ID, 'GCEP_tipo_plano', true ) ?: 'gratis';
							$tipo   = get_post_meta( $anuncio->ID, 'GCEP_tipo_anuncio', true ) ?: 'empresa';
							$justificativa = get_post_meta( $anuncio->ID, 'GCEP_ai_justificativa', true );
							$views_total = (int) ( $views_by_post[ $anuncio->ID ] ?? 0 );
							$can_edit_scripts = class_exists( 'GCEP_Anuncio_Custom_Scripts' ) && GCEP_Anuncio_Custom_Scripts::is_scripts_available_for_edit( $anuncio->ID );
						?>
						<tr class="hover:bg-slate-50/50 transition-colors group">
							<td class="px-4 lg:px-6 py-4">
								<p class="text-sm font-bold text-slate-900 group-hover:text-[#0052cc] transition-colors truncate max-w-[180px] lg:max-w-none"><?php echo esc_html( $anuncio->post_title ); ?></p>
								<p class="text-xs text-slate-400">ID: #<?php echo esc_html( $anuncio->ID ); ?></p>
							</td>
							<td class="px-4 lg:px-6 py-4 hidden md:table-cell">
								<span class="text-sm text-slate-600 font-medium"><?php echo esc_html( 'empresa' === $tipo ? __( 'Empresa', 'guiawp' ) : __( 'Profissional', 'guiawp' ) ); ?></span>
							</td>
							<td class="px-4 lg:px-6 py-4 hidden sm:table-cell">
								<span class="text-sm font-bold bg-slate-100 px-2.5 py-1 rounded-lg"><?php echo esc_html( ucfirst( $plano ) ); ?></span>
							</td>
							<td class="px-4 lg:px-6 py-4">
								<span class="inline-flex items-center gap-1.5 whitespace-nowrap border px-2.5 py-1 rounded-full text-xs font-bold <?php echo esc_attr( GCEP_Helpers::get_status_badge_classes( $status ) ); ?>">
									<?php echo esc_html( GCEP_Helpers::get_status_label_short( $status ) ); ?>
								</span>
							</td>
							<td class="px-4 lg:px-6 py-4 hidden md:table-cell">
								<div class="flex items-center gap-2 text-sm font-bold text-slate-700">
									<span class="material-symbols-outlined text-[18px] text-slate-400">visibility</span>
									<span><?php echo esc_html( number_format_i18n( $views_total ) ); ?></span>
								</div>
							</td>
							<td class="px-4 lg:px-6 py-4 text-sm text-slate-500 font-medium hidden lg:table-cell"><?php echo esc_html( get_the_date( 'd/m/Y', $anuncio ) ); ?></td>
							<td class="px-4 lg:px-6 py-4 text-right">
								<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
									<?php if ( 'rejeitado' === $status ) :
										$motivo_rejeicao = ! empty( $justificativa ) ? $justificativa : __( 'Rejeitado pelo administrador.', 'guiawp' );
									?>
									<button type="button" class="gcep-show-rejection-reason inline-flex items-center justify-center w-8 h-8 rounded-lg border border-rose-200 bg-rose-50 text-rose-500 hover:bg-rose-100 transition-colors" data-title="<?php echo esc_attr( $anuncio->post_title ); ?>" data-reason="<?php echo esc_attr( $motivo_rejeicao ); ?>" title="<?php esc_attr_e( 'Motivo da rejeição', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">info</span>
									</button>
									<?php endif; ?>
									<?php if ( $can_edit_scripts ) : ?>
									<button type="button" class="gcep-open-anuncio-scripts inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-title="<?php echo esc_attr( $anuncio->post_title ); ?>" title="<?php esc_attr_e( 'Scripts premium', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">code_blocks</span>
									</button>
									<?php endif; ?>
									<?php if ( 'aguardando_pagamento' === $status ) : ?>
									<a href="<?php echo esc_url( home_url( '/painel/pagamento?anuncio_id=' . $anuncio->ID ) ); ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-amber-300 bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors" title="<?php esc_attr_e( 'Pagar', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">payment</span>
									</a>
									<?php endif; ?>
									<?php if ( 'publicado' === $status ) : ?>
									<a href="<?php echo esc_url( get_permalink( $anuncio->ID ) ); ?>" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Ver Anúncio', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">visibility</span>
									</a>
									<?php endif; ?>
									<a href="<?php echo esc_url( home_url( '/painel/editar-anuncio?id=' . $anuncio->ID ) ); ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>">
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
			$base_url = home_url( '/painel/anuncios' );
			include GCEP_PLUGIN_DIR . 'templates/partials/pagination.php';
			?>
		</div>
		<?php else : ?>
		<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
			<span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">campaign</span>
			<h3 class="text-xl font-bold text-slate-900 mb-2"><?php esc_html_e( 'Nenhum anúncio ainda', 'guiawp' ); ?></h3>
			<p class="text-slate-500 mb-6"><?php esc_html_e( 'Crie seu primeiro anúncio agora.', 'guiawp' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/painel/criar-anuncio' ) ); ?>" class="inline-flex items-center gap-2 bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-primary/20 transition-all">
				<span class="material-symbols-outlined">add</span>
				<?php esc_html_e( 'Criar Anúncio', 'guiawp' ); ?>
			</a>
		</div>
		<?php endif; ?>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-rejection-modal.php'; ?>
<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-anuncio-scripts-modal.php'; ?>
<script>
(function () {
	var modal = document.getElementById('gcep-anuncio-scripts-modal');
	var form = document.getElementById('gcep-anuncio-scripts-form');
	var feedback = document.getElementById('gcep-anuncio-scripts-feedback');
	var loading = document.getElementById('gcep-anuncio-scripts-loading');
	var title = document.getElementById('gcep-anuncio-scripts-title');
	var hiddenId = document.getElementById('gcep-anuncio-scripts-id');
	var saveButton = document.getElementById('gcep-anuncio-scripts-save');
	if (!modal || !form || !feedback || !loading || !title || !hiddenId || !saveButton) return;

	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var nonce = <?php echo wp_json_encode( wp_create_nonce( 'gcep_anuncio_scripts' ) ); ?>;
	var defaultTitle = <?php echo wp_json_encode( __( 'Editar Scripts do Anúncio', 'guiawp' ) ); ?>;
	var originalSaveLabel = saveButton.innerHTML;

	function openModal() {
		modal.classList.remove('hidden');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		modal.classList.add('hidden');
		document.body.style.overflow = '';
		form.reset();
		hiddenId.value = '';
		title.textContent = defaultTitle;
		setFeedback('', '');
		setLoading(false);
		setBusy(false);
	}

	function setFeedback(type, message) {
		if (!message) {
			feedback.className = 'hidden rounded-2xl border px-4 py-3 text-sm font-medium';
			feedback.textContent = '';
			return;
		}

		var tone = type === 'error'
			? 'border-rose-200 bg-rose-50 text-rose-700'
			: 'border-emerald-200 bg-emerald-50 text-emerald-700';

		feedback.className = 'rounded-2xl border px-4 py-3 text-sm font-medium ' + tone;
		feedback.textContent = message;
	}

	function setLoading(active) {
		loading.classList.toggle('hidden', !active);
	}

	function setBusy(active, label) {
		Array.prototype.forEach.call(form.querySelectorAll('textarea, button'), function (element) {
			if (element.hasAttribute('data-gcep-scripts-close')) {
				return;
			}
			element.disabled = !!active;
		});

		saveButton.innerHTML = active && label
			? '<span class="inline-block w-4 h-4 rounded-full border-2 border-white/50 border-t-white animate-spin"></span><span>' + label + '</span>'
			: originalSaveLabel;
	}

	function request(action, payload) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', nonce);
		Object.keys(payload).forEach(function (key) {
			body.append(key, payload[key] || '');
		});

		return fetch(ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		}).then(function (response) {
			return response.json();
		});
	}

	function populateFields(scripts) {
		form.elements.head.value = scripts && scripts.head ? scripts.head : '';
		form.elements.body_start.value = scripts && scripts.body_start ? scripts.body_start : '';
		form.elements.body_end.value = scripts && scripts.body_end ? scripts.body_end : '';
	}

	Array.prototype.forEach.call(document.querySelectorAll('.gcep-open-anuncio-scripts'), function (button) {
		button.addEventListener('click', function () {
			var anuncioId = this.getAttribute('data-id');
			var anuncioTitle = this.getAttribute('data-title') || '';
			if (!anuncioId) return;

			openModal();
			title.textContent = anuncioTitle ? '<?php echo esc_js( __( 'Scripts Premium: ', 'guiawp' ) ); ?>' + anuncioTitle : defaultTitle;
			hiddenId.value = anuncioId;
			populateFields({});
			setFeedback('', '');
			setLoading(true);
			setBusy(true, <?php echo wp_json_encode( __( 'Carregando...', 'guiawp' ) ); ?>);

			request('gcep_get_anuncio_scripts', { anuncio_id: anuncioId })
				.then(function (result) {
					if (!result || !result.success) {
						throw new Error(result && result.data && result.data.message ? result.data.message : '<?php echo esc_js( __( 'Não foi possível carregar os scripts do anúncio.', 'guiawp' ) ); ?>');
					}

					populateFields(result.data && result.data.scripts ? result.data.scripts : {});
				})
				.catch(function (error) {
					setFeedback('error', error.message || '<?php echo esc_js( __( 'Não foi possível carregar os scripts do anúncio.', 'guiawp' ) ); ?>');
				})
				.finally(function () {
					setLoading(false);
					setBusy(false);
				});
		});
	});

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		if (!hiddenId.value) return;

		setFeedback('', '');
		setBusy(true, <?php echo wp_json_encode( __( 'Salvando...', 'guiawp' ) ); ?>);

		request('gcep_save_anuncio_scripts', {
			anuncio_id: hiddenId.value,
			head: form.elements.head.value,
			body_start: form.elements.body_start.value,
			body_end: form.elements.body_end.value
		})
			.then(function (result) {
				if (!result || !result.success) {
					throw new Error(result && result.data && result.data.message ? result.data.message : '<?php echo esc_js( __( 'Não foi possível salvar os scripts.', 'guiawp' ) ); ?>');
				}

				setFeedback('success', result.data && result.data.message ? result.data.message : '<?php echo esc_js( __( 'Scripts premium salvos com sucesso.', 'guiawp' ) ); ?>');
			})
			.catch(function (error) {
				setFeedback('error', error.message || '<?php echo esc_js( __( 'Não foi possível salvar os scripts.', 'guiawp' ) ); ?>');
			})
			.finally(function () {
				setBusy(false);
			});
	});

	Array.prototype.forEach.call(document.querySelectorAll('[data-gcep-scripts-close]'), function (element) {
		element.addEventListener('click', closeModal);
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
			closeModal();
		}
	});
})();
</script>
<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-footer.php'; ?>
