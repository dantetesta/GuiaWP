<?php
/**
 * Sistema de expiração de anúncios premium
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.3.0 - 2026-03-11
 * @modified 1.8.0 - 2026-03-20 - date() substituído por wp_date(); cron_schedules sem duplo registro; renew() não calcula datas antecipadas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Expiration {

	public function init(): void {
		// Registrado uma única vez aqui — schedule_event() não precisa registrar novamente
		add_filter( 'cron_schedules', [ __CLASS__, 'register_schedule' ] );
		add_action( 'init', [ __CLASS__, 'schedule_event' ] );
		add_action( 'gcep_check_expired', [ $this, 'check_expired' ] );
	}

	public static function register_schedule( array $schedules ): array {
		$schedules['gcep_fifteen_minutes'] = [
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'A cada 15 minutos', 'guiawp' ),
		];

		return $schedules;
	}

	public static function schedule_event(): void {
		if ( ! wp_next_scheduled( 'gcep_check_expired' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'gcep_fifteen_minutes', 'gcep_check_expired' );
		}
	}

	public static function clear_scheduled_event(): void {
		wp_clear_scheduled_hook( 'gcep_check_expired' );
	}

	public function check_expired(): void {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		$expired_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'GCEP_status_anuncio' AND pm_status.meta_value = 'publicado'
			 INNER JOIN {$wpdb->postmeta} pm_plano ON p.ID = pm_plano.post_id AND pm_plano.meta_key = 'GCEP_tipo_plano' AND pm_plano.meta_value = 'premium'
			 INNER JOIN {$wpdb->postmeta} pm_exp ON p.ID = pm_exp.post_id AND pm_exp.meta_key = 'GCEP_data_expiracao'
			 WHERE p.post_type = 'gcep_anuncio'
			 AND p.post_status = 'publish'
			 AND pm_exp.meta_value != ''
			 AND pm_exp.meta_value <= %s",
			$today
		) );

		if ( empty( $expired_ids ) ) {
			return;
		}

		$expired_ids = array_map( 'intval', $expired_ids );

		// Batch UPDATE no postmeta (1 query em vez de N)
		$ids_placeholder = implode( ',', $expired_ids );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- IDs já são inteiros validados
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND post_id IN ({$ids_placeholder})",
				'expirado',
				'GCEP_status_anuncio'
			)
		);

		// Batch UPDATE no post_status (1 query em vez de N)
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			"UPDATE {$wpdb->posts} SET post_status = 'draft' WHERE ID IN ({$ids_placeholder})"
		);

		// Limpar cache dos posts afetados
		foreach ( $expired_ids as $post_id ) {
			clean_post_cache( $post_id );
		}
	}

	/**
	 * Calcula e grava as datas de vigência a partir de hoje.
	 * Deve ser chamado SOMENTE após confirmação de pagamento.
	 */
	public static function set_expiration( int $post_id, int $days ): void {
		$start_date = current_time( 'Y-m-d' );
		// wp_date() respeita o timezone configurado no WordPress
		$end_date   = wp_date( 'Y-m-d', strtotime( $start_date . ' +' . $days . ' days' ) );

		update_post_meta( $post_id, 'GCEP_data_aprovacao', $start_date );
		update_post_meta( $post_id, 'GCEP_data_expiracao', $end_date );
		update_post_meta( $post_id, 'GCEP_vigencia_dias', $days );
	}

	/**
	 * Inicia fluxo de renovação: grava apenas o número de dias e aguarda pagamento.
	 * As datas NÃO são calculadas aqui — serão definidas em confirm_payment() após pagamento confirmado.
	 */
	public static function renew( int $post_id, int $days ): void {
		// Preserva vigencia_dias para confirm_payment() usar ao aprovar
		update_post_meta( $post_id, 'GCEP_vigencia_dias', $days );
		// Limpa datas antigas para evitar que o cron expire antes do pagamento
		delete_post_meta( $post_id, 'GCEP_data_aprovacao' );
		delete_post_meta( $post_id, 'GCEP_data_expiracao' );
		update_post_meta( $post_id, 'GCEP_status_anuncio', 'aguardando_pagamento' );
		GCEP_Helpers::sync_anuncio_post_status( $post_id, 'aguardando_pagamento' );
	}

	public static function get_remaining_days( int $post_id ): int {
		$exp = get_post_meta( $post_id, 'GCEP_data_expiracao', true );
		if ( empty( $exp ) ) {
			return 0;
		}

		$diff = strtotime( $exp ) - strtotime( current_time( 'Y-m-d' ) );
		return max( 0, (int) ceil( $diff / DAY_IN_SECONDS ) );
	}

	public static function is_expired( int $post_id ): bool {
		$exp = get_post_meta( $post_id, 'GCEP_data_expiracao', true );
		if ( empty( $exp ) ) {
			return false;
		}
		return strtotime( $exp ) < strtotime( current_time( 'Y-m-d' ) );
	}
}
