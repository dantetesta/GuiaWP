<?php
/**
 * Template: Mapa de Anúncios (página pública com Leaflet)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.7.0 - 2026-03-11
 * @modified 1.9.4 - 2026-03-21 - Redesign modal: capa + foto perfil sobreposta, layout card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categorias = get_terms( [ 'taxonomy' => 'gcep_categoria', 'hide_empty' => false ] );
$nome_guia  = GCEP_Settings::get( 'nome_guia', 'GuiaWP' );
?>

<main class="relative">
	<!-- Barra de filtros fixa no topo -->
	<div id="gcep-map-filters" class="bg-white border-b border-slate-200 shadow-sm relative z-[1000]">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 py-3">
			<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
				<!-- Busca -->
				<div class="relative flex-1">
					<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
					<input type="text" id="gcep-map-search" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0052cc]/20 focus:border-[#0052cc] outline-none" placeholder="<?php esc_attr_e( 'Buscar por nome ou serviço...', 'guiawp' ); ?>">
				</div>

				<!-- Categoria -->
				<?php if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) : ?>
				<div class="relative">
					<select id="gcep-map-category" class="appearance-none w-full sm:w-56 pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0052cc]/20 focus:border-[#0052cc] outline-none cursor-pointer">
						<option value=""><?php esc_html_e( 'Todas as categorias', 'guiawp' ); ?></option>
						<?php foreach ( $categorias as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">expand_more</span>
				</div>
				<?php endif; ?>

				<!-- Botão filtrar -->
				<button type="button" id="gcep-map-filter-btn" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#0052cc] text-white text-sm font-bold rounded-lg hover:bg-[#003d99] hover:shadow-lg transition-all">
					<span class="material-symbols-outlined text-lg">filter_list</span>
					<?php esc_html_e( 'Filtrar', 'guiawp' ); ?>
				</button>

				<!-- Botão minha localização -->
				<button type="button" id="gcep-map-geoloc-btn" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-slate-200 bg-white text-sm font-semibold text-slate-700 rounded-lg hover:bg-slate-50 transition-all" title="<?php esc_attr_e( 'Usar minha localização', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-lg text-[#0052cc]">my_location</span>
					<span class="hidden sm:inline"><?php esc_html_e( 'Perto de mim', 'guiawp' ); ?></span>
				</button>
			</div>
		</div>
	</div>

	<!-- Container do mapa -->
	<div id="gcep-map-fullscreen" class="w-full" style="height: calc(100vh - 130px); min-height: 400px;"></div>

	<!-- Modal de anúncio -->
	<div id="gcep-map-modal-overlay" style="position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;"></div>
	<div id="gcep-map-modal" style="position:fixed;z-index:2001;top:50%;left:50%;transform:translate(-50%,-50%);width:92vw;max-width:380px;background:#fff;border-radius:16px;box-shadow:0 25px 50px rgba(0,0,0,.25);display:none;overflow:hidden;">

		<!-- Foto de capa -->
		<div id="gcep-modal-capa" style="position:relative;width:100%;height:160px;background:#e2e8f0;overflow:hidden;">
			<div id="gcep-modal-capa-placeholder" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:linear-gradient(135deg,#0052cc 0%,#3b82f6 100%);">
				<span class="material-symbols-outlined" style="font-size:48px;color:rgba(255,255,255,.3);">photo_camera</span>
			</div>
			<!-- Botao fechar -->
			<button type="button" id="gcep-modal-close" style="position:absolute;top:10px;right:10px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(0,0,0,.4);color:#fff;border:none;cursor:pointer;backdrop-filter:blur(4px);">
				<span class="material-symbols-outlined" style="font-size:18px;">close</span>
			</button>
		</div>

		<!-- Foto de perfil sobreposta -->
		<div style="display:flex;justify-content:center;margin-top:-40px;position:relative;z-index:1;">
			<div id="gcep-modal-logo" style="width:80px;height:80px;border-radius:50%;border:4px solid #fff;background:#f1f5f9;overflow:hidden;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.15);">
				<span class="material-symbols-outlined" style="font-size:32px;color:#cbd5e1;">person</span>
			</div>
		</div>

		<!-- Conteúdo: titulo, categoria, endereco, contatos, botão -->
		<div style="padding:12px 24px 24px;text-align:center;">
			<h3 id="gcep-modal-titulo" style="font-size:18px;font-weight:800;color:#0f172a;line-height:1.3;margin:0 0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></h3>
			<span id="gcep-modal-cat" style="display:inline-block;font-size:11px;font-weight:700;color:#0052cc;background:rgba(0,82,204,.08);padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:.08em;"></span>

			<!-- Endereço completo -->
			<p id="gcep-modal-endereco" style="display:none;font-size:12px;color:#64748b;margin:10px 0 0;line-height:1.4;"></p>

			<!-- Ícones de contato -->
			<div id="gcep-modal-contatos" style="display:none;margin-top:12px;justify-content:center;gap:8px;flex-wrap:wrap;">
				<a id="gcep-modal-tel" href="#" style="display:none;width:40px;height:40px;border-radius:50%;border:1px solid #e2e8f0;align-items:center;justify-content:center;color:#64748b;transition:all .2s;text-decoration:none;" title="<?php esc_attr_e( 'Telefone', 'guiawp' ); ?>" aria-label="<?php esc_attr_e( 'Telefone', 'guiawp' ); ?>">
					<span class="material-symbols-outlined" style="font-size:20px;">call</span>
				</a>
				<a id="gcep-modal-wpp" href="#" target="_blank" rel="noopener" style="display:none;width:40px;height:40px;border-radius:50%;border:1px solid #e2e8f0;align-items:center;justify-content:center;color:#64748b;transition:all .2s;text-decoration:none;" title="WhatsApp" aria-label="WhatsApp">
					<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor;"><path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.56 2 2.1 6.46 2.1 11.93c0 1.75.46 3.46 1.33 4.96L2 22l5.24-1.37a9.9 9.9 0 0 0 4.79 1.22h.01c5.47 0 9.93-4.46 9.93-9.93a9.85 9.85 0 0 0-2.92-7.01Zm-7.02 15.26h-.01a8.3 8.3 0 0 1-4.23-1.16l-.3-.18-3.11.81.83-3.03-.2-.31a8.25 8.25 0 0 1-1.27-4.37c0-4.56 3.71-8.27 8.28-8.27 2.21 0 4.28.86 5.84 2.42a8.2 8.2 0 0 1 2.42 5.84c0 4.56-3.71 8.27-8.25 8.27Zm4.54-6.19c-.25-.13-1.47-.73-1.7-.81-.23-.08-.39-.13-.56.13-.16.25-.64.81-.78.98-.14.16-.29.19-.54.06-.25-.13-1.04-.38-1.98-1.21-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.42.08-.16.04-.31-.02-.43-.06-.13-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42h-.47c-.16 0-.43.06-.65.31-.23.25-.86.84-.86 2.04s.88 2.36 1 2.52c.13.16 1.74 2.66 4.21 3.73.59.25 1.05.4 1.41.51.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29Z"/></svg>
				</a>
				<a id="gcep-modal-email" href="#" style="display:none;width:40px;height:40px;border-radius:50%;border:1px solid #e2e8f0;align-items:center;justify-content:center;color:#64748b;transition:all .2s;text-decoration:none;" title="<?php esc_attr_e( 'E-mail', 'guiawp' ); ?>" aria-label="<?php esc_attr_e( 'E-mail', 'guiawp' ); ?>">
					<span class="material-symbols-outlined" style="font-size:20px;">mail</span>
				</a>
				<a id="gcep-modal-site" href="#" target="_blank" rel="noopener" style="display:none;width:40px;height:40px;border-radius:50%;border:1px solid #e2e8f0;align-items:center;justify-content:center;color:#64748b;transition:all .2s;text-decoration:none;" title="<?php esc_attr_e( 'Website', 'guiawp' ); ?>" aria-label="<?php esc_attr_e( 'Website', 'guiawp' ); ?>">
					<span class="material-symbols-outlined" style="font-size:20px;">public</span>
				</a>
			</div>

			<div style="margin-top:16px;">
				<a id="gcep-modal-link" href="#" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;background:#0052cc;color:#fff;font-weight:700;font-size:14px;border-radius:12px;text-decoration:none;transition:background .2s,box-shadow .2s;"
					onmouseover="this.style.background='#003d99';this.style.boxShadow='0 4px 12px rgba(0,82,204,.4)'"
					onmouseout="this.style.background='#0052cc';this.style.boxShadow='none'">
					<span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
					<?php esc_html_e( 'Saiba mais', 'guiawp' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Badge de contagem -->
	<div id="gcep-map-count" class="absolute bottom-6 left-1/2 -translate-x-1/2 z-[1000] bg-white/95 backdrop-blur border border-slate-200 rounded-full px-5 py-2 shadow-lg text-sm font-semibold text-slate-700 flex items-center gap-2">
		<span class="material-symbols-outlined text-[#0052cc] text-lg">pin_drop</span>
		<span id="gcep-map-count-text">0 <?php esc_html_e( 'anúncios no mapa', 'guiawp' ); ?></span>
	</div>
</main>

<?php get_footer(); ?>
