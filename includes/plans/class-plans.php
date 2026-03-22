<?php
/**
 * Gestão de planos premium
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Plans {

	private static bool $table_synced = false;

	public static function create_table(): void {
		global $wpdb;
		$table           = $wpdb->prefix . 'gcep_plans';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			days int(11) unsigned NOT NULL DEFAULT 30,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			description text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function ensure_table(): void {
		if ( self::$table_synced ) {
			return;
		}

		// dbDelta tambem atualiza colunas/indices quando a tabela ja existe.
		self::create_table();
		self::$table_synced = true;
	}

	public static function get_all( string $status = '' ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_plans';

		self::ensure_table();

		if ( $status ) {
			$query = $wpdb->prepare( "SELECT * FROM $table WHERE status = %s ORDER BY sort_order ASC, id ASC", $status );
		} else {
			$query = "SELECT * FROM $table ORDER BY sort_order ASC, id ASC";
		}

		return $wpdb->get_results( $query, ARRAY_A ) ?: [];
	}

	public static function get_active(): array {
		return self::get_all( 'active' );
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_plans';

		self::ensure_table();

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	public static function create( array $data ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_plans';

		self::ensure_table();

		$wpdb->insert( $table, [
			'name'        => sanitize_text_field( $data['name'] ?? '' ),
			'days'        => absint( $data['days'] ?? 30 ),
			'price'       => floatval( $data['price'] ?? 0 ),
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'status'      => in_array( ( $data['status'] ?? 'active' ), [ 'active', 'inactive' ], true ) ? $data['status'] : 'active',
			'sort_order'  => intval( $data['sort_order'] ?? 0 ),
		], [ '%s', '%d', '%f', '%s', '%s', '%d' ] );

		if ( false === $wpdb->insert ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public static function update( int $id, array $data ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_plans';

		self::ensure_table();

		$update = [];
		$format = [];

		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( $data['name'] );
			$format[]       = '%s';
		}
		if ( isset( $data['days'] ) ) {
			$update['days'] = absint( $data['days'] );
			$format[]       = '%d';
		}
		if ( isset( $data['price'] ) ) {
			$update['price'] = floatval( $data['price'] );
			$format[]        = '%f';
		}
		if ( isset( $data['description'] ) ) {
			$update['description'] = sanitize_textarea_field( $data['description'] );
			$format[]              = '%s';
		}
		if ( isset( $data['status'] ) && in_array( $data['status'], [ 'active', 'inactive' ], true ) ) {
			$update['status'] = $data['status'];
			$format[]         = '%s';
		}
		if ( isset( $data['sort_order'] ) ) {
			$update['sort_order'] = intval( $data['sort_order'] );
			$format[]             = '%d';
		}

		if ( empty( $update ) ) {
			return false;
		}

		return false !== $wpdb->update( $table, $update, [ 'id' => $id ], $format, [ '%d' ] );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'gcep_plans';

		self::ensure_table();

		return false !== $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	public static function get_last_error(): string {
		global $wpdb;

		return (string) $wpdb->last_error;
	}

	public static function format_duration( int $days ): string {
		if ( $days >= 365 ) {
			$years = floor( $days / 365 );
			return sprintf( _n( '%d ano', '%d anos', $years, 'guiawp' ), $years );
		}
		if ( $days >= 30 ) {
			$months = floor( $days / 30 );
			return sprintf( _n( '%d mês', '%d meses', $months, 'guiawp' ), $months );
		}
		return sprintf( _n( '%d dia', '%d dias', $days, 'guiawp' ), $days );
	}
}
