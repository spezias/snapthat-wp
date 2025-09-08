
<?php
/**
 * Plugin Name: SnapThat WordPress Plugin
 * Description: Bindet das SnapThat JS-Plugin ein. Konfigurierbar über das WordPress Backend. Erfordert einen SnapThat-Account.
 * Version:     1.2.0
 * Author:      David Wright, AdventureKingz GmbH
 * Text Domain: snapthat-plugin
 */

if (!defined('ABSPATH')) exit;

class SnapThat_WP_Plugin {

    private $option_name = 'snapthat_plugin_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_color_picker']);
        add_action('wp_footer', [$this, 'inject_script']);
    }

    public function add_settings_page() {
        add_options_page(
            __('SnapThat Plugin', 'snapthat-plugin'),
            __('SnapThat Plugin', 'snapthat-plugin'),
            'manage_options',
            'snapthat-plugin',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_options']);
    }

    public function enqueue_color_picker($hook) {
        if ($hook !== 'settings_page_snapthat-plugin') return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('snapthat-color-picker', plugins_url('snapthat-color.js', __FILE__), ['wp-color-picker'], false, true);
    }

    public function get_default_options() {
        return [
            'api'       => 'https://snapthat.dev/api',
            'brand'     => '#41b1cd',
            'pin'       => '#ef4444',
            'theme'     => 'light',
            'pos'       => 'left',
            'offset_x'  => '30',
            'offset_y'  => '30',
            'roles'     => ['administrator'],
            'public'    => false,
        ];
    }

    public function sanitize_options($input) {
        $defaults = $this->get_default_options();
        $output = wp_parse_args($input, $defaults);

        $output['api']      = esc_url_raw($output['api']);
        $output['brand']    = sanitize_hex_color($output['brand']);
        $output['pin']      = sanitize_hex_color($output['pin']);
        $output['theme']    = in_array($output['theme'], ['light','dark']) ? $output['theme'] : 'light';
        $output['pos']      = in_array($output['pos'], ['left','right']) ? $output['pos'] : 'left';
        $output['offset_x'] = intval($output['offset_x']);
        $output['offset_y'] = intval($output['offset_y']);
        $output['roles']    = array_map('sanitize_text_field', (array)$output['roles']);
        $output['public']   = !empty($output['public']);

        return $output;
    }

    public function render_settings_page() {
        $options = get_option($this->option_name, $this->get_default_options());
        ?>
        <div class="wrap">
            <h1><?php _e('SnapThat Plugin Einstellungen', 'snapthat-plugin'); ?></h1>
            <p><?php _e('Zur Nutzung ist ein Account bei SnapThat erforderlich.', 'snapthat-plugin'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="api"><?php _e('API URL', 'snapthat-plugin'); ?></label></th>
                        <td><input type="url" name="<?php echo $this->option_name; ?>[api]" value="<?php echo esc_attr($options['api']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="brand"><?php _e('Brand-Farbe', 'snapthat-plugin'); ?></label></th>
                        <td><input type="text" name="<?php echo $this->option_name; ?>[brand]" value="<?php echo esc_attr($options['brand']); ?>" class="snapthat-color-field"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pin"><?php _e('Pin-Farbe', 'snapthat-plugin'); ?></label></th>
                        <td><input type="text" name="<?php echo $this->option_name; ?>[pin]" value="<?php echo esc_attr($options['pin']); ?>" class="snapthat-color-field"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="theme"><?php _e('Theme', 'snapthat-plugin'); ?></label></th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[theme]">
                                <option value="light" <?php selected($options['theme'], 'light'); ?>><?php _e('Light', 'snapthat-plugin'); ?></option>
                                <option value="dark" <?php selected($options['theme'], 'dark'); ?>><?php _e('Dark', 'snapthat-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pos"><?php _e('Position', 'snapthat-plugin'); ?></label></th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[pos]">
                                <option value="left" <?php selected($options['pos'], 'left'); ?>><?php _e('Links', 'snapthat-plugin'); ?></option>
                                <option value="right" <?php selected($options['pos'], 'right'); ?>><?php _e('Rechts', 'snapthat-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Offset X', 'snapthat-plugin'); ?></th>
                        <td><input type="number" name="<?php echo $this->option_name; ?>[offset_x]" value="<?php echo esc_attr($options['offset_x']); ?>" class="small-text"> px</td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Offset Y', 'snapthat-plugin'); ?></th>
                        <td><input type="number" name="<?php echo $this->option_name; ?>[offset_y]" value="<?php echo esc_attr($options['offset_y']); ?>" class="small-text"> px</td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Rollen/Gruppen', 'snapthat-plugin'); ?></th>
                        <td>
                            <?php
                            global $wp_roles;
                            foreach ($wp_roles->roles as $role_key => $role) {
                                ?>
                                <label>
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $options['roles'])); ?>>
                                    <?php echo esc_html(translate_user_role($role['name'])); ?>
                                </label><br>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Öffentlich anzeigen', 'snapthat-plugin'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[public]" value="1" <?php checked($options['public'], true); ?>>
                                <?php _e('Plugin auch für nicht eingeloggte Besucher aktivieren', 'snapthat-plugin'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function inject_script() {
        $options = get_option($this->option_name, $this->get_default_options());

        if (!$options['public']) {
            if (!is_user_logged_in()) return;
            $user = wp_get_current_user();
            if (empty(array_intersect($options['roles'], (array)$user->roles))) return;
        }

        ?>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script src="//snapthat.dev/snapthat.plugin.js"
                data-api="<?php echo esc_attr($options['api']); ?>"
                data-brand="<?php echo esc_attr($options['brand']); ?>"
                data-pin="<?php echo esc_attr($options['pin']); ?>"
                data-theme="<?php echo esc_attr($options['theme']); ?>"
                data-pos="<?php echo esc_attr($options['pos']); ?>"
                data-offset-x="<?php echo esc_attr($options['offset_x']); ?>"
                data-offset-y="<?php echo esc_attr($options['offset_y']); ?>"
        ></script>
        <?php
    }
}

new SnapThat_WP_Plugin();

add_action('admin_footer', function(){
    ?>
    <script>
    jQuery(document).ready(function($){
        $('.snapthat-color-field').wpColorPicker();
    });
    </script>
    <?php
});
