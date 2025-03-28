# SM - WooCommerce Product Carousel

[![Licence](https://img.shields.io/badge/LICENSE-GPL2.0+-blue)](./LICENSE)

- **Developed by:** Martin Nestorov 
    - Explore more at [nestorov.dev](https://github.com/mnestorov)
- **Plugin URI:** https://github.com/mnestorov/smarty-woocommerce-product-carousel

## Support The Project

Your support is greatly appreciated and will help ensure all of the projects continued development and improvement. Thank you for being a part of the community!
You can send me money on Revolut by following this link: https://revolut.me/mnestorovv

## Overview

The Smarty Studio WooCommerce Product Carousel plugin provides a sleek and customizable product carousel for WooCommerce stores. Enhance your online store's user experience by showcasing your products in a dynamic and responsive carousel.

## Features

- **Customizable product selection:** Choose exactly which products you want to display in the carousel.
- **Responsive design:** Ensures a great experience across all devices.
- **Customizable texts:** Easily change the texts for "Save", "Add To Cart", and "Exclusive" directly from the plugin's settings.
- **Variable product support:** Displays discounts and allows users to add variable products to their cart from the carousel.
- **Styling options:** Modify the appearance of the carousel with custom CSS.
- Supports WooCommerce up-sell and cross-sell features.
- Admin settings to customize the number of slides to show, slides to scroll, autoplay, and speed.
- Display the carousel on checkout and thank you pages with specific order details.
- AJAX-based product search for quick selection in the admin panel.
- Integration with Select2 for enhanced product selection in settings.
- Custom CSS to further customize the carousel appearance.
- Time-based restrictions for adding products post-purchase.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/smarty-woocommerce-product-carousel` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

To display the product carousel, use the shortcode `[smarty_pc_product_carousel]` in your posts or pages. You can customize the carousel further with shortcode attributes or via the plugin's settings page.

### Shortcode Attributes

- **ids:** Comma-separated list of product IDs to display in the carousel.
- **speed:** Scrolling speed in milliseconds (default is 300).
- **autoplay:** Enable or disable autoplay (true or false).
- **autoplay_speed:** Speed of autoplay in milliseconds (default is 3000).
- **slides_to_show:** Number of slides to show at once (default is based on the plugin settings).
- **source:** Source context for the carousel (e.g., checkout_page, thankyou_page).
- **order_id:** Order ID to reference on thank you pages.

### Example Shortcode Usage

```php
[smarty_pc_product_carousel ids="1,2,3" speed="500" autoplay="true" autoplay_speed="3000" slides_to_show="1"]
```

### Example CSS styling

```css
/* ===========================
   1) Carousel Container & Title
   =========================== */
#smarty-pc-woo-carousel.smarty-pc-carousel {
  text-align: center;
}
#smarty-pc-woo-carousel.smarty-pc-carousel.slick-initialized.slick-slider {
  padding: 10px;
}
.smarty-pc-carousel-title {
  text-align: center;
  font-size: 100% !important;
}

/* ===========================
   2) Text Label & Discount Label
   =========================== */
#smarty-pc-woo-carousel .text-label {
  position: absolute;
  top: 40%;
  left: 50%;
  transform: translateX(-50%);
  background-color: #FFD700;
  color: #000;
  padding: 2px 20px;
  font-size: 14px;
  font-weight: bold;
  text-align: center;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .text-label {
  /* Overridden when slick initializes? */
  background-color: #FFD700 !important;
  color: #000;
  top: 0;
  left: auto !important;
  right: -25px !important;
}
#smarty-pc-woo-carousel .discount-label {
  position: absolute;
  top: 0;
  left: 0;
  background-color: rgb(210,184,133);
  border-radius: 50px;
  width: auto;
  color: #ffffff;
  padding: 10px 5px;
  font-size: 14px;
  font-weight: bold;
}
#smarty-pc-woo-carousel .discount-label s {
  color: #bbeebb;
}

/* ===========================
   3) Product Container & Titles
   =========================== */
