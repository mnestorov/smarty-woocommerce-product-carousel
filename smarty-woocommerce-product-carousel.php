<?php
/**
 * Plugin Name: SM - WooCommerce Product Carousel
 * Plugin URI:  https://smartystudio.net/smarty-product-carousel
 * Description: A custom WooCommerce product carousel plugin.
 * Version:     1.0.0
 * Author:      Smarty Studio | Martin Nestorov
 * Author URI:  https://smartystudio.net
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: smarty-product-carousel
 * 
 * Usage: 
 *  - [smarty_pc_product_carousel ids="1,2,3" speed="500" autoplay="true" autoplay_speed="3000"]
 *  - [smarty_pc_product_carousel slides_to_show="1"]
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (!function_exists('smarty_pc_check_woocommerce_exists')) {
    function smarty_pc_check_woocommerce_exists() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', 'smarty_pc_missing_woocommerce_notice');
        }
    }
    add_action('admin_init', 'smarty_pc_check_woocommerce_exists');
}

if (!function_exists('smarty_pc_missing_woocommerce_notice')) {
    function smarty_pc_missing_woocommerce_notice(){
        echo '<div class="error"><p><strong>' . __('SM - WooCommerce Product Carousel requires WooCommerce to be installed and active.', 'smarty-product-carousel') . '</strong></p></div>';
    }
}

if (!function_exists('smarty_pc_enqueue_slick_carousel')) {
    function smarty_pc_enqueue_slick_carousel() {
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
    }
    add_action('wp_enqueue_scripts', 'smarty_pc_enqueue_slick_carousel');
}

if (!function_exists('smarty_pc_enqueue_admin_scripts')) {
    /**
     * Enqueue required scripts and styles.
     */
    function smarty_pc_enqueue_admin_scripts($hook) {
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');

        // Enqueue admin script for settings page
        if ('toplevel_page_smarty-pc-admin-page' === $hook) {
            wp_enqueue_script('smarty-pc-admin-js', plugins_url('/admin/js/smarty-pc-admin.js', __FILE__), array('jquery'), null, true);
        }
    }
    add_action('admin_enqueue_scripts', 'smarty_pc_enqueue_admin_scripts');
}

if (!function_exists('smarty_pc_add_async_attribute')) {
    function smarty_pc_add_async_attribute($tag, $handle) {
        if ('slick' === $handle || 'select2' === $handle) {
            return str_replace(' src', ' async="async" src', $tag);
        }
        return $tag;
    }
    add_filter('script_loader_tag', 'smarty_pc_add_async_attribute', 10, 2);
}

if (!function_exists('smarty_pc_admin_menu')) {
    /**
     * Add admin menu for plugin settings.
     */
    function smarty_pc_admin_menu() {
        add_submenu_page(
            'woocommerce',                  // The slug name for the parent menu (or the file name of a standard WordPress admin page)
            'Products Carousel',            // The text to be displayed in the title tags of the page when the menu is selected
            'Products Carousel',            // The text to be used for the menu
            'manage_options',               // The capability required for this menu to be displayed to the user
            'smarty-pc-settings',           // The slug name to refer to this menu by (should be unique for this menu)
            'smarty_pc_admin_page_html'     // The function to be called to output the content for this page
        );
    }
    add_action('admin_menu', 'smarty_pc_admin_menu');
}

if (!function_exists('smarty_pc_admin_page_html')) {
    /**
     * Admin page HTML content.
     */
    function smarty_pc_admin_page_html() {
        // Fetch the selected products before the select element
        $options = get_option('smarty_pc_carousel_options');
        $selected_products = isset($options['products']) ? $options['products'] : [];
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Products Carousel | Settings', 'smarty-product-carousel')); ?></h1>
            <form method="post" action="options.php">
                <?php
                wp_nonce_field('smarty_pc_save_settings_action', 'smarty_pc_settings_nonce');
                settings_fields('smarty-pc-settings-group');
                do_settings_sections('smarty-pc-settings-group');
                ?>
                
                <h2><?php echo __('Products', 'smarty-product-carousel'); ?></h2>
                <p><?php echo __('Select products to add to the carousel.', 'smarty-product-carousel'); ?></p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="smarty-pc-product-search"><?php echo esc_html__('Select Products', 'smarty-product-carousel'); ?></label></th>
                            <td>
                                <select id="smarty-pc-product-search" name="smarty_pc_carousel_options[products][]" multiple="multiple" style="width: 100%">
                                <?php
                                    foreach ($selected_products as $product_id) {
                                        $product = wc_get_product($product_id);
                                        
                                        if ($product) {
                                            // Format the option text to include both product name and ID
                                            $option_text = sprintf('%s (ID: %d)', $product->get_name(), $product_id);
                                            echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . esc_html($option_text) . '</option>';
                                        }
                                    }
                                ?>
                                </select>
                                <p class="description"><?php echo esc_html__('Select products to display in the carousel.', 'smarty-product-carousel'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#smarty-pc-product-search').select2({
                ajax: {
                    url: ajaxurl, // WordPress AJAX
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            action: 'smarty_pc_search_products' // WordPress AJAX action
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 3,
                placeholder: 'Search for a product',
            });
        });
        </script>
        <?php
    }
}

