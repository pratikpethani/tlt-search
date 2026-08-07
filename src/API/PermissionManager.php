<?php

namespace TLTSuite\TLTSearch\API;

class PermissionManager {

	public function public_search(): bool {

		return true;
	}

	public function manage_settings(): bool {

		return current_user_can(
			'manage_options'
		);
	}
}