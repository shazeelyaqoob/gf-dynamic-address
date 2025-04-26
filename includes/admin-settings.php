<?php
if (!defined('ABSPATH')) {
    exit;
}

class GF_DA_Admin_Settings {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'gf_edit_forms',
            __('Dynamic Address Settings', 'gf-dynamic-address'),
            __('Dynamic Address', 'gf-dynamic-address'),
            'manage_options',
            'gf_da_settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('gf_da_settings', 'gf_da_repeater_settings');
        add_settings_section('gf_da_main', __('Repeater Settings', 'gf-dynamic-address'), null, 'gf_da_settings');
        add_settings_field(
            'min_rows',
            __('Minimum Rows', 'gf-dynamic-address'),
            [$this, 'min_rows_callback'],
            'gf_da_settings',
            'gf_da_main'
        );
        add_settings_field(
            'max_rows',
            __('Maximum Rows', 'gf-dynamic-address'),
            [$this, 'max_rows_callback'],
            'gf_da_settings',
            'gf_da_main'
        );
    }

    public function min_rows_callback() {
        $options = get_option('gf_da_repeater_settings', ['min_rows' => 1, 'max_rows' => 5]);
        ?>
        <input type="number" name="gf_da_repeater_settings[min_rows]" value="<?php echo esc_attr($options['min_rows']); ?>" min="1">
        <?php
    }

    public function max_rows_callback() {
        $options = get_option('gf_da_repeater_settings', ['min_rows' => 1, 'max_rows' => 5]);
        ?>
        <input type="number" name="gf_da_repeater_settings[max_rows]" value="<?php echo esc_attr($options['max_rows']); ?>" min="1">
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Dynamic Address Settings', 'gf-dynamic-address'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gf_da_settings');
                do_settings_sections('gf_da_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}