if (!function_exists('smarty_pc_register_settings')) {
    /**
     * Register settings, sections, and fields
     */
    function smarty_pc_register_settings() {
        register_setting('smarty-pc-settings-group', 'smarty_pc_carousel_options', 'smarty_pc_options_sanitize');

        add_settings_section(
            'smarty_pc_carousel_settings', 
            'General', 
            'smarty_pc_carousel_settings_section_callback', 
            'smarty-pc-settings-group'
        );

        add_settings_section(
            'smarty_pc_carousel_discount', 
            'Discount', 
            'smarty_pc_carousel_discount_section_callback', 
            'smarty-pc-settings-group'
        );

        add_settings_section(
            'smarty_pc_carousel_texts', 
            'Texts', 
            'smarty_pc_carousel_texts_section_callback', 
            'smarty-pc-settings-group'
        );

        add_settings_section(
            'smarty_pc_carousel_styling', 
            'Styling', 
            'smarty_pc_carousel_styling_section_callback', 
            'smarty-pc-settings-group'
        );

        // Add a field for Hide Arrows
        add_settings_field(
            'smarty_pc_display_arrows',                // ID
            'Display Arrows',                       // Title
            'smarty_pc_display_arrows_callback',       // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_arrow_color',                   // ID
            'Arrow Color',                          // Title
            'smarty_pc_arrow_color_callback',          // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        // Add a field for Display Dots
        add_settings_field(
            'smarty_pc_display_dots',                  // ID
            'Display Dots',                         // Title
            'smarty_pc_display_dots_callback',         // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );
        
        add_settings_field(
            'smarty_pc_dot_color',                     // ID
            'Dot Color',                            // Title
            'smarty_pc_dot_color_callback',            // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_slides_to_show',                // ID
            'Slides to Show',                       // Title
            'smarty_pc_slides_to_show_callback',       // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_slides_to_scroll',              // ID
            'Slides to Scroll',                     // Title
            'smarty_pc_slides_to_scroll_callback',     // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );
        
        add_settings_field(
            'smarty_pc_slide_padding',                 // ID
            'Slide Padding',                        // Title
            'smarty_pc_slide_padding_callback',        // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_speed',                         // ID
            'Scrolling Speed',                       // Title
            'smarty_pc_speed_callback',                // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );        
        
        add_settings_field(
            'smarty_pc_autoplay_indicator',            // ID
            'Autoplay Indicator',                   // Title
            'smarty_pc_autoplay_indicator_callback',   // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_autoplay_speed',                // ID
            'Autoplay Speed',                       // Title
            'smarty_pc_autoplay_speed_callback',       // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_infinite',                      // ID
            'Infinite',                             // Title
            'smarty_pc_infinite_callback',             // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_settings'              // Section
        );

        add_settings_field(
            'smarty_pc_discount',                      // ID
            'Old Discount',                         // Title
            'smarty_pc_discount_callback',             // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_discount'              // Section
        );

        // Add a new field for custom title
        add_settings_field(
            'smarty_pc_custom_title',                   // ID
            'Custom Title',                          // Title
            'smarty_pc_custom_title_callback',          // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_texts'                  // Section
        );

        // Save text
        add_settings_field(
            'smarty_pc_save_text', 
            'Save Text', 
            'smarty_pc_save_text_callback', 
            'smarty-pc-settings-group', 
            'smarty_pc_carousel_texts'
        );

        // Add to Cart text
        add_settings_field(
            'smarty_pc_add_to_cart_text', 
            'Add To Cart Text', 
            'smarty_pc_add_to_cart_text_callback', 
            'smarty-pc-settings-group', 
            'smarty_pc_carousel_texts'
        );

        // Label text
        add_settings_field(
            'smarty_pc_label_text', 
            'Label Text', 
            'smarty_pc_label_text_callback', 
            'smarty-pc-settings-group', 
            'smarty_pc_carousel_texts'
        );
        
        // Add a field for custom CSS
        add_settings_field(
            'smarty_pc_custom_css',                    // ID
            'Custom CSS',                           // Title
            'smarty_pc_custom_css_callback',           // Callback function
            'smarty-pc-settings-group',                // Page
            'smarty_pc_carousel_styling'               // Section
        );
    }
    add_action('admin_init', 'smarty_pc_register_settings');
}

if (!function_exists('smarty_pc_carousel_settings_section_callback')) {
    function smarty_pc_carousel_settings_section_callback() { ?>
        <p><?php echo __('Customize the appearance and behavior of the WooCommerce products carousel.', 'smarty-product-carousel'); ?></p><?php 
    }
}

if (!function_exists('smarty_pc_carousel_styling_section_callback')) {
    function smarty_pc_carousel_styling_section_callback() { ?>
        <p><?php echo __('Customize the appearance of the products carousel with your own CSS. Add styles that will be applied directly to the carousel, giving you the flexibility to tailor its look and feel to match your site\'s design. Whether you need to adjust the padding, colors, or any other aspect, the Custom CSS field is your canvas.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_carousel_discount_section_callback')) {
    function smarty_pc_carousel_discount_section_callback() { ?>
       <p><?php echo __('Add a number to visualize the old percentage discount.', 'smarty-product-carousel'); ?></p>
       <?php
    }
}

if (!function_exists('smarty_pc_carousel_texts_section_callback')) {
    function smarty_pc_carousel_texts_section_callback() { ?>
        <p><?php echo __('Customize the texts displayed in the product carousel.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_display_arrows_callback')) {
    function smarty_pc_display_arrows_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $display_arrows = isset($options['smarty_pc_display_arrows']) ? $options['smarty_pc_display_arrows'] : '';
        ?>
        <input type="checkbox" id="smarty_pc_display_arrows" name="smarty_pc_carousel_options[smarty_pc_display_arrows]" value="1" <?php checked(1, $display_arrows, true); ?>/>
        <label for="smarty_pc_display_arrows"><?php echo esc_html__('Display arrows on the carousel.', 'smarty-product-carousel'); ?></label>
        <?php
    }
}

if (!function_exists('smarty_pc_arrow_color_callback')) {
    function smarty_pc_arrow_color_callback() {
        $options = get_option('smarty_pc_carousel_options'); ?>
        <input type="text" name="smarty_pc_carousel_options[smarty_pc_arrow_color]" value="<?php echo esc_attr($options['smarty_pc_arrow_color'] ?? ''); ?>" class="regular-text">
        <p class="description"><?php echo __('Example: #cc0000', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_display_dots_callback')) {
    function smarty_pc_display_dots_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $display_dots = isset($options['smarty_pc_display_dots']) ? $options['smarty_pc_display_dots'] : '';
        ?>
        <input type="checkbox" id="smarty_pc_display_dots" name="smarty_pc_carousel_options[smarty_pc_display_dots]" value="1" <?php checked(1, $display_dots, true); ?>/>
        <label for="smarty_pc_display_dots"><?php echo esc_html__('Display dots under the carousel.', 'smarty-product-carousel'); ?></label>
        <?php
    }
}

if (!function_exists('smarty_pc_dot_color_callback')) {
    function smarty_pc_dot_color_callback() {
        $options = get_option('smarty_pc_carousel_options'); ?>
        <input type="text" name="smarty_pc_carousel_options[smarty_pc_dot_color]" value="<?php echo esc_attr($options['smarty_pc_dot_color'] ?? ''); ?>" class="regular-text">
        <p class="description"><?php echo __('Example: #cc0000', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_slides_to_show_callback')) {
    function smarty_pc_slides_to_show_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $value = isset($options['smarty_pc_slides_to_show']) ? $options['smarty_pc_slides_to_show'] : '3'; ?>
        <input type='number' name='smarty_pc_carousel_options[smarty_pc_slides_to_show]' value='<?php echo esc_attr($value); ?>' min='1' class="small-text" />
        <p class="description"><?php echo __('Set the default slides to show.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_slides_to_scroll_callback')) {
    function smarty_pc_slides_to_scroll_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $value = isset($options['smarty_pc_slides_to_scroll']) ? $options['smarty_pc_slides_to_scroll'] : '1'; ?>
        <input type='number' name='smarty_pc_carousel_options[smarty_pc_slides_to_scroll]' value='<?php echo esc_attr($value); ?>' min='1' class="small-text" />
        <p class="description"><?php echo __('Set the default slides to scroll.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_slide_padding_callback')) {
    function smarty_pc_slide_padding_callback() {
        $options = get_option('smarty_pc_carousel_options'); ?>
        <input type="number" name="smarty_pc_carousel_options[smarty_pc_slide_padding]" value="<?php echo esc_attr($options['smarty_pc_slide_padding'] ?? '0'); ?>" class="small-text"> px<?php
    }
}

if (!function_exists('smarty_pc_speed_callback')) {
    function smarty_pc_speed_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $speed = isset($options['smarty_pc_speed']) ? $options['smarty_pc_speed'] : '300'; // Default speed
        ?>
        <input type="number" id="smarty_pc_speed" name="smarty_pc_carousel_options[smarty_pc_speed]" value="<?php echo esc_attr($speed); ?>" class="small-text" /> ms
        <p class="description"><?php echo __('Set the default scrolling speed in milliseconds.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_autoplay_indicator_callback')) {
    function smarty_pc_autoplay_indicator_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $checked = isset($options['smarty_pc_autoplay_indicator']) && $options['smarty_pc_autoplay_indicator'] ? 'checked' : ''; ?>
        <input type="checkbox" name="smarty_pc_carousel_options[smarty_pc_autoplay_indicator]" <?php echo $checked; ?>><?php
    }
}

if (!function_exists('smarty_pc_autoplay_speed_callback')) {
    function smarty_pc_autoplay_speed_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $autoplay_speed = isset($options['smarty_pc_autoplay_speed']) ? $options['smarty_pc_autoplay_speed'] : '3000'; ?>
        <input type="number" id="smarty_pc_autoplay_speed" name="smarty_pc_carousel_options[smarty_pc_autoplay_speed]" value="<?php echo esc_attr($autoplay_speed); ?>" class="small-text" /> ms
        <p class="description"><?php echo __('Set the autoplay speed in milliseconds.', 'smarty-product-carousel'); ?></p><?php
    }
}

if (!function_exists('smarty_pc_infinite_callback')) {
    function smarty_pc_infinite_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $checked = isset($options['smarty_pc_infinite']) && $options['smarty_pc_infinite'] ? 'checked' : ''; ?>
        <input type="checkbox" name="smarty_pc_carousel_options[smarty_pc_infinite]" <?php echo $checked; ?>><?php
    }
}

if (!function_exists('smarty_pc_discount_callback')) {
    function smarty_pc_discount_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $value = isset($options['smarty_pc_discount']) ? $options['smarty_pc_discount'] : '10'; ?>
        <input type='number' name='smarty_pc_carousel_options[smarty_pc_discount]' value='<?php echo esc_attr($value); ?>' min='0' class="small-text" />
        <p class="description"><?php echo __('Set the old products discount.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_custom_title_callback')) {
    function smarty_pc_custom_title_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $title = $options['smarty_pc_custom_title'] ?? ''; // Default value is empty
        ?>
        <input type="text" id="smarty_pc_custom_title" name="smarty_pc_carousel_options[smarty_pc_custom_title]" value="<?php echo esc_attr($title); ?>" class="regular-text">
        <p class="description"><?php echo __('Enter a custom title for your product carousel.', 'smarty-product-carousel'); ?></p>
        <?php
    }
}

if (!function_exists('smarty_pc_save_text_callback')) {
    function smarty_pc_save_text_callback() {
        $options = get_option('smarty_pc_carousel_options'); ?>
        <input type="text" name="smarty_pc_carousel_options[smarty_pc_save_text]" value="<?php echo esc_attr($options['smarty_pc_save_text'] ?? 'Save') ; ?>" />
        <?php
    }
}

if (!function_exists('smarty_pc_add_to_cart_text_callback')) {
    function smarty_pc_add_to_cart_text_callback() {
        $options = get_option('smarty_pc_carousel_options'); ?>
        <input type="text" name="smarty_pc_carousel_options[smarty_pc_add_to_cart_text]" value="<?php echo esc_attr($options['smarty_pc_add_to_cart_text'] ?? 'Add To Cart'); ?>" />
        <?php
    }
}

if (!function_exists('smarty_pc_label_text_callback')) {
    function smarty_pc_label_text_callback() {
        $options = get_option('smarty_pc_carousel_options'); ?>
        <input type="text" name="smarty_pc_carousel_options[smarty_pc_label_text]" value="<?php echo esc_attr($options['smarty_pc_label_text'] ?? 'Exclusive'); ?>" />
        <?php
    }
}

if (!function_exists('smarty_pc_custom_css_callback')) {
    function smarty_pc_custom_css_callback() {
        $options = get_option('smarty_pc_carousel_options');
        $custom_css = isset($options['custom_css']) ? $options['custom_css'] : ''; ?>
        <textarea id="smarty_pc_custom_css" name="smarty_pc_carousel_options[custom_css]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
        <p class="description"><?php echo __('Add custom CSS for the carousel here.', 'smarty-product-carousel'); ?></p><?php
    }
}

if (!function_exists('smarty_pc_print_custom_css')) {
    function smarty_pc_print_custom_css() {
        $options = get_option('smarty_pc_carousel_options');

        echo "<style type=\"text/css\">\n";
        // Base styles for arrows and dots with dynamic colors
        echo ".slick-prev:before, .slick-next:before { color: " . esc_attr($options['smarty_pc_arrow_color'] ?? '') . " !important; }\n";
        echo ".slick-dots li button:before { color: " . esc_attr($options['smarty_pc_dot_color'] ?? '') . " !important; }\n";
        // Check if dots should be displayed
        if (!empty($options['smarty_pc_display_dots'])) {
            echo ".slick-dots { display: block !important; }\n";
        }
        // Custom CSS for slide padding
        if (!empty($options['smarty_pc_slide_padding'])) {
            echo ".smarty-pc-carousel .product { padding: " . esc_attr($options['smarty_pc_slide_padding']) . "px; }\n";
        }

        // Additional custom CSS styles specific to the carousel
        ?>

        #smarty-pc-woo-carousel.smarty-pc-carousel .product { 
			padding: <?php echo intval($options['smarty_pc_slide_padding'] ?? '0'); ?>px; 
		}

        <?php
        // Echo additional saved custom CSS if set
        if (!empty($options['custom_css'])) {
            echo esc_attr($options['custom_css']) . "\n";
        }
        echo "</style>\n";
    }
    add_action('wp_head', 'smarty_pc_print_custom_css');
}

if (!function_exists('smarty_pc_options_sanitize')) {
    function smarty_pc_options_sanitize($input) {
        $input['smarty_pc_display_arrows'] = isset($input['smarty_pc_display_arrows']) ? 1 : 0;
        $input['smarty_pc_arrow_color'] = sanitize_hex_color($input['smarty_pc_arrow_color']);
        $input['smarty_pc_display_dots'] = isset($input['smarty_pc_display_dots']) ? 1 : 0;
        $input['smarty_pc_dot_color'] = sanitize_hex_color($input['smarty_pc_dot_color']);
        $input['smarty_pc_slide_padding'] = intval($input['smarty_pc_slide_padding']);
        $input['smarty_pc_speed'] = isset($input['smarty_pc_speed']) ? intval($input['smarty_pc_speed']) : 300;
        $input['smarty_pc_autoplay_indicator'] = !empty($input['smarty_pc_autoplay_indicator']) ? true : false;
        $input['smarty_pc_autoplay_speed'] = intval($input['smarty_pc_autoplay_speed']);
        $input['smarty_pc_infinite'] = !empty($input['smarty_pc_infinite']) ? true : false;
        $input['smarty_pc_custom_title'] = sanitize_text_field($input['smarty_pc_custom_title'] ?? '');
        $input['smarty_pc_save_text'] = sanitize_text_field($input['smarty_pc_save_text'] ?? 'Save');
        $input['smarty_pc_add_to_cart_text'] = sanitize_text_field($input['smarty_pc_add_to_cart_text'] ?? 'Add To Cart');
        $input['smarty_pc_label_text'] = sanitize_text_field($input['smarty_pc_label_text'] ?? 'Exclusive');

        return $input;
    }
}

if (!function_exists('smarty_pc_save_settings')) {
    function smarty_pc_save_settings() {
        // Check if our nonce is set.
        if (!isset($_POST['smarty_pc_settings_nonce'])) {
            wp_die('Nonce value cannot be verified.');
        }

        // Verify the nonce.
        if (!wp_verify_nonce($_POST['smarty_pc_settings_nonce'], 'smarty_pc_save_settings_action')) {
            wp_die('Nonce verification failed', 'Invalid Request', array('response' => 403));
        }

        // Ensure we're getting the correct options array from the form
        $options = isset($_POST['smarty_pc_carousel_options']) ? $_POST['smarty_pc_carousel_options'] : [];

        // Sanitize and save each option manually
        $safe_options = [];
        $safe_options['smarty_pc_arrow_color'] = isset($options['smarty_pc_arrow_color']) ? sanitize_hex_color($options['smarty_pc_arrow_color']) : '';
        $safe_options['smarty_pc_dot_color'] = isset($options['smarty_pc_dot_color']) ? sanitize_hex_color($options['smarty_pc_dot_color']) : '';
        $safe_options['smarty_pc_slide_padding'] = isset($options['smarty_pc_slide_padding']) ? intval($options['smarty_pc_slide_padding']) : 0;
        $safe_options['smarty_pc_slides_to_show'] = isset($options['smarty_pc_slides_to_show']) ? intval($options['smarty_pc_slides_to_show']) : '3';
        $safe_options['smarty_pc_slides_to_scroll'] =  isset($options['smarty_pc_slides_to_scroll']) ? intval($options['smarty_pc_slides_to_scroll']) : '1';
        $safe_options['smarty_pc_speed'] = isset($options['smarty_pc_speed']) ? intval($options['smarty_pc_speed']) : 0;
        $safe_options['smarty_pc_autoplay_indicator'] = isset($options['smarty_pc_autoplay_indicator']) ? filter_var($options['smarty_pc_autoplay_indicator'], FILTER_VALIDATE_BOOLEAN) : false;
        $safe_options['smarty_pc_autoplay_speed'] = isset($options['smarty_pc_autoplay_speed']) ? intval($options['smarty_pc_autoplay_speed']) : 0;
        $safe_options['smarty_pc_infinite'] = isset($options['smarty_pc_infinite']) ? filter_var($options['smarty_pc_infinite'], FILTER_VALIDATE_BOOLEAN) : false;
        $safe_options['smarty_pc_discount'] = isset($options['smarty_pc_discount']) ? intval($options['smarty_pc_discount']) : '10';
        $safe_options['custom_css'] = isset($options['custom_css']) ? wp_strip_all_tags($options['custom_css']) : '';
        $safe_options['products'] = isset($options['products']) ? array_map('sanitize_text_field', $options['products']) : [];

        // Update the entire options array
        update_option('smarty_pc_carousel_options', $safe_options);

        // Redirect back to settings page
        wp_redirect(add_query_arg('page', 'smarty-pc-admin-page', admin_url('admin.php')));
        exit;
    }
    add_action('admin_post_smarty_pc_save_settings', 'smarty_pc_save_settings');
}

if (!function_exists('smarty_pc_search_products')) {
    function smarty_pc_search_products() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $term = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        $query_args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            's'              => $term,
            'posts_per_page' => -1,
        );

        $query = new WP_Query($query_args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id'    => get_the_ID(),
                    'text'  => get_the_title() . ' (ID: ' . get_the_ID() . ')',
                );
            }
        }

        wp_send_json($results);
    }
    add_action('wp_ajax_smarty_pc_search_products', 'smarty_pc_search_products');
}

