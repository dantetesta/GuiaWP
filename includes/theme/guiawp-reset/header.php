<?php
/**
 * Header do tema GuiaWP Reset
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.5 - 2026-03-21 - Touch target menu mobile w-10→w-11 (44px minimo)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nome_guia     = guiawp_reset_get_setting( 'nome_guia', 'GuiaWP' );
$logo_url      = guiawp_reset_get_setting( 'logo_url', '' );
$logo_largura  = intval( guiawp_reset_get_setting( 'logo_largura', '150' ) );
$blog_url      = home_url( '/blog' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-background-light font-display antialiased' ); ?> style="color:var(--gcep-color-texto, #0f172a)">
<?php wp_body_open(); ?>

<header class="fixed top-0 left-0 right-0 z-50 glass-effect border-b border-slate-200">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 md:h-20 flex items-center justify-between">
		<a href="<?php echo esc_url( home_url() ); ?>" class="flex items-center gap-2 text-primary">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( $logo_largura ); ?>px;" class="object-contain">
			<?php else : ?>
				<span class="material-symbols-outlined text-3xl md:text-4xl font-bold">explore</span>
				<span class="text-xl md:text-2xl font-extrabold tracking-tight text-slate-900"><?php echo esc_html( $nome_guia ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="hidden md:flex items-center gap-8">
			<a class="inline-flex items-center gap-2 text-sm font-semibold hover:text-primary transition-colors" href="<?php echo esc_url( home_url() ); ?>">
				<span class="material-symbols-outlined text-[18px]">home</span>
				<?php esc_html_e( 'Início', 'guiawp-reset' ); ?>
			</a>
			<a class="text-sm font-semibold hover:text-primary transition-colors" href="<?php echo esc_url( get_post_type_archive_link( 'gcep_anuncio' ) ); ?>"><?php esc_html_e( 'Explorar Anúncios', 'guiawp-reset' ); ?></a>
			<a class="text-sm font-semibold hover:text-primary transition-colors" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'guiawp-reset' ); ?></a>
			<a class="inline-flex items-center gap-1 text-sm font-semibold hover:text-primary transition-colors" href="<?php echo esc_url( home_url( '/anuncios-mapa' ) ); ?>">
				<span class="material-symbols-outlined text-[16px]">map</span>
				<?php esc_html_e( 'Mapa', 'guiawp-reset' ); ?>
			</a>
			<a class="text-sm font-semibold hover:text-primary transition-colors" href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>"><?php esc_html_e( 'Anunciar', 'guiawp-reset' ); ?></a>
		</nav>

		<div class="flex items-center gap-2 md:gap-4">
			<?php if ( is_user_logged_in() ) : ?>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a href="<?php echo esc_url( home_url( '/painel-admin' ) ); ?>" class="hidden sm:inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 hover:bg-slate-100 rounded-lg transition-all">
						<span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
						<?php esc_html_e( 'Admin', 'guiawp-reset' ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>" class="bg-[#0052cc] text-white text-xs md:text-sm font-bold px-3 md:px-6 py-2 md:py-2.5 rounded-lg hover:bg-[#003d99] hover:shadow-lg transition-all inline-flex items-center gap-2">
					<span class="material-symbols-outlined text-[18px]">dashboard</span>
					<span class="md:hidden"><?php esc_html_e( 'Painel', 'guiawp-reset' ); ?></span>
					<span class="hidden md:inline"><?php esc_html_e( 'Meu Painel', 'guiawp-reset' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=gcep_logout' ) ); ?>" class="hidden sm:inline-flex w-11 h-11 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-rose-50 hover:border-rose-100 hover:text-rose-500 transition-all" title="<?php esc_attr_e( 'Sair', 'guiawp-reset' ); ?>" aria-label="<?php esc_attr_e( 'Sair', 'guiawp-reset' ); ?>">
					<span class="material-symbols-outlined text-[20px]">logout</span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="hidden sm:inline-flex text-sm font-semibold px-4 py-2 hover:bg-slate-100 rounded-lg transition-all"><?php esc_html_e( 'Entrar', 'guiawp-reset' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>" class="bg-[#0052cc] text-white text-xs md:text-sm font-bold px-4 md:px-6 py-2 md:py-2.5 rounded-lg hover:bg-[#003d99] hover:shadow-lg transition-all"><?php esc_html_e( 'Quero Anunciar', 'guiawp-reset' ); ?></a>
			<?php endif; ?>
			<button type="button" id="gcep-theme-menu-toggle" class="md:hidden w-11 h-11 inline-flex items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" aria-label="<?php esc_attr_e( 'Abrir menu', 'guiawp-reset' ); ?>">
				<span class="material-symbols-outlined text-2xl">menu</span>
			</button>
		</div>
	</div>
</header>

<div id="gcep-theme-menu-overlay" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm hidden"></div>
<nav id="gcep-theme-mobile-menu" class="fixed top-0 right-0 z-50 w-72 max-w-[80vw] h-full bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out">
	<div class="flex items-center justify-between p-4 border-b border-slate-200">
		<span class="text-lg font-bold text-slate-900"><?php echo esc_html( $nome_guia ); ?></span>
		<button type="button" id="gcep-theme-menu-close" class="w-10 h-10 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
			<span class="material-symbols-outlined">close</span>
		</button>
	</div>
	<div class="p-4 space-y-1">
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( home_url() ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">home</span>
			<?php esc_html_e( 'Início', 'guiawp-reset' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( get_post_type_archive_link( 'gcep_anuncio' ) ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">travel_explore</span>
			<?php esc_html_e( 'Explorar Anúncios', 'guiawp-reset' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( $blog_url ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">article</span>
			<?php esc_html_e( 'Blog', 'guiawp-reset' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( home_url( '/anuncios-mapa' ) ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">map</span>
			<?php esc_html_e( 'Anúncios no Mapa', 'guiawp-reset' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">campaign</span>
			<?php esc_html_e( 'Anunciar', 'guiawp-reset' ); ?>
		</a>
	</div>
	<div class="p-4 border-t border-slate-200 space-y-2">
		<?php if ( is_user_logged_in() ) : ?>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( home_url( '/painel-admin' ) ); ?>" class="flex w-full items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
					<span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
					<?php esc_html_e( 'Painel Admin', 'guiawp-reset' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>" class="flex w-full items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#0052cc] text-white text-sm font-bold hover:shadow-lg transition-all">
				<span class="material-symbols-outlined text-[18px]">dashboard</span>
				<?php esc_html_e( 'Painel', 'guiawp-reset' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=gcep_logout' ) ); ?>" class="flex w-full items-center justify-center gap-2 px-4 py-3 rounded-xl border border-rose-100 bg-rose-50 text-sm font-bold text-rose-600 hover:bg-rose-100 transition-colors">
				<span class="material-symbols-outlined text-[18px]">logout</span>
				<?php esc_html_e( 'Sair', 'guiawp-reset' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="block w-full text-center px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
				<?php esc_html_e( 'Entrar', 'guiawp-reset' ); ?>
			</a>
			<a href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>" class="block w-full text-center px-4 py-3 rounded-xl bg-[#0052cc] text-white text-sm font-bold hover:shadow-lg transition-all">
				<?php esc_html_e( 'Quero Anunciar', 'guiawp-reset' ); ?>
			</a>
		<?php endif; ?>
	</div>
</nav>
<script>
(function(){
	var toggle = document.getElementById('gcep-theme-menu-toggle');
	var menu = document.getElementById('gcep-theme-mobile-menu');
	var overlay = document.getElementById('gcep-theme-menu-overlay');
	var close = document.getElementById('gcep-theme-menu-close');
	if(!toggle||!menu||!overlay||!close) return;
	function openMenu(){ menu.classList.remove('translate-x-full'); overlay.classList.remove('hidden'); document.body.style.overflow='hidden'; }
	function closeMenu(){ menu.classList.add('translate-x-full'); overlay.classList.add('hidden'); document.body.style.overflow=''; }
	toggle.addEventListener('click', openMenu);
	close.addEventListener('click', closeMenu);
	overlay.addEventListener('click', closeMenu);
	menu.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', closeMenu); });
})();
</script>

<div class="pt-16 md:pt-20">
