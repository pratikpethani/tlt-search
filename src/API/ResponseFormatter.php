<?php

namespace TLTSuite\TLTSearch\API;

class ResponseFormatter {

	public function success( array $data = [], int $status = 200 ): array {

		return [
			'success' => true,
			'status'  => $status,
			'data'    => $data,
		];
	}

	public function error( string $code, string $message, int $status = 400 ): array {

		return [
			'success' => false,
			'status'  => $status,
			'error'   => [
				'code'    => $code,
				'message' => $message,
			],
		];
	}
}