if (!function_exists('smarty_pc_product_carousel_shortcode')) {
    function smarty_pc_product_carousel_shortcode($atts) {
        global $wpdb; 
        $options = get_option('smarty_pc_carousel_options');
        $custom_title = $options['smarty_pc_custom_title'] ?? '';
        $plugin_slides_to_show = isset($options['smarty_pc_slides_to_show']) && is_numeric($options['smarty_pc_slides_to_show']) ? intval($options['smarty_pc_slides_to_show']) : 3;
        
        $attributes = shortcode_atts(
            array(
                'slides_to_show' => $plugin_slides_to_show, // Use the plugin setting as the default value
            ), 
            $atts, 
            'smarty_pc_product_carousel'
        );

        // IDs from settings
        $saved_ids = isset($options['products']) ? $options['products'] : [];
        
        $display_arrows = isset($options['smarty_pc_display_arrows']) && $options['smarty_pc_display_arrows'] ? 'true' : 'false';
        $saved_arrow_color = isset($options['smarty_pc_arrow_color']) ? $options['smarty_pc_arrow_color'] : '';
        
        $display_dots = isset($options['smarty_pc_display_dots']) && $options['smarty_pc_display_dots'] ? 'true' : 'false';
        $saved_dot_color = isset($options['smarty_pc_dot_color']) ? $options['smarty_pc_dot_color'] : '';
        $saved_slide_padding = isset($options['smarty_pc_slide_padding']) ? $options['smarty_pc_slide_padding'] : '';
        
        $slides_to_show = $attributes['slides_to_show'];
        $slides_to_scroll = isset($options['smarty_pc_slides_to_scroll']) ? $options['smarty_pc_slides_to_scroll'] : '1';
       
        $speed = isset($options['smarty_pc_speed']) ? $options['smarty_pc_speed'] : '300';
        $autoplay = isset($options['smarty_pc_autoplay_indicator']) && $options['smarty_pc_autoplay_indicator'] ? 'true' : 'false';
        $autoplay_speed = isset($options['smarty_pc_autoplay_speed']) ? $options['smarty_pc_autoplay_speed'] : '3000';
        
        $infinite = isset($options['smarty_pc_infinite']) && $options['smarty_pc_infinite'] ? 'true' : 'false';
        
        $save_text = $options['smarty_pc_save_text'] ?? 'Save';
        $add_to_cart_text = $options['smarty_pc_add_to_cart_text'] ?? 'Add To Cart';
        $label_text = $options['smarty_pc_label_text'] ?? 'Exclusive';

        // Get product names from cart
        $cart_product_names = smarty_pc_get_cart_product_names();

        // Retrieve IDs of products set in the carousel settings
        $carousel_ids = $options['products'] ?? [];

        // Build a list of IDs to exclude based on cart item names
        $excluded_ids = [];
        foreach ($carousel_ids as $id) {
            $product = wc_get_product($id);
            if ($product) {
                foreach ($cart_product_names as $cart_name) {
                    if (stripos($product->get_name(), $cart_name) !== false || stripos($cart_name, $product->get_name()) !== false) {
                        $excluded_ids[] = $id;
                        break;
                    }
                }
            }
        }

        $included_ids = array_diff($carousel_ids, $excluded_ids);

        // Prepare query arguments excluding cart items
        $query_args = array(
            'limit'          => -1,
            'post_type'      => 'product',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'status'         => 'publish',
            'include'        => $included_ids,
        );

        // Query products
        $query = new WC_Product_Query($query_args);
        $products = $query->get_products();

        // Start building carousel HTML
        $carousel_html = '';  // Start with an empty string.
        
        // Add custom title if it exists
        if (!empty($custom_title)) {
            $carousel_html .= '<h5 class="smarty-pc-carousel-title">' . esc_html($custom_title) . '</h5>';
        }

        // Start the carousel div after adding the title
        $carousel_html .= '<div id="smarty-pc-woo-carousel" class="smarty-pc-carousel">';

        // Flag to identify the first product
        $is_first_product = true;

        foreach ($products as $product) {
            $carousel_html .= '<div class="product">';
        
            $max_discount = 0;
            $max_amount_saved = 0;
            $old_discount = isset($options['smarty_pc_discount']) ? $options['smarty_pc_discount'] : '10';

            // Label for the first product
            if ($is_first_product) {
                if (!empty($label_text)) {
                    $carousel_html .= "<div class='text-label'>{$label_text}</div>";
                }
                $is_first_product = false; // Reset flag so it's only applied to the first product
            }

            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();

                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);
                    if ($variation_obj->is_on_sale()) {
                        $regular_price = floatval($variation_obj->get_regular_price());
                        $sale_price = floatval($variation_obj->get_sale_price());
                        if ($regular_price > 0) { // Ensure there's a valid regular price
                            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
                            $amount_saved = $regular_price - $sale_price;
                            if ($discount_percentage > $max_discount) {
                                $max_discount = $discount_percentage;
                                $max_amount_saved = $amount_saved;
                            }
                        }
                    }
                }
            } else if ($product->is_on_sale()) {
            
                $regular_price = floatval($product->get_regular_price());
                $sale_price = floatval($product->get_sale_price());

                if ($regular_price > 0) { // Ensure there's a valid regular price
                    $max_discount = round((($regular_price - $sale_price) / $regular_price) * 100);
                    $max_amount_saved = $regular_price - $sale_price;
                }
            }

            // Now, adjust the logic for displaying the discount label
            // Check if there is a discount and if old discount is not disabled (not set to 0)
            if ($max_discount > 0) {
                if ($old_discount != 0) {
                    // Show the discount including the old discount adjustment
                    $carousel_html .= '<div class="discount-label"><s>-' . $max_discount - $old_discount . '%</s> -' . $max_discount . '%</div>';
                } else {
                    // Old discount is disabled, show only the actual discount
                    $carousel_html .= '<div class="discount-label">-' . $max_discount . '%</div>';
                }
            }
        
            $carousel_html .= '<img src="' . wp_get_attachment_url($product->get_image_id()) . '" alt="' . $product->get_name() . '" title="' . $product->get_name() . '">';
            $carousel_html .= '<h2>' . $product->get_name() . '</h2>';
            $carousel_html .= '<span class="price"><small>' . $product->get_price_html() . '</small></span>';
            
            if ($max_discount > 0) {
                $saved_formatted = wc_price($max_amount_saved);
                $carousel_html .= "<p class='save-info'>{$save_text} {$max_discount}% ($saved_formatted)</p>";
            }

            // Add to Cart button
            if ($product->is_type('simple')) {
                // Simple product: Directly add the product to the cart
                //$add_to_cart_url = '?add-to-cart=' . $product->get_id();
                $add_to_cart_url = '?add-to-cart=' . $product->get_id() . '&source=upsell';
                $carousel_html .= '<a href="' . esc_url(home_url($add_to_cart_url)) . '" id="smartyCarousel" class="button add_to_cart_button ajax_add_to_cart" data-product_id="' . $product->get_id() . '">' . $add_to_cart_text . '</a>';
            } elseif ($product->is_type('variable')) {
                // Variable product: Add the first available variation to the cart
                $available_variations = $product->get_available_variations();
                $first_variation_id = $available_variations[0]['variation_id'] ?? 0;
                if ($first_variation_id > 0) {
                    //$add_to_cart_url = '?add-to-cart=' . $product->get_id() . '&variation_id=' . $first_variation_id;
                    $add_to_cart_url = '?add-to-cart=' . $product->get_id() . '&variation_id=' . $first_variation_id . '&source=upsell';
                    // Automatically selecting the first variation attributes might be required, you can append them to the URL if needed
                    foreach ($available_variations[0]['attributes'] as $attr_key => $attr_value) {
                        $add_to_cart_url .= '&' . $attr_key . '=' . $attr_value;
                    }
                    $carousel_html .= '<a href="' . esc_url(home_url($add_to_cart_url)) . '" id="smartyCarousel" class="button add_to_cart_button ajax_add_to_cart" data-product_id="' . $product->get_id() . '" data-variation_id="' . $first_variation_id . '">' . $add_to_cart_text . '</a>';
                } else {
                    // Fallback link to the product page if no variations are available
                    $product_url = get_permalink($product->get_id());
                    //$carousel_html .= '<a href="' . esc_url($product_url) . '" class="button">Select Options</a>';
                    $carousel_html .= '<a href="' . esc_url($product_url) . '?source=upsell' . '" id="smartyCarousel" class="button">Select Options</a>';
                }
            }
            
            $carousel_html .= '</div>';
        }
       
        $carousel_html .= '</div>';

        $carousel_html .= "<script>
            jQuery(document).ready(function($) {
                $('.smarty-pc-carousel').slick({
                    speed: " . intval($speed) . ",
                    autoplay: {$autoplay},
                    autoplaySpeed: " . intval($autoplay_speed) . ",
                    slidesToShow: " . $slides_to_show . ",
                    slidesToScroll: " . intval($options['smarty_pc_slides_to_scroll']) . ",
                    infinite: {$infinite},
                    adaptiveHeight: 'false',
                    arrows: {$display_arrows},
                    dots: {$display_dots},
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3,
                                infinite: true,
                                dots: true
                            }
                        },
                        {
                            breakpoint: 600,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                        // You can add more breakpoints as needed
                    ]
                });
            });

            jQuery(document.body).on('added_to_cart', function() {
				if (window.location.href.indexOf('checkout') !== -1) {
					window.location.reload();
				} else {
					jQuery(document.body).trigger('wc_fragment_refresh');
				}
			});                      
        </script>";

        return $carousel_html;
    }
    add_shortcode('smarty_pc_product_carousel', 'smarty_pc_product_carousel_shortcode');
}

