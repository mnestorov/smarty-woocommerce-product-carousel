# Changelog

### 1.0.0 (2024.03.07)
- Initial release.

### 1.0.1 (2025.02.18)
- Bug fixes
    - Checks if `WC()->cart` exists before calling `get_total()`, avoiding the null error.
    - Skips execution in the admin panel unless it’s an AJAX request.
    - Ensures that WooCommerce is fully initialized before running.
    - If the cart is empty, it returns nothing instead of causing errors.
- Plugin settings page enhancement