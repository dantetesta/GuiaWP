<?php
/**
 * Template: Renovar Anúncio Expirado
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login' ) );
	exit;
}

$anuncio_id = absint( $_GET['anuncio_id'] ?? 0 );
$user_id    = get_current_user_id();

if ( $anuncio_id <= 0 ) {
	wp_safe_redirect( home_url( '/painel' ) );
	exit;
}

$post = get_post( $anuncio_id );
if ( ! $post || 'gcep_anuncio' !== $post->post_type || (int) $post->post_author !== $user_id ) {
	wp_safe_redirect( home_url( '/painel' ) );
	exit;
}

$status = get_post_meta( $anuncio_id, 'GCEP_status_anuncio', true );
if ( 'expirado' !== $status ) {
	wp_safe_redirect( home_url( '/painel/anuncios' ) );
	exit;
}

$planos = GCEP_Plans::get_active();
$msg      = isset( $_GET['gcep_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_msg'] ) ) : '';
$msg_type = isset( $_GET['gcep_type'] ) ? sanitize_text_field( wp_unslash( $_GET['gcep_type'] ) ) : '';

include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-header.php';
include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-sidebar.php';
?>

<main class="flex-1 lg:ml-64 min-h-screen flex flex-col min-w-0 overflow-x-hidden">
	<header class="h-14 lg:h-16 bg-white/80 backdrop-blur-md sticky top-14 lg:top-0 z-10 border-b border-slate-200 px-4 lg:px-8 flex items-center">
		<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>" class="text-slate-400 hover:text-slate-600 mr-3">
			<span class="material-symbols-outlined">arrow_back</span>
		</a>
		<h2 class="text-sm font-bold text-slate-900"><?php esc_html_e( 'Renovar Anúncio', 'guiawp' ); ?></h2>
	</header>

	<div class="p-4 sm:p-8 max-w-[700px] w-full mx-auto">

		<?php if ( $msg ) : ?>
		<script>document.addEventListener('DOMContentLoaded',function(){gcepToast(<?php echo wp_json_encode($msg); ?>,<?php echo wp_json_encode('error'===$msg_type?'error':'success'); ?>);});</script>
		<?php endif; ?>

		<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-6">
			<div class="flex items-center gap-4 mb-6">
				<div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
					<span class="material-symbols-outlined text-amber-600 text-2xl">autorenew</span>
				</div>
				<div>
					<h1 class="text-xl font-black text-slate-900"><?php esc_html_e( 'Renovar Anúncio', 'guiawp' ); ?></h1>
					<p class="text-sm text-slate-500"><?php echo esc_html( $post->post_title ); ?></p>
				</div>
			</div>

			<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8">
				<p class="text-sm text-amber-800">
					<span class="font-bold"><?php esc_html_e( 'Anúncio expirado.', 'guiawp' ); ?></span>
					<?php esc_html_e( 'Escolha um novo plano para renovar a vigência do seu anúncio e voltar a exibi-lo.', 'guiawp' ); ?>
				</p>
			</div>

			<?php if ( empty( $planos ) ) : ?>
				<p class="text-center text-slate-400 py-8"><?php esc_html_e( 'Nenhum plano disponível no momento.', 'guiawp' ); ?></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="gcep_renew_anuncio">
					<input type="hidden" name="anuncio_id" value="<?php echo esc_attr( $anuncio_id ); ?>">
					<?php wp_nonce_field( 'gcep_renew_anuncio', 'gcep_nonce' ); ?>

					<span class="text-sm font-semibold text-slate-700 mb-4 block"><?php esc_html_e( 'Escolha o Plano', 'guiawp' ); ?></span>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
						<?php foreach ( $planos as $i => $plano_item ) : ?>
						<label class="relative cursor-pointer">
							<input type="radio" name="plano_id" value="<?php echo esc_attr( $plano_item['id'] ); ?>" class="peer sr-only" <?php checked( $i, 0 ); ?>>
							<div class="border-2 border-slate-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 rounded-xl p-5 transition-all text-center">
								<span class="material-symbols-outlined text-2xl text-amber-500 mb-2 block">schedule</span>
								<h3 class="font-bold text-slate-900 text-sm"><?php echo esc_html( $plano_item['name'] ); ?></h3>
								<p class="text-lg font-black text-amber-600 mt-1">
									<?php echo esc_html( GCEP_Plans::format_duration( (int) $plano_item['days'] ) ); ?>
								</p>
								<?php if ( (float) $plano_item['price'] > 0 ) : ?>
								<p class="text-sm font-bold text-slate-700 mt-1">R$ <?php echo esc_html( number_format( (float) $plano_item['price'], 2, ',', '.' ) ); ?></p>
								<?php endif; ?>
								<?php if ( ! empty( $plano_item['description'] ) ) : ?>
								<p class="text-[11px] text-slate-400 mt-2"><?php echo esc_html( $plano_item['description'] ); ?></p>
								<?php endif; ?>
							</div>
						</label>
						<?php endforeach; ?>
					</div>

					<button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-amber-600/20 transition-all flex items-center justify-center gap-2">
						<span class="material-symbols-outlined">autorenew</span>
						<?php esc_html_e( 'Renovar e Ir para Pagamento', 'guiawp' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

<?php include GCEP_PLUGIN_DIR . 'templates/partials/dashboard-footer.php'; ?>
