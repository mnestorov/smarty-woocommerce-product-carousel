<?php
/**
 * Plugin Name: SM - WooCommerce Product Carousel
 * Plugin URI:  https://smartystudio.net/smarty-woocommerce-product-carousel
 * Description: A custom WooCommerce product carousel plugin.
 * Version:     1.0.0
 * Author:      Smarty Studio | Martin Nestorov
 * Author URI:  https://smartystudio.net
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: smarty-woocommerce-product-carousel
 * 
 * Usage: [smarty_product_carousel ids="1,2,3" speed="500" autoplay="true" autoplay_speed="3000"]
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (!function_exists('smarty_check_woocommerce_exists')) {
    function smarty_check_woocommerce_exists() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', 'smarty_missing_woocommerce_notice');
        }
    }
    add_action('admin_init', 'smarty_check_woocommerce_exists');
}

if (!function_exists('smarty_missing_woocommerce_notice')) {
    function smarty_missing_woocommerce_notice(){
        echo '<div class="error"><p><strong>' . __('SM - WooCommerce Product Carousel requires WooCommerce to be installed and active.', 'smarty-woocommerce-product-carousel') . '</strong></p></div>';
    }
}

if (!function_exists('smarty_enqueue_admin_scripts')) {
    /**
     * Enqueue required scripts and styles.
     */
    function smarty_enqueue_admin_scripts($hook) {
        wp_enqueue_script('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);
        wp_enqueue_style('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');

        // Enqueue admin script for settings page
        if ('toplevel_page_smarty-admin-page' === $hook) {
            wp_enqueue_script('smarty-admin-js', plugins_url('/admin/js/smarty-admin.js', __FILE__), array('jquery'), null, true);
        }
    }
    add_action('admin_enqueue_scripts', 'smarty_enqueue_admin_scripts');
}

if (!function_exists('smarty_add_async_attribute')) {
    function smarty_add_async_attribute($tag, $handle) {
        if ('slick' === $handle || 'select2' === $handle) {
            return str_replace(' src', ' async="async" src', $tag);
        }
        return $tag;
    }
    add_filter('script_loader_tag', 'smarty_add_async_attribute', 10, 2);
}

if (!function_exists('smarty_admin_menu')) {
    /**
     * Add admin menu for plugin settings.
     */
    function smarty_admin_menu() {
        add_menu_page('Product Carousel', 'Product Carousel', 'manage_options', 'smarty-admin-page', 'smarty_admin_page_html', 'dashicons-images-alt2');
    }
    add_action('admin_menu', 'smarty_admin_menu');
}

if (!function_exists('smarty_admin_page_html')) {
    /**
     * Admin page HTML content.
     */
    function smarty_admin_page_html() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Product Carousel', 'smarty-woocommerce-product-carousel')); ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="smarty_save_settings">
                <?php
                wp_nonce_field('smarty_save_settings_action', 'smarty_settings_nonce');
                settings_fields('smarty-settings-group');
                do_settings_sections('smarty-settings-group');
                submit_button();
                ?>
                <select id="smarty-product-search" name="products[]" multiple="multiple" style="width: 50%"></select>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#smarty-product-search').select2({
                ajax: {
                    url: ajaxurl, // WordPress AJAX
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            action: 'smarty_search_products' // WordPress AJAX action
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

if (!function_exists('smarty_register_settings')) {
    /**
     * Register settings, sections, and fields
     */
    function smarty_register_settings() {
        register_setting('smarty-settings-group', 'smarty_carousel_options', 'smarty_options_sanitize');

        add_settings_section('smarty_carousel_settings', 'Carousel Settings', 'smarty_carousel_settings_section_callback', 'smarty-settings-group');

        add_settings_field('smarty_arrow_color', 'Arrow Color', 'smarty_arrow_color_callback', 'smarty-settings-group', 'smarty_carousel_settings');
        add_settings_field('smarty_dot_color', 'Dot Color', 'smarty_dot_color_callback', 'smarty-settings-group', 'smarty_carousel_settings');
        add_settings_field('smarty_slide_padding', 'Slide Padding', 'smarty_slide_padding_callback', 'smarty-settings-group', 'smarty_carousel_settings');
        add_settings_field('smarty_autoplay_indicator', 'Autoplay Indicator', 'smarty_autoplay_indicator_callback', 'smarty-settings-group', 'smarty_carousel_settings');
    }
    add_action('admin_init', 'smarty_register_settings');
}

if (!function_exists('smarty_options_sanitize')) {
    function smarty_options_sanitize($input) {
        $input['smarty_arrow_color'] = sanitize_hex_color($input['smarty_arrow_color']);
        $input['smarty_dot_color'] = sanitize_hex_color($input['smarty_dot_color']);
        $input['smarty_slide_padding'] = intval($input['smarty_slide_padding']);
        $input['smarty_autoplay_indicator'] = !empty($input['smarty_autoplay_indicator']) ? true : false;

        return $input;
    }
}

if (!function_exists('smarty_carousel_settings_section_callback')) {
    function smarty_carousel_settings_section_callback() { ?>
        <p><?php echo __('Customize the appearance and behavior of the WooCommerce product carousel.', 'smarty-woocommerce-product-carousel'); ?></p><?php 
    }
}

if (!function_exists('smarty_arrow_color_callback')) {
    function smarty_arrow_color_callback() {
        $options = get_option('smarty_carousel_options'); ?>
        <input type="text" name="smarty_carousel_options[smarty_arrow_color]" value="<?php echo esc_attr($options['smarty_arrow_color'] ?? ''); ?>" class="regular-text"><?php
    }
}

if (!function_exists('smarty_dot_color_callback')) {
    function smarty_dot_color_callback() {
        $options = get_option('smarty_carousel_options'); ?>
        <input type="text" name="smarty_carousel_options[smarty_dot_color]" value="<?php echo esc_attr($options['smarty_dot_color'] ?? ''); ?>" class="regular-text"><?php
    }
}

if (!function_exists('smarty_slide_padding_callback')) {
    function smarty_slide_padding_callback() {
        $options = get_option('smarty_carousel_options'); ?>
        <input type="number" name="smarty_carousel_options[smarty_slide_padding]" value="<?php echo esc_attr($options['smarty_slide_padding'] ?? '0'); ?>" class="small-text">px<?php
    }
}

if (!function_exists('smarty_autoplay_indicator_callback')) {
    function smarty_autoplay_indicator_callback() {
        $options = get_option('smarty_carousel_options');
        $checked = isset($options['smarty_autoplay_indicator']) && $options['smarty_autoplay_indicator'] ? 'checked' : ''; ?>
        <input type="checkbox" name="smarty_carousel_options[smarty_autoplay_indicator]" <?php echo $checked; ?>><?php
    }
}

if (!function_exists('smarty_save_settings')) {
    function smarty_save_settings() {
        // Check if our nonce is set.
        if (!isset($_POST['smarty_settings_nonce'])) {
            wp_die('Nonce value cannot be verified.');
        }

        // Verify the nonce.
        if (!wp_verify_nonce($_POST['smarty_settings_nonce'], 'smarty_save_settings_action')) {
            wp_die('Security check failed');
        }

        // Process form data and save settings here
        // For example, update_option('smarty_option', sanitize_text_field($_POST['some_field']));

        // Redirect back to settings page
        wp_redirect(html_entity_decode(wp_get_referer()));
        exit;
    }
    add_action('admin_post_smarty_save_settings', 'smarty_save_settings');
}

if (!function_exists('smarty_search_products')) {
    function smarty_search_products() {
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
                    'id' => get_the_ID(),
                    'text' => get_the_title(),
                );
            }
        }

        wp_send_json($results);
    }
    add_action('wp_ajax_smarty_search_products', 'smarty_search_products');
}

