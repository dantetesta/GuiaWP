<?php
/**
 * Instalador do tema guiawp-reset
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.7.5 - 2026-03-14 - Ativacao automatica ao ativar o plugin e restauracao do tema anterior ao desativar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Theme_Installer {

	private const PREVIOUS_THEME_OPTION = 'gcep_previous_theme';

	public static function maybe_install(): void {
		$theme_dir = get_theme_root() . '/guiawp-reset';
		if ( is_dir( $theme_dir ) ) {
			return;
		}

		$source = GCEP_PLUGIN_DIR . 'includes/theme/guiawp-reset';
		if ( ! is_dir( $source ) ) {
			return;
		}

		self::copy_recursive( $source, $theme_dir );
	}

	public static function activate(): void {
		if ( self::is_theme_active() ) {
			return;
		}

		if ( ! wp_get_theme( 'guiawp-reset' )->exists() ) {
			return;
		}

		update_option( self::PREVIOUS_THEME_OPTION, get_stylesheet(), false );
		switch_theme( 'guiawp-reset' );
	}

	public static function restore_previous_theme(): void {
		if ( ! self::is_theme_active() ) {
			delete_option( self::PREVIOUS_THEME_OPTION );
			return;
		}

		$previous = (string) get_option( self::PREVIOUS_THEME_OPTION, '' );

		if ( '' !== $previous && wp_get_theme( $previous )->exists() ) {
			switch_theme( $previous );
		} else {
			$default = WP_Theme::get_core_default_theme();
			if ( $default instanceof WP_Theme ) {
				switch_theme( $default->get_stylesheet() );
			}
		}

		delete_option( self::PREVIOUS_THEME_OPTION );
	}

	public static function is_theme_active(): bool {
		return 'guiawp-reset' === get_stylesheet();
	}

	private static function copy_recursive( string $src, string $dst ): void {
		$dir = opendir( $src );
		wp_mkdir_p( $dst );
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			if ( is_dir( $src . '/' . $file ) ) {
				self::copy_recursive( $src . '/' . $file, $dst . '/' . $file );
			} else {
				copy( $src . '/' . $file, $dst . '/' . $file );
			}
		}
		closedir( $dir );
	}
}
