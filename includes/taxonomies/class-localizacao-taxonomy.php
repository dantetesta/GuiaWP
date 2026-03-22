<?php
/**
 * Taxonomia gcep_localizacao
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Localizacao_Taxonomy {

	public function register(): void {
		$labels = [
			'name'              => __( 'Localizações', 'guiawp' ),
			'singular_name'     => __( 'Localização', 'guiawp' ),
			'search_items'      => __( 'Buscar Localizações', 'guiawp' ),
			'all_items'         => __( 'Todas as Localizações', 'guiawp' ),
			'parent_item'       => __( 'Localização Pai', 'guiawp' ),
			'parent_item_colon' => __( 'Localização Pai:', 'guiawp' ),
			'edit_item'         => __( 'Editar Localização', 'guiawp' ),
			'update_item'       => __( 'Atualizar Localização', 'guiawp' ),
			'add_new_item'      => __( 'Nova Localização', 'guiawp' ),
			'new_item_name'     => __( 'Nome da Localização', 'guiawp' ),
			'menu_name'         => __( 'Localizações', 'guiawp' ),
		];

		register_taxonomy( 'gcep_localizacao', 'gcep_anuncio', [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'localizacao', 'hierarchical' => true ],
		] );
	}
}
