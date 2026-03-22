<?php
/**
 * Taxonomia gcep_categoria
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Categoria_Taxonomy {

	public function register(): void {
		$labels = [
			'name'              => __( 'Categorias', 'guiawp' ),
			'singular_name'     => __( 'Categoria', 'guiawp' ),
			'search_items'      => __( 'Buscar Categorias', 'guiawp' ),
			'all_items'         => __( 'Todas as Categorias', 'guiawp' ),
			'parent_item'       => __( 'Categoria Pai', 'guiawp' ),
			'parent_item_colon' => __( 'Categoria Pai:', 'guiawp' ),
			'edit_item'         => __( 'Editar Categoria', 'guiawp' ),
			'update_item'       => __( 'Atualizar Categoria', 'guiawp' ),
			'add_new_item'      => __( 'Nova Categoria', 'guiawp' ),
			'new_item_name'     => __( 'Nome da Categoria', 'guiawp' ),
			'menu_name'         => __( 'Categorias', 'guiawp' ),
		];

		register_taxonomy( 'gcep_categoria', 'gcep_anuncio', [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'categoria', 'hierarchical' => true ],
		] );
	}
}
