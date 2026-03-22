<?php
/**
 * Template: Admin - Gestão de Planos Premium
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$plans = GCEP_Plans::get_all();

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="lg:ml-64 flex-1 p-4 lg:p-8 min-w-0 overflow-x-hidden">
	<header class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
		<div>
			<h2 class="text-3xl font-black tracking-tight mb-2"><?php esc_html_e( 'Planos Premium', 'guiawp' ); ?></h2>
			<p class="text-slate-500"><?php esc_html_e( 'Gerencie os planos de vigência para anúncios premium.', 'guiawp' ); ?></p>
		</div>
		<button type="button" id="gcep-add-plan" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-primary/20 transition-all">
			<span class="material-symbols-outlined">add</span>
			<?php esc_html_e( 'Novo Plano', 'guiawp' ); ?>
		</button>
	</header>

	<!-- Formulário de criação/edição -->
	<div id="gcep-plan-form" class="hidden bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
		<h3 id="gcep-plan-form-title" class="text-lg font-bold mb-6"><?php esc_html_e( 'Novo Plano', 'guiawp' ); ?></h3>
		<input type="hidden" id="gcep-plan-id" value="0">
		<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
			<label class="flex flex-col gap-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nome do Plano', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
				<input type="text" id="gcep-plan-name" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Ex: Plano Mensal', 'guiawp' ); ?>">
			</label>
			<label class="flex flex-col gap-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Vigência (dias)', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
				<input type="number" id="gcep-plan-days" min="1" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="30">
			</label>
			<label class="flex flex-col gap-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Preço (R$)', 'guiawp' ); ?></span>
				<input type="number" id="gcep-plan-price" min="0" step="0.01" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="0.00">
			</label>
			<label class="flex flex-col gap-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Status', 'guiawp' ); ?></span>
				<select id="gcep-plan-status" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none">
					<option value="active"><?php esc_html_e( 'Ativo', 'guiawp' ); ?></option>
					<option value="inactive"><?php esc_html_e( 'Inativo', 'guiawp' ); ?></option>
				</select>
			</label>
			<label class="flex flex-col gap-2 md:col-span-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Descrição', 'guiawp' ); ?></span>
				<textarea id="gcep-plan-description" rows="2" class="rounded-lg border-slate-200 bg-slate-50 p-3 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="<?php esc_attr_e( 'Descrição breve do plano...', 'guiawp' ); ?>"></textarea>
			</label>
		</div>
		<div class="flex gap-3">
			<button type="button" id="gcep-plan-save" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-2.5 rounded-lg font-bold text-sm transition-colors"><?php esc_html_e( 'Salvar', 'guiawp' ); ?></button>
			<button type="button" id="gcep-plan-cancel" class="border border-slate-200 text-slate-600 px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-100 transition-colors"><?php esc_html_e( 'Cancelar', 'guiawp' ); ?></button>
		</div>
	</div>

	<!-- Lista de planos -->
	<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
		<div class="overflow-x-auto">
			<table class="w-full text-left border-collapse">
				<thead>
					<tr class="bg-slate-50">
						<th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Plano', 'guiawp' ); ?></th>
						<th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Vigência', 'guiawp' ); ?></th>
						<th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Preço', 'guiawp' ); ?></th>
						<th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Status', 'guiawp' ); ?></th>
						<th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
					</tr>
				</thead>
				<tbody id="gcep-plans-list" class="divide-y divide-slate-100">
					<?php if ( empty( $plans ) ) : ?>
					<tr id="gcep-no-plans">
						<td colspan="5" class="px-6 py-12 text-center text-slate-400">
							<span class="material-symbols-outlined text-4xl block mb-2">workspace_premium</span>
							<?php esc_html_e( 'Nenhum plano cadastrado.', 'guiawp' ); ?>
						</td>
					</tr>
					<?php else : ?>
						<?php foreach ( $plans as $plan ) : ?>
						<tr data-plan-id="<?php echo esc_attr( $plan['id'] ); ?>" class="hover:bg-slate-50/50 transition-colors">
							<td class="px-6 py-4">
								<p class="text-sm font-bold text-slate-900"><?php echo esc_html( $plan['name'] ); ?></p>
								<?php if ( ! empty( $plan['description'] ) ) : ?>
									<p class="text-xs text-slate-400 mt-1"><?php echo esc_html( $plan['description'] ); ?></p>
								<?php endif; ?>
							</td>
							<td class="px-6 py-4">
								<span class="text-sm font-medium"><?php echo esc_html( GCEP_Plans::format_duration( (int) $plan['days'] ) ); ?></span>
								<span class="text-xs text-slate-400 block"><?php printf( esc_html__( '%d dias', 'guiawp' ), (int) $plan['days'] ); ?></span>
							</td>
							<td class="px-6 py-4">
								<span class="text-sm font-bold text-slate-900">R$ <?php echo esc_html( number_format( (float) $plan['price'], 2, ',', '.' ) ); ?></span>
							</td>
							<td class="px-6 py-4">
								<?php if ( 'active' === $plan['status'] ) : ?>
									<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><?php esc_html_e( 'Ativo', 'guiawp' ); ?></span>
								<?php else : ?>
									<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold"><span class="w-2 h-2 rounded-full bg-slate-400"></span><?php esc_html_e( 'Inativo', 'guiawp' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="px-6 py-4 text-right">
								<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
									<button type="button" class="gcep-edit-plan inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>"
										data-id="<?php echo esc_attr( $plan['id'] ); ?>"
										data-name="<?php echo esc_attr( $plan['name'] ); ?>"
										data-days="<?php echo esc_attr( $plan['days'] ); ?>"
										data-price="<?php echo esc_attr( $plan['price'] ); ?>"
										data-description="<?php echo esc_attr( $plan['description'] ); ?>"
										data-status="<?php echo esc_attr( $plan['status'] ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">edit</span>
									</button>
									<button type="button" class="gcep-delete-plan inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" title="<?php esc_attr_e( 'Excluir', 'guiawp' ); ?>" data-id="<?php echo esc_attr( $plan['id'] ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
									</button>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</main>

<script>
(function(){
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';

	var form      = document.getElementById('gcep-plan-form');
	var formTitle = document.getElementById('gcep-plan-form-title');
	var planId    = document.getElementById('gcep-plan-id');
	var nameInput = document.getElementById('gcep-plan-name');
	var daysInput = document.getElementById('gcep-plan-days');
	var priceInput = document.getElementById('gcep-plan-price');
	var statusInput = document.getElementById('gcep-plan-status');
	var descInput  = document.getElementById('gcep-plan-description');

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

	function resetForm() {
		planId.value = '0';
		nameInput.value = '';
		daysInput.value = '';
		priceInput.value = '';
		statusInput.value = 'active';
		descInput.value = '';
		formTitle.textContent = '<?php echo esc_js( __( 'Novo Plano', 'guiawp' ) ); ?>';
		form.classList.add('hidden');
	}

	document.getElementById('gcep-add-plan').addEventListener('click', function() {
		resetForm();
		form.classList.remove('hidden');
		nameInput.focus();
	});

	document.getElementById('gcep-plan-cancel').addEventListener('click', resetForm);

	document.getElementById('gcep-plan-save').addEventListener('click', function() {
		var id = parseInt(planId.value);
		var action = id > 0 ? 'gcep_update_plan' : 'gcep_create_plan';

		if (!nameInput.value.trim() || !daysInput.value || parseInt(daysInput.value, 10) <= 0) {
			gcepToast('<?php echo esc_js( __( 'Preencha nome e vigência corretamente.', 'guiawp' ) ); ?>', 'warning');
			return;
		}

		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', nonce);
		fd.append('name', nameInput.value);
		fd.append('days', daysInput.value);
		fd.append('price', priceInput.value);
		fd.append('status', statusInput.value);
		fd.append('description', descInput.value);
		if (id > 0) fd.append('plan_id', id);

		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(parseAjaxResponse)
			.then(function(res) {
				if (res.success) {
					location.reload();
				} else {
					gcepToast(res.data.message || 'Erro', 'error');
				}
			})
			.catch(function(err) {
				gcepToast(err && err.message ? err.message : 'Erro ao salvar plano.', 'error');
			});
	});

	// Editar
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.gcep-edit-plan');
		if (!btn) return;
		planId.value     = btn.dataset.id;
		nameInput.value  = btn.dataset.name;
		daysInput.value  = btn.dataset.days;
		priceInput.value = btn.dataset.price;
		statusInput.value = btn.dataset.status;
		descInput.value  = btn.dataset.description;
		formTitle.textContent = '<?php echo esc_js( __( 'Editar Plano', 'guiawp' ) ); ?>';
		form.classList.remove('hidden');
		nameInput.focus();
	});

	// Excluir
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.gcep-delete-plan');
		if (!btn) return;
		if (!confirm('<?php echo esc_js( __( 'Excluir este plano?', 'guiawp' ) ); ?>')) return;
		var fd = new FormData();
		fd.append('action', 'gcep_delete_plan');
		fd.append('nonce', nonce);
		fd.append('plan_id', btn.dataset.id);
		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(parseAjaxResponse)
			.then(function(res) {
				if (res.success) {
					var row = document.querySelector('tr[data-plan-id="' + btn.dataset.id + '"]');
					if (row) row.remove();
				} else {
					gcepToast(res.data.message || 'Erro', 'error');
				}
			})
			.catch(function(err) {
				gcepToast(err && err.message ? err.message : 'Erro ao excluir plano.', 'error');
			});
	});
})();
</script>

</div>
<?php wp_footer(); ?>
</body>
</html>
