<?php
/**
 * EDD Unit Tests Bootstrap
 *
 * @since 2.5
 */
class EDD_Unit_Tests_Bootstrap {

	/** @var \WC_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment
	 *
	 * @since 2.2
	 */
	public function __construct() {
		global $current_user, $edd_options;

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['SERVER_NAME'] = '';
		$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

		define( 'EDD_USE_PHP_SESSIONS', false );

		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		define( 'WP_PLUGIN_DIR', dirname( $this->plugin_dir ) );
		$this->core_dir     = '/tmp/wordpress';
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );

		// load WC
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_edd' ), 9 );

		// install WC
		tests_add_filter( 'setup_theme', array( $this, 'install_edd' ), 10 );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load WC testing framework
		$this->includes();
	}

	/**
	 * Load WooCommerce
	 *
	 * @since 2.2
	 */
	public function load_edd() {
		require_once( $this->plugin_dir . '/easy-digital-downloads.php' );
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded
	 *
	 * @since 2.2
	 */
	public function install_edd() {
		global $current_user, $edd_options;

		echo "Installing Easy Digital Downloads...\n";
		//activate_plugin( 'easy-digital-downloads/easy-digital-downloads.php' );

		// Install Easy Digital Downloads
		edd_install();

		$edd_options = get_option( 'edd_settings' );

		$current_user = new WP_User(1);
		$current_user->set_role('administrator');
	}

	/**
	 * Load EDD-specific test cases and factories
	 *
	 * @since 2.2
	 */
	public function includes() {
		// Include helpers
		require_once $this->tests_dir . '/helpers/shims.php';
		require_once $this->tests_dir . '/helpers/class-helper-download.php';
		require_once $this->tests_dir . '/helpers/class-helper-payment.php';
		require_once $this->tests_dir . '/helpers/class-helper-discount.php';
	}

	/**
	 * Get the single class instance
	 *
	 * @since 2.2
	 * @return EDD_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

EDD_Unit_Tests_Bootstrap::instance();
