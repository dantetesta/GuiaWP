<?php
/**
 * Footer parcial para templates do plugin
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nome_guia     = GCEP_Settings::get( 'nome_guia', 'GuiaWP' );
$logo_url      = GCEP_Settings::get( 'logo_url', '' );
$logo_largura  = intval( GCEP_Settings::get( 'logo_largura', '150' ) );
$telefone      = (string) GCEP_Settings::get( 'telefone_principal', '' );
$email         = (string) GCEP_Settings::get( 'email_principal', '' );
$whatsapp      = (string) GCEP_Settings::get( 'whatsapp_principal', '' );
$instagram_url = (string) GCEP_Settings::get( 'instagram_url', '' );
$facebook_url  = (string) GCEP_Settings::get( 'facebook_url', '' );
$x_url         = (string) GCEP_Settings::get( 'x_url', '' );
$blog_url      = home_url( '/blog' );
$help_url      = GCEP_Helpers::get_page_url_by_path( 'central-de-ajuda' );
$terms_url     = GCEP_Helpers::get_page_url_by_path( 'termos-de-uso' );
$privacy_url   = GCEP_Helpers::get_page_url_by_path( 'privacidade' );

$phone_digits    = GCEP_Helpers::sanitize_phone( $telefone );
$phone_label     = GCEP_Helpers::format_phone( $telefone );
$whatsapp_label  = GCEP_Helpers::format_phone( $whatsapp );
$telephone_href  = '';

if ( '' !== $phone_digits ) {
	$telephone_href = 'tel:' . ( in_array( strlen( $phone_digits ), [ 10, 11 ], true ) ? '+55' : '' ) . $phone_digits;
}

$footer_socials = [];

if ( '' !== $telephone_href ) {
	$footer_socials[] = [
		'url'      => $telephone_href,
		'icon'     => 'call',
		'label'    => __( 'Telefone', 'guiawp' ),
		'hover'    => 'hover:bg-[#0052cc] hover:border-[#0052cc] hover:text-white',
		'value'    => $phone_label,
		'external' => false,
	];
}

if ( '' !== $email ) {
	$footer_socials[] = [
		'url'      => 'mailto:' . $email,
		'icon'     => 'mail',
		'label'    => __( 'E-mail', 'guiawp' ),
		'hover'    => 'hover:bg-slate-900 hover:border-slate-900 hover:text-white',
		'value'    => $email,
		'external' => false,
	];
}

if ( '' !== $whatsapp ) {
	$footer_socials[] = [
		'url'      => GCEP_Helpers::get_whatsapp_url( $whatsapp ),
		'icon'     => 'chat',
		'label'    => __( 'WhatsApp', 'guiawp' ),
		'hover'    => 'hover:bg-[#25D366] hover:border-[#25D366] hover:text-white',
		'value'    => $whatsapp_label,
		'external' => true,
	];
}

require_once GCEP_PLUGIN_DIR . 'templates/partials/social-icons.php';
$footer_svg_icons = gcep_social_svg_icons( 'w-5 h-5' );

if ( '' !== $instagram_url ) {
	$footer_socials[] = [
		'url'      => $instagram_url,
		'svg'      => $footer_svg_icons['instagram'],
		'label'    => __( 'Instagram', 'guiawp' ),
		'hover'    => 'hover:bg-[#E1306C] hover:border-[#E1306C] hover:text-white',
		'external' => true,
	];
}

if ( '' !== $facebook_url ) {
	$footer_socials[] = [
		'url'      => $facebook_url,
		'svg'      => $footer_svg_icons['facebook'],
		'label'    => __( 'Facebook', 'guiawp' ),
		'hover'    => 'hover:bg-[#1877F2] hover:border-[#1877F2] hover:text-white',
		'external' => true,
	];
}

if ( '' !== $x_url ) {
	$footer_socials[] = [
		'url'      => $x_url,
		'svg'      => $footer_svg_icons['x_twitter'],
		'label'    => __( 'X', 'guiawp' ),
		'hover'    => 'hover:bg-slate-900 hover:border-slate-900 hover:text-white',
		'external' => true,
	];
}
?>

<footer class="bg-white border-t border-slate-200 pt-12 md:pt-20 pb-8 md:pb-10">
	<div class="max-w-7xl mx-auto px-4 sm:px-6">
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12 mb-12 md:mb-20">
			<div class="md:col-span-2 lg:col-span-2">
				<div class="flex items-center gap-2 text-primary mb-6">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $nome_guia ); ?>" style="width: <?php echo esc_attr( $logo_largura ); ?>px;" class="object-contain">
					<?php else : ?>
						<span class="material-symbols-outlined text-3xl font-bold">explore</span>
						<h2 class="text-xl font-extrabold text-slate-900"><?php echo esc_html( $nome_guia ); ?></h2>
					<?php endif; ?>
				</div>
				<p class="text-slate-500 max-w-sm leading-relaxed">
					<?php esc_html_e( 'A maior plataforma de descoberta de serviços e locais. Conectando pessoas a experiências todos os dias.', 'guiawp' ); ?>
				</p>
				<?php if ( ! empty( $footer_socials ) ) : ?>
					<div class="flex flex-wrap gap-3 mt-6">
						<?php foreach ( $footer_socials as $social ) : ?>
							<a class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 transition-all <?php echo esc_attr( $social['hover'] ); ?>" href="<?php echo esc_url( $social['url'] ); ?>" <?php echo ! empty( $social['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> title="<?php echo esc_attr( isset( $social['value'] ) ? $social['label'] . ': ' . $social['value'] : $social['label'] ); ?>" aria-label="<?php echo esc_attr( $social['label'] ); ?>">
								<?php if ( ! empty( $social['svg'] ) ) : ?>
									<?php echo $social['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php else : ?>
									<span class="material-symbols-outlined text-[20px]"><?php echo esc_html( $social['icon'] ); ?></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div>
				<h5 class="font-bold text-slate-900 mb-6"><?php esc_html_e( 'Plataforma', 'guiawp' ); ?></h5>
				<ul class="space-y-4 text-sm text-slate-500">
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Explorar', 'guiawp' ); ?></a></li>
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( get_post_type_archive_link( 'gcep_anuncio' ) ); ?>"><?php esc_html_e( 'Categorias', 'guiawp' ); ?></a></li>
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'guiawp' ); ?></a></li>
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( home_url( '/cadastro' ) ); ?>"><?php esc_html_e( 'Anunciar', 'guiawp' ); ?></a></li>
				</ul>
			</div>
			<div>
				<h5 class="font-bold text-slate-900 mb-6"><?php esc_html_e( 'Suporte', 'guiawp' ); ?></h5>
				<ul class="space-y-4 text-sm text-slate-500">
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( $help_url ); ?>"><?php esc_html_e( 'Central de Ajuda', 'guiawp' ); ?></a></li>
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Termos de Uso', 'guiawp' ); ?></a></li>
					<li><a class="hover:text-primary transition-colors" href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacidade', 'guiawp' ); ?></a></li>
				</ul>
			</div>
		</div>
		<div class="border-t border-slate-100 pt-8 md:pt-10 flex flex-col md:flex-row items-center justify-between gap-4">
			<p class="text-sm text-slate-400">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $nome_guia ); ?>. <?php esc_html_e( 'Todos os direitos reservados.', 'guiawp' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
