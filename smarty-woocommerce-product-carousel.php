<?php
/**
 * Plugin Name:          SM - WooCommerce Product Carousel
 * Plugin URI:           https://github.com/mnestorov/smarty-woocommerce-product-carousel
 * Description:          A custom WooCommerce product carousel plugin.
 * Version:              1.0.3
 * Author:               Martin Nestorov
 * Author URI:           https://github.com/mnestorov
 * License:              GPL-2.0+
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:          smarty-product-carousel
 * WC requires at least: 3.5.0
 * WC tested up to:      9.0.2
 * Requires Plugins:     woocommerce
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
        wp_enqueue_script('smarty-pc-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-pc-admin.js', array('jquery', 'select2'), '1.0.1', true);
        wp_enqueue_style('smarty-pc-admin-css', plugin_dir_url(__FILE__) . 'css/smarty-pc-admin.css', array(), '1.0.1');
        wp_localize_script(
            'smarty-pc-admin-js',
            'smartyProductCarousel',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'siteUrl' => site_url(),
                'nonce'   => wp_create_nonce('smarty_product_carousel_nonce'),
            )
        );
    }
    add_action('admin_enqueue_scripts', 'smarty_pc_enqueue_admin_scripts');
}

if (!function_exists('smarty_pc_enqueue_checkout_scripts')) {
    function smarty_pc_enqueue_checkout_scripts() {
        if (is_checkout()) {
            wp_enqueue_script('wc-checkout', WC()->plugin_url() . '/assets/js/frontend/checkout.min.js', array('jquery', 'woocommerce'), WC_VERSION, true);
            wp_enqueue_script('wc-add-to-cart', WC()->plugin_url() . '/assets/js/frontend/add-to-cart.min.js', array('jquery'), WC_VERSION, true);
            // Localize AJAX URL for WooCommerce scripts
            wp_localize_script('wc-add-to-cart', 'wc_add_to_cart_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
            ));
        }
    }
    add_action('wp_enqueue_scripts', 'smarty_pc_enqueue_checkout_scripts');
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
            <div id="smarty-pc-settings-container">
                <div>
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
                <div id="smarty-pc-tabs-container">
                    <div>
                        <h2 class="smarty-pc-nav-tab-wrapper">
                            <a href="#smarty-pc-documentation" class="smarty-pc-nav-tab smarty-pc-nav-tab-active"><?php esc_html_e('Documentation', 'smarty-product-carousel'); ?></a>
                            <a href="#smarty-pc-changelog" class="smarty-pc-nav-tab"><?php esc_html_e('Changelog', 'smarty-product-carousel'); ?></a>
                        </h2>
                        <div id="smarty-pc-documentation" class="smarty-pc-tab-content active">
                            <div class="smarty-pc-view-more-container">
                                <p><?php esc_html_e('Click "View More" to load the plugin documentation.', 'smarty-product-carousel'); ?></p>
                                <button id="smarty-pc-load-readme-btn" class="button button-primary">
                                    <?php esc_html_e('View More', 'smarty-product-carousel'); ?>
                                </button>
                            </div>
                            <div id="smarty-pc-readme-content" style="margin-top: 20px;"></div>
                        </div>
                        <div id="smarty-pc-changelog" class="smarty-pc-tab-content">
                            <div class="smarty-pc-view-more-container">
                                <p><?php esc_html_e('Click "View More" to load the plugin changelog.', 'smarty-product-carousel'); ?></p>
                                <button id="smarty-pc-load-changelog-btn" class="button button-primary">
                                    <?php esc_html_e('View More', 'smarty-product-carousel'); ?>
                                </button>
                            </div>
                            <div id="smarty-pc-changelog-content" style="margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>
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
            'smarty_pc_display_arrows',                 // ID
            'Display Arrows',                           // Title
            'smarty_pc_display_arrows_callback',        // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_arrow_color',                    // ID
            'Arrow Color',                              // Title
            'smarty_pc_arrow_color_callback',           // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        // Add a field for Display Dots
        add_settings_field(
            'smarty_pc_display_dots',                   // ID
            'Display Dots',                             // Title
            'smarty_pc_display_dots_callback',          // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );
        
        add_settings_field(
            'smarty_pc_dot_color',                      // ID
            'Dot Color',                                // Title
            'smarty_pc_dot_color_callback',             // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_slides_to_show',                 // ID
            'Slides to Show',                           // Title
            'smarty_pc_slides_to_show_callback',        // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_slides_to_scroll',               // ID
            'Slides to Scroll',                         // Title
            'smarty_pc_slides_to_scroll_callback',      // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );
        
        add_settings_field(
            'smarty_pc_slide_padding',                  // ID
            'Slide Padding',                            // Title
            'smarty_pc_slide_padding_callback',         // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_speed',                          // ID
            'Scrolling Speed',                          // Title
            'smarty_pc_speed_callback',                 // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );        
        
        add_settings_field(
            'smarty_pc_autoplay_indicator',             // ID
            'Autoplay Indicator',                       // Title
            'smarty_pc_autoplay_indicator_callback',    // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_autoplay_speed',                 // ID
            'Autoplay Speed',                           // Title
            'smarty_pc_autoplay_speed_callback',        // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_infinite',                       // ID
            'Infinite',                                 // Title
            'smarty_pc_infinite_callback',              // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_settings'               // Section
        );

        add_settings_field(
            'smarty_pc_discount',                       // ID
            'Old Discount',                             // Title
            'smarty_pc_discount_callback',              // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_discount'               // Section
        );

        // Add a new field for custom title
        add_settings_field(
            'smarty_pc_custom_title',                   // ID
            'Custom Title',                             // Title
            'smarty_pc_custom_title_callback',          // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_texts'                  // Section
        );

        // Save text
        add_settings_field(
            'smarty_pc_save_text',                      // ID
            'Save Text',                                // Title
            'smarty_pc_save_text_callback',             // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_texts'                  // Section
        );

        // Add to Cart text
        add_settings_field(
            'smarty_pc_add_to_cart_text',               // ID
            'Add To Cart Text',                         // Title
            'smarty_pc_add_to_cart_text_callback',      // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_texts'                  // Section
        );

        // Label text
        add_settings_field(
            'smarty_pc_label_text',                     // ID
            'Label Text',                               // Title
            'smarty_pc_label_text_callback',            // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_texts'                  // Section
        );
        
        // Add a field for custom CSS
        add_settings_field(
            'smarty_pc_custom_css',                     // ID
            'Custom CSS',                               // Title
            'smarty_pc_custom_css_callback',            // Callback function
            'smarty-pc-settings-group',                 // Page
            'smarty_pc_carousel_styling'                // Section
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
        echo ".slick-prev:before, .slick-next:before { color: " . esc_attr($options['smarty_pc_arrow_color'] ?? '') . " !important; }\n";
        echo ".slick-dots li button:before { color: " . esc_attr($options['smarty_pc_dot_color'] ?? '') . " !important; }\n";
        if (!empty($options['smarty_pc_display_dots'])) {
            echo ".slick-dots { display: block !important; }\n";
        }
        if (!empty($options['smarty_pc_slide_padding'])) {
            echo ".smarty-pc-carousel .product { padding: " . esc_attr($options['smarty_pc_slide_padding']) . "px; }\n";
        }
        echo "
            #smarty-pc-woo-carousel.smarty-pc-carousel .product { 
                padding: " . intval($options['smarty_pc_slide_padding'] ?? '0') . "px; 
            }
            #smarty-pc-woo-carousel.smarty-pc-carousel .ajax_add_to_cart.added,
            #smarty-pc-woo-carousel.smarty-pc-carousel .ajax_add_to_cart[disabled],
            #smarty-pc-woo-carousel.smarty-pc-carousel .ajax_add_to_cart[aria-disabled='true'] {
                opacity: 0.6 !important;
                cursor: not-allowed !important;
                pointer-events: none !important; /* Prevent clicks entirely */
            }
            #smarty-pc-woo-carousel.smarty-pc-carousel .ajax_add_to_cart.added:hover,
            #smarty-pc-woo-carousel.smarty-pc-carousel .ajax_add_to_cart[disabled]:hover {
                background: #0b100d !important;
                opacity: 0.6 !important;
            }
        ";
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

