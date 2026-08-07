<?php

namespace TLTSuite\TLTSearch\API;

use WP_REST_Request;

class RateLimiter {

	const LIMIT_PER_MINUTE = 30;
	const LIMIT_PER_HOUR   = 300;
	const CACHE_GROUP      = 'tlt_search_rate_limit';

	public function allow_request( WP_REST_Request $request ): bool {

		$client_id = $this->get_client_identifier();

		$limit_per_minute = (int) apply_filters( 'tlt_search_rate_limit_per_minute', self::LIMIT_PER_MINUTE );
		$limit_per_hour   = (int) apply_filters( 'tlt_search_rate_limit_per_hour', self::LIMIT_PER_HOUR );

		if ( $limit_per_minute > 0 && ! $this->check_rate_limit( $client_id, 'minute', $limit_per_minute, 60 ) ) {
			return false;
		}

		if ( $limit_per_hour > 0 && ! $this->check_rate_limit( $client_id, 'hour', $limit_per_hour, 3600 ) ) {
			return false;
		}

		return apply_filters( 'tlt_search_allow_request', true, $request );
	}

	private function get_client_identifier(): string {

		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		$ip = $this->get_client_ip();
		return 'ip_' . sanitize_text_field( $ip );
	}

	private function get_client_ip(): string {

		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '0.0.0.0';
	}

	private function check_rate_limit( string $client_id, string $window, int $limit, int $seconds ): bool {

		$cache_key = $client_id . ':' . $window;
		$count     = (int) wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( $count >= $limit ) {
			return false;
		}

		wp_cache_set( $cache_key, $count + 1, self::CACHE_GROUP, $seconds );
		return true;
	}
}