if (!function_exists('smarty_pc_get_cart_product_names')) {
    function smarty_pc_get_cart_product_names() {
        $names = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $names[] = $product->get_name();
        }
        return $names;
    }
}

if (!function_exists('smarty_pc_check_upsell_products_in_cart')) {
    /**
     * Function to check the upsell products in cart
     * TODO: Select the category for product upsells trough select field in plugin settings page
     */
    function smarty_pc_check_upsell_products_in_cart() {
        if (is_admin() && !defined('DOING_AJAX')) return;

        $cart = WC()->cart->get_cart();
        $regular_product_found = false;
        $removed_items = false;  // Flag to track if any upsell items were removed

        // Check each cart item to determine if there are any non-upsell products
        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            // Check if the product is not in the 'upsell' category
            if (!has_term('upsell', 'product_cat', $product->get_id())) {
                $regular_product_found = true;
                break;
            }
        }

        // If no non-upsell (regular) products are found, remove upsell products
        if (!$regular_product_found) {
            foreach ($cart as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                // Again, check if the product is in the 'upsell' category
                if (has_term('upsell', 'product_cat', $product->get_id())) {
                    WC()->cart->remove_cart_item($cart_item_key);
                    $removed_items = true;  // Set flag to true as we're removing an item
                }
            }

            // If any upsell items were removed, add a notice to the cart
            if ($removed_items) {
				wc_clear_notices();
                wc_add_notice(__('You cannot have only upsell products in the cart.', 'smarty-product-carousel'), 'error');
            }
        }
    }
    add_action('woocommerce_before_cart', 'smarty_pc_check_upsell_products_in_cart');
    add_action('woocommerce_cart_item_removed', 'smarty_pc_check_upsell_products_in_cart');
    add_action('woocommerce_cart_updated', 'smarty_pc_check_upsell_products_in_cart');
}