if (!function_exists('smarty_pc_add_source_to_order_item_meta')) {
    /**
     * Add the source to order item meta data.
     */
    function smarty_pc_add_source_to_order_item_meta($item, $cart_item_key, $values, $order) {
        $product_id = $values['data']->get_id();
        if ($product_id) {
            $source = WC()->session->get('_source_' . $product_id);
            if ($source) {
                $item->add_meta_data('_source', $source, true);
                WC()->session->__unset('_source_' . $product_id); // Clear the session data
            }
        }
    }
    add_action('woocommerce_checkout_create_order_line_item', 'smarty_pc_add_source_to_order_item_meta', 10, 4);
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

        // Ensure WooCommerce is active and the cart is available
        if (!function_exists('WC') || !WC()->cart || !did_action('woocommerce_init')) {
            return ''; // Avoid executing if WooCommerce isn't fully loaded
        }

        // Prevent execution in the admin area unless it's an AJAX request
        if (is_admin() && !wp_doing_ajax()) {
            return '';
        }

        // Ensure WooCommerce session is active before accessing the cart
        // Skip cart empty check on Thank You page
        if (WC()->cart->is_empty() && !is_wc_endpoint_url('order-received')) {
            return ''; // Avoid errors if the cart is empty (except on Thank You page)
        }

        $options = get_option('smarty_pc_carousel_options');
        $custom_title = $options['smarty_pc_custom_title'] ?? '';
        $plugin_slides_to_show = isset($options['smarty_pc_slides_to_show']) && is_numeric($options['smarty_pc_slides_to_show']) 
            ? intval($options['smarty_pc_slides_to_show']) 
            : 3;
        
        // Shortcode Attributes
        $attributes = shortcode_atts(
            array(
                'slides_to_show' => $plugin_slides_to_show,
                'source'         => 'checkout_page',
                'order_id'       => 0,
            ), 
            $atts, 
            'smarty_pc_product_carousel'
        );

        // Basic variables
        $source     = $attributes['source'];
        $order_id   = intval($attributes['order_id']);
        $order_product_ids = array();

        // Handle different sources
        if ($source === 'thankyou_page' && $order_id > 0) {
            $order = wc_get_order($order_id);
            if ($order) {
                foreach ($order->get_items() as $item) {
                    $order_product_ids[] = $item->get_product_id();
                }
            }
        } elseif ($source === 'checkout_page' || $source === 'mini_cart') {
            if (!WC()->cart || !is_object(WC()->cart)) {
                return ''; // Prevent errors when the cart isn't ready
            }
            foreach (WC()->cart->get_cart() as $cart_item) {
                $order_product_ids[] = $cart_item['product_id'];
            }
        }

        // IDs from plugin settings
        $saved_ids = isset($options['products']) ? $options['products'] : [];

        // Grab carousel style/behavior settings
        $display_arrows       = isset($options['smarty_pc_display_arrows']) && $options['smarty_pc_display_arrows'] ? 'true' : 'false';
        $display_dots         = isset($options['smarty_pc_display_dots']) && $options['smarty_pc_display_dots'] ? 'true' : 'false';
        $slides_to_show       = $attributes['slides_to_show'];
        $slides_to_scroll     = isset($options['smarty_pc_slides_to_scroll']) ? $options['smarty_pc_slides_to_scroll'] : '1';
        $speed                = isset($options['smarty_pc_speed']) ? $options['smarty_pc_speed'] : '300';
        $autoplay             = isset($options['smarty_pc_autoplay_indicator']) && $options['smarty_pc_autoplay_indicator'] ? 'true' : 'false';
        $autoplay_speed       = isset($options['smarty_pc_autoplay_speed']) ? $options['smarty_pc_autoplay_speed'] : '3000';
        $infinite             = isset($options['smarty_pc_infinite']) && $options['smarty_pc_infinite'] ? 'true' : 'false';

        $save_text            = $options['smarty_pc_save_text'] ?? 'Save';
        $add_to_cart_text     = $options['smarty_pc_add_to_cart_text'] ?? 'Add To Cart';
        $label_text           = $options['smarty_pc_label_text'] ?? 'Exclusive';
        $old_discount         = isset($options['smarty_pc_discount']) ? $options['smarty_pc_discount'] : '10';

        // Carousel IDs are all saved IDs from settings
        $carousel_ids = $saved_ids;

        // Query the products
        $query_args = array(
            'limit'     => -1,
            'post_type' => 'product',
            'orderby'   => 'menu_order',
            'order'     => 'ASC',
            'status'    => 'publish',
            'include'   => $carousel_ids,
        );

        $query = new WC_Product_Query($query_args);
        $products = $query->get_products();

        // Get cart/order product IDs to disable buttons
        $cart_product_ids = ($source === 'thankyou_page') 
            ? $order_product_ids 
            : array_map(function($cart_item) { return $cart_item['product_id']; }, WC()->cart->get_cart());

        // Build the carousel HTML
        $carousel_html = '';

        if (!empty($custom_title)) {
            $carousel_html .= '<h5 class="smarty-pc-carousel-title">' . esc_html($custom_title) . '</h5>';
        }

        $carousel_html .= '<div id="smarty-pc-woo-carousel" class="smarty-pc-carousel">';

        $is_first_product = true;

        foreach ($products as $product) {
            $carousel_html .= '<div class="product">';

            $max_discount = 0;
            $max_amount_saved = 0;

            if ($is_first_product && !empty($label_text)) {
                $carousel_html .= '<div class="text-label">' . esc_html($label_text) . '</div>';
                $is_first_product = false;
            }

            // Calculate discount if on sale
            $regular_price = 0;
            $sale_price    = 0;
            $is_on_sale    = false;

            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
                $variation_prices = [];
                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);
                    $variation_regular_price = floatval($variation_obj->get_regular_price());
                    $variation_sale_price    = floatval($variation_obj->get_sale_price());
                    if ($variation_obj->is_on_sale() && $variation_regular_price > 0 && $variation_sale_price > 0) {
                        $variation_prices[] = [
                            'regular'  => $variation_regular_price,
                            'sale'     => $variation_sale_price,
                            'discount' => round((($variation_regular_price - $variation_sale_price) / $variation_regular_price) * 100)
                        ];
                    }
                }
                if (!empty($variation_prices)) {
                    usort($variation_prices, function($a, $b) { return $b['discount'] <=> $a['discount']; });
                    $regular_price    = $variation_prices[0]['regular'];
                    $sale_price       = $variation_prices[0]['sale'];
                    $max_discount     = $variation_prices[0]['discount'];
                    $max_amount_saved = $regular_price - $sale_price;
                    $is_on_sale       = true;
                }
            } elseif ($product->is_on_sale()) {
                $regular_price = floatval($product->get_regular_price());
                $sale_price    = floatval($product->get_sale_price());
                if ($regular_price > 0 && $sale_price > 0) {
                    $max_discount     = round((($regular_price - $sale_price) / $regular_price) * 100);
                    $max_amount_saved = $regular_price - $sale_price;
                    $is_on_sale       = true;
                }
            }

            if ($max_discount > 0) {
                if ($old_discount != 0) {
                    $carousel_html .= '<div class="discount-label"><s>-' . ($max_discount - $old_discount) . '%</s> -' . $max_discount . '%</div>';
                } else {
                    $carousel_html .= '<div class="discount-label">-' . $max_discount . '%</div>';
                }
            }

            $carousel_html .= '<img src="' . esc_url(wp_get_attachment_url($product->get_image_id())) . '" alt="' . esc_attr($product->get_name()) . '" title="' . esc_attr($product->get_name()) . '">';
            $carousel_html .= '<h2>' . esc_html($product->get_name()) . '</h2>';

            if ($is_on_sale && $regular_price && $sale_price) {
                $regular_price_html = wc_price($regular_price);
                $sale_price_html    = wc_price($sale_price);
                $price_html         = '<span class="price"><small><del aria-hidden="true">' . $regular_price_html . '</del> <ins aria-hidden="true">' . $sale_price_html . '</ins></small></span>';
            } else {
                $price_html = '<span class="price"><small>' . $product->get_price_html() . '</small></span>';
            }

            $carousel_html .= wp_kses_post($price_html);

            if ($max_discount > 0) {
                $saved_formatted = wc_price($max_amount_saved);
                $carousel_html .= "<p class='save-info'>" . esc_html($save_text) . " {$max_discount}% (" . wp_kses_post($saved_formatted) . ")</p>";
            }

            // Check if product is already in cart/order
            $in_cart = in_array($product->get_id(), $cart_product_ids, true);

            if ($in_cart) {
                $button_text = ($source === 'thankyou_page') ? __('In Order', 'smarty-product-carousel') : __('In Cart', 'smarty-product-carousel');
                $carousel_html .= '<button class="button add_to_cart_button disabled" disabled="disabled">'
                    . esc_html($button_text)
                    . '</button>';
            } else {
                if ($product->is_type('simple')) {
                    $carousel_html .= '<button class="button add_to_cart_button ajax_add_to_cart" '
                        . 'data-product_id="' . esc_attr($product->get_id()) . '" '
                        . 'data-source="' . esc_attr($source) . '">'
                        . esc_html($add_to_cart_text) 
                        . '</button>';
                } elseif ($product->is_type('variable')) {
                    $available_variations = $product->get_available_variations();
                    $first_variation_id = $available_variations[0]['variation_id'] ?? 0;
                    if ($first_variation_id > 0) {
                        $carousel_html .= '<button class="button add_to_cart_button ajax_add_to_cart" '
                            . 'data-product_id="' . esc_attr($product->get_id()) . '" '
                            . 'data-source="' . esc_attr($source) . '" '
                            . 'data-variation_id="' . esc_attr($first_variation_id) . '">'
                            . esc_html($add_to_cart_text) 
                            . '</button>';
                    } else {
                        $product_url = get_permalink($product->get_id());
                        $carousel_html .= '<a href="' . esc_url($product_url) . '?source=upsell' . '" '
                            . 'class="button">'
                            . esc_html__('Select Options', 'smarty-product-carousel')
                            . '</a>';
                    }
                }
            }

            $carousel_html .= '</div>'; // End .product
        }

        $carousel_html .= '</div>'; // End #smarty-pc-woo-carousel

        // Initialize Slick carousel and custom AJAX handling
        $carousel_html .= "<script>
            jQuery(document).ready(function($) {
                // Initialize Slick Carousel
                function initSlickCarousel() {
                    $('#smarty-pc-woo-carousel').not('.slick-initialized').slick({
                        speed: " . intval($speed) . ",
                        autoplay: {$autoplay},
                        autoplaySpeed: " . intval($autoplay_speed) . ",
                        slidesToShow: " . intval($slides_to_show) . ",
                        slidesToScroll: " . intval($slides_to_scroll) . ",
                        infinite: {$infinite},
                        adaptiveHeight: true,
                        arrows: {$display_arrows},
                        dots: {$display_dots},
                        responsive: [
                            { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 3 } },
                            { breakpoint: 600,  settings: { slidesToShow: 2, slidesToScroll: 2 } },
                            { breakpoint: 480,  settings: { slidesToShow: 1, slidesToScroll: 1 } }
                        ]
                    });
                }
                initSlickCarousel();

                // Function to update button states based on cart/order contents
                function updateCarouselButtons() {
                    console.log('Updating carousel buttons...');
                    $.ajax({
                        url: '" . admin_url('admin-ajax.php') . "',
                        type: 'POST',
                        data: {
                            action: 'smarty_pc_get_cart_contents',
                            order_id: $('#order_id').val() || 0
                        },
                        success: function(response) {
                            console.log('Response:', response);
                            if (response.success && response.data.contents) {
                                var contents = response.data.contents.map(Number);
                                $('#smarty-pc-woo-carousel .ajax_add_to_cart').each(function() {
                                    var \$button = $(this);
                                    var product_id = Number(\$button.data('product_id'));
                                    var in_contents = contents.includes(product_id);
                                    var source = \$button.data('source');
                                    var buttonText = (source === 'thankyou_page') ? '" . esc_js(__('In Order', 'smarty-product-carousel')) . "' : '" . esc_js(__('In Cart', 'smarty-product-carousel')) . "';
                                    if (in_contents) {
                                        \$button.addClass('added')
                                            .text(buttonText)
                                            .prop('disabled', true)
                                            .attr('aria-disabled', 'true');
                                    } else {
                                        \$button.removeClass('added')
                                            .text('" . esc_js($add_to_cart_text) . "')
                                            .prop('disabled', false)
                                            .removeAttr('aria-disabled');
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Failed to get contents: ' + error);
                        }
                    });
                }

                // Initial update on page load
                updateCarouselButtons();

                // Custom Add to Order/Cart handler
                $(document.body).on('click', '#smarty-pc-woo-carousel .ajax_add_to_cart', function(e) {
                    e.preventDefault();
                    var \$thisbutton = $(this);
                    var product_id = Number(\$thisbutton.data('product_id'));
                    var in_cart = \$thisbutton.hasClass('added') || \$thisbutton.prop('disabled');
                    var source = \$thisbutton.data('source');
                    var order_id = $('#order_id').val() || 0;
                    var variation_id = \$thisbutton.data('variation_id') || 0;

                    if (in_cart) {
                        console.log('Click prevented for product ID ' + product_id);
                        return false;
                    }

                    \$thisbutton.addClass('loading');

                    var ajaxData = {
                        action: 'smarty_pc_add_to_order',
                        product_id: product_id,
                        source: source
                    };

                    if (source === 'thankyou_page') {
                        ajaxData.order_id = order_id;
                        if (variation_id) {
                            ajaxData.variation_id = variation_id;
                        }
                    } else if (variation_id) {
                        ajaxData.variation_id = variation_id;
                    }

                    $.ajax({
                        url: '" . admin_url('admin-ajax.php') . "',
                        type: 'POST',
                        data: ajaxData,
                        success: function(response) {
                            \$thisbutton.removeClass('loading');
                            if (response.success) {
                                var successText = (source === 'thankyou_page') ? '" . esc_js(__('In Order', 'smarty-product-carousel')) . "' : '" . esc_js(__('In Cart', 'smarty-product-carousel')) . "';
                                \$thisbutton.addClass('added')
                                    .text(successText)
                                    .prop('disabled', true)
                                    .attr('aria-disabled', 'true');
                                console.log(response.data.message);
                                if (response.data.reload) {
                                    window.location.reload();
                                }
                            } else {
                                console.error('Error: ' + response.data.message);
                                alert(response.data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            \$thisbutton.removeClass('loading');
                            console.error('AJAX error: ' + error);
                            alert('An error occurred. Please try again.');
                        }
                    });
                });

                // Re-apply button states after checkout update
                $(document.body).on('updated_checkout', function() {
                    console.log('Checkout updated, re-applying button states');
                    updateCarouselButtons();
                });
            });
        </script>";

        return $carousel_html;
    }
    add_shortcode('smarty_pc_product_carousel', 'smarty_pc_product_carousel_shortcode');
}

if (!function_exists('smarty_pc_get_cart_contents')) {
    function smarty_pc_get_cart_contents() {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $contents = array();

        if ($order_id > 0 && is_wc_endpoint_url('order-received')) {
            $order = wc_get_order($order_id);
            if ($order) {
                foreach ($order->get_items() as $item) {
                    $contents[] = intval($item->get_product_id());
                    if ($item->get_variation_id()) {
                        $contents[] = intval($item->get_variation_id());
                    }
                }
            }
        } else {
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $contents[] = intval($cart_item['product_id']);
                    if ($cart_item['variation_id']) {
                        $contents[] = intval($cart_item['variation_id']);
                    }
                }
            }
        }

        wp_send_json_success(array('contents' => $contents));
    }
    add_action('wp_ajax_smarty_pc_get_cart_contents', 'smarty_pc_get_cart_contents');
    add_action('wp_ajax_nopriv_smarty_pc_get_cart_contents', 'smarty_pc_get_cart_contents');
}

if (!function_exists('smarty_pc_refresh_cart')) {
    function smarty_pc_refresh_cart() {
        WC()->cart->calculate_totals();
        $fragments = array(
            'div.woocommerce-checkout-review-order-table' => wc_cart_totals_order_review_html(),
            '.cart_totals' => wc_cart_totals_html(),
        );
        wp_send_json_success(array('fragments' => $fragments));
    }
    add_action('wp_ajax_smarty_pc_refresh_cart', 'smarty_pc_refresh_cart');
    add_action('wp_ajax_nopriv_smarty_pc_refresh_cart', 'smarty_pc_refresh_cart');
}

if (!function_exists('smarty_pc_set_source')) {
    function smarty_pc_set_source() {
        if (!isset($_POST['product_id']) || !isset($_POST['source'])) {
            wp_send_json_error(array('message' => __('Missing parameters', 'smarty-product-carousel')));
        }

        $product_id = intval($_POST['product_id']);
        $source = sanitize_text_field($_POST['source']);

        if ($product_id > 0 && !empty($source)) {
            WC()->session->set('_source_' . $product_id, $source);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Invalid parameters', 'smarty-product-carousel')));
        }
    }
    add_action('wp_ajax_smarty_pc_set_source', 'smarty_pc_set_source');
    add_action('wp_ajax_nopriv_smarty_pc_set_source', 'smarty_pc_set_source');
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
		
		// Skip this check if we are on the Thank You page
        if (is_wc_endpoint_url('order-received')) {
            return;
        }
		
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
            //if ($removed_items) {
				//wc_clear_notices();
                //wc_add_notice(__('You cannot have only upsell products in the cart.', 'smarty-product-carousel'), 'error');
            //}
        }
    }
    add_action('woocommerce_before_cart', 'smarty_pc_check_upsell_products_in_cart');
    add_action('woocommerce_cart_item_removed', 'smarty_pc_check_upsell_products_in_cart');
    add_action('woocommerce_cart_updated', 'smarty_pc_check_upsell_products_in_cart');
}

