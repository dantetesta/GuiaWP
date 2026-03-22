<?php
/**
 * Template: Admin Dashboard Externo
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.6.0 - 2026-03-11 - Graficos de visitas, ranking top 20 anuncios e posts, filtro de periodo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$resolved_period    = GCEP_Dashboard_Admin::resolve_admin_dashboard_period(
	isset( $_GET['year'] ) ? intval( $_GET['year'] ) : 0,
	isset( $_GET['month'] ) ? intval( $_GET['month'] ) : 0
);
$available_years    = $resolved_period['available_years'] ?? [];
$months_by_year     = $resolved_period['months_by_year'] ?? [];
$available_months   = $resolved_period['available_months'] ?? [];
$has_period_filters = ! empty( $resolved_period['has_periods'] );
$filter_year        = (int) ( $resolved_period['year'] ?? current_time( 'Y' ) );
$filter_month       = (int) ( $resolved_period['month'] ?? current_time( 'm' ) );

$month_labels = [];
for ( $mi = 1; $mi <= 12; $mi++ ) {
	$month_labels[ $mi ] = date_i18n( 'F', mktime( 0, 0, 0, $mi, 1 ) );
}

$stats         = GCEP_Dashboard_Admin::get_admin_stats( $filter_year, $filter_month );
$chart_data    = GCEP_Dashboard_Admin::get_admin_chart_data( $filter_year, $filter_month );
$top_anuncios  = class_exists( 'GCEP_Analytics' ) ? GCEP_Analytics::get_top_anuncios( 20, $filter_year, $filter_month ) : [];
$top_posts     = class_exists( 'GCEP_Analytics' ) ? GCEP_Analytics::get_top_blog_posts( 20, $filter_year, $filter_month ) : [];

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/admin-sidebar.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<header class="h-14 lg:h-16 bg-white/80 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200 px-4 lg:px-8 flex items-center">
		<h2 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Painel de Controle', 'guiawp' ); ?></h2>
	</header>

	<div class="p-4 lg:p-8 max-w-[1400px] w-full mx-auto space-y-8">

		<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
			<div>
				<h1 class="text-3xl font-black tracking-tight text-slate-900"><?php esc_html_e( 'Painel de Controle', 'guiawp' ); ?></h1>
				<p class="text-slate-500 mt-1"><?php esc_html_e( 'Resumo geral da plataforma.', 'guiawp' ); ?></p>
			</div>

			<?php if ( $has_period_filters ) : ?>
			<form method="get" action="<?php echo esc_url( home_url( '/painel-admin' ) ); ?>" id="gcep-admin-period-filter" class="flex items-center gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
				<label class="text-xs font-bold text-slate-500 uppercase"><?php esc_html_e( 'Período:', 'guiawp' ); ?></label>
				<select id="gcep-admin-month" name="month" class="text-sm border-slate-200 rounded-lg focus:ring-primary/20 py-1.5">
					<?php foreach ( $available_months as $m ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $filter_month, $m ); ?>>
							<?php echo esc_html( $month_labels[ $m ] ?? $m ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<select id="gcep-admin-year" name="year" class="text-sm border-slate-200 rounded-lg focus:ring-primary/20 py-1.5">
					<?php foreach ( $available_years as $y ) : ?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $filter_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" id="gcep-admin-filter-submit" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-4 py-1.5 rounded-lg text-sm font-bold transition-colors"><?php esc_html_e( 'Filtrar', 'guiawp' ); ?></button>
			</form>
			<?php endif; ?>
		</div>

		<!-- Cards de estatísticas -->
		<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-6">
			<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
				<div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 mb-3">
					<span class="material-symbols-outlined text-xl">campaign</span>
				</div>
				<p class="text-slate-500 text-xs font-medium"><?php esc_html_e( 'Total Anúncios', 'guiawp' ); ?></p>
				<h3 id="gcep-admin-stat-total" class="text-xl font-bold mt-0.5"><?php echo esc_html( number_format_i18n( $stats['total_anuncios'] ) ); ?></h3>
			</div>
			<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
				<div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 mb-3">
					<span class="material-symbols-outlined text-xl">check_circle</span>
				</div>
				<p class="text-slate-500 text-xs font-medium"><?php esc_html_e( 'Publicados', 'guiawp' ); ?></p>
				<h3 id="gcep-admin-stat-publicados" class="text-xl font-bold mt-0.5"><?php echo esc_html( number_format_i18n( $stats['publicados'] ) ); ?></h3>
			</div>
			<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
				<div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 mb-3">
					<span class="material-symbols-outlined text-xl">hourglass_top</span>
				</div>
				<p class="text-slate-500 text-xs font-medium"><?php esc_html_e( 'Pendentes', 'guiawp' ); ?></p>
				<h3 id="gcep-admin-stat-pendentes" class="text-xl font-bold mt-0.5"><?php echo esc_html( number_format_i18n( $stats['pendentes'] ) ); ?></h3>
			</div>
			<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
				<div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600 mb-3">
					<span class="material-symbols-outlined text-xl">cancel</span>
				</div>
				<p class="text-slate-500 text-xs font-medium"><?php esc_html_e( 'Rejeitados', 'guiawp' ); ?></p>
				<h3 id="gcep-admin-stat-rejeitados" class="text-xl font-bold mt-0.5"><?php echo esc_html( number_format_i18n( $stats['rejeitados'] ) ); ?></h3>
			</div>
			<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
				<div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mb-3">
					<span class="material-symbols-outlined text-xl">group</span>
				</div>
				<p class="text-slate-500 text-xs font-medium"><?php esc_html_e( 'Usuários', 'guiawp' ); ?></p>
				<h3 id="gcep-admin-stat-usuarios" class="text-xl font-bold mt-0.5"><?php echo esc_html( number_format_i18n( $stats['total_usuarios'] ) ); ?></h3>
			</div>
			<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
				<div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600 mb-3">
					<span class="material-symbols-outlined text-xl">visibility</span>
				</div>
				<p class="text-slate-500 text-xs font-medium"><?php esc_html_e( 'Visitas', 'guiawp' ); ?></p>
				<h3 id="gcep-admin-stat-visitas" class="text-xl font-bold mt-0.5"><?php echo esc_html( number_format_i18n( $stats['visitas'] ) ); ?></h3>
			</div>
		</div>

		<!-- Gráfico: Visitas por Dia -->
		<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
			<h2 id="gcep-admin-chart-title" class="text-lg font-bold mb-6">
				<?php printf( esc_html__( 'Visitas por Dia - %s/%s', 'guiawp' ), date_i18n( 'F', mktime( 0, 0, 0, $filter_month, 1 ) ), $filter_year ); ?>
			</h2>
			<canvas id="gcep-admin-visits-chart" class="w-full" style="max-height: 300px;"></canvas>
		</div>

		<!-- Rankings: Top 20 Anúncios + Top 20 Posts -->
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
			<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
				<h2 class="text-lg font-bold mb-4"><?php esc_html_e( 'Top 20 Anúncios', 'guiawp' ); ?></h2>
				<?php if ( ! empty( $top_anuncios ) ) : ?>
				<canvas id="gcep-admin-top-anuncios-chart" style="max-height: 500px;"></canvas>
				<?php else : ?>
				<p class="text-sm text-slate-400 py-8 text-center"><?php esc_html_e( 'Sem dados de visualizações neste período.', 'guiawp' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
				<h2 class="text-lg font-bold mb-4"><?php esc_html_e( 'Top 20 Posts do Blog', 'guiawp' ); ?></h2>
				<?php if ( ! empty( $top_posts ) ) : ?>
				<canvas id="gcep-admin-top-posts-chart" style="max-height: 500px;"></canvas>
				<?php else : ?>
				<p class="text-sm text-slate-400 py-8 text-center"><?php esc_html_e( 'Sem dados de visualizações neste período.', 'guiawp' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Tabela: Anúncios Recentes -->
		<?php
		$recentes = GCEP_Dashboard_Admin::get_anuncios( [ 'posts_per_page' => 10 ] );
		if ( ! empty( $recentes ) ) :
		?>
		<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
			<div class="p-6 border-b border-slate-200 flex justify-between items-center">
				<h3 class="text-lg font-bold"><?php esc_html_e( 'Anúncios Recentes', 'guiawp' ); ?></h3>
				<a href="<?php echo esc_url( home_url( '/painel-admin/anuncios' ) ); ?>" class="text-[#0052cc] text-sm font-bold hover:underline"><?php esc_html_e( 'Ver todos', 'guiawp' ); ?></a>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full text-left">
					<thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
						<tr>
							<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Anúncio', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 hidden sm:table-cell"><?php esc_html_e( 'Usuário', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4"><?php esc_html_e( 'Status', 'guiawp' ); ?></th>
							<th class="px-4 lg:px-6 py-4 text-right"><?php esc_html_e( 'Ações', 'guiawp' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						<?php foreach ( $recentes as $anuncio ) :
							$status        = get_post_meta( $anuncio->ID, 'GCEP_status_anuncio', true ) ?: 'rascunho';
							$color         = GCEP_Helpers::get_status_color( $status );
							$author        = get_userdata( $anuncio->post_author );
							$justificativa = get_post_meta( $anuncio->ID, 'GCEP_ai_justificativa', true );
						?>
						<tr class="hover:bg-slate-50" data-row-id="<?php echo esc_attr( $anuncio->ID ); ?>">
							<td class="px-4 lg:px-6 py-4">
								<p class="text-sm font-semibold truncate max-w-[160px] lg:max-w-[200px]"><?php echo esc_html( $anuncio->post_title ); ?></p>
								<p class="text-xs text-slate-500">ID: #<?php echo esc_html( $anuncio->ID ); ?></p>
							</td>
							<td class="px-4 lg:px-6 py-4 hidden sm:table-cell">
								<span class="text-sm font-medium"><?php echo esc_html( $author ? $author->display_name : '—' ); ?></span>
							</td>
							<td class="px-4 lg:px-6 py-4">
								<span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo esc_attr( GCEP_Helpers::get_status_badge_classes( $status ) ); ?>">
									<?php echo esc_html( GCEP_Helpers::get_status_label_short( $status ) ); ?>
								</span>
							</td>
							<td class="px-4 lg:px-6 py-4 text-right">
								<div class="flex justify-end items-center whitespace-nowrap" style="gap:4px;">
									<?php if ( 'rejeitado' === $status && ! empty( $justificativa ) ) : ?>
									<button type="button" class="gcep-show-rejection-reason inline-flex items-center justify-center w-8 h-8 rounded-lg border border-rose-200 bg-rose-50 text-rose-500 hover:bg-rose-100 transition-colors" data-title="<?php echo esc_attr( $anuncio->post_title ); ?>" data-reason="<?php echo esc_attr( $justificativa ); ?>" title="<?php esc_attr_e( 'Ver motivo da rejeição', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">info</span>
									</button>
									<?php endif; ?>
									<?php if ( 'publicado' !== $status ) : ?>
									<button class="gcep-status-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-status="publicado" title="<?php esc_attr_e( 'Publicar', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">check_circle</span>
									</button>
									<?php endif; ?>
									<?php if ( 'rejeitado' !== $status ) : ?>
									<button class="gcep-status-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-status="rejeitado" title="<?php esc_attr_e( 'Rejeitar', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">cancel</span>
									</button>
									<?php endif; ?>
									<?php if ( 'aguardando_aprovacao' !== $status ) : ?>
									<button class="gcep-status-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600 transition-colors hidden lg:inline-flex" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-status="aguardando_aprovacao" title="<?php esc_attr_e( 'Pendente', 'guiawp' ); ?>">
										<span class="material-symbols-outlined" style="font-size:18px;">hourglass_top</span>
									</button>
									<?php endif; ?>
									<a href="<?php echo esc_url( get_permalink( $anuncio->ID ) ); ?>" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors hidden lg:inline-flex" title="<?php esc_attr_e( 'Visualizar', 'guiawp' ); ?>"><span class="material-symbols-outlined" style="font-size:18px;">visibility</span></a>
									<a href="<?php echo esc_url( home_url( '/painel-admin/editar-anuncio?id=' . $anuncio->ID ) ); ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-[#0052cc] hover:bg-blue-50 hover:text-[#0052cc] transition-colors" title="<?php esc_attr_e( 'Editar', 'guiawp' ); ?>"><span class="material-symbols-outlined" style="font-size:18px;">edit</span></a>
									<button type="button" class="gcep-delete-anuncio-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-colors" data-id="<?php echo esc_attr( $anuncio->ID ); ?>" data-title="<?php echo esc_attr( $anuncio->post_title ); ?>" title="<?php esc_attr_e( 'Remover', 'guiawp' ); ?>"><span class="material-symbols-outlined" style="font-size:18px;">delete</span></button>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endif; ?>

	</div><!-- /.max-w-[1400px] -->
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
	// ===== Gráfico: Visitas por Dia =====
	var lineCtx = document.getElementById('gcep-admin-visits-chart');
	if (lineCtx) {
		var chartData = <?php echo wp_json_encode( array_values( $chart_data ) ); ?>;
		var labels = <?php echo wp_json_encode( array_keys( $chart_data ) ); ?>;

		window.gcepAdminLineChart = new Chart(lineCtx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [{
					label: '<?php echo esc_js( __( 'Visitas', 'guiawp' ) ); ?>',
					data: chartData,
					borderColor: '#0052cc',
					backgroundColor: 'rgba(0, 82, 204, 0.1)',
					borderWidth: 2,
					fill: true,
					tension: 0.4,
					pointRadius: 3,
					pointHoverRadius: 5,
					pointBackgroundColor: '#0052cc',
					pointBorderColor: '#fff',
					pointBorderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 12, cornerRadius: 8 } },
				scales: {
					y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 }, color: '#64748b' }, grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false } },
					x: { ticks: { font: { size: 11 }, color: '#64748b', maxRotation: 0 }, grid: { display: false } }
				}
			}
		});
	}

	// ===== Helper: criar gráfico de barras horizontal =====
	function createBarChart(canvasId, items, color) {
		var el = document.getElementById(canvasId);
		if (!el || !items || !items.length) return null;

		var labels = items.map(function(i) {
			var t = i.title || '';
			return t.length > 35 ? t.substring(0, 35) + '…' : t;
		});
		var data = items.map(function(i) { return i.views || 0; });
		var h = Math.max(250, items.length * 28);
		el.parentElement.style.maxHeight = h + 'px';
		el.style.maxHeight = h + 'px';

		return new Chart(el, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					data: data,
					backgroundColor: color,
					borderRadius: 4,
					barThickness: 18
				}]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 10, cornerRadius: 8 }
				},
				scales: {
					x: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 }, color: '#64748b' }, grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false } },
					y: { ticks: { font: { size: 11 }, color: '#334155' }, grid: { display: false } }
				}
			}
		});
	}

	var topAnunciosData = <?php echo wp_json_encode( array_map( function ( $r ) {
		return [ 'title' => $r['post_title'] ?? '', 'views' => (int) ( $r['views'] ?? 0 ) ];
	}, $top_anuncios ) ); ?>;

	var topPostsData = <?php echo wp_json_encode( array_map( function ( $r ) {
		return [ 'title' => $r['post_title'] ?? '', 'views' => (int) ( $r['views'] ?? 0 ) ];
	}, $top_posts ) ); ?>;

	window.gcepAdminBarAnuncios = createBarChart('gcep-admin-top-anuncios-chart', topAnunciosData, 'rgba(0, 82, 204, 0.7)');
	window.gcepAdminBarPosts    = createBarChart('gcep-admin-top-posts-chart', topPostsData, 'rgba(16, 185, 129, 0.7)');

	// ===== Filtro AJAX =====
	var form        = document.getElementById('gcep-admin-period-filter');
	var yearSelect  = document.getElementById('gcep-admin-year');
	var monthSelect = document.getElementById('gcep-admin-month');
	if (!form || !yearSelect || !monthSelect) return;

	var monthsByYear   = <?php echo wp_json_encode( $months_by_year ); ?>;
	var monthLabels    = <?php echo wp_json_encode( $month_labels ); ?>;
	var submitBtn      = document.getElementById('gcep-admin-filter-submit');
	var chartTitle     = document.getElementById('gcep-admin-chart-title');
	var nf             = new Intl.NumberFormat('pt-BR');

	var statsMap = {
		total_anuncios: document.getElementById('gcep-admin-stat-total'),
		publicados:     document.getElementById('gcep-admin-stat-publicados'),
		pendentes:      document.getElementById('gcep-admin-stat-pendentes'),
		rejeitados:     document.getElementById('gcep-admin-stat-rejeitados'),
		total_usuarios: document.getElementById('gcep-admin-stat-usuarios'),
		visitas:        document.getElementById('gcep-admin-stat-visitas')
	};

	function rebuildMonthOptions(selYear, prefMonth) {
		var key = String(selYear);
		var months = Array.isArray(monthsByYear[key]) ? monthsByYear[key] : [];
		monthSelect.innerHTML = '';
		months.forEach(function(m) {
			var o = document.createElement('option');
			o.value = String(m);
			o.textContent = monthLabels[String(m)] || String(m);
			if (String(prefMonth) === String(m)) o.selected = true;
			monthSelect.appendChild(o);
		});
		monthSelect.disabled = !months.length;
		if (!monthSelect.value && months.length) monthSelect.value = String(months[0]);
	}

	yearSelect.addEventListener('change', function() {
		rebuildMonthOptions(yearSelect.value, monthSelect.value);
	});
	rebuildMonthOptions(yearSelect.value, monthSelect.value);

	function updateBarChart(chart, canvasId, items, color) {
		if (chart) { chart.destroy(); chart = null; }
		var container = document.getElementById(canvasId);
		if (!container) return null;
		var wrapper = container.parentElement;
		// Recriar canvas
		var newCanvas = document.createElement('canvas');
		newCanvas.id = canvasId;
		container.replaceWith(newCanvas);
		if (!items || !items.length) {
			newCanvas.style.display = 'none';
			wrapper.style.maxHeight = '';
			return null;
		}
		newCanvas.style.display = '';
		return createBarChart(canvasId, items, color);
	}

	form.addEventListener('submit', function(e) {
		e.preventDefault();
		if (typeof gcepData === 'undefined' || !gcepData.ajaxUrl) { form.submit(); return; }

		var origLabel = submitBtn ? submitBtn.textContent : '';
		if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = '<?php echo esc_js( __( 'Filtrando...', 'guiawp' ) ); ?>'; }

		var payload = new URLSearchParams();
		payload.set('action', 'gcep_filter_admin_dashboard');
		payload.set('nonce', gcepData.nonce || '');
		payload.set('year', yearSelect.value);
		payload.set('month', monthSelect.value);

		fetch(gcepData.ajaxUrl, {
			method: 'POST', credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: payload.toString()
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (!res || !res.success || !res.data) throw new Error(res?.data?.message || '<?php echo esc_js( __( 'Erro ao filtrar.', 'guiawp' ) ); ?>');

			var d = res.data;
			var period = d.period || {};
			if (period.year) { yearSelect.value = String(period.year); rebuildMonthOptions(period.year, period.month); }
			if (chartTitle && period.label) chartTitle.textContent = period.label;

			// Stats
			var st = d.stats || {};
			Object.keys(statsMap).forEach(function(k) {
				if (statsMap[k] && typeof st[k] !== 'undefined') statsMap[k].textContent = nf.format(Number(st[k]) || 0);
			});

			// Gráfico de linha
			if (window.gcepAdminLineChart && d.chart) {
				window.gcepAdminLineChart.data.labels = d.chart.labels || [];
				window.gcepAdminLineChart.data.datasets[0].data = d.chart.data || [];
				window.gcepAdminLineChart.update();
			}

			// Rankings
			window.gcepAdminBarAnuncios = updateBarChart(window.gcepAdminBarAnuncios, 'gcep-admin-top-anuncios-chart', d.top_anuncios || [], 'rgba(0, 82, 204, 0.7)');
			window.gcepAdminBarPosts    = updateBarChart(window.gcepAdminBarPosts, 'gcep-admin-top-posts-chart', d.top_posts || [], 'rgba(16, 185, 129, 0.7)');

			// URL
			var url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
			url.searchParams.set('year', period.year || yearSelect.value);
			url.searchParams.set('month', period.month || monthSelect.value);
			window.history.replaceState({}, '', url.pathname + url.search);
		})
		.catch(function(err) { gcepToast(err.message, 'error'); })
		.finally(function() { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = origLabel; } });
	});
})();
</script>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-rejection-modal.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
