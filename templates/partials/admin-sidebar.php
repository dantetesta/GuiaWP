<?php
/**
 * Sidebar do painel admin externo
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.9.5 - 2026-03-21 - Touch target logout w-8→w-11 (44px minimo), aria-label adicionado
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_route = get_query_var( 'gcep_route', 'painel-admin' );
$user          = wp_get_current_user();
$nome_guia     = GCEP_Settings::get( 'nome_guia', 'GuiaWP' );
$logo_url      = GCEP_Settings::get( 'logo_url', '' );
$logo_largura  = intval( GCEP_Settings::get( 'logo_largura', '150' ) );
$avatar_url    = GCEP_Helpers::get_user_gravatar_url( $user, 40 );
$user_initials = GCEP_Helpers::get_user_initials( $user );

$menu_items = [
	'painel-admin'               => [ 'icon' => 'grid_view',    'label' => __( 'Dashboard', 'guiawp' ) ],
	'painel-admin/anuncios'      => [ 'icon' => 'ads_click',    'label' => __( 'Anúncios', 'guiawp' ) ],
	'painel-admin/blog'          => [ 'icon' => 'article',      'label' => __( 'Blog', 'guiawp' ) ],
	'painel-admin/usuarios'      => [ 'icon' => 'group',        'label' => __( 'Usuários', 'guiawp' ) ],
	'painel-admin/categorias'    => [ 'icon' => 'category',     'label' => __( 'Categorias', 'guiawp' ) ],
	'painel-admin/planos'        => [ 'icon' => 'workspace_premium', 'label' => __( 'Planos', 'guiawp' ) ],
	'painel-admin/configuracoes' => [ 'icon' => 'settings',     'label' => __( 'Configurações', 'guiawp' ) ],
];
?>
<aside class="gcep-sidebar w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full z-20">
	<div class="p-6 hidden lg:flex items-center gap-3">
		<?php if ( $logo_url ) : ?>
			<a href="<?php echo esc_url( home_url() ); ?>">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( min( $logo_largura, 180 ) ); ?>px;" class="object-contain">
			</a>
		<?php else : ?>
			<div class="bg-primary rounded-lg p-2 text-white">
				<span class="material-symbols-outlined">dashboard_customize</span>
			</div>
			<div>
				<h1 class="text-lg font-bold leading-tight"><?php echo esc_html( $nome_guia ); ?></h1>
				<p class="text-xs text-slate-500">Admin Panel</p>
			</div>
		<?php endif; ?>
	</div>

	<nav class="flex-1 px-4 space-y-1 overflow-y-auto">
		<?php foreach ( $menu_items as $route => $item ) :
			$is_active = ( $current_route === $route );
			$classes   = $is_active
				? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/10 text-primary font-medium'
				: 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50';
		?>
			<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( home_url( '/' . $route ) ); ?>">
				<span class="material-symbols-outlined"><?php echo esc_html( $item['icon'] ); ?></span>
				<span class="text-sm font-medium"><?php echo esc_html( $item['label'] ); ?></span>
			</a>
		<?php endforeach; ?>

		<div class="my-2 border-t border-slate-100"></div>

		<a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50" href="<?php echo esc_url( admin_url() ); ?>" target="_blank" rel="noopener">
			<span class="material-symbols-outlined">admin_panel_settings</span>
			<span class="text-sm font-medium"><?php esc_html_e( 'WP Admin', 'guiawp' ); ?></span>
			<span class="material-symbols-outlined ml-auto text-slate-300" style="font-size:14px;">open_in_new</span>
		</a>
		<a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50" href="<?php echo esc_url( home_url() ); ?>" target="_blank" rel="noopener">
			<span class="material-symbols-outlined">home</span>
			<span class="text-sm font-medium"><?php esc_html_e( 'Ver Site', 'guiawp' ); ?></span>
			<span class="material-symbols-outlined ml-auto text-slate-300" style="font-size:14px;">open_in_new</span>
		</a>
	</nav>

	<div class="p-3 border-t border-slate-100">
		<div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors">
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
				<p class="text-[11px] text-slate-400"><?php esc_html_e( 'Administrador', 'guiawp' ); ?></p>
			</div>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gcep_logout' ), 'gcep_logout' ) ); ?>" class="w-11 h-11 flex items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors" title="<?php esc_attr_e( 'Sair', 'guiawp' ); ?>" aria-label="<?php esc_attr_e( 'Sair', 'guiawp' ); ?>">
				<span class="material-symbols-outlined text-[18px]">logout</span>
			</a>
		</div>
	</div>
</aside>
