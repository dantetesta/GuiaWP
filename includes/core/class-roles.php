<?php
/**
 * Gerenciamento de roles
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Roles {

	public static function ensure_roles(): void {
		if ( ! get_role( 'gcep_anunciante' ) ) {
			add_role( 'gcep_anunciante', __( 'Anunciante', 'guiawp' ), [
				'read'         => true,
				'upload_files' => true,
			] );
		}
	}
}
