<?php
/**
 * Footer do tema GuiaWP Reset
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 2.0.0 - 2026-03-22 - Rodape com cores configuráveis (fundo sólido/gradiente, títulos, textos, links)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nome_guia     = guiawp_reset_get_setting( 'nome_guia', 'GuiaWP' );
$logo_url      = guiawp_reset_get_setting( 'logo_url', '' );
$logo_largura  = intval( guiawp_reset_get_setting( 'logo_largura', '150' ) );
$telefone      = (string) guiawp_reset_get_setting( 'telefone_principal', '' );
$email         = (string) guiawp_reset_get_setting( 'email_principal', '' );
$whatsapp      = (string) guiawp_reset_get_setting( 'whatsapp_principal', '' );
$instagram_url = (string) guiawp_reset_get_setting( 'instagram_url', '' );
$facebook_url  = (string) guiawp_reset_get_setting( 'facebook_url', '' );
$x_url         = (string) guiawp_reset_get_setting( 'x_url', '' );
$blog_url      = home_url( '/blog' );
$help_url      = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::get_page_url_by_path( 'central-de-ajuda' ) : '#';
$terms_url     = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::get_page_url_by_path( 'termos-de-uso' ) : '#';
$privacy_url   = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::get_page_url_by_path( 'privacidade' ) : '#';

$phone_digits    = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::sanitize_phone( $telefone ) : preg_replace( '/[^0-9]/', '', $telefone );
$phone_label     = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::format_phone( $telefone ) : $telefone;
$whatsapp_label  = class_exists( 'GCEP_Helpers' ) ? GCEP_Helpers::format_phone( $whatsapp ) : $whatsapp;
$telephone_href  = '';

if ( '' !== $phone_digits ) {
	$telephone_href = 'tel:' . ( in_array( strlen( $phone_digits ), [ 10, 11 ], true ) ? '+55' : '' ) . $phone_digits;
}

$footer_socials = [];

if ( '' !== $telephone_href ) {
	$footer_socials[] = [
		'url'   => $telephone_href,
		'icon'  => 'call',
		'label' => __( 'Telefone', 'guiawp-reset' ),
		'hover' => 'hover:bg-[#0052cc] hover:border-[#0052cc] hover:text-white',
		'value' => $phone_label,
		'external' => false,
	];
}

if ( '' !== $email ) {
	$footer_socials[] = [
		'url'   => 'mailto:' . $email,
		'icon'  => 'mail',
		'label' => __( 'E-mail', 'guiawp-reset' ),
		'hover' => 'hover:bg-slate-900 hover:border-slate-900 hover:text-white',
		'value' => $email,
		'external' => false,
	];
}

if ( '' !== $whatsapp && class_exists( 'GCEP_Helpers' ) ) {
	$footer_socials[] = [
		'url'   => GCEP_Helpers::get_whatsapp_url( $whatsapp ),
		'icon'  => 'chat',
		'label' => __( 'WhatsApp', 'guiawp-reset' ),
		'hover' => 'hover:bg-[#25D366] hover:border-[#25D366] hover:text-white',
		'value' => $whatsapp_label,
		'external' => true,
	];
}

if ( '' !== $instagram_url ) {
	$footer_socials[] = [
		'url'   => $instagram_url,
		'icon'  => 'photo_camera',
		'label' => __( 'Instagram', 'guiawp-reset' ),
		'hover' => 'hover:bg-[#E1306C] hover:border-[#E1306C] hover:text-white',
		'external' => true,
	];
}

if ( '' !== $facebook_url ) {
	$footer_socials[] = [
		'url'   => $facebook_url,
		'icon'  => 'thumb_up',
		'label' => __( 'Facebook', 'guiawp-reset' ),
		'hover' => 'hover:bg-[#1877F2] hover:border-[#1877F2] hover:text-white',
		'external' => true,
	];
}

if ( '' !== $x_url ) {
	$footer_socials[] = [
		'url'   => $x_url,
		'icon'  => 'alternate_email',
		'label' => __( 'X', 'guiawp-reset' ),
		'hover' => 'hover:bg-slate-900 hover:border-slate-900 hover:text-white',
		'external' => true,
	];
}

// Cores do rodapé
$ft_tipo  = guiawp_reset_get_setting( 'cor_rodape_tipo', 'solido' );
$ft_cor1  = guiawp_reset_get_setting( 'cor_rodape', '#1e293b' );
$ft_cor2  = guiawp_reset_get_setting( 'cor_rodape_cor2', '#0f172a' );
$ft_dir   = guiawp_reset_get_setting( 'cor_rodape_direcao', 'to bottom' );
$ft_opac  = intval( guiawp_reset_get_setting( 'cor_rodape_opacidade', '100' ) ) / 100;
$ft_tit   = guiawp_reset_get_setting( 'cor_rodape_titulo', '#f1f5f9' );
$ft_txt   = guiawp_reset_get_setting( 'cor_rodape_texto', '#94a3b8' );
$ft_lnk   = guiawp_reset_get_setting( 'cor_rodape_link', '#cbd5e1' );
$ft_lnkh  = guiawp_reset_get_setting( 'cor_rodape_link_hover', '#ffffff' );

if ( 'gradiente' === $ft_tipo ) {
	$ft_bg = 'linear-gradient(' . esc_attr( $ft_dir ) . ',' . esc_attr( $ft_cor1 ) . ',' . esc_attr( $ft_cor2 ) . ')';
} else {
	$ft_bg = esc_attr( $ft_cor1 );
}
?>
</div><!-- .pt-16/20 -->

<footer class="border-t pt-12 md:pt-20 pb-8 md:pb-10" style="background:<?php echo $ft_bg; ?>;opacity:<?php echo esc_attr( $ft_opac ); ?>">
	<div class="max-w-7xl mx-auto px-4 sm:px-6">
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12 mb-12 md:mb-20">
			<div class="md:col-span-2 lg:col-span-2">
				<div class="flex items-center gap-2 mb-6">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( $logo_largura ); ?>px;" class="object-contain">
					<?php else : ?>
						<span class="material-symbols-outlined text-3xl font-bold" style="color:<?php echo esc_attr( $ft_tit ); ?>">explore</span>
						<h2 class="text-xl font-extrabold" style="color:<?php echo esc_attr( $ft_tit ); ?>"><?php echo esc_html( $nome_guia ); ?></h2>
					<?php endif; ?>
				</div>
				<p class="max-w-sm leading-relaxed" style="color:<?php echo esc_attr( $ft_txt ); ?>">
					<?php esc_html_e( 'A maior plataforma de descoberta de serviços e locais. Conectando pessoas a experiências todos os dias.', 'guiawp-reset' ); ?>
				</p>
				<?php if ( ! empty( $footer_socials ) ) : ?>
					<div class="flex flex-wrap gap-3 mt-6">
						<?php foreach ( $footer_socials as $social ) : ?>
							<a class="inline-flex h-11 w-11 items-center justify-center rounded-full border transition-all <?php echo esc_attr( $social['hover'] ); ?>" style="border-color:<?php echo esc_attr( $ft_txt ); ?>33;color:<?php echo esc_attr( $ft_txt ); ?>" href="<?php echo esc_url( $social['url'] ); ?>" <?php echo ! empty( $social['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> title="<?php echo esc_attr( isset( $social['value'] ) ? $social['label'] . ': ' . $social['value'] : $social['label'] ); ?>" aria-label="<?php echo esc_attr( $social['label'] ); ?>">
								<span class="material-symbols-outlined text-[20px]"><?php echo esc_html( $social['icon'] ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div>
				<h5 class="font-bold mb-6" style="color:<?php echo esc_attr( $ft_tit ); ?>"><?php esc_html_e( 'Plataforma', 'guiawp-reset' ); ?></h5>
				<ul class="space-y-4 text-sm" style="color:<?php echo esc_attr( $ft_lnk ); ?>">
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Explorar', 'guiawp-reset' ); ?></a></li>
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( home_url( '/categorias/' ) ); ?>"><?php esc_html_e( 'Categorias', 'guiawp-reset' ); ?></a></li>
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'guiawp-reset' ); ?></a></li>
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>"><?php esc_html_e( 'Anunciar', 'guiawp-reset' ); ?></a></li>
				</ul>
			</div>
			<div>
				<h5 class="font-bold mb-6" style="color:<?php echo esc_attr( $ft_tit ); ?>"><?php esc_html_e( 'Suporte', 'guiawp-reset' ); ?></h5>
				<ul class="space-y-4 text-sm" style="color:<?php echo esc_attr( $ft_lnk ); ?>">
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( $help_url ); ?>"><?php esc_html_e( 'Central de Ajuda', 'guiawp-reset' ); ?></a></li>
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Termos de Uso', 'guiawp-reset' ); ?></a></li>
					<li><a class="transition-colors" style="color:inherit" onmouseover="this.style.color='<?php echo esc_attr( $ft_lnkh ); ?>'" onmouseout="this.style.color='<?php echo esc_attr( $ft_lnk ); ?>'" href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacidade', 'guiawp-reset' ); ?></a></li>
				</ul>
			</div>
		</div>
		<div class="pt-8 md:pt-10 flex flex-col md:flex-row items-center justify-between gap-4" style="border-top:1px solid <?php echo esc_attr( $ft_txt ); ?>33">
			<p class="text-sm" style="color:<?php echo esc_attr( $ft_txt ); ?>">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $nome_guia ); ?>. <?php esc_html_e( 'Todos os direitos reservados.', 'guiawp-reset' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
