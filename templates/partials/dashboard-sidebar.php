<?php
/**
 * Sidebar do dashboard do anunciante
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_route = get_query_var( 'gcep_route', 'painel' );
$user          = wp_get_current_user();
$nome_guia     = GCEP_Settings::get( 'nome_guia', 'GuiaWP' );
$logo_url      = GCEP_Settings::get( 'logo_url', '' );
$logo_largura  = intval( GCEP_Settings::get( 'logo_largura', '150' ) );
$avatar_url    = GCEP_Helpers::get_user_gravatar_url( $user, 32 );
$user_initials = GCEP_Helpers::get_user_initials( $user );

$menu_items = [
	'painel'              => [ 'icon' => 'dashboard',       'label' => __( 'Visão Geral', 'guiawp' ) ],
	'painel/anuncios'     => [ 'icon' => 'campaign',        'label' => __( 'Meus Anúncios', 'guiawp' ) ],
	'painel/criar-anuncio'=> [ 'icon' => 'add_circle',      'label' => __( 'Criar Anúncio', 'guiawp' ) ],
	'painel/perfil'       => [ 'icon' => 'person',          'label' => __( 'Perfil', 'guiawp' ) ],
];
?>
<aside class="gcep-sidebar w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full z-20">
	<div class="p-6 hidden lg:flex items-center gap-3">
		<?php if ( $logo_url ) : ?>
			<a href="<?php echo esc_url( home_url() ); ?>">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( min( $logo_largura, 180 ) ); ?>px;" class="object-contain">
			</a>
		<?php else : ?>
			<div class="bg-primary rounded-lg p-1.5 text-white">
				<span class="material-symbols-outlined block">grid_view</span>
			</div>
			<div>
				<h1 class="font-bold text-lg tracking-tight text-slate-900"><?php echo esc_html( $nome_guia ); ?></h1>
				<p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><?php esc_html_e( 'Painel do Anunciante', 'guiawp' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<nav class="flex-1 px-3 py-2 space-y-0.5">
		<?php foreach ( $menu_items as $route => $item ) :
			$is_active = ( $current_route === $route );
			$classes   = $is_active
				? 'flex items-center gap-3 px-3 py-2 rounded-md bg-primary/10 text-primary font-semibold transition-colors'
				: 'flex items-center gap-3 px-3 py-2 rounded-md text-slate-500 hover:bg-slate-50 transition-colors';
		?>
			<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( home_url( '/' . $route ) ); ?>">
				<span class="material-symbols-outlined text-[20px]"><?php echo esc_html( $item['icon'] ); ?></span>
				<span class="text-[14px]"><?php echo esc_html( $item['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="p-3 border-t border-slate-100">
		<div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors group">
			<div class="relative w-9 h-9 flex-shrink-0">
				<?php if ( $avatar_url ) : ?>
					<img
						src="<?php echo esc_url( $avatar_url ); ?>"
						alt="<?php echo esc_attr( $user->display_name ); ?>"
						class="w-9 h-9 rounded-lg object-cover"
						loading="lazy"
						onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
					>
				<?php endif; ?>
				<div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-sm <?php echo $avatar_url ? 'hidden' : ''; ?>">
					<?php echo esc_html( $user_initials ); ?>
				</div>
			</div>
			<div class="flex-1 min-w-0">
				<p class="text-[13px] font-bold text-slate-800 truncate"><?php echo esc_html( $user->display_name ); ?></p>
				<p class="text-[11px] text-slate-400 truncate"><?php echo esc_html( $user->user_email ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=gcep_logout' ) ); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors" title="<?php esc_attr_e( 'Sair', 'guiawp' ); ?>">
				<span class="material-symbols-outlined text-[18px]">logout</span>
			</a>
		</div>
	</div>
</aside>