if (!function_exists('smarty_pc_display_carousel_for_cod')) {
    function smarty_pc_display_carousel_for_cod($order_id) {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order || is_wp_error($order)) return; // Add this check
        if ($order->get_payment_method() !== 'cod') return; // Use strict comparison

        // existing code here...
        echo do_shortcode('[smarty_pc_product_carousel source="thankyou_page" order_id="' . esc_attr($order_id) . '"]');
        
        echo '<input type="hidden" id="order_id" value="' . esc_attr($order_id) . '">';

        $order_time = get_post_meta($order_id, '_order_time', true);
        $expiry_time = $order_time + 300;
        $current_time = current_time('timestamp');

        if ($current_time > $expiry_time) {
            echo '<p><small><strong>' . __('Time is expired:', 'smarty-product-carousel') . '</strong> ' . __('You can\'t add additional products to your order.', 'smarty-product-carousel') . '</small></p>';
            echo '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    $("#smarty-pc-woo-carousel a.add_to_cart_button").hide();
                });
            </script>';
        } else {
            $expiry_datetime = new DateTime();
            $expiry_datetime->setTimestamp($expiry_time);
            $expiry_time_formatted = $expiry_datetime->format('H:i:s');
            echo '<p><small>' . __('You can add additional products to your order until:', 'smarty-product-carousel') . ' ' . $expiry_time_formatted . '</small></p>';
        }
    }
    add_action('woocommerce_thankyou', 'smarty_pc_display_carousel_for_cod', 5, 1);
}

