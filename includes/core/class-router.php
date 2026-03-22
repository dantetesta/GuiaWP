<?php
/**
 * Roteador de páginas customizadas
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Router {

	private array $routes = [
		'cadastro'              => 'front/cadastro',
		'login'                 => 'front/login',
		'blog'                  => 'front/blog',
		'painel'                => 'dashboard/index',
		'painel/anuncios'       => 'dashboard/anuncios',
		'painel/criar-anuncio'  => 'dashboard/criar-anuncio',
		'painel/editar-anuncio' => 'dashboard/editar-anuncio',
		'painel/perfil'         => 'dashboard/perfil',
		'painel/pagamento'      => 'dashboard/pagamento',
		'painel/renovar'        => 'dashboard/renovar',
		'painel-admin'          => 'admin-panel/index',
		'painel-admin/anuncios' => 'admin-panel/anuncios',
		'painel-admin/editar-anuncio' => 'dashboard/editar-anuncio',
		'painel-admin/pagamento' => 'dashboard/pagamento',
		'painel-admin/blog'     => 'admin-panel/blog',
		'painel-admin/usuarios' => 'admin-panel/usuarios',
		'painel-admin/categorias' => 'admin-panel/categorias',
		'painel-admin/planos'   => 'admin-panel/planos',
		'painel-admin/configuracoes' => 'admin-panel/configuracoes',
		'anuncios-mapa'             => 'front/anuncios-mapa',
		'categorias'                => 'front/categorias',
	];

	public function register_routes(): void {
		foreach ( $this->routes as $slug => $template ) {
			add_rewrite_rule(
				'^' . preg_quote( $slug, '/' ) . '/?$',
				'index.php?gcep_route=' . $slug,
				'top'
			);
		}
		add_rewrite_tag( '%gcep_route%', '([^&]+)' );
	}

	public function load_template( string $template ): string {
		$route = $this->resolve_route();

		if ( empty( $route ) ) {
			return $template;
		}

		set_query_var( 'gcep_route', $route );

		// Verificar permissões das rotas protegidas
		if ( str_starts_with( $route, 'painel-admin' ) && ! GCEP_Helpers::is_gcep_admin() ) {
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		}

		if ( str_starts_with( $route, 'painel' ) && ! str_starts_with( $route, 'painel-admin' ) ) {
			if ( ! is_user_logged_in() ) {
				wp_safe_redirect( home_url( '/login' ) );
				exit;
			}
		}

		$file = GCEP_PLUGIN_DIR . 'templates/' . $this->routes[ $route ] . '.php';
		if ( file_exists( $file ) ) {
			if ( is_404() ) {
				global $wp_query;

				if ( $wp_query instanceof WP_Query ) {
					$wp_query->is_404 = false;
				}
				status_header( 200 );
			}

			return $file;
		}

		return $template;
	}

	private function resolve_route(): string {
		$route = get_query_var( 'gcep_route' );

		if ( is_string( $route ) && isset( $this->routes[ $route ] ) ) {
			return $route;
		}

		$request_path = $this->get_request_path();
		if ( '' !== $request_path && isset( $this->routes[ $request_path ] ) ) {
			return $request_path;
		}

		$queried_object = get_queried_object();
		if ( $queried_object instanceof WP_Post && 'page' === $queried_object->post_type ) {
			$page_path = trim( (string) get_page_uri( $queried_object ), '/' );
			if ( isset( $this->routes[ $page_path ] ) ) {
				return $page_path;
			}
		}

		return '';
	}

	private function get_request_path(): string {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		$request_uri  = wp_unslash( $_SERVER['REQUEST_URI'] );
		$request_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$home_path    = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

		if ( '' !== $home_path ) {
			if ( $request_path === $home_path ) {
				return '';
			}

			$prefix = $home_path . '/';
			if ( str_starts_with( $request_path, $prefix ) ) {
				$request_path = substr( $request_path, strlen( $prefix ) );
			}
		}

		return trim( $request_path, '/' );
	}
}
