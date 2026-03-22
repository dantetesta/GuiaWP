<?php
/**
 * Template: Dashboard Anunciante - Visão Geral
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

$user_id = get_current_user_id();
$user    = wp_get_current_user();

$resolved_period     = GCEP_Dashboard_Advertiser::resolve_user_dashboard_period(
	$user_id,
	isset( $_GET['year'] ) ? intval( $_GET['year'] ) : 0,
	isset( $_GET['month'] ) ? intval( $_GET['month'] ) : 0
);
$available_periods   = $resolved_period['available_periods'] ?? [];
$available_years     = $resolved_period['available_years'] ?? [];
$months_by_year      = $resolved_period['months_by_year'] ?? [];
$available_months    = $resolved_period['available_months'] ?? [];
$has_period_filters  = ! empty( $resolved_period['has_periods'] );
$filter_year         = (int) ( $resolved_period['year'] ?? current_time( 'Y' ) );
$filter_month        = (int) ( $resolved_period['month'] ?? current_time( 'm' ) );

$month_labels = [];
for ( $month_index = 1; $month_index <= 12; $month_index++ ) {
	$month_labels[ $month_index ] = date_i18n( 'F', mktime( 0, 0, 0, $month_index, 1 ) );
}

$stats           = GCEP_Dashboard_Advertiser::get_user_stats( $user_id, $filter_year, $filter_month );
$chart_data      = GCEP_Dashboard_Advertiser::get_user_chart_data( $user_id, $filter_year, $filter_month );
$expirados       = GCEP_Dashboard_Advertiser::get_user_anuncios( $user_id, 5, [ 'expirado' ] );
$recent_anuncios = GCEP_Dashboard_Advertiser::get_user_anuncios( $user_id, 5 );

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-sidebar.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<header class="h-14 lg:h-16 bg-white/80 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-3">
		<h2 class="text-base lg:text-lg font-bold text-slate-900 truncate"><?php esc_html_e( 'Visão Geral', 'guiawp' ); ?></h2>
		<a href="<?php echo esc_url( home_url( '/painel/criar-anuncio' ) ); ?>" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-3 lg:px-4 py-1.5 lg:py-2 rounded-lg font-bold text-xs flex items-center gap-1.5 shadow-sm hover:shadow-md transition-all flex-shrink-0">
			<span class="material-symbols-outlined text-base">add</span>
			<span class="hidden sm:inline"><?php esc_html_e( 'Criar Anúncio', 'guiawp' ); ?></span>
		</a>
	</header>

	<div class="p-4 lg:p-8 max-w-[1400px] w-full mx-auto space-y-8">
		<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
			<div>
				<h1 class="text-3xl font-black tracking-tight text-slate-900">
					<?php printf( esc_html__( 'Bem-vindo, %s 👋', 'guiawp' ), esc_html( $user->display_name ) ); ?>
				</h1>
				<p class="text-slate-500 mt-1"><?php esc_html_e( 'Aqui está o que está acontecendo com seus anúncios hoje.', 'guiawp' ); ?></p>
			</div>

			<?php if ( $has_period_filters ) : ?>
			<form method="get" action="<?php echo esc_url( home_url( '/painel' ) ); ?>" id="gcep-dashboard-period-filter" class="flex items-center gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
				<label class="text-xs font-bold text-slate-500 uppercase"><?php esc_html_e( 'Período:', 'guiawp' ); ?></label>
				<select id="gcep-dashboard-month" name="month" class="text-sm border-slate-200 rounded-lg focus:ring-primary/20 py-1.5">
					<?php foreach ( $available_months as $m ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $filter_month, $m ); ?>>
							<?php echo esc_html( $month_labels[ $m ] ?? $m ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<select id="gcep-dashboard-year" name="year" class="text-sm border-slate-200 rounded-lg focus:ring-primary/20 py-1.5">
					<?php foreach ( $available_years as $y ) : ?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $filter_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
					<?php endforeach; ?>
				</select>
					<button type="submit" id="gcep-dashboard-filter-submit" class="bg-[#0052cc] hover:bg-[#003d99] text-white px-4 py-1.5 rounded-lg text-sm font-bold transition-colors"><?php esc_html_e( 'Filtrar', 'guiawp' ); ?></button>
				</form>
			<?php else : ?>
			<div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm text-sm text-slate-500">
				<?php esc_html_e( 'Os filtros de período aparecem quando você tiver anúncios criados.', 'guiawp' ); ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
			<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
				<div class="flex items-center justify-between mb-4">
					<div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
						<span class="material-symbols-outlined text-2xl">campaign</span>
					</div>
				</div>
				<p class="text-slate-500 text-sm font-medium"><?php esc_html_e( 'Total de Anúncios', 'guiawp' ); ?></p>
				<h3 id="gcep-dashboard-stat-total" class="text-2xl font-bold mt-1"><?php echo esc_html( $stats['total'] ); ?></h3>
			</div>

			<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
				<div class="flex items-center justify-between mb-4">
					<div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
						<span class="material-symbols-outlined text-2xl">check_circle</span>
					</div>
				</div>
				<p class="text-slate-500 text-sm font-medium"><?php esc_html_e( 'Publicados', 'guiawp' ); ?></p>
				<h3 id="gcep-dashboard-stat-publicados" class="text-2xl font-bold mt-1"><?php echo esc_html( $stats['publicados'] ); ?></h3>
			</div>

			<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
				<div class="flex items-center justify-between mb-4">
					<div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600">
						<span class="material-symbols-outlined text-2xl">hourglass_top</span>
					</div>
				</div>
				<p class="text-slate-500 text-sm font-medium"><?php esc_html_e( 'Pendentes', 'guiawp' ); ?></p>
				<h3 id="gcep-dashboard-stat-pendentes" class="text-2xl font-bold mt-1"><?php echo esc_html( $stats['pendentes'] ); ?></h3>
			</div>

			<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
				<div class="flex items-center justify-between mb-4">
					<div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600">
						<span class="material-symbols-outlined text-2xl">cancel</span>
					</div>
				</div>
				<p class="text-slate-500 text-sm font-medium"><?php esc_html_e( 'Rejeitados', 'guiawp' ); ?></p>
				<h3 id="gcep-dashboard-stat-rejeitados" class="text-2xl font-bold mt-1"><?php echo esc_html( $stats['rejeitados'] ); ?></h3>
			</div>

			<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
				<div class="flex items-center justify-between mb-4">
					<div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600">
						<span class="material-symbols-outlined text-2xl">visibility</span>
					</div>
				</div>
				<p class="text-slate-500 text-sm font-medium"><?php esc_html_e( 'Visitas', 'guiawp' ); ?></p>
				<h3 id="gcep-dashboard-stat-visitas" class="text-2xl font-bold mt-1"><?php echo esc_html( number_format_i18n( $stats['visitas'] ) ); ?></h3>
			</div>
		</div>

		<!-- Gráfico de Visitas -->
		<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
			<h2 id="gcep-dashboard-chart-title" class="text-lg font-bold mb-6"><?php printf( esc_html__( 'Visitas por Dia - %s/%s', 'guiawp' ), date_i18n( 'F', mktime( 0, 0, 0, $filter_month, 1 ) ), $filter_year ); ?></h2>
			<canvas id="gcep-visits-chart" class="w-full" style="max-height: 300px;"></canvas>
		</div>

		<?php if ( ! empty( $expirados ) ) : ?>
		<div class="space-y-3">
			<?php foreach ( $expirados as $exp ) : ?>
			<div class="bg-amber-50 border border-amber-200 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">
				<div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
					<span class="material-symbols-outlined text-amber-600">schedule</span>
				</div>
				<div class="flex-1">
					<p class="text-sm font-bold text-amber-800">
						<?php printf( esc_html__( '"%s" expirou', 'guiawp' ), esc_html( $exp->post_title ) ); ?>
					</p>
					<p class="text-xs text-amber-600 mt-1"><?php esc_html_e( 'Seu anúncio está fora do ar. Renove para voltar a exibir.', 'guiawp' ); ?></p>
				</div>
				<a href="<?php echo esc_url( home_url( '/painel/renovar?anuncio_id=' . $exp->ID ) ); ?>" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition-colors flex-shrink-0">
					<?php esc_html_e( 'Renovar Anúncio', 'guiawp' ); ?>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php /* Seção Anúncios Recentes removida em 1.9.2 - 2026-03-21 */ ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
	var ctx = document.getElementById('gcep-visits-chart');
	if (!ctx) return;

	var chartData = <?php echo wp_json_encode( array_values( $chart_data ) ); ?>;
	var labels = <?php echo wp_json_encode( array_keys( $chart_data ) ); ?>;

	window.gcepDashboardChart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: labels,
			datasets: [{
				label: '<?php esc_html_e( 'Visitas', 'guiawp' ); ?>',
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
			plugins: {
				legend: {
					display: false
				},
				tooltip: {
					backgroundColor: 'rgba(0, 0, 0, 0.8)',
					padding: 12,
					titleFont: { size: 13, weight: 'bold' },
					bodyFont: { size: 12 },
					cornerRadius: 8
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						precision: 0,
						font: { size: 11 },
						color: '#64748b'
					},
					grid: {
						color: 'rgba(0, 0, 0, 0.05)',
						drawBorder: false
					}
				},
				x: {
					ticks: {
						font: { size: 11 },
						color: '#64748b',
						maxRotation: 0
					},
					grid: {
						display: false
					}
				}
			}
		}
	});
})();