if (!function_exists('smarty_pc_add_to_order')) {
    function smarty_pc_add_to_order() {
        if (!isset($_POST['product_id']) || !isset($_POST['source'])) {
            wp_send_json_error(array('message' => __('Invalid request: Missing parameters', 'smarty-product-carousel')));
        }

        $product_id = intval($_POST['product_id']);
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $source = sanitize_text_field($_POST['source']);
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;

        if ($product_id <= 0 || empty($source)) {
            wp_send_json_error(array('message' => __('Invalid request: Invalid parameters', 'smarty-product-carousel')));
        }

        if ($source === 'thankyou_page' && $order_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid request: Missing order_id for thankyou_page', 'smarty-product-carousel')));
        }

        $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found.', 'smarty-product-carousel')));
        }

        if ($source === 'thankyou_page') {
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(array('message' => __('Order not found.', 'smarty-product-carousel')));
            }

            if ($order->get_payment_method() !== 'cod') {
                wp_send_json_error(array('message' => __('This feature is only available for Cash on Delivery orders.', 'smarty-product-carousel')));
            }

            if ($order->get_status() !== 'pending' && $order->get_status() !== 'processing') {
                wp_send_json_error(array('message' => __('Order cannot be modified.', 'smarty-product-carousel')));
            }

            $order_time = get_post_meta($order_id, '_order_time', true);
            $current_time = current_time('timestamp');
            if ($current_time - $order_time > 300) {
                wp_send_json_error(array('message' => __('Time expired. Cannot add more products.', 'smarty-product-carousel')));
            }

            // Check if product is already in order
            foreach ($order->get_items() as $item) {
                $item_product_id = $item->get_product_id();
                $item_variation_id = $item->get_variation_id();
                if ($item_product_id === $product_id && (!$variation_id || $item_variation_id === $variation_id)) {
                    wp_send_json_error(array('message' => __('Product already in order.', 'smarty-product-carousel')));
                }
            }

            $item_id = $order->add_product($product, 1);
            if (!$item_id) {
                wp_send_json_error(array('message' => __('Failed to add product to order.', 'smarty-product-carousel')));
            }

            wc_add_order_item_meta($item_id, '_source', $source, true);
            $order->calculate_totals();
            $order->save();

            smarty_pc_send_order_update_email($order_id);
            wp_send_json_success(array('message' => __('Product added to order successfully.', 'smarty-product-carousel'), 'reload' => true));
        } else {
            // For checkout_page or mini_cart, add to cart
            remove_action('woocommerce_before_cart', 'smarty_pc_check_upsell_products_in_cart');
            remove_action('woocommerce_cart_item_removed', 'smarty_pc_check_upsell_products_in_cart');
            remove_action('woocommerce_cart_updated', 'smarty_pc_check_upsell_products_in_cart');

            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, $variation_id);
            if (!$cart_item_key) {
                wp_send_json_error(array('message' => __('Failed to add product to cart.', 'smarty-product-carousel')));
            }

            WC()->session->set('_source_' . $product_id, $source);
            wp_send_json_success(array('message' => __('Product added to cart successfully.', 'smarty-product-carousel'), 'reload' => true));
        }
    }
    add_action('wp_ajax_smarty_pc_add_to_order', 'smarty_pc_add_to_order');
    add_action('wp_ajax_nopriv_smarty_pc_add_to_order', 'smarty_pc_add_to_order');
}

