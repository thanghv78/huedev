<?php
/*
  Plugin Name: HueDev ERP
  Plugin URI: https://huedev.com/
  Description: HueDev ERP
  Author: thanghv
  Author URI: https://huedev.com
  Text Domain: hderp
  Version: 0.0.2
*/



// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HueDev_ERP class
 *
 * @class HueDev_ERP The class that holds the entire HueDev_ERP plugin
 */
final class HueDev_ERP {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.2';

    /**
     * Minimum PHP version required
     *
     * @var string
     */
    private $min_php = '5.4.0';

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = array();

    /**
     * Initializes the HueDev_ERP() class
     *
     * Checks for an existing HueDev_ERP() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Constructor for the HueDev_ERP class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct() {
        // dry check on older PHP versions, if found deactivate itself with an error
        register_activation_hook( __FILE__, array( $this, 'auto_deactivate' ) );

        if ( ! $this->is_supported_php() ) {
            return;
        }

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        // instantiate classes
        $this->instantiate();

        // Initialize the action hooks
        $this->init_actions();

        // load the modules
        $this->load_module();

        // Loaded action
        do_action( 'hd_erp_loaded' );
    }

    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    /**
     * Check if the PHP version is supported
     *
     * @return bool
     */
    public function is_supported_php() {
        if ( version_compare( PHP_VERSION, $this->min_php, '<=' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Bail out if the php version is lower than
     *
     * @return void
     */
    function auto_deactivate() {
        if ( $this->is_supported_php() ) {
            return;
        }

        deactivate_plugins( basename( __FILE__ ) );

        $error = __( '<h1>An Error Occured</h1>', 'hderp' );
        $error .= __( '<h2>Your installed PHP Version is: ', 'hderp' ) . PHP_VERSION . '</h2>';
        $error .= __( '<p>The <strong>WP ERP</strong> plugin requires PHP version <strong>', 'hderp' ) . $this->min_php . __( '</strong> or greater', 'hderp' );
        $error .= __( '<p>The version of your PHP is ', 'hderp' ) . '<a href="http://php.net/supported-versions.php" target="_blank"><strong>' . __( 'unsupported and old', 'hderp' ) . '</strong></a>.';
        $error .= __( 'You should update your PHP software or contact your host regarding this matter.</p>', 'hderp' );
        wp_die( $error, __( 'Plugin Activation Error', 'hderp' ), array( 'response' => 200, 'back_link' => true ) );
    }

    /**
     * Define the plugin constants
     *
     * @return void
     */
    private function define_constants() {
        define( 'HDERP_VERSION', $this->version );
        define( 'HDERP_FILE', __FILE__ );
        define( 'HDERP_PATH', dirname( HDERP_FILE ) );
        define( 'HDERP_INCLUDES', HDERP_PATH . '/includes' );
        define( 'HDERP_MODULES', HDERP_PATH . '/modules' );
        define( 'HDERP_URL', plugins_url( '', HDERP_FILE ) );
        define( 'HDERP_ASSETS', HDERP_URL . '/assets' );
        define( 'HDERP_VIEWS', HDERP_INCLUDES . '/admin/views' );
    }

    /**
     * Include the required files
     *
     * @return void
     */
    private function includes() {
        include dirname( __FILE__ ) . '/vendor/autoload.php';

        require_once HDERP_INCLUDES . '/class-install.php';
        require_once HDERP_INCLUDES . '/functions.php';
        require_once HDERP_INCLUDES . '/actions-filters.php';
        require_once HDERP_INCLUDES . '/functions-html.php';
        require_once HDERP_INCLUDES . '/functions-company.php';
        require_once HDERP_INCLUDES . '/functions-people.php';
		require_once HDERP_INCLUDES . '/functions-customer.php';
		require_once HDERP_INCLUDES . '/functions-general.php';
		require_once HDERP_INCLUDES . '/functions-address.php';
        require_once HDERP_INCLUDES . '/lib/class-wedevs-insights.php';

        if ( is_admin() ) {
            require_once HDERP_INCLUDES . '/admin/functions.php';
            require_once HDERP_INCLUDES . '/admin/class-menu.php';
            require_once HDERP_INCLUDES . '/admin/class-admin.php';
        }
    }

    /**
     * Instantiate classes
     *
     * @return void
     */
    private function instantiate() {

        new \HueDev\ERP\Admin\User_Profile();
        new \HueDev\ERP\Scripts();
        new \HueDev\ERP\Updates();
        //new \HueDev\ERP\Tracker();

        $this->container['modules']     = new \HueDev\ERP\Framework\Modules();
        $this->container['emailer']     = \HueDev\ERP\Emailer::init();
        $this->container['integration'] = \HueDev\ERP\Integration::init();
    }

    /**
     * Initialize WordPress action hooks
     *
     * @return void
     */
    private function init_actions() {

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
        add_action( 'init', array( $this, 'setup_database' ) );

        // initialize emailer class
        add_action( 'hd_erp_loaded', array( $this->container['emailer'], 'init_emails' ) );

        // initialize integration class
        add_action( 'hd_erp_loaded', array( $this->container['integration'], 'init_integrations' ) );
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'hderp', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );
    }

    /**
     * Setup database related tasks
     *
     * @return void
     */
    public function setup_database() {
        global $wpdb;

        $wpdb->hd_erp_peoplemeta = $wpdb->prefix . 'hd_erp_peoplemeta';
    }

    /**
     * Load the current ERP module
     *
     * We don't load every module at once, just load
     * what is necessary
     *
     * @return void
     */
    public function load_module() {
        $modules = $this->modules->get_modules();

        if ( ! $modules ) {
            return;
        }

        foreach ($modules as $key => $module) {

            if ( ! $this->modules->is_module_active( $key ) ) {
                continue;
            }

            if ( isset( $module['callback'] ) && class_exists( $module['callback'] ) ) {
                new $module['callback']( $this );
            }
        }
    }

} // HueDev_ERP

/**
 * Init the wperp plugin
 *
 * @return HueDev_ERP the plugin object
 */
function hderp() {
    return HueDev_ERP::init();
}

// kick it off
hderp();