(function(){
	var form = document.getElementById('gcep-dashboard-period-filter');
	var yearSelect = document.getElementById('gcep-dashboard-year');
	var monthSelect = document.getElementById('gcep-dashboard-month');
	if (!form || !yearSelect || !monthSelect) return;

	var monthsByYear = <?php echo wp_json_encode( $months_by_year ); ?>;
	var monthLabels = <?php echo wp_json_encode( $month_labels ); ?>;
	var submitButton = document.getElementById('gcep-dashboard-filter-submit');
	var chartTitle = document.getElementById('gcep-dashboard-chart-title');
	var numberFormatter = new Intl.NumberFormat('pt-BR');
	var statsMap = {
		total: document.getElementById('gcep-dashboard-stat-total'),
		publicados: document.getElementById('gcep-dashboard-stat-publicados'),
		pendentes: document.getElementById('gcep-dashboard-stat-pendentes'),
		rejeitados: document.getElementById('gcep-dashboard-stat-rejeitados'),
		visitas: document.getElementById('gcep-dashboard-stat-visitas')
	};

	function rebuildMonthOptions(selectedYear, preferredMonth) {
		var yearKey = String(selectedYear);
		var months = Array.isArray(monthsByYear[yearKey]) ? monthsByYear[yearKey] : [];
		var nextMonth = String(preferredMonth || '');

		monthSelect.innerHTML = '';

		months.forEach(function(month) {
			var monthValue = String(month);
			var option = document.createElement('option');
			option.value = monthValue;
			option.textContent = monthLabels[monthValue] || monthValue;

			if (nextMonth === monthValue) {
				option.selected = true;
			}

			monthSelect.appendChild(option);
		});

		if (!months.length) {
			monthSelect.disabled = true;
			return;
		}

		monthSelect.disabled = false;

		if (!monthSelect.value) {
			monthSelect.value = String(months[0]);
		}
	}

	yearSelect.addEventListener('change', function() {
		rebuildMonthOptions(yearSelect.value, monthSelect.value);
	});

	rebuildMonthOptions(yearSelect.value, monthSelect.value);

	function updateStats(stats) {
		if (!stats) return;

		Object.keys(statsMap).forEach(function(key) {
			if (!statsMap[key] || typeof stats[key] === 'undefined') return;
			statsMap[key].textContent = numberFormatter.format(Number(stats[key]) || 0);
		});
	}

	function updateChart(chartPayload, title) {
		if (chartTitle && title) {
			chartTitle.textContent = title;
		}

		if (!window.gcepDashboardChart || !chartPayload) {
			return;
		}

		window.gcepDashboardChart.data.labels = Array.isArray(chartPayload.labels) ? chartPayload.labels : [];
		window.gcepDashboardChart.data.datasets[0].data = Array.isArray(chartPayload.data) ? chartPayload.data : [];
		window.gcepDashboardChart.update();
	}

		function updateDashboardUrl(year, month) {
			var actionUrl = form.getAttribute('action');
			var url = actionUrl ? new URL(actionUrl, window.location.origin) : new URL(window.location.href);
			url.searchParams.set('year', year);
			url.searchParams.set('month', month);
			window.history.replaceState({}, '', url.pathname + url.search + url.hash);
		}

	form.addEventListener('submit', function(event) {
		event.preventDefault();

		if (typeof gcepData === 'undefined' || !gcepData.ajaxUrl) {
			form.submit();
			return;
		}

		var originalLabel = submitButton ? submitButton.textContent : '';
		if (submitButton) {
			submitButton.disabled = true;
			submitButton.textContent = '<?php echo esc_js( __( 'Filtrando...', 'guiawp' ) ); ?>';
		}

		var payload = new URLSearchParams();
		payload.set('action', 'gcep_filter_dashboard');
		payload.set('nonce', gcepData.nonce || '');
		payload.set('year', yearSelect.value);
		payload.set('month', monthSelect.value);

		fetch(gcepData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: payload.toString()
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(result) {
			if (!result || !result.success || !result.data) {
				throw new Error((result && result.data && result.data.message) || '<?php echo esc_js( __( 'Não foi possível filtrar o dashboard.', 'guiawp' ) ); ?>');
			}

			var period = result.data.period || {};
			if (period.year) {
				yearSelect.value = String(period.year);
				rebuildMonthOptions(period.year, period.month);
			}

			updateStats(result.data.stats || {});
			updateChart(result.data.chart || {}, period.label || '');
			updateDashboardUrl(period.year || yearSelect.value, period.month || monthSelect.value);
		})
		.catch(function(error) {
			gcepToast(error.message || '<?php echo esc_js( __( 'Não foi possível filtrar o dashboard.', 'guiawp' ) ); ?>', 'error');
		})
		.finally(function() {
			if (submitButton) {
				submitButton.disabled = false;
				submitButton.textContent = originalLabel;
			}
		});
	});
})();
</script>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-rejection-modal.php'; ?>
<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-footer.php'; ?>