if (!function_exists('smarty_pc_send_order_update_email')) {
    /**
     * Send an updated order email to the customer
     */
    function smarty_pc_send_order_update_email($order_id) {
        $order = wc_get_order($order_id);

        // Ensure the order exists
        if (!$order) {
            error_log("Order not found for ID: {$order_id}");
            return;
        }

        // Log to confirm function is called
        error_log("Triggering email for Order ID: {$order_id}");

        // Get WooCommerce mailer
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();

        // Ensure the email exists
        if (!isset($emails['WC_Email_Customer_Processing_Order'])) {
            error_log("Processing order email is not set up.");
            return;
        }

        // Trigger the email
        $emails['WC_Email_Customer_Processing_Order']->trigger($order_id);

        // Log successful email trigger
        error_log("Email triggered successfully for Order ID: {$order_id}");
    }
}

if (!function_exists('smarty_pc_display_woocommerce_notices')) {
    function smarty_pc_display_woocommerce_notices() {
        if (function_exists('wc_print_notices')) {
            wc_print_notices();
        }
    }
    add_action('woocommerce_before_thankyou', 'smarty_pc_display_woocommerce_notices', 20);
}

if (!function_exists('smarty_pc_add_completed_status_meta_box')) {
    function smarty_pc_add_completed_status_meta_box() {
        add_meta_box(
            'smarty-pc-completed-status', // Meta box ID
            __('Order Completed Status', 'smarty-product-carousel'), // Meta box title
            'smarty_pc_render_completed_status_meta_box', // Callback function
            'shop_order', // Post type
            'side', // Context
            'default' // Priority
        );
    }
    add_action('add_meta_boxes', 'smarty_pc_add_completed_status_meta_box');
}

