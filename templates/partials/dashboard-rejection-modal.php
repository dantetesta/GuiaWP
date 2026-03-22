<?php
/**
 * Modal de motivo de rejeicao no dashboard
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.4.4 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="gcep-rejection-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
	<div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
		<div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-slate-200">
			<div>
				<p class="text-xs font-bold uppercase tracking-[0.18em] text-rose-600 mb-2"><?php esc_html_e( 'Motivo da Rejeicao', 'guiawp' ); ?></p>
				<h3 id="gcep-rejection-modal-title" class="text-xl font-black tracking-tight text-slate-900"></h3>
			</div>
			<button type="button" id="gcep-rejection-modal-close" class="w-10 h-10 inline-flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors" aria-label="<?php esc_attr_e( 'Fechar modal', 'guiawp' ); ?>">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>
		<div class="px-6 py-6">
			<div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
				<p id="gcep-rejection-modal-reason" class="text-sm leading-relaxed text-rose-700 whitespace-pre-wrap"></p>
			</div>
		</div>
		<div class="px-6 py-5 border-t border-slate-200 flex justify-end">
			<button type="button" id="gcep-rejection-modal-action" class="px-6 py-3 rounded-xl bg-[#0052cc] text-white text-sm font-bold hover:bg-[#003d99] transition-colors">
				<?php esc_html_e( 'Entendi', 'guiawp' ); ?>
			</button>
		</div>
	</div>
</div>

<script>
(function() {
	var modal = document.getElementById('gcep-rejection-modal');
	if (!modal) return;

	var titleEl = document.getElementById('gcep-rejection-modal-title');
	var reasonEl = document.getElementById('gcep-rejection-modal-reason');
	var closeButton = document.getElementById('gcep-rejection-modal-close');
	var actionButton = document.getElementById('gcep-rejection-modal-action');
	var activeTrigger = null;

	function openModal(trigger) {
		activeTrigger = trigger;
		titleEl.textContent = trigger.dataset.title || '<?php echo esc_js( __( 'Anuncio Rejeitado', 'guiawp' ) ); ?>';
		reasonEl.textContent = trigger.dataset.reason || '';
		modal.classList.remove('hidden');
		document.body.classList.add('overflow-hidden');
		closeButton.focus();
	}

	function closeModal() {
		modal.classList.add('hidden');
		document.body.classList.remove('overflow-hidden');
		if (activeTrigger) {
			activeTrigger.focus();
		}
	}

	document.addEventListener('click', function(event) {
		var trigger = event.target.closest('.gcep-show-rejection-reason');
		if (!trigger) return;

		event.preventDefault();
		openModal(trigger);
	});

	closeButton.addEventListener('click', closeModal);
	actionButton.addEventListener('click', closeModal);

	modal.addEventListener('click', function(event) {
		if (event.target === modal) {
			closeModal();
		}
	});

	document.addEventListener('keydown', function(event) {
		if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
			closeModal();
		}
	});
})();
</script>
