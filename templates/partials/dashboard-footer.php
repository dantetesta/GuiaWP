<?php
/**
 * Footer do dashboard
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		<footer class="mt-auto border-t border-slate-100 py-6 px-4 lg:px-0 text-center">
			<p class="text-[12px] text-slate-400">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( GCEP_Settings::get( 'nome_guia', 'GuiaWP' ) ); ?>. <?php esc_html_e( 'Todos os direitos reservados.', 'guiawp' ); ?></p>
		</footer>
	</div>
</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