if (!function_exists('smarty_pc_render_completed_status_meta_box')) {
    function smarty_pc_render_completed_status_meta_box($post) {
        // Retrieve the current _is_completed value
        $is_completed = get_post_meta($post->ID, '_is_completed', true);
        
        // Nonce field for security
        wp_nonce_field('smarty_pc_save_completed_status_meta_box', 'smarty_pc_completed_status_nonce');
        ?>
        <p>
            <label for="smarty_pc_is_completed">
                <strong><?php echo __('Is Completed:', 'smarty-product-carousel'); ?></strong>
            </label>
            <select name="smarty_pc_is_completed" id="smarty_pc_is_completed" style="width: 100%;">
                <option value="no" <?php selected($is_completed, 'no'); ?>><?php echo __('No', 'smarty-product-carousel'); ?></option>
                <option value="yes" <?php selected($is_completed, 'yes'); ?>><?php echo __('Yes', 'smarty-product-carousel'); ?></option>
            </select>
        </p>
        <?php
    }
}

if (!function_exists('smarty_pc_save_completed_status')) {
    function smarty_pc_save_completed_status($post_id) {
        // Verify nonce
        if (!isset($_POST['smarty_pc_completed_status_nonce']) || !wp_verify_nonce($_POST['smarty_pc_completed_status_nonce'], 'smarty_pc_save_completed_status_meta_box')) {
            return;
        }

        // Check if the current user can edit orders
        if (!current_user_can('edit_shop_order', $post_id)) {
            return;
        }

        // Check if the _is_completed field is set
        if (isset($_POST['smarty_pc_is_completed'])) {
            $new_value = sanitize_text_field($_POST['smarty_pc_is_completed']);
            update_post_meta($post_id, '_is_completed', $new_value);
        }
    }
    add_action('save_post_shop_order', 'smarty_pc_save_completed_status');
}

