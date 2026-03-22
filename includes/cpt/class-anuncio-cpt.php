<?php
/**
 * CPT gcep_anuncio
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Anuncio_CPT {

	public function register(): void {
		$labels = [
			'name'               => __( 'Anúncios', 'guiawp' ),
			'singular_name'      => __( 'Anúncio', 'guiawp' ),
			'add_new'            => __( 'Novo Anúncio', 'guiawp' ),
			'add_new_item'       => __( 'Adicionar Anúncio', 'guiawp' ),
			'edit_item'          => __( 'Editar Anúncio', 'guiawp' ),
			'view_item'          => __( 'Ver Anúncio', 'guiawp' ),
			'search_items'       => __( 'Buscar Anúncios', 'guiawp' ),
			'not_found'          => __( 'Nenhum anúncio encontrado', 'guiawp' ),
			'not_found_in_trash' => __( 'Nenhum anúncio na lixeira', 'guiawp' ),
			'all_items'          => __( 'Todos os Anúncios', 'guiawp' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'has_archive'         => true,
			'rewrite'             => [ 'slug' => 'anuncios', 'with_front' => false ],
			'supports'            => [ 'title', 'thumbnail', 'author' ],
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_rest'        => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
		];

		register_post_type( 'gcep_anuncio', $args );
	}
}
