<?php
/**
 * Template: Pagina de Pagamento Premium
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.3.0 - 2026-03-11 - Integracao Mercado Pago (PIX + Cartao) e Pagou (PIX)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$anuncio_id   = intval( $_GET['anuncio_id'] ?? 0 );
$payment      = GCEP_Payment_Handler::get_payment_info();
$gateway      = GCEP_Gateway::get_active();
$gateway_id   = $gateway ? $gateway->get_id() : '';
$tem_gateway  = ! empty( $gateway );
$tem_cartao   = $tem_gateway && $gateway->suporta_cartao();
$current_route = (string) get_query_var( 'gcep_route', 'painel/pagamento' );
$is_admin_context = str_starts_with( $current_route, 'painel-admin' );
$preco        = (float) get_post_meta( $anuncio_id, 'GCEP_plano_preco', true );
$plano_nome   = '';
$plano_id     = (int) get_post_meta( $anuncio_id, 'GCEP_plano_id', true );
if ( $plano_id && class_exists( 'GCEP_Plans' ) ) {
	$plan_data  = GCEP_Plans::get( $plano_id );
	$plano_nome = $plan_data ? $plan_data['name'] : '';
}

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/' . ( $is_admin_context ? 'admin-sidebar' : 'dashboard-sidebar' ) . '.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<header class="h-14 lg:h-16 bg-white/80 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200 px-4 lg:px-8 flex items-center">
		<h2 class="text-base lg:text-lg font-bold text-slate-900"><?php esc_html_e( 'Pagamento Premium', 'guiawp' ); ?></h2>
	</header>

	<div class="p-4 sm:p-8 max-w-[1000px] w-full mx-auto">

		<!-- Mensagem de sucesso (oculta) -->
		<div id="gcep-msg-sucesso" style="display:none" class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-5">
			<span class="material-symbols-outlined text-3xl text-emerald-500">check_circle</span>
			<div>
				<p class="font-bold text-lg"><?php esc_html_e( 'Pagamento confirmado!', 'guiawp' ); ?></p>
				<p class="text-sm"><?php esc_html_e( 'Redirecionando para seus anuncios...', 'guiawp' ); ?></p>
			</div>
		</div>

		<!-- Mensagem de erro (oculta) -->
		<div id="gcep-msg-erro" style="display:none" class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
			<p class="font-bold"><?php esc_html_e( 'Erro', 'guiawp' ); ?></p>
			<p id="gcep-msg-erro-texto" class="text-sm mt-1"></p>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

			<!-- Coluna principal -->
			<div class="lg:col-span-7 flex flex-col gap-6">
				<div>
					<h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900"><?php esc_html_e( 'Finalize sua Assinatura', 'guiawp' ); ?></h1>
					<p class="text-slate-600 mt-1">
						<?php if ( $plano_nome && $preco > 0 ) : ?>
							<?php printf( esc_html__( '%s — R$ %s', 'guiawp' ), esc_html( $plano_nome ), esc_html( number_format( $preco, 2, ',', '.' ) ) ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Escolha a forma de pagamento abaixo.', 'guiawp' ); ?>
						<?php endif; ?>
					</p>
				</div>

				<?php if ( $tem_gateway ) : ?>
				<!-- Abas de pagamento via gateway -->
				<div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">

					<!-- Tabs -->
					<div class="p-3 bg-slate-100 flex gap-1 rounded-t-xl">
						<button type="button" id="gcep-tab-pix" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm transition-all bg-white text-slate-900 shadow-sm font-bold">
							<span class="material-symbols-outlined text-lg">qr_code_2</span> PIX
						</button>
						<?php if ( $tem_cartao ) : ?>
						<button type="button" id="gcep-tab-cartao" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm transition-all text-slate-500 hover:text-slate-700">
							<span class="material-symbols-outlined text-lg">credit_card</span> <?php esc_html_e( 'Cartao', 'guiawp' ); ?>
						</button>
						<?php endif; ?>
					</div>

					<?php if ( 'mercadopago' === $gateway_id && ! $tem_cartao ) : ?>
					<div class="mx-6 mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
						<?php esc_html_e( 'Pagamento com cartão foi desativado por padrão nesta versão por segurança. Use PIX ou integre tokenização client-side oficial antes de reativar cartão.', 'guiawp' ); ?>
					</div>
					<?php endif; ?>

					<!-- Painel PIX -->
					<div id="gcep-panel-pix" class="p-6">

						<!-- Formulario PIX -->
						<form id="gcep-pay-form-pix" class="space-y-4">
							<input type="hidden" name="anuncio_id" value="<?php echo esc_attr( $anuncio_id ); ?>">
							<div>
								<label class="block text-sm font-semibold text-slate-700 mb-1"><?php esc_html_e( 'CPF', 'guiawp' ); ?></label>
								<input type="text" name="cpf" required maxlength="14" placeholder="000.000.000-00"
									class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm">
							</div>
							<button type="submit" class="w-full py-3 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-xl font-bold shadow-lg shadow-[#0052cc]/20 transition-all flex items-center justify-center gap-2">
								<span class="material-symbols-outlined">qr_code_2</span>
								<?php printf( esc_html__( 'Gerar PIX — R$ %s', 'guiawp' ), esc_html( number_format( $preco, 2, ',', '.' ) ) ); ?>
							</button>
						</form>

						<!-- QR Code (oculto ate gerar) -->
						<div id="gcep-qr-section" style="display:none" class="space-y-5">
							<div class="flex flex-col items-center gap-4">
								<img id="gcep-qr-img" alt="QR Code PIX" class="w-56 h-56 rounded-xl border-4 border-slate-200 shadow-md">
								<div class="w-full">
									<label class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wider"><?php esc_html_e( 'Codigo Copia e Cola', 'guiawp' ); ?></label>
									<div class="flex items-center gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
										<code id="gcep-qr-code" class="text-xs text-slate-600 truncate flex-1 text-left break-all"></code>
										<button type="button" id="gcep-btn-copiar" class="shrink-0 flex items-center gap-1 bg-[#0052cc] hover:bg-[#003d99] text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
											<span class="material-symbols-outlined text-sm">content_copy</span> <?php esc_html_e( 'Copiar PIX', 'guiawp' ); ?>
										</button>
									</div>
								</div>
							</div>
							<div class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-lg p-3">
								<div class="flex items-center gap-2">
									<span class="material-symbols-outlined text-amber-500 animate-pulse">schedule</span>
									<p id="gcep-pix-status" class="text-sm font-medium text-amber-600"><?php esc_html_e( 'Aguardando pagamento...', 'guiawp' ); ?></p>
								</div>
								<span id="gcep-pix-timer" class="text-sm font-mono font-bold text-amber-700">30:00</span>
							</div>
						</div>
					</div>

					<?php if ( $tem_cartao ) : ?>
					<!-- Painel Cartao -->
					<div id="gcep-panel-cartao" style="display:none" class="p-6">
						<form id="gcep-pay-form-cartao" class="space-y-4">
							<input type="hidden" name="anuncio_id" value="<?php echo esc_attr( $anuncio_id ); ?>">

							<div>
								<label class="block text-sm font-semibold text-slate-700 mb-1"><?php esc_html_e( 'CPF do titular', 'guiawp' ); ?></label>
								<input type="text" name="cpf" required maxlength="14" placeholder="000.000.000-00"
									class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm">
							</div>

							<div>
								<label class="block text-sm font-semibold text-slate-700 mb-1"><?php esc_html_e( 'Nome no cartao', 'guiawp' ); ?></label>
								<input type="text" name="cartao_titular" required placeholder="NOME COMO NO CARTAO"
									class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm uppercase">
							</div>

							<div>
								<label class="block text-sm font-semibold text-slate-700 mb-1"><?php esc_html_e( 'Numero do cartao', 'guiawp' ); ?></label>
								<input type="text" name="cartao_numero" required maxlength="19" placeholder="0000 0000 0000 0000" inputmode="numeric"
									class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm font-mono tracking-wider">
							</div>

							<div class="grid grid-cols-3 gap-3">
								<div>
									<label class="block text-sm font-semibold text-slate-700 mb-1"><?php esc_html_e( 'Mes', 'guiawp' ); ?></label>
									<select name="cartao_mes" required class="w-full px-3 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm">
										<option value="">--</option>
										<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
										<option value="<?php echo esc_attr( $m ); ?>"><?php echo esc_html( str_pad( $m, 2, '0', STR_PAD_LEFT ) ); ?></option>
										<?php endfor; ?>
									</select>
								</div>
								<div>
									<label class="block text-sm font-semibold text-slate-700 mb-1"><?php esc_html_e( 'Ano', 'guiawp' ); ?></label>
									<select name="cartao_ano" required class="w-full px-3 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm">
										<option value="">--</option>
										<?php for ( $y = (int) gmdate( 'Y' ); $y <= (int) gmdate( 'Y' ) + 12; $y++ ) : ?>
										<option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
										<?php endfor; ?>
									</select>
								</div>
								<div>
									<label class="block text-sm font-semibold text-slate-700 mb-1">CVV</label>
									<input type="text" name="cartao_cvv" required maxlength="4" placeholder="123" inputmode="numeric"
										class="w-full px-3 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0052cc]/30 focus:border-[#0052cc] transition-all text-sm font-mono text-center">
								</div>
							</div>

							<div id="gcep-cartao-erro" style="display:none" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm font-medium"></div>

							<button type="submit" class="w-full py-3 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-xl font-bold shadow-lg shadow-[#0052cc]/20 transition-all flex items-center justify-center gap-2">
								<span class="material-symbols-outlined">credit_card</span>
								<?php printf( esc_html__( 'Pagar R$ %s', 'guiawp' ), esc_html( number_format( $preco, 2, ',', '.' ) ) ); ?>
							</button>
						</form>
					</div>
					<?php endif; ?>

					<!-- Rodape seguranca -->
					<div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
						<div class="flex items-center gap-2 text-slate-500">
							<span class="material-symbols-outlined text-green-500">lock</span>
							<span class="text-xs font-medium uppercase tracking-wider"><?php esc_html_e( 'Pagamento Seguro', 'guiawp' ); ?></span>
						</div>
						<span class="text-xs text-slate-400">
							<?php
							if ( 'mercadopago' === $gateway_id ) {
								esc_html_e( 'Processado por Mercado Pago', 'guiawp' );
							} elseif ( 'pagou' === $gateway_id ) {
								esc_html_e( 'Processado por Pagou', 'guiawp' );
							}
							?>
						</span>
					</div>
				</div>
				<?php endif; ?>

				<!-- Pagamento manual (fallback ou quando nao ha gateway) -->
				<div id="gcep-panel-manual" class="<?php echo $tem_gateway ? 'hidden' : ''; ?>">
					<div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
						<div class="p-6 border-b border-slate-200 flex items-center gap-3">
							<div class="size-10 bg-[#0052cc]/10 rounded-full flex items-center justify-center text-[#0052cc]">
								<span class="material-symbols-outlined">verified</span>
							</div>
							<div>
								<h3 class="font-bold text-lg"><?php esc_html_e( 'Pagamento Manual', 'guiawp' ); ?></h3>
								<p class="text-slate-500 text-xs"><?php esc_html_e( 'PIX com aprovacao manual', 'guiawp' ); ?></p>
							</div>
						</div>

						<div class="p-6">
							<div class="bg-[#0052cc]/5 rounded-lg p-6 flex flex-col items-center gap-6 border border-dashed border-[#0052cc]/30">
								<?php if ( $payment['chave_pix'] ) : ?>
								<div class="w-full space-y-3 text-center">
									<p class="text-sm font-medium text-slate-600"><?php esc_html_e( 'Copie a chave PIX abaixo:', 'guiawp' ); ?></p>
									<div class="flex items-center gap-2 bg-white p-3 rounded-lg border border-slate-200">
										<code class="text-xs text-slate-600 truncate flex-1 text-left" id="gcep-pix-key"><?php echo esc_html( $payment['chave_pix'] ); ?></code>
										<button type="button" onclick="navigator.clipboard.writeText(document.getElementById('gcep-pix-key').textContent)" class="flex items-center gap-1 bg-[#0052cc] hover:bg-[#003d99] text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
											<span class="material-symbols-outlined text-sm">content_copy</span> COPIAR
										</button>
									</div>
									<?php if ( $payment['nome_recebedor'] ) : ?>
										<p class="text-xs text-slate-500"><?php printf( esc_html__( 'Recebedor: %s', 'guiawp' ), esc_html( $payment['nome_recebedor'] ) ); ?></p>
									<?php endif; ?>
								</div>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $payment['instrucoes'] ) : ?>
						<div class="px-6 pb-6">
							<p class="text-sm text-slate-600"><?php echo esc_html( $payment['instrucoes'] ); ?></p>
						</div>
						<?php endif; ?>
					</div>

					<?php if ( $payment['whatsapp'] ) : ?>
					<a href="<?php echo esc_url( GCEP_Helpers::get_whatsapp_url( (string) $payment['whatsapp'], sprintf( __( 'Ola! Realizei o pagamento do anuncio #%d e gostaria de confirmar.', 'guiawp' ), $anuncio_id ) ) ); ?>" target="_blank" class="mt-4 flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white font-bold py-3 rounded-lg transition-colors shadow-lg shadow-[#25D366]/20">
						<span class="material-symbols-outlined">chat_bubble</span>
						<?php esc_html_e( 'Enviar Comprovante via WhatsApp', 'guiawp' ); ?>
					</a>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-4">
						<input type="hidden" name="action" value="gcep_confirm_payment">
						<input type="hidden" name="anuncio_id" value="<?php echo esc_attr( $anuncio_id ); ?>">
						<?php wp_nonce_field( 'gcep_confirm_payment', 'gcep_payment_nonce' ); ?>
						<button type="submit" class="w-full py-3 bg-[#0052cc] hover:bg-[#003d99] text-white rounded-xl font-bold shadow-lg shadow-[#0052cc]/20 transition-all">
							<?php esc_html_e( 'Ja realizei o pagamento', 'guiawp' ); ?>
						</button>
					</form>

					<?php if ( $payment['prazo'] ) : ?>
					<p class="text-sm text-slate-500 text-center mt-3">
						<?php printf( esc_html__( 'A aprovacao pode levar ate %s horas.', 'guiawp' ), esc_html( $payment['prazo'] ) ); ?>
					</p>
					<?php endif; ?>
				</div>

			</div>

			<!-- Sidebar beneficios -->
			<div class="lg:col-span-5 flex flex-col gap-6">

				<!-- Card resumo -->
				<?php if ( $preco > 0 ) : ?>
				<div class="bg-gradient-to-br from-[#0052cc] to-[#003d99] text-white rounded-xl p-6 shadow-lg">
					<p class="text-sm font-medium opacity-80"><?php esc_html_e( 'Total a pagar', 'guiawp' ); ?></p>
					<p class="text-4xl font-black mt-1">R$ <?php echo esc_html( number_format( $preco, 2, ',', '.' ) ); ?></p>
					<?php if ( $plano_nome ) : ?>
					<p class="text-sm mt-2 opacity-80"><?php echo esc_html( $plano_nome ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
					<h4 class="font-bold text-lg mb-4"><?php esc_html_e( 'O que voce recebe:', 'guiawp' ); ?></h4>
					<ul class="space-y-4">
						<li class="flex items-start gap-3">
							<span class="material-symbols-outlined text-[#0052cc] text-xl">check_circle</span>
							<div>
								<p class="text-sm font-semibold"><?php esc_html_e( 'Galeria de ate 30 fotos', 'guiawp' ); ?></p>
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Mostre seu trabalho com imagens de qualidade.', 'guiawp' ); ?></p>
							</div>
						</li>
						<li class="flex items-start gap-3">
							<span class="material-symbols-outlined text-[#0052cc] text-xl">check_circle</span>
							<div>
								<p class="text-sm font-semibold"><?php esc_html_e( 'Videos do YouTube', 'guiawp' ); ?></p>
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Ate 10 videos incorporados.', 'guiawp' ); ?></p>
							</div>
						</li>
						<li class="flex items-start gap-3">
							<span class="material-symbols-outlined text-[#0052cc] text-xl">check_circle</span>
							<div>
								<p class="text-sm font-semibold"><?php esc_html_e( 'Layout exclusivo', 'guiawp' ); ?></p>
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Sem anuncios relacionados competindo.', 'guiawp' ); ?></p>
							</div>
						</li>
						<li class="flex items-start gap-3">
							<span class="material-symbols-outlined text-[#0052cc] text-xl">check_circle</span>
							<div>
								<p class="text-sm font-semibold"><?php esc_html_e( 'Descricao completa', 'guiawp' ); ?></p>
								<p class="text-xs text-slate-500"><?php esc_html_e( 'Editor avancado para conteudo rico.', 'guiawp' ); ?></p>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-footer.php'; ?>