if (!function_exists('smarty_pc_store_order_time')) {
    function smarty_pc_store_order_time($order_id) {
        $current_time = current_time('timestamp');
        //error_log('Storing order time for Order ID: ' . $order_id . ' | Time: ' . $current_time);

        if (!get_post_meta($order_id, '_order_time', true)) {
            update_post_meta($order_id, '_order_time', $current_time);
        }

        if (!get_post_meta($order_id, '_is_completed', true)) {
            update_post_meta($order_id, '_is_completed', 'no');
        }
    }
    add_action('woocommerce_checkout_order_processed', 'smarty_pc_store_order_time', 10, 1);
}

if (!function_exists('smarty_pc_schedule_order_check')) {
    /**
     * Schedule the cron job.
     */
    function smarty_pc_schedule_order_check() {
        if (!wp_next_scheduled('smarty_pc_check_order_completion')) {
            wp_schedule_event(time(), 'every_five_minutes', 'smarty_pc_check_order_completion');
        }
    }
    add_action('wp', 'smarty_pc_schedule_order_check');
}

if (!function_exists('smarty_pc_cron_schedules')) {
    /**
     * Create a custom interval for the cron job.
     */
    function smarty_pc_cron_schedules($schedules) {
        $schedules['every_five_minutes'] = array(
            'interval' => 300, // 300 seconds = 5 minutes
            'display'  => __('Every Five Minutes', 'smarty-product-carousel')
        );
        return $schedules;
    }
    add_filter('cron_schedules', 'smarty_pc_cron_schedules');
}

