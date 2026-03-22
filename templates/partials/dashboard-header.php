<?php
/**
 * Header do dashboard (barra superior interna)
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cor_primaria   = GCEP_Settings::get( 'cor_primaria', '#0052cc' );
$cor_secundaria = GCEP_Settings::get( 'cor_secundaria', '#f5f7f8' );
$cor_destaque   = GCEP_Settings::get( 'cor_destaque', '#22c55e' );
$cor_fundo      = GCEP_Settings::get( 'cor_fundo', '#f5f7f8' );
$cor_texto      = GCEP_Settings::get( 'cor_texto', '#0f172a' );
$cor_rodape     = GCEP_Settings::get( 'cor_rodape', '#1e293b' );
$cor_fundo_categorias = GCEP_Settings::get( 'cor_fundo_categorias', '#f5f7f8' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
	<style>:root{--gcep-color-primaria:<?php echo esc_attr( $cor_primaria ); ?>;--gcep-color-primary:<?php echo esc_attr( $cor_primaria ); ?>;--gcep-color-secundaria:<?php echo esc_attr( $cor_secundaria ); ?>;--gcep-color-destaque:<?php echo esc_attr( $cor_destaque ); ?>;--gcep-color-fundo:<?php echo esc_attr( $cor_fundo ); ?>;--gcep-color-texto:<?php echo esc_attr( $cor_texto ); ?>;--gcep-color-rodape:<?php echo esc_attr( $cor_rodape ); ?>;--gcep-color-fundo-categorias:<?php echo esc_attr( $cor_fundo_categorias ); ?>;}body{overflow-x:hidden;}</style>
	<?php wp_head(); ?>
	<title><?php wp_title( '|', true, 'right' ); ?><?php echo esc_html( GCEP_Settings::get( 'nome_guia', 'GuiaWP' ) ); ?></title>
</head>
<body class="bg-background-light font-display text-slate-900 antialiased">
<div class="gcep-mobile-header lg:hidden fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4">
	<button type="button" id="gcep-sidebar-toggle" class="w-10 h-10 inline-flex items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 transition-colors" aria-label="<?php esc_attr_e( 'Abrir menu', 'guiawp' ); ?>">
		<span class="material-symbols-outlined text-2xl">menu</span>
	</button>
	<?php
	$logo_url = GCEP_Settings::get( 'logo_url', '' );
	$nome_guia = GCEP_Settings::get( 'nome_guia', 'GuiaWP' );
	if ( $logo_url ) : ?>
		<a href="<?php echo esc_url( home_url() ); ?>" class="flex-1 flex justify-center">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" class="h-8 object-contain">
		</a>
	<?php else : ?>
		<span class="text-sm font-bold text-slate-900 truncate"><?php echo esc_html( $nome_guia ); ?></span>
	<?php endif; ?>
	<div class="w-10"></div>
</div>
<div class="gcep-sidebar-overlay" id="gcep-sidebar-overlay"></div>
<script>
(function(){
	document.addEventListener('DOMContentLoaded', function(){
		var toggle = document.getElementById('gcep-sidebar-toggle');
		var overlay = document.getElementById('gcep-sidebar-overlay');
		var sidebar = document.querySelector('.gcep-sidebar');
		if (!toggle || !overlay || !sidebar) return;
		function open(){ sidebar.classList.add('gcep-sidebar-open'); overlay.classList.add('active'); document.body.style.overflow='hidden'; }
		function close(){ sidebar.classList.remove('gcep-sidebar-open'); overlay.classList.remove('active'); document.body.style.overflow=''; }
		toggle.addEventListener('click', function(){ sidebar.classList.contains('gcep-sidebar-open') ? close() : open(); });
		overlay.addEventListener('click', close);
		sidebar.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', close); });
	});
})();
</script>
<div class="flex min-h-screen pt-14 lg:pt-0 w-full max-w-full overflow-x-hidden">
