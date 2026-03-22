<?php
/**
 * Header parcial para templates do plugin
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nome_guia      = GCEP_Settings::get( 'nome_guia', 'GuiaWP' );
$cor_primaria   = GCEP_Settings::get( 'cor_primaria', '#0052cc' );
$cor_secundaria = GCEP_Settings::get( 'cor_secundaria', '#f5f7f8' );
$cor_destaque   = GCEP_Settings::get( 'cor_destaque', '#22c55e' );
$cor_fundo      = GCEP_Settings::get( 'cor_fundo', '#f5f7f8' );
$cor_texto      = GCEP_Settings::get( 'cor_texto', '#0f172a' );
$cor_rodape     = GCEP_Settings::get( 'cor_rodape', '#1e293b' );
$cor_fundo_categorias = GCEP_Settings::get( 'cor_fundo_categorias', '#f5f7f8' );
$logo_url       = GCEP_Settings::get( 'logo_url', '' );
$logo_largura = intval( GCEP_Settings::get( 'logo_largura', '150' ) );
$blog_url     = home_url( '/blog' );
$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
$request_path = trim( wp_parse_url( $request_uri, PHP_URL_PATH ) ?: '', '/' );
$is_auth_page = in_array( $request_path, [ 'login', 'cadastro' ], true );

$body_classes   = $is_auth_page ? 'bg-slate-950 font-display text-slate-900 antialiased' : 'bg-background-light font-display text-slate-900 antialiased';
$header_classes = $is_auth_page
	? 'fixed top-0 left-0 right-0 z-50 border-b border-white/10 bg-slate-950/45 backdrop-blur-2xl'
	: 'fixed top-0 left-0 right-0 z-50 glass-effect border-b border-slate-200';
$brand_text     = $is_auth_page ? 'text-white' : 'text-slate-900';
$nav_link       = $is_auth_page ? 'text-slate-200 hover:text-white' : 'hover:text-primary';
$ghost_link     = $is_auth_page ? 'text-slate-200 hover:bg-white/10 hover:text-white' : 'hover:bg-slate-100';
$menu_toggle    = $is_auth_page ? 'text-slate-200 hover:bg-white/10' : 'text-slate-600 hover:bg-slate-100';
$primary_button = 'background-color:' . esc_attr( $cor_primaria ) . ';';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
	<style>:root{--gcep-color-primaria:<?php echo esc_attr( $cor_primaria ); ?>;--gcep-color-primary:<?php echo esc_attr( $cor_primaria ); ?>;--gcep-color-secundaria:<?php echo esc_attr( $cor_secundaria ); ?>;--gcep-color-destaque:<?php echo esc_attr( $cor_destaque ); ?>;--gcep-color-fundo:<?php echo esc_attr( $cor_fundo ); ?>;--gcep-color-texto:<?php echo esc_attr( $cor_texto ); ?>;--gcep-color-rodape:<?php echo esc_attr( $cor_rodape ); ?>;--gcep-color-fundo-categorias:<?php echo esc_attr( $cor_fundo_categorias ); ?>;}</style>
	<?php wp_head(); ?>
	<title><?php wp_title( '|', true, 'right' ); ?><?php echo esc_html( $nome_guia ); ?></title>
</head>
<body class="<?php echo esc_attr( $body_classes ); ?>">

<header class="<?php echo esc_attr( $header_classes ); ?>">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 md:h-20 flex items-center justify-between">
		<a href="<?php echo esc_url( home_url() ); ?>" class="flex items-center gap-2 text-primary">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( min( $logo_largura, 180 ) ); ?>px;" class="object-contain">
			<?php else : ?>
				<span class="material-symbols-outlined text-3xl md:text-4xl font-bold">explore</span>
				<span class="text-xl md:text-2xl font-extrabold tracking-tight <?php echo esc_attr( $brand_text ); ?>">
					<?php echo esc_html( $nome_guia ); ?>
				</span>
			<?php endif; ?>
		</a>

		<nav class="hidden md:flex items-center gap-8">
			<a class="inline-flex items-center gap-2 text-sm font-semibold transition-colors <?php echo esc_attr( $nav_link ); ?>" href="<?php echo esc_url( home_url() ); ?>">
				<span class="material-symbols-outlined text-[18px]">home</span>
				<?php esc_html_e( 'Início', 'guiawp' ); ?>
			</a>
			<a class="text-sm font-semibold transition-colors <?php echo esc_attr( $nav_link ); ?>" href="<?php echo esc_url( get_post_type_archive_link( 'gcep_anuncio' ) ); ?>">
				<?php esc_html_e( 'Explorar Anúncios', 'guiawp' ); ?>
			</a>
			<a class="text-sm font-semibold transition-colors <?php echo esc_attr( $nav_link ); ?>" href="<?php echo esc_url( $blog_url ); ?>">
				<?php esc_html_e( 'Blog', 'guiawp' ); ?>
			</a>
			<a class="text-sm font-semibold transition-colors <?php echo esc_attr( $nav_link ); ?>" href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>">
				<?php esc_html_e( 'Anunciar', 'guiawp' ); ?>
			</a>
		</nav>

		<div class="flex items-center gap-2 md:gap-4">
			<?php if ( is_user_logged_in() ) : ?>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a href="<?php echo esc_url( home_url( '/painel-admin' ) ); ?>" class="hidden sm:inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg transition-all <?php echo esc_attr( $ghost_link ); ?>">
						<span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
						<?php esc_html_e( 'Admin', 'guiawp' ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>" class="text-white text-xs md:text-sm font-bold px-3 md:px-6 py-2 md:py-2.5 rounded-lg hover:shadow-lg transition-all inline-flex items-center gap-2" style="<?php echo esc_attr( $primary_button ); ?>">
					<span class="material-symbols-outlined text-[18px]">dashboard</span>
					<span class="md:hidden"><?php esc_html_e( 'Painel', 'guiawp' ); ?></span>
					<span class="hidden md:inline"><?php esc_html_e( 'Meu Painel', 'guiawp' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=gcep_logout' ) ); ?>" class="hidden sm:inline-flex w-11 h-11 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-rose-50 hover:border-rose-100 hover:text-rose-500 transition-all" title="<?php esc_attr_e( 'Sair', 'guiawp' ); ?>" aria-label="<?php esc_attr_e( 'Sair', 'guiawp' ); ?>">
					<span class="material-symbols-outlined text-[20px]">logout</span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="hidden sm:inline-flex text-sm font-semibold px-4 py-2 rounded-lg transition-all <?php echo esc_attr( $ghost_link ); ?>">
					<?php esc_html_e( 'Entrar', 'guiawp' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>" class="inline-flex items-center justify-center text-white text-xs md:text-sm font-bold px-4 md:px-6 py-2 md:py-2.5 rounded-lg hover:shadow-lg transition-all" style="<?php echo esc_attr( $primary_button ); ?>">
					<?php esc_html_e( 'Quero Anunciar', 'guiawp' ); ?>
				</a>
			<?php endif; ?>
			<button type="button" id="gcep-mobile-menu-toggle" class="md:hidden w-10 h-10 inline-flex items-center justify-center rounded-lg transition-colors <?php echo esc_attr( $menu_toggle ); ?>" aria-label="<?php esc_attr_e( 'Abrir menu', 'guiawp' ); ?>">
				<span class="material-symbols-outlined text-2xl">menu</span>
			</button>
		</div>
	</div>
</header>

<div id="gcep-mobile-menu-overlay" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm hidden"></div>
<nav id="gcep-mobile-menu" class="fixed top-0 right-0 z-50 w-72 max-w-[80vw] h-full bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out">
	<div class="flex items-center justify-between p-4 border-b border-slate-200">
		<?php if ( $logo_url ) : ?>
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( min( $logo_largura, 150 ) ); ?>px;" class="object-contain">
		<?php else : ?>
			<span class="text-lg font-bold text-slate-900"><?php echo esc_html( $nome_guia ); ?></span>
		<?php endif; ?>
		<button type="button" id="gcep-mobile-menu-close" class="w-10 h-10 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
			<span class="material-symbols-outlined">close</span>
		</button>
	</div>
	<div class="p-4 space-y-1">
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( home_url() ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">home</span>
			<?php esc_html_e( 'Início', 'guiawp' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( get_post_type_archive_link( 'gcep_anuncio' ) ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">travel_explore</span>
			<?php esc_html_e( 'Explorar Anúncios', 'guiawp' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( $blog_url ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">article</span>
			<?php esc_html_e( 'Blog', 'guiawp' ); ?>
		</a>
		<a class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors" href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>">
			<span class="material-symbols-outlined text-xl text-slate-400">campaign</span>
			<?php esc_html_e( 'Anunciar', 'guiawp' ); ?>
		</a>
	</div>
	<div class="p-4 border-t border-slate-200 space-y-2">
		<?php if ( is_user_logged_in() ) : ?>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( home_url( '/painel-admin' ) ); ?>" class="flex w-full items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
					<span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
					<?php esc_html_e( 'Painel Admin', 'guiawp' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>" class="flex w-full items-center justify-center gap-2 px-4 py-3 rounded-xl text-white text-sm font-bold hover:shadow-lg transition-all" style="<?php echo esc_attr( $primary_button ); ?>">
				<span class="material-symbols-outlined text-[18px]">dashboard</span>
				<?php esc_html_e( 'Painel', 'guiawp' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=gcep_logout' ) ); ?>" class="flex w-full items-center justify-center gap-2 px-4 py-3 rounded-xl border border-rose-100 bg-rose-50 text-sm font-bold text-rose-600 hover:bg-rose-100 transition-colors">
				<span class="material-symbols-outlined text-[18px]">logout</span>
				<?php esc_html_e( 'Sair', 'guiawp' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="block w-full text-center px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
				<?php esc_html_e( 'Entrar', 'guiawp' ); ?>
			</a>
			<a href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>" class="block w-full text-center px-4 py-3 rounded-xl text-white text-sm font-bold hover:shadow-lg transition-all" style="<?php echo esc_attr( $primary_button ); ?>">
				<?php esc_html_e( 'Quero Anunciar', 'guiawp' ); ?>
			</a>
		<?php endif; ?>
	</div>
</nav>
<script>
(function(){
	var toggle = document.getElementById('gcep-mobile-menu-toggle');
	var menu = document.getElementById('gcep-mobile-menu');
	var overlay = document.getElementById('gcep-mobile-menu-overlay');
	var close = document.getElementById('gcep-mobile-menu-close');
	if(!toggle||!menu||!overlay||!close) return;
	function openMenu(){ menu.classList.remove('translate-x-full'); overlay.classList.remove('hidden'); document.body.style.overflow='hidden'; }
	function closeMenu(){ menu.classList.add('translate-x-full'); overlay.classList.add('hidden'); document.body.style.overflow=''; }
	toggle.addEventListener('click', openMenu);
	close.addEventListener('click', closeMenu);
	overlay.addEventListener('click', closeMenu);
	menu.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', closeMenu); });
})();
</script>
