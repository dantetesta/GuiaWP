<?php
/**
 * Template: Admin - Categorias (CRUD completo via frontend)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.2.0 - 2026-03-11 - CRUD completo via AJAX no frontend
 * @modified 1.8.0 - 2026-03-20 - Imagem da categoria com crop quadrado 400x400 WebP 90% em modal sobreposto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$categorias = get_terms( [ 'taxonomy' => 'gcep_categoria', 'hide_empty' => false ] );

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="lg:ml-64 flex-1 p-4 lg:p-8 min-w-0 overflow-x-hidden">
	<header class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
		<div>
			<h2 class="text-3xl font-black tracking-tight mb-2"><?php esc_html_e( 'Categorias', 'guiawp' ); ?></h2>
			<p class="text-slate-500" id="gcep-cat-count"><?php printf( esc_html__( '%d categorias cadastradas', 'guiawp' ), is_array( $categorias ) ? count( $categorias ) : 0 ); ?></p>
		</div>
		<button type="button" id="gcep-btn-add-cat" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-[#0052cc]/20 transition-all flex items-center gap-2">
			<span class="material-symbols-outlined text-lg">add</span>
			<?php esc_html_e( 'Nova Categoria', 'guiawp' ); ?>
		</button>
	</header>

	<!-- Feedback -->

	<section class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
		<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
			<label class="flex-1 flex flex-col gap-2">
				<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Pesquisar Categorias', 'guiawp' ); ?></span>
				<div class="relative">
					<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
					<input type="search" id="gcep-cat-search" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Buscar por nome, ícone ou ID...', 'guiawp' ); ?>">
				</div>
			</label>
			<div class="lg:w-64 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
				<p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400"><?php esc_html_e( 'Busca', 'guiawp' ); ?></p>
				<p id="gcep-cat-search-summary" class="text-sm text-slate-600 mt-1 hidden"></p>
			</div>
		</div>
	</section>

	<!-- ======================== MODAL: Criar / Editar Categoria ======================== -->
	<div id="gcep-cat-modal" class="hidden fixed inset-0 flex items-center justify-center bg-black/40 p-4" style="z-index:1000;">
		<div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
			<h3 id="gcep-modal-title" class="text-xl font-bold text-slate-900 mb-6"></h3>
			<input type="hidden" id="gcep-cat-edit-id" value="0">

			<div class="space-y-5">
				<label class="flex flex-col gap-2">
					<span class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Nome da Categoria', 'guiawp' ); ?> <span class="text-rose-500">*</span></span>
					<input type="text" id="gcep-cat-name" class="rounded-lg border-slate-200 bg-slate-50 p-4 text-slate-900 focus:ring-2 focus:ring-[#0052cc]/20 outline-none" placeholder="<?php esc_attr_e( 'Ex: Restaurantes', 'guiawp' ); ?>">
				</label>

				<div>
					<span class="text-sm font-semibold text-slate-700 mb-3 block"><?php esc_html_e( 'Ícone', 'guiawp' ); ?></span>
					<div class="grid grid-cols-6 sm:grid-cols-8 gap-2" id="gcep-icon-grid">
						<?php
						$icons = [
							'category', 'restaurant', 'local_hospital', 'school', 'shopping_cart',
							'build', 'directions_car', 'fitness_center', 'pets', 'spa',
							'local_cafe', 'local_bar', 'hotel', 'flight', 'local_pharmacy',
							'gavel', 'account_balance', 'storefront', 'home_repair_service', 'handyman',
							'brush', 'camera', 'computer', 'headphones', 'sports_esports',
							'music_note', 'local_florist', 'park', 'child_care', 'local_laundry_service',
							'cleaning_services', 'plumbing', 'electrical_services', 'architecture', 'engineering',
							'dentistry', 'psychology', 'medical_services', 'volunteer_activism', 'celebration',
						];
						foreach ( $icons as $icon ) :
						?>
						<button type="button" data-icon="<?php echo esc_attr( $icon ); ?>" class="gcep-icon-option w-10 h-10 rounded-lg border border-slate-200 flex items-center justify-center text-slate-500 hover:border-[#0052cc] hover:text-[#0052cc] hover:bg-[#0052cc]/5 transition-all cursor-pointer">
							<span class="material-symbols-outlined text-xl"><?php echo esc_html( $icon ); ?></span>
						</button>
						<?php endforeach; ?>
					</div>
					<input type="hidden" id="gcep-cat-icon" value="category">
				</div>

				<!-- Imagem da Categoria -->
				<div>
					<span class="text-sm font-semibold text-slate-700 mb-2 block"><?php esc_html_e( 'Imagem da Categoria', 'guiawp' ); ?></span>
					<p class="text-xs text-slate-400 mb-3"><?php esc_html_e( 'Quadrada, 400×400px, WebP otimizado.', 'guiawp' ); ?></p>

					<!-- Sem imagem -->
					<div id="gcep-cat-img-empty" class="space-y-2">
						<label class="flex items-center gap-3 px-4 py-3 bg-slate-50 border border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-[#0052cc] hover:bg-[#0052cc]/5 transition-all">
							<span class="material-symbols-outlined text-slate-400">add_photo_alternate</span>
							<span class="text-sm text-slate-600"><?php esc_html_e( 'Selecionar imagem...', 'guiawp' ); ?></span>
							<input type="file" id="gcep-cat-img-input" accept="image/*" class="hidden">
						</label>
						<?php if ( GCEP_Gemini_Imagen::has_api_key() ) : ?>
						<button type="button" id="gcep-cat-img-ai" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all" style="background:<?php echo esc_attr( GCEP_Settings::get( 'cor_primaria', '#0052cc' ) ); ?>10;border:1px solid <?php echo esc_attr( GCEP_Settings::get( 'cor_primaria', '#0052cc' ) ); ?>30;color:<?php echo esc_attr( GCEP_Settings::get( 'cor_primaria', '#0052cc' ) ); ?>;">
							<span class="material-symbols-outlined text-base">auto_awesome</span>
							<?php esc_html_e( 'Gerar com IA', 'guiawp' ); ?>
						</button>
						<?php endif; ?>
					</div>

					<!-- Com imagem (preview) -->
					<div id="gcep-cat-img-filled" class="hidden">
						<div class="flex items-center gap-4 p-3 bg-slate-50 border border-slate-200 rounded-xl">
							<img id="gcep-cat-img-thumb" src="" alt="" class="w-16 h-16 rounded-lg object-cover border border-slate-200 shadow-sm">
							<div class="flex-1 min-w-0">
								<p class="text-sm font-semibold text-slate-700 truncate"><?php esc_html_e( 'Imagem definida', 'guiawp' ); ?></p>
								<p class="text-xs text-slate-400">400×400 WebP</p>
							</div>
							<div class="flex gap-1">
								<button type="button" id="gcep-cat-img-change" class="p-2 text-slate-400 hover:text-[#0052cc] hover:bg-[#0052cc]/10 rounded-lg transition-all" title="<?php esc_attr_e( 'Trocar', 'guiawp' ); ?>">
									<span class="material-symbols-outlined text-lg">edit</span>
								</button>
								<button type="button" id="gcep-cat-img-remove" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>">
									<span class="material-symbols-outlined text-lg">delete</span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="flex gap-3 mt-8">
				<button type="button" id="gcep-modal-save" class="flex-1 px-6 py-3 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-xl font-bold text-sm transition-all">
					<?php esc_html_e( 'Salvar', 'guiawp' ); ?>
				</button>
				<button type="button" id="gcep-modal-cancel" class="px-6 py-3 border border-slate-200 text-slate-600 rounded-xl font-semibold text-sm hover:bg-slate-100 transition-all">
					<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- ======================== MODAL: Crop de Imagem (z-60, acima do modal de categoria) ======================== -->
	<div id="gcep-crop-modal" class="hidden fixed inset-0 flex items-center justify-center bg-black/80 p-4" style="z-index:9999;">
		<div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col" style="max-height:90vh;">
			<div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 flex-shrink-0">
				<div>
					<h3 class="text-lg font-bold text-slate-900"><?php esc_html_e( 'Recortar Imagem', 'guiawp' ); ?></h3>
					<p class="text-xs text-slate-400 mt-0.5"><?php esc_html_e( 'Arraste para posicionar e use as alças para redimensionar', 'guiawp' ); ?></p>
				</div>
				<button type="button" id="gcep-crop-close" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
			<div class="flex-1 overflow-hidden p-6 flex items-center justify-center bg-slate-100/80">
				<div id="gcep-crop-stage" class="relative inline-block" style="touch-action:none;user-select:none;"></div>
			</div>
			<div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 flex-shrink-0">
				<button type="button" id="gcep-crop-cancel" class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-100 transition-colors">
					<?php esc_html_e( 'Cancelar', 'guiawp' ); ?>
				</button>
				<button type="button" id="gcep-crop-confirm" class="px-6 py-2.5 bg-[#0052cc] text-white text-sm font-bold rounded-xl hover:bg-[#003d99] transition-colors flex items-center gap-2 shadow-lg shadow-[#0052cc]/20">
					<span class="material-symbols-outlined text-base">crop</span>
					<?php esc_html_e( 'Aplicar Recorte', 'guiawp' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- ======================== Tabela ======================== -->
	<section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
		<div class="overflow-x-auto">
			<table class="w-full text-left border-collapse">
				<thead>
					<tr class="bg-slate-50">
						<th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Categoria', 'guiawp' ); ?></th>
						<th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'ID', 'guiawp' ); ?></th>
						<th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Ícone', 'guiawp' ); ?></th>
						<th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php esc_html_e( 'Anúncios', 'guiawp' ); ?></th>
						<th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
					</tr>
				</thead>
				<tbody id="gcep-cat-list" class="divide-y divide-slate-100">
					<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
						<?php foreach ( $categorias as $cat ) : ?>
							<?php
							$icon        = get_term_meta( $cat->term_id, 'gcep_icon', true ) ?: 'category';
							$img_id      = absint( get_term_meta( $cat->term_id, 'gcep_image', true ) );
							$img_url     = $img_id ? wp_get_attachment_image_url( $img_id, 'thumbnail' ) : '';
							$search_text = strtolower( remove_accents( $cat->name . ' ' . $icon . ' ' . $cat->term_id ) );
							?>
							<tr class="gcep-cat-row hover:bg-slate-50/70 transition-colors" data-term-id="<?php echo esc_attr( $cat->term_id ); ?>" data-search="<?php echo esc_attr( $search_text ); ?>" data-img-url="<?php echo esc_attr( $img_url ); ?>">
								<td class="px-4 py-3">
									<div class="flex items-center gap-3 min-w-[240px]">
										<?php if ( $img_url ) : ?>
											<img src="<?php echo esc_url( $img_url ); ?>" alt="" class="w-10 h-10 rounded-xl object-cover flex-shrink-0 border border-slate-200 gcep-cat-thumb">
										<?php else : ?>
											<div class="w-10 h-10 rounded-xl bg-[#0052cc]/10 text-[#0052cc] flex items-center justify-center flex-shrink-0 gcep-cat-thumb-icon">
												<span class="material-symbols-outlined text-xl gcep-cat-icon"><?php echo esc_html( $icon ); ?></span>
											</div>
										<?php endif; ?>
										<div class="min-w-0">
											<p class="text-sm font-bold text-slate-900 truncate gcep-cat-name"><?php echo esc_html( $cat->name ); ?></p>
										</div>
									</div>
								</td>
								<td class="px-4 py-3 text-sm text-slate-500 font-medium whitespace-nowrap">#<?php echo esc_html( $cat->term_id ); ?></td>
								<td class="px-4 py-3">
									<code class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600 gcep-cat-icon-label"><?php echo esc_html( $icon ); ?></code>
								</td>
								<td class="px-4 py-3 text-sm text-slate-600">
									<span class="font-semibold gcep-cat-anuncios"><?php echo esc_html( $cat->count ); ?></span>
								</td>
								<td class="px-4 py-3 text-right">
									<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
										<button type="button" class="gcep-edit-cat inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>">
											<span class="material-symbols-outlined" style="font-size:18px;">edit</span>
										</button>
										<button type="button" class="gcep-delete-cat inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" title="<?php esc_attr_e( 'Excluir', 'guiawp' ); ?>">
											<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					<tr id="gcep-cat-empty-row" class="<?php echo ( empty( $categorias ) || is_wp_error( $categorias ) ) ? '' : 'hidden'; ?>">
						<td colspan="5" class="px-6 py-16 text-center text-slate-400">
							<span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">category</span>
							<p id="gcep-cat-empty-text"><?php esc_html_e( 'Nenhuma categoria cadastrada.', 'guiawp' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</section>
</main>
</div>

<script>
(function() {
	'use strict';

	var modal       = document.getElementById('gcep-cat-modal');
	var modalTitle  = document.getElementById('gcep-modal-title');
	var inputName   = document.getElementById('gcep-cat-name');
	var inputIcon   = document.getElementById('gcep-cat-icon');
	var editId      = document.getElementById('gcep-cat-edit-id');
	var list        = document.getElementById('gcep-cat-list');
	var emptyRow    = document.getElementById('gcep-cat-empty-row');
	var emptyText   = document.getElementById('gcep-cat-empty-text');
	var countLabel  = document.getElementById('gcep-cat-count');
	var searchInput = document.getElementById('gcep-cat-search');
	var searchSummary = document.getElementById('gcep-cat-search-summary');
	var ajaxUrl     = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce       = '<?php echo esc_js( wp_create_nonce( 'gcep_nonce' ) ); ?>';

	var imgInput    = document.getElementById('gcep-cat-img-input');
	var imgEmpty    = document.getElementById('gcep-cat-img-empty');
	var imgFilled   = document.getElementById('gcep-cat-img-filled');
	var imgThumb    = document.getElementById('gcep-cat-img-thumb');
	var imgChange   = document.getElementById('gcep-cat-img-change');
	var imgRemove   = document.getElementById('gcep-cat-img-remove');

	var cropModal   = document.getElementById('gcep-crop-modal');
	var cropStage   = document.getElementById('gcep-crop-stage');
	var cropConfirm = document.getElementById('gcep-crop-confirm');
	var cropCancel  = document.getElementById('gcep-crop-cancel');
	var cropClose   = document.getElementById('gcep-crop-close');

	var pendingCroppedFile = null;
	var currentImgUrl = '';
	var imageRemoved = false;

	var cropImg = null, cropBox = null;
	var isDragging = false, isResizing = false, resizingTL = false;
	var startX, startY, startLeft, startTop, startW, startH;

	function gcepToast(msg, type) {
		feedback.textContent = msg;
		feedback.className = 'mb-6 p-4 rounded-lg text-sm font-medium ' + (type === 'error' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200');
		setTimeout(function() { feedback.className = 'hidden'; }, 4000);
	}
	function escapeHtml(v) { return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
	function normalizeText(v) { return String(v||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim(); }

	/* ===== Imagem: estado no modal de categoria ===== */
	function showImgEmpty() { imgEmpty.classList.remove('hidden'); imgFilled.classList.add('hidden'); }
	function showImgFilled(url) { imgThumb.src = url; imgEmpty.classList.add('hidden'); imgFilled.classList.remove('hidden'); }
	function resetImageState() { pendingCroppedFile = null; currentImgUrl = ''; imageRemoved = false; imgInput.value = ''; window.gcepImagenAttachmentId = null; window.gcepImagenUrl = null; showImgEmpty(); }

	/* ===== CROP MODAL ===== */
	function openCropModal(src) {
		cropStage.innerHTML = '';
		cropModal.classList.remove('hidden');
		cropImg = new Image();
		cropImg.onload = function() {
			var stageW = cropStage.parentElement.clientWidth - 48;
			var stageH = Math.min(500, window.innerHeight * 0.55);
			var scale  = Math.min(stageW / cropImg.naturalWidth, stageH / cropImg.naturalHeight, 1);
			var dispW  = Math.round(cropImg.naturalWidth * scale);
			var dispH  = Math.round(cropImg.naturalHeight * scale);

			cropImg.style.cssText = 'width:'+dispW+'px;height:'+dispH+'px;display:block;user-select:none;';
			cropImg.draggable = false;
			cropStage.innerHTML = '';
			cropStage.style.width = dispW + 'px';
			cropStage.style.height = dispH + 'px';
			cropStage.appendChild(cropImg);

			var sz = Math.min(dispW, dispH) * 0.75;
			var ix = Math.round((dispW - sz) / 2);
			var iy = Math.round((dispH - sz) / 2);

			var ov = document.createElement('div');
			ov.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;';
			ov.innerHTML =
				'<div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);pointer-events:none;"></div>' +
				'<div id="gcep-cbox" style="position:absolute;left:'+ix+'px;top:'+iy+'px;width:'+sz+'px;height:'+sz+'px;border:2px solid #fff;box-shadow:0 0 0 9999px rgba(0,0,0,0.5);cursor:move;z-index:2;touch-action:none;border-radius:6px;">' +
					'<div style="position:absolute;top:33.33%;left:0;right:0;height:1px;background:rgba(255,255,255,0.25);pointer-events:none;"></div>' +
					'<div style="position:absolute;top:66.66%;left:0;right:0;height:1px;background:rgba(255,255,255,0.25);pointer-events:none;"></div>' +
					'<div style="position:absolute;left:33.33%;top:0;bottom:0;width:1px;background:rgba(255,255,255,0.25);pointer-events:none;"></div>' +
					'<div style="position:absolute;left:66.66%;top:0;bottom:0;width:1px;background:rgba(255,255,255,0.25);pointer-events:none;"></div>' +
					'<div class="cbox-se" style="position:absolute;right:-7px;bottom:-7px;width:14px;height:14px;background:#fff;border-radius:50%;cursor:nwse-resize;z-index:3;touch-action:none;box-shadow:0 1px 4px rgba(0,0,0,0.3);"></div>' +
					'<div class="cbox-tl" style="position:absolute;left:-7px;top:-7px;width:14px;height:14px;background:#fff;border-radius:50%;cursor:nwse-resize;z-index:3;touch-action:none;box-shadow:0 1px 4px rgba(0,0,0,0.3);"></div>' +
				'</div>';
			cropStage.appendChild(ov);
			cropBox = document.getElementById('gcep-cbox');

			cropBox.addEventListener('mousedown', onDragStart);
			cropBox.addEventListener('touchstart', onDragStart, {passive:false});
			ov.querySelector('.cbox-se').addEventListener('mousedown', onResizeSE);
			ov.querySelector('.cbox-se').addEventListener('touchstart', onResizeSE, {passive:false});
			ov.querySelector('.cbox-tl').addEventListener('mousedown', onResizeTL);
			ov.querySelector('.cbox-tl').addEventListener('touchstart', onResizeTL, {passive:false});
		};
		cropImg.src = src;
	}

	function closeCropModal() { cropModal.classList.add('hidden'); cropStage.innerHTML=''; cropImg=null; cropBox=null; isDragging=false; isResizing=false; resizingTL=false; imgInput.value=''; }

	function getPos(e) { return e.touches&&e.touches.length ? {x:e.touches[0].clientX,y:e.touches[0].clientY} : {x:e.clientX,y:e.clientY}; }

	function onDragStart(e) {
		if (e.target.classList.contains('cbox-se')||e.target.classList.contains('cbox-tl')) return;
		e.preventDefault(); isDragging=true;
		var p=getPos(e); startX=p.x; startY=p.y;
		startLeft=parseInt(cropBox.style.left); startTop=parseInt(cropBox.style.top);
	}
	function onResizeSE(e) { e.preventDefault(); e.stopPropagation(); isResizing=true; var p=getPos(e); startX=p.x; startY=p.y; startW=parseInt(cropBox.style.width); }
	function onResizeTL(e) {
		e.preventDefault(); e.stopPropagation(); resizingTL=true;
		var p=getPos(e); startX=p.x; startY=p.y; startW=parseInt(cropBox.style.width);
		startLeft=parseInt(cropBox.style.left); startTop=parseInt(cropBox.style.top);
	}

	document.addEventListener('mousemove', onCropMove);
	document.addEventListener('touchmove', onCropMove, {passive:false});
	document.addEventListener('mouseup', function() { isDragging=false; isResizing=false; resizingTL=false; });
	document.addEventListener('touchend', function() { isDragging=false; isResizing=false; resizingTL=false; });

	function onCropMove(e) {
		if (!isDragging&&!isResizing&&!resizingTL) return;
		e.preventDefault();
		var p=getPos(e), dx=p.x-startX, dy=p.y-startY;
		var pw=cropStage.clientWidth, ph=cropStage.clientHeight;

		if (isDragging) {
			var w=parseInt(cropBox.style.width), h=parseInt(cropBox.style.height);
			cropBox.style.left = Math.max(0,Math.min(startLeft+dx, pw-w))+'px';
			cropBox.style.top  = Math.max(0,Math.min(startTop+dy, ph-h))+'px';
		}
		if (isResizing) {
			var d=Math.max(dx,dy), nw=Math.max(50,startW+d);
			var bx=parseInt(cropBox.style.left), by=parseInt(cropBox.style.top);
			if (bx+nw>pw) nw=pw-bx;
			if (by+nw>ph) nw=ph-by;
			cropBox.style.width=Math.round(nw)+'px'; cropBox.style.height=Math.round(nw)+'px';
		}
		if (resizingTL) {
			var d=Math.min(dx,dy), nw=Math.max(50,startW-d);
			var nl=startLeft+(startW-nw), nt=startTop+(startW-nw);
			if (nl<0){nw+=nl;nl=0;} if(nt<0){nw+=nt;nt=0;}
			cropBox.style.width=Math.round(nw)+'px'; cropBox.style.height=Math.round(nw)+'px';
			cropBox.style.left=Math.round(nl)+'px'; cropBox.style.top=Math.round(nt)+'px';
		}
	}

	function executeCrop() {
		if (!cropImg||!cropBox) return;
		var sx = parseInt(cropBox.style.left) * (cropImg.naturalWidth/cropImg.clientWidth);
		var sy = parseInt(cropBox.style.top) * (cropImg.naturalHeight/cropImg.clientHeight);
		var sw = parseInt(cropBox.style.width) * (cropImg.naturalWidth/cropImg.clientWidth);
		var sh = parseInt(cropBox.style.height) * (cropImg.naturalHeight/cropImg.clientHeight);
		var c=document.createElement('canvas'); c.width=400; c.height=400;
		c.getContext('2d').drawImage(cropImg, sx,sy,sw,sh, 0,0,400,400);
		c.toBlob(function(blob) {
			if (!blob) return;
			pendingCroppedFile = new File([blob], 'cat_'+Date.now()+'.webp', {type:'image/webp'});
			showImgFilled(URL.createObjectURL(blob));
			closeCropModal();
		}, 'image/webp', 0.9);
	}

	cropConfirm.addEventListener('click', executeCrop);
	cropCancel.addEventListener('click', closeCropModal);
	cropClose.addEventListener('click', closeCropModal);
	cropModal.addEventListener('click', function(e) { if(e.target===cropModal) closeCropModal(); });

	/* ===== Eventos de imagem no modal de categoria ===== */
	imgInput.addEventListener('change', function(e) {
		var f=e.target.files[0];
		if (!f||!f.type.startsWith('image/')) return;
		var r=new FileReader();
		r.onload=function(ev){openCropModal(ev.target.result);};
		r.readAsDataURL(f);
	});
	imgChange.addEventListener('click', function() { imgInput.value=''; imgInput.click(); });
	imgRemove.addEventListener('click', function() { imageRemoved=true; pendingCroppedFile=null; currentImgUrl=''; showImgEmpty(); });

	/* ===== Modal de categoria ===== */
	function openModal(title, name, icon, termId, imgUrl) {
		modalTitle.textContent=title; inputName.value=name||''; inputIcon.value=icon||'category';
		editId.value=termId||'0'; selectIcon(icon||'category'); resetImageState();
		if (imgUrl) { currentImgUrl=imgUrl; showImgFilled(imgUrl); }
		modal.classList.remove('hidden');
		setTimeout(function(){inputName.focus();},100);
	}
	function closeModal() { modal.classList.add('hidden'); resetImageState(); }

	document.getElementById('gcep-btn-add-cat').addEventListener('click', function() {
		openModal('<?php echo esc_js(__('Nova Categoria','guiawp'));?>', '', 'category', '0', '');
	});
	document.getElementById('gcep-modal-cancel').addEventListener('click', closeModal);
	modal.addEventListener('click', function(e) { if(e.target===modal) closeModal(); });

	/* ===== Ícones ===== */
	function selectIcon(icon) {
		document.querySelectorAll('.gcep-icon-option').forEach(function(b) {
			var s=b.dataset.icon===icon;
			b.classList.toggle('border-[#0052cc]',s); b.classList.toggle('bg-[#0052cc]/10',s);
			b.classList.toggle('text-[#0052cc]',s); b.classList.toggle('border-slate-200',!s); b.classList.toggle('text-slate-500',!s);
		});
		inputIcon.value=icon;
	}
	document.getElementById('gcep-icon-grid').addEventListener('click', function(e) {
		var b=e.target.closest('[data-icon]'); if(b) selectIcon(b.dataset.icon);
	});

	/* ===== Tabela helpers ===== */
	function getRows() { return Array.prototype.slice.call(list.querySelectorAll('.gcep-cat-row')); }
	function buildThumbHtml(url,icon) {
		if (url) return '<img src="'+escapeHtml(url)+'" alt="" class="w-10 h-10 rounded-xl object-cover flex-shrink-0 border border-slate-200 gcep-cat-thumb">';
		return '<div class="w-10 h-10 rounded-xl bg-[#0052cc]/10 text-[#0052cc] flex items-center justify-center flex-shrink-0 gcep-cat-thumb-icon"><span class="material-symbols-outlined text-xl gcep-cat-icon">'+escapeHtml(icon)+'</span></div>';
	}
	function buildRow(t) {
		var r=document.createElement('tr');
		r.className='gcep-cat-row hover:bg-slate-50/70 transition-colors';
		r.dataset.termId=t.term_id; r.dataset.search=normalizeText(t.name+' '+t.icon+' '+t.term_id); r.dataset.imgUrl=t.img_url||'';
		r.innerHTML='<td class="px-4 py-3"><div class="flex items-center gap-3 min-w-[240px]">'+buildThumbHtml(t.img_url,t.icon)+'<div class="min-w-0"><p class="text-sm font-bold text-slate-900 truncate gcep-cat-name">'+escapeHtml(t.name)+'</p></div></div></td><td class="px-4 py-3 text-sm text-slate-500 font-medium whitespace-nowrap">#'+t.term_id+'</td><td class="px-4 py-3"><code class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600 gcep-cat-icon-label">'+escapeHtml(t.icon)+'</code></td><td class="px-4 py-3 text-sm text-slate-600"><span class="font-semibold gcep-cat-anuncios">'+(t.count||0)+'</span></td><td class="px-4 py-3"><div class="flex items-center justify-end gap-1"><button type="button" class="gcep-edit-cat p-2 text-slate-400 hover:text-[#0052cc] hover:bg-[#0052cc]/10 rounded-lg transition-all" title="Editar"><span class="material-symbols-outlined text-[20px]">edit</span></button><button type="button" class="gcep-delete-cat p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all" title="Excluir"><span class="material-symbols-outlined text-[20px]">delete</span></button></div></td>';
		return r;
	}
	function updateRowThumb(tid,url,icon) {
		var row=list.querySelector('[data-term-id="'+tid+'"]'); if(!row)return;
		row.dataset.imgUrl=url||'';
		var cell=row.querySelector('td:first-child .flex'); if(!cell)return;
		var o1=cell.querySelector('.gcep-cat-thumb'),o2=cell.querySelector('.gcep-cat-thumb-icon');
		if(o1)o1.remove(); if(o2)o2.remove();
		var tmp=document.createElement('div'); tmp.innerHTML=buildThumbHtml(url,icon);
		cell.insertBefore(tmp.firstElementChild,cell.firstElementChild);
	}

	/* ===== Busca ===== */
	function syncEmptyState(tot,vis,q) {
		if(!tot){emptyText.textContent='<?php echo esc_js(__('Nenhuma categoria cadastrada.','guiawp'));?>';emptyRow.classList.remove('hidden');return;}
		if(!vis&&q){emptyText.textContent='<?php echo esc_js(__('Nenhuma categoria encontrada para a busca.','guiawp'));?>';emptyRow.classList.remove('hidden');return;}
		emptyRow.classList.add('hidden');
	}
	function updateCount(){countLabel.textContent=getRows().length+' categorias cadastradas';}
	function applySearch() {
		var q=normalizeText(searchInput.value),rows=getRows(),vis=0;
		rows.forEach(function(r){var m=!q||r.dataset.search.indexOf(q)!==-1;r.classList.toggle('hidden',!m);if(m)vis++;});
		if(q){searchSummary.textContent=vis+' resultado(s) para "'+searchInput.value.trim()+'"';searchSummary.classList.remove('hidden');}
		else{searchSummary.classList.add('hidden');searchSummary.textContent='';}
		syncEmptyState(rows.length,vis,q);
	}
	searchInput.addEventListener('input', applySearch);

	/* ===== AJAX: imagem ===== */
	function uploadCategoryImage(tid,cb) {
		if(!pendingCroppedFile){cb(true,'');return;}
		var fd=new FormData();
		fd.append('action','gcep_upload_category_image'); fd.append('nonce',nonce);
		fd.append('term_id',tid); fd.append('category_image',pendingCroppedFile);
		fetch(ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){cb(res.success,res.success?res.data.url:'');}).catch(function(){cb(false,'');});
	}
	function removeCategoryImage(tid) {
		fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=gcep_remove_category_image&nonce='+nonce+'&term_id='+tid});
	}
	function setCategoryAiImage(tid,attId,cb) {
		fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=gcep_set_category_ai_image&nonce='+nonce+'&term_id='+tid+'&attachment_id='+attId})
		.then(function(r){return r.json();}).then(function(res){cb(res.success,res.success?res.data.url:'');}).catch(function(){cb(false,'');});
	}

	/* ===== Salvar ===== */
	document.getElementById('gcep-modal-save').addEventListener('click', function() {
		var name=inputName.value.trim(), icon=inputIcon.value, termId=editId.value, btn=this;
		if(!name){inputName.focus();return;}
		btn.disabled=true; btn.textContent='<?php echo esc_js(__('Salvando...','guiawp'));?>';
		var action=termId==='0'?'gcep_create_category':'gcep_update_category';
		var body='action='+action+'&nonce='+nonce+'&name='+encodeURIComponent(name)+'&icon='+encodeURIComponent(icon);
		if(termId!=='0')body+='&term_id='+termId;
		fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body})
		.then(function(r){return r.json();})
		.then(function(res){
			if(!res.success){gcepToast(res.data.message,'error');btn.disabled=false;btn.textContent='<?php echo esc_js(__('Salvar','guiawp'));?>';return;}
			var tid=res.data.term_id;
			if(imageRemoved&&termId!=='0') removeCategoryImage(tid);
			if(window.gcepImagenAttachmentId) {
				setCategoryAiImage(tid,window.gcepImagenAttachmentId,function(ok,url){finishSave(res,tid,ok?url:'',icon,btn);});
			} else if(pendingCroppedFile) uploadCategoryImage(tid,function(ok,url){finishSave(res,tid,ok?url:'',icon,btn);});
			else finishSave(res,tid,imageRemoved?'':currentImgUrl,icon,btn);
		})
		.catch(function(){gcepToast('<?php echo esc_js(__('Erro de conexão.','guiawp'));?>','error');btn.disabled=false;btn.textContent='<?php echo esc_js(__('Salvar','guiawp'));?>';});
	});

	function finishSave(res,tid,imgUrl,icon,btn) {
		closeModal(); gcepToast(res.data.message,'success');
		var ex=list.querySelector('[data-term-id="'+tid+'"]');
		if(!ex) list.insertBefore(buildRow({term_id:tid,name:res.data.name,icon:res.data.icon,count:0,img_url:imgUrl}),emptyRow);
		else {
			ex.querySelector('.gcep-cat-name').textContent=res.data.name;
			var ic=ex.querySelector('.gcep-cat-icon'); if(ic)ic.textContent=res.data.icon;
			ex.querySelector('.gcep-cat-icon-label').textContent=res.data.icon;
			ex.dataset.search=normalizeText(res.data.name+' '+res.data.icon+' '+tid);
			updateRowThumb(tid,imgUrl,res.data.icon);
		}
		btn.disabled=false; btn.textContent='<?php echo esc_js(__('Salvar','guiawp'));?>';
		updateCount(); applySearch();
	}

	/* ===== Delegação: editar / excluir ===== */
	list.addEventListener('click', function(e) {
		var eb=e.target.closest('.gcep-edit-cat'), db=e.target.closest('.gcep-delete-cat');
		if(eb) {
			var row=eb.closest('.gcep-cat-row'), tid=row.dataset.termId, name=row.querySelector('.gcep-cat-name').textContent;
			var ic=row.querySelector('.gcep-cat-icon'), icon=ic?ic.textContent:(row.querySelector('.gcep-cat-icon-label')?row.querySelector('.gcep-cat-icon-label').textContent:'category');
			openModal('<?php echo esc_js(__('Editar Categoria','guiawp'));?>',name,icon,tid,row.dataset.imgUrl||'');
		}
		if(db) {
			var row=db.closest('.gcep-cat-row'),tid=row.dataset.termId,name=row.querySelector('.gcep-cat-name').textContent;
			if(!confirm('<?php echo esc_js(__('Excluir a categoria','guiawp'));?> "'+name+'"?'))return;
			fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=gcep_delete_category&nonce='+nonce+'&term_id='+tid})
			.then(function(r){return r.json();})
			.then(function(res){if(res.success){row.remove();gcepToast(res.data.message,'success');updateCount();applySearch();}else gcepToast(res.data.message,'error');});
		}
	});

	updateCount(); applySearch();
})();
</script>

<?php include __DIR__ . '/partial-modal-imagen.php'; ?>

<script>
(function(){
	var aiBtn = document.getElementById('gcep-cat-img-ai');
	if (!aiBtn || !window.gcepImagenModal) return;

	aiBtn.addEventListener('click', function(){
		window.gcepImagenModal.open('categoria', function(attachmentId, url){
			// Aplicar imagem gerada no formulário de categoria
			var imgEmpty   = document.getElementById('gcep-cat-img-empty');
			var imgFilled  = document.getElementById('gcep-cat-img-filled');
			var imgThumb   = document.getElementById('gcep-cat-img-thumb');

			imgThumb.src = url;
			imgEmpty.classList.add('hidden');
			imgFilled.classList.remove('hidden');

			// Setar attachment no estado interno
			window.gcepImagenAttachmentId = attachmentId;
			window.gcepImagenUrl = url;
		});
	});
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
