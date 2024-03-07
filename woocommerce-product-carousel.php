<?php
/**
 * Plugin Name: SM - WooCommerce Product Carousel
 * Plugin URI:  https://smartystudio.net/woocommerce-product-carousel
 * Description: A custom WooCommerce product carousel plugin.
 * Version:     1.0.0
 * Author:      Smarty Studio | Martin Nestorov
 * Author URI:  https://smartystudio.net
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * Usage: [mwpc_product_carousel ids="1,2,3" speed="500" autoplay="true" autoplay_speed="3000"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function mwpc_enqueue_admin_scripts($hook) {
    if ('toplevel_page_mwpc-admin-page' !== $hook) {
        return;
    }
    wp_enqueue_script('slick', 'path_to_slick_js', array('jquery'), '1.8.0', true);
    wp_enqueue_style('slick', 'path_to_slick_css');
    //wp_enqueue_style('slick-theme', 'path_to_slick_theme_css'); // If you use Slick's default theme
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
}
add_action('admin_enqueue_scripts', 'mwpc_enqueue_admin_scripts');

// This is a simplified version. You should add nonce checks and proper sanitization/validation for security.

function mwpc_admin_menu() {
    add_menu_page('My WooCommerce Product Carousel', 'Product Carousel', 'manage_options', 'mwpc-admin-page', 'mwpc_admin_page_html');
}
add_action('admin_menu', 'mwpc_admin_menu');

function mwpc_admin_page_html() {
    ?>
    <div class="wrap">
        <h1>My WooCommerce Product Carousel</h1>
        <form>
            <!-- Select2 searchable dropdown for products -->
            <select id="mwpc-product-search" name="products[]" multiple="multiple" style="width: 50%"></select>
        </form>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#mwpc-product-search').select2({
            ajax: {
                url: ajaxurl, // WordPress AJAX
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        action: 'mwpc_search_products' // WordPress AJAX action
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

function mwpc_search_products() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $term = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

    $query_args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        's' => $term,
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
add_action('wp_ajax_mwpc_search_products', 'mwpc_search_products');

function mwpc_product_carousel_shortcode($atts) {
    // Extend default attributes with new carousel settings
    $atts = shortcode_atts(array(
        'ids' => '',
        'categories' => '',
        'skus' => '',
        'speed' => '300', // Default speed in milliseconds
        'autoplay' => 'false', // Default autoplay (true or false)
        'autoplay_speed' => '5000', // Default autoplay speed in milliseconds
        // Add other carousel settings as needed
    ), $atts, 'mwpc_product_carousel');

    // Prepare query arguments based on shortcode attributes
    $query_args = array(
        'limit' => -1,
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
    $carousel_html = '<div class="mwpc-carousel">';

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

    // Before returning $carousel_html, include a script tag to initialize the carousel with the specified settings

    $carousel_html .= "<script>
        jQuery(document).ready(function($) {
            $('.mwpc-carousel').slick({
                speed: {$atts['speed']},
                autoplay: {$atts['autoplay']},
                autoplaySpeed: {$atts['autoplay_speed']},
                // Add other settings as required
            });
        });
    </script>";

    return $carousel_html;
}
add_shortcode('mwpc_product_carousel', 'mwpc_product_carousel_shortcode');