if (!function_exists('smarty_product_carousel_shortcode')) {
    function smarty_product_carousel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ids'              => '',
            'categories'       => '',
            'skus'             => '',
            'speed'            => '300',
            'autoplay'         => 'false',
            'autoplay_speed'   => '5000',
            'slides_to_show'   => '3',
            'slides_to_scroll' => '1',
            'infinite'         => 'true',
            'adaptive_height'  => 'false',
        ), $atts, 'smarty_product_carousel');

        // Prepare query arguments based on shortcode attributes
        $query_args = array(
            'limit'  => -1,
            'status' => 'publish',
        );

        // Add IDs to query args if present
        if (!empty($atts['ids'])) {
            $query_args['include'] = explode(',', $atts['ids']);
        }

        // Add category slugs to query args if present
        if (!empty($atts['categories'])) {
            $query_args['category'] = explode(',', $atts['categories']);
        }

        // Add SKUs to query args if present (you'll need to handle this part within your query as 'sku' is not a direct argument in WC_Product_Query)
        if (!empty($atts['skus'])) {
            $query_args['sku'] = explode(',', $atts['skus']);
        }

        // Query products
        $query = new WC_Product_Query($query_args);
        $products = $query->get_products();

        // Start building carousel HTML
        $carousel_html = '<div class="smarty-carousel">';

        foreach ($products as $product) {
            // You can customize the display here
            $carousel_html .= '<div class="product">';
            $carousel_html .= '<a href="' . get_permalink($product->get_id()) . '">';
            $carousel_html .= '<img src="' . wp_get_attachment_url($product->get_image_id()) . '" alt="' . $product->get_name() . '">';
            $carousel_html .= '<h2>' . $product->get_name() . '</h2>';
            $carousel_html .= '</a>';
            $carousel_html .= '<span class="price">' . $product->get_price_html() . '</span>';
            $carousel_html .= '</div>';
        }

        $carousel_html .= '</div>';

        $carousel_html .= "<script>
            jQuery(document).ready(function($) {
                $('.smarty-carousel').slick({
                    speed: {$atts['speed']},
                    autoplay: {$atts['autoplay']},
                    autoplaySpeed: {$atts['autoplay_speed']},
                    slidesToShow: " . intval($atts['slides_to_show']) . ",
                    slidesToScroll: " . intval($atts['slides_to_scroll']) . ",
                    infinite: {$atts['infinite']},
                    adaptiveHeight: {$atts['adaptive_height']},
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
        </script>";

        return $carousel_html;
    }
    add_shortcode('smarty_product_carousel', 'smarty_product_carousel_shortcode');
}