<?php
/**
 * Analytics e tracking de visitas
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.2.3 - 2026-03-11
 * @modified 1.5.3 - 2026-03-11 - Tabela blog_views com tracking unico por IP/dia
 * @modified 1.6.0 - 2026-03-11 - Deduplicacao por IP/dia em anuncios, metodos globais para admin dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Analytics {

	public static function create_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'gcep_analytics';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			date date NOT NULL,
			ip_hash varchar(64) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			UNIQUE KEY post_date_ip (post_id, date, ip_hash),
			KEY post_id (post_id),
			KEY date (date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Migrar unique key antiga (post_date → post_date_ip)
		$old_key = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'post_date'",
				DB_NAME,
				$table_name
			)
		);

		if ( $old_key ) {
			$wpdb->query( "ALTER TABLE $table_name DROP INDEX post_date" );
		}
	}

	// 1 view unica por IP/dia/anuncio
	public static function track_view( int $post_id ): void {
		if ( $post_id <= 0 || ! self::should_track_view( $post_id ) ) {
			return;
		}

		$ip      = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ) );
		$ip_hash = hash( 'sha256', $ip . wp_salt( 'auth' ) );
		$date    = current_time( 'Y-m-d' );

		global $wpdb;
		$table = $wpdb->prefix . 'gcep_analytics';

		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO $table (post_id, date, ip_hash) VALUES (%d, %s, %s)",
				$post_id,
				$date,
				$ip_hash
			)
		);
	}

	public static function get_views_by_month( int $post_id, int $year, int $month ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_analytics';

		$start_date = sprintf( '%04d-%02d-01', $year, $month );
		$end_date   = date( 'Y-m-t', strtotime( $start_date ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DAY(date) AS day, COUNT(*) AS views FROM $table
				 WHERE post_id = %d AND date >= %s AND date <= %s
				 GROUP BY DAY(date)
				 ORDER BY date ASC",
				$post_id,
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		$days_in_month = (int) date( 't', strtotime( $start_date ) );
		$data          = array_fill( 1, $days_in_month, 0 );

		foreach ( $results as $row ) {
			$data[ (int) $row['day'] ] = (int) $row['views'];
		}

		return $data;
	}

	public static function get_total_views( int $post_id, int $year = 0, int $month = 0 ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_analytics';

		if ( $year > 0 && $month > 0 ) {
			$start_date = sprintf( '%04d-%02d-01', $year, $month );
			$end_date   = date( 'Y-m-t', strtotime( $start_date ) );

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE post_id = %d AND date >= %s AND date <= %s",
					$post_id,
					$start_date,
					$end_date
				)
			);
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE post_id = %d",
				$post_id
			)
		);
	}

	public static function get_views_for_posts( array $post_ids, int $year = 0, int $month = 0 ): array {
		$post_ids = array_values(
			array_filter(
				array_unique(
					array_map( 'absint', $post_ids )
				)
			)
		);

		if ( empty( $post_ids ) ) {
			return [];
		}

		global $wpdb;
		$table        = $wpdb->prefix . 'gcep_analytics';
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$params       = $post_ids;
		$sql          = "SELECT post_id, COUNT(*) AS views FROM $table WHERE post_id IN ($placeholders)";

		if ( $year > 0 && $month > 0 ) {
			$start_date = sprintf( '%04d-%02d-01', $year, $month );
			$end_date   = date( 'Y-m-t', strtotime( $start_date ) );
			$sql       .= ' AND date >= %s AND date <= %s';
			$params[]   = $start_date;
			$params[]   = $end_date;
		}

		$sql .= ' GROUP BY post_id';

		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$views = array_fill_keys( $post_ids, 0 );

		foreach ( $rows as $row ) {
			$current_post_id = absint( $row['post_id'] ?? 0 );
			if ( $current_post_id <= 0 ) {
				continue;
			}

			$views[ $current_post_id ] = (int) ( $row['views'] ?? 0 );
		}

		return $views;
	}

	public static function get_user_total_views( int $user_id, int $year = 0, int $month = 0 ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_analytics';

		$date_clause = '';
		$params      = [ $user_id ];

		if ( $year > 0 && $month > 0 ) {
			$start_date  = sprintf( '%04d-%02d-01', $year, $month );
			$end_date    = date( 'Y-m-t', strtotime( $start_date ) );
			$date_clause = ' AND a.date >= %s AND a.date <= %s';
			$params[]    = $start_date;
			$params[]    = $end_date;
		}

		$sql = "SELECT COUNT(*) FROM $table a
				INNER JOIN {$wpdb->posts} p ON a.post_id = p.ID
				WHERE p.post_author = %d AND p.post_type = 'gcep_anuncio' AND p.post_status = 'publish'
				$date_clause";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
	}

	// ==================== Blog Views (unicas por IP/dia) ====================

	public static function create_blog_views_table(): void {
		global $wpdb;
		$table           = $wpdb->prefix . 'gcep_blog_views';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			date date NOT NULL,
			ip_hash varchar(64) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			UNIQUE KEY post_date_ip (post_id, date, ip_hash),
			KEY post_id (post_id),
			KEY date (date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function track_blog_view( int $post_id ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		// Ignorar bots e crawlers
		$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) );
		if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|mediapartners|preview/i', $ua ) ) {
			return;
		}

		$ip      = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ) );
		$ip_hash = hash( 'sha256', $ip . wp_salt( 'auth' ) );
		$date    = current_time( 'Y-m-d' );

		global $wpdb;
		$table = $wpdb->prefix . 'gcep_blog_views';

		// INSERT IGNORE para garantir unicidade IP/dia/post
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO $table (post_id, date, ip_hash) VALUES (%d, %s, %s)",
				$post_id,
				$date,
				$ip_hash
			)
		);
	}

	public static function get_blog_total_views( int $post_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_blog_views';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE post_id = %d",
				$post_id
			)
		);
	}

	// ==================== Metodos Globais (Admin Dashboard) ====================

	// Views globais de todos os anuncios por dia
	public static function get_global_views_by_month( int $year, int $month ): array {
		global $wpdb;
		$table      = $wpdb->prefix . 'gcep_analytics';
		$start_date = sprintf( '%04d-%02d-01', $year, $month );
		$end_date   = date( 'Y-m-t', strtotime( $start_date ) );
		$days       = (int) date( 't', strtotime( $start_date ) );
		$data       = array_fill( 1, $days, 0 );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DAY(date) AS day, COUNT(*) AS views
				FROM $table
				WHERE date >= %s AND date <= %s
				GROUP BY DAY(date)
				ORDER BY date ASC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$data[ (int) $row['day'] ] = (int) $row['views'];
		}

		return $data;
	}

	// Total global de views de anuncios
	public static function get_global_total_views( int $year = 0, int $month = 0 ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_analytics';

		if ( $year > 0 && $month > 0 ) {
			$start_date = sprintf( '%04d-%02d-01', $year, $month );
			$end_date   = date( 'Y-m-t', strtotime( $start_date ) );

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE date >= %s AND date <= %s",
					$start_date,
					$end_date
				)
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
	}

	// Top N anuncios mais visualizados
	public static function get_top_anuncios( int $limit = 20, int $year = 0, int $month = 0 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_analytics';

		$date_clause = '';
		$params      = [];

		if ( $year > 0 && $month > 0 ) {
			$start_date  = sprintf( '%04d-%02d-01', $year, $month );
			$end_date    = date( 'Y-m-t', strtotime( $start_date ) );
			$date_clause = ' AND a.date >= %s AND a.date <= %s';
			$params[]    = $start_date;
			$params[]    = $end_date;
		}

		$params[] = $limit;

		$sql = "SELECT a.post_id, p.post_title, COUNT(*) AS views
				FROM $table a
				INNER JOIN {$wpdb->posts} p ON a.post_id = p.ID
				WHERE p.post_type = 'gcep_anuncio'
				AND p.post_status IN ('publish', 'draft')
				$date_clause
				GROUP BY a.post_id, p.post_title
				ORDER BY views DESC
				LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
	}

	// Top N posts do blog mais visualizados
	public static function get_top_blog_posts( int $limit = 20, int $year = 0, int $month = 0 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_blog_views';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return [];
		}

		$date_clause = '';
		$params      = [];

		if ( $year > 0 && $month > 0 ) {
			$start_date  = sprintf( '%04d-%02d-01', $year, $month );
			$end_date    = date( 'Y-m-t', strtotime( $start_date ) );
			$date_clause = ' AND bv.date >= %s AND bv.date <= %s';
			$params[]    = $start_date;
			$params[]    = $end_date;
		}

		$params[] = $limit;

		$sql = "SELECT bv.post_id, p.post_title, COUNT(*) AS views
				FROM $table bv
				INNER JOIN {$wpdb->posts} p ON bv.post_id = p.ID
				WHERE p.post_type = 'post'
				AND p.post_status = 'publish'
				$date_clause
				GROUP BY bv.post_id, p.post_title
				ORDER BY views DESC
				LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
	}

	public static function get_user_views_by_month( int $user_id, int $year, int $month ): array {
		global $wpdb;
		$table      = $wpdb->prefix . 'gcep_analytics';
		$start_date = sprintf( '%04d-%02d-01', $year, $month );
		$end_date   = date( 'Y-m-t', strtotime( $start_date ) );
		$days       = (int) date( 't', strtotime( $start_date ) );
		$data       = array_fill( 1, $days, 0 );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DAY(a.date) AS day, COUNT(*) AS views
				FROM $table a
				INNER JOIN {$wpdb->posts} p ON a.post_id = p.ID
				WHERE p.post_author = %d
				AND p.post_type = 'gcep_anuncio'
				AND p.post_status IN ('publish', 'draft')
				AND a.date >= %s
				AND a.date <= %s
				GROUP BY DAY(a.date)
				ORDER BY a.date ASC",
				$user_id,
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$data[ (int) $row['day'] ] = (int) $row['views'];
		}

		return $data;
	}

	private static function should_track_view( int $post_id ): bool {
		if ( is_admin() || wp_doing_ajax() ) {
			return false;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$ua = strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		if ( '' === $ua ) {
			return false;
		}

		if ( preg_match( '/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headless|preview/i', $ua ) ) {
			return false;
		}

		return true;
	}
}
