<?php
/**
 * Shared top of every TLT Search admin page: header bar and tab bar.
 * The including page sets $tlt_page and closes the three wrapper divs.
 */
defined( 'ABSPATH' ) || exit;

// Tab key => [ label, menu page slug ].
$tlt_nav = [
	'dashboard'     => [ __( 'Dashboard', 'tlt-search' ), 'tlt-search' ],
	'settings'      => [ __( 'Settings', 'tlt-search' ), 'tlt-search-settings' ],
	'analytics'     => [ __( 'Analytics', 'tlt-search' ), 'tlt-search-analytics' ],
	'synonyms'      => [ __( 'Synonyms', 'tlt-search' ), 'tlt-search-synonyms' ],
	'merchandising' => [ __( 'Merchandising', 'tlt-search' ), 'tlt-search-merchandising' ],
	'diagnostics'   => [ __( 'Diagnostics', 'tlt-search' ), 'tlt-search-diagnostics' ],
];
?>
<div id="tlt-suite">
	<div class="tlt-settings-page tlt-ui">
		<div class="tlt-header">
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<strong class="tlt-header__name"><?php esc_html_e( 'TLT Search', 'tlt-search' ); ?></strong>
			<span class="tlt-header__sep" aria-hidden="true">/</span>
			<h1><?php echo esc_html( $tlt_nav[ $tlt_page ][0] ); ?></h1>
			<span class="tlt-header__version">v<?php echo esc_html( TLT_SEARCH_VERSION ); ?></span>
		</div>
		<nav class="nav-tab-wrapper tlt-tabs">
			<?php foreach ( $tlt_nav as $key => [ $label, $slug ] ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="nav-tab<?php echo $key === $tlt_page ? ' nav-tab-active' : ''; ?>"<?php echo $key === $tlt_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="tlt-body">
			<hr class="wp-header-end">
