<?php
	// Only admins can access this page.
	if( !function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) ) ) {
		die( esc_html__( "You do not have permissions to perform this action.", 'pmpro-toolkit' ) );
	}

	// Load the admin header.
	require_once PMPRO_DIR . '/adminpages/admin_header.php';

	//$section = !empty( $_REQUEST['section']  ) ? sanitize_text_field( $_REQUEST[ 'section' ] ) : 'options';
	$section = pmpro_getParam( 'section', 'REQUEST', 'options', );
?>
<h1><?php esc_html_e( "Developer's Toolkit", 'pmpro-toolkit' ); ?></h1>

<!-- nav tabs -->

<nav class="pmpro-nav-primary" aria-labelledby="pmpro-toolkit-menu">
	<h2 id="pmpro-toolkit-menu" class="screen-reader-text"><?php esc_html_e( 'Toolkit Menu', 'pmpro-toolkit' ); ?></h2>
	<ul>
		<li>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-toolkit', 'section' => 'options' ), admin_url( 'admin.php' ) ) ); ?>"<?php if ( $section == 'options') { ?> class="current"<?php } ?>>
				<?php esc_html_e( 'Toolkit Options', 'pmpro-toolkit' ); ?>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-toolkit', 'section' => 'scripts' ), admin_url( 'admin.php' ) ) ); ?>"<?php if ( $section == 'scripts' ) { ?> class="current"<?php } ?>>
				<?php esc_html_e( 'Database Scripts', 'pmpro-toolkit' ); ?>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-toolkit', 'section' => 'cli-commands' ), admin_url( 'admin.php' ) ) ); ?>"<?php if ( $section == 'cli-commands' ) { ?> class="current"<?php } ?>>
				<?php esc_html_e( 'CLI Commands', 'pmpro-toolkit' ); ?>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pmpro-toolkit', 'section' => 'migration' ), admin_url( 'admin.php' ) ) ); ?>"<?php if ( $section == 'migration' ) { ?> class="current"<?php } ?>>
				<?php esc_html_e( 'Migration Assistant', 'pmpro-toolkit' ); ?>
			</a>
		</li>
	</ul>
</nav>

<hr class="wp-header-end">

<?php
	// Show the appropriate section.
	if( 'scripts' == $section ) {
		require_once 'scripts.php';
	} elseif( 'migration' == $section ) {
		require_once( 'migration.php' );
	} elseif( 'cli-commands' == $section ) {
		require_once( 'cli-commands.php' );
	} else {
		require_once( 'settings.php' );
	}
?>

<?php
// Load the admin footer.
require_once PMPRO_DIR . '/adminpages/admin_footer.php';