#smarty-pc-woo-carousel .product {
  position: relative;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .product h2 {
  font-size: 16px;
  text-wrap: balance;
  margin-bottom: 5px;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .added_to_cart.wc-forward {
  display: none;
}

/* ===========================
   4) Price & Savings Styles
   =========================== */
/* Specific colors for old/new prices */
#smarty-pc-woo-carousel span.price small del span.woocommerce-Price-amount.amount {
  color: #dd5444 !important;
}
#smarty-pc-woo-carousel span.price small ins span.woocommerce-Price-amount.amount {
  color: #709900 !important;
}
/* Font sizing for amounts, del, and save info */
#smarty-pc-woo-carousel.smarty-pc-carousel .price .woocommerce-Price-amount.amount {
  font-size: 16px;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .price del {
  font-size: 18px;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .save-info {
  font-size: 14px;
  margin-bottom: 0.25rem;
}

/* ===========================
   5) Slick Prev/Next Buttons
   =========================== */
#smarty-pc-woo-carousel.smarty-pc-carousel .slick-prev,
#smarty-pc-woo-carousel.smarty-pc-carousel .slick-next {
  font-size: 0;
  line-height: 0;
  position: absolute;
  top: 50%;
  transform: translate(0, -50%);
  background: transparent;
  border: none;
  z-index: 25;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .slick-prev {
  left: 25px;
  z-index: 1;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .slick-next {
  right: 25px;
  z-index: 1;
}

/* ===========================
   6) Buttons
   =========================== */
#smarty-pc-woo-carousel.smarty-pc-carousel .button {
  background-color: #0b100d;
  color: #fff;
  border-radius: 5px;
}
#smarty-pc-woo-carousel.smarty-pc-carousel .button:hover {
  background-color: #d2b885;
  color: #fff;
}

/* ===========================
   7) Product Images in Slides
   =========================== */
.slick-slide img {
  width: 150px;
  margin: 0 auto;
}
```

### Customizing Texts

Navigate to the plugin's settings under the WooCommerce section in your WordPress admin. Here you can find options to customize the texts for "Save", "Add To Cart", and "Exclusive".

### Admin Settings

Go to _WooCommerce > Products Carousel_. Customize various settings such as:

- Display Arrows
- Arrow Color
- Display Dots
- Dot Color
- Slides to Show
- Slides to Scroll
- Slide Padding
- Scrolling Speed
- Autoplay Indicator
- Autoplay Speed
- Infinite Loop
- Old Discount
- Custom Title
- Save Text
- Add To Cart Text
- Label Text
- Custom CSS

## Requirements

- WordPress 4.7+ or higher.
- WooCommerce 5.1.0 or higher.
- PHP 7.2+

## Changelog

For a detailed list of changes and updates made to this project, please refer to our [Changelog](./CHANGELOG.md).

## Contributing

Contributions are welcome. Please follow the WordPress coding standards and submit pull requests for any enhancements.

## Support The Project

If you find this script helpful and would like to support its development and maintenance, please consider the following options:

- **_Star the repository_**: If you're using this script from a GitHub repository, please give the project a star on GitHub. This helps others discover the project and shows your appreciation for the work done.

- **_Share your feedback_**: Your feedback, suggestions, and feature requests are invaluable to the project's growth. Please open issues on the GitHub repository or contact the author directly to provide your input.

- **_Contribute_**: You can contribute to the project by submitting pull requests with bug fixes, improvements, or new features. Make sure to follow the project's coding style and guidelines when making changes.

- **_Spread the word_**: Share the project with your friends, colleagues, and social media networks to help others benefit from the script as well.

- **_Donate_**: Show your appreciation with a small donation. Your support will help me maintain and enhance the script. Every little bit helps, and your donation will make a big difference in my ability to keep this project alive and thriving.

Your support is greatly appreciated and will help ensure all of the projects continued development and improvement. Thank you for being a part of the community!
You can send me money on Revolut by following this link: https://revolut.me/mnestorovv

---

## License

This project is released under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).