if (!function_exists('smarty_pc_check_order_completion')) {
    /**
     * Function to check and update the is_completed field.
     */
    function smarty_pc_check_order_completion() {
        // Use WC_Order_Query to query orders based on metadata
        $args = array(
            'limit'      => -1, // Get all matching orders
            'status'     => array('processing', 'on-hold', 'completed'), // Adjust as needed
            'meta_key'   => '_is_completed', // Meta key to search for
            'meta_value' => 'no', // Only get orders where _is_completed is 'no'
        );

        // Create a new order query
        $order_query = new WC_Order_Query($args);
        $orders = $order_query->get_orders();

        $current_time = current_time('timestamp');

        // Loop through the orders and update the _is_completed field
        foreach ($orders as $order) {
            $order_time = get_post_meta($order->get_id(), '_order_time', true);

            // Ensure $order_time is an integer
            $order_time = (int)$order_time;

            // Check if 5 minutes (300 seconds) have passed since the order was placed
            if (($current_time - $order_time) > 300) {
                update_post_meta($order->get_id(), '_is_completed', 'yes');
            }
        }
    }
    add_action('smarty_pc_check_order_completion', 'smarty_pc_check_order_completion');
}

if (!function_exists('smarty_pc_deactivate')) {
    function smarty_pc_deactivate() {
        $timestamp = wp_next_scheduled('smarty_pc_check_order_completion');
        wp_unschedule_event($timestamp, 'smarty_pc_check_order_completion');
    }
    register_deactivation_hook(__FILE__, 'smarty_pc_deactivate');
}

if (!function_exists('smarty_pc_add_source_column_header')) {
    function smarty_pc_add_source_column_header() {
        echo '<th class="source">' . __('Source', 'smarty-product-carousel') . '</th>';
    }
    add_action('woocommerce_admin_order_item_headers', 'smarty_pc_add_source_column_header');
}

if (!function_exists('smarty_pc_add_source_column_value')) {
    function smarty_pc_add_source_column_value($product, $item, $item_id) {
        $source = wc_get_order_item_meta($item_id, '_source', true);
        echo '<td class="source">' . esc_html($source) . '</td>';
    }
    add_action('woocommerce_admin_order_item_values', 'smarty_pc_add_source_column_value', 10, 3);
}

if (!function_exists('smarty_pc_hide_source_meta')) {
    /**
     * Hide the _source meta key from the admin order view
     */
    function smarty_pc_hide_source_meta($hidden_meta_keys) {
        $hidden_meta_keys[] = '_source';
        return $hidden_meta_keys;
    }
    add_filter('woocommerce_hidden_order_itemmeta', 'smarty_pc_hide_source_meta');
}

if (!function_exists('smarty_pc_load_readme')) {
    /**
     * AJAX handler to load and parse the README.md content.
     */
    function smarty_pc_load_readme() {
        check_ajax_referer('smarty_product_carousel_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $readme_path = plugin_dir_path(__FILE__) . 'README.md';
        if (file_exists($readme_path)) {
            // Include Parsedown library
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($readme_path);
            $html_content = $parsedown->text($markdown_content);
    
            // Remove <img> tags from the content
            $html_content = preg_replace('/<img[^>]*>/', '', $html_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('README.md file not found.');
        }
    }    
    add_action('wp_ajax_smarty_pc_load_readme', 'smarty_pc_load_readme');
}

if (!function_exists('smarty_pc_load_changelog')) {
    /**
     * AJAX handler to load and parse the CHANGELOG.md content.
     */
    function smarty_pc_load_changelog() {
        check_ajax_referer('smarty_product_carousel_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $changelog_path = plugin_dir_path(__FILE__) . 'CHANGELOG.md';
        if (file_exists($changelog_path)) {
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($changelog_path);
            $html_content = $parsedown->text($markdown_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('CHANGELOG.md file not found.');
        }
    }
    add_action('wp_ajax_smarty_pc_load_changelog', 'smarty_pc_load_changelog');
}

// Add a links on the Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=smarty-pc-settings') . '">' . __('Settings', 'smarty-product-carousel') . '</a>';
    $links[] = '<a href="https://github.com/mnestorov/smarty-woocommerce-product-carousel" target="_blank">' . __('GitHub', 'smarty-product-carousel') . '</a>';
    return $links;
});