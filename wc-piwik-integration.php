<?php

/*
Plugin Name: WooCommerce Piwik integration
Description: Provides support for Piwik integration via the WP-Piwik plugin in your WooCommerce powered store.
Author: Coen Jacobs
Author URI: http://coenjacobs.me
Version: 0.1.1
*/

if ( ! function_exists( 'woocommerce_ecommerce_tracking_piwik' ) ) {
	add_action( 'woocommerce_thankyou', 'wcpiwik_ecommerce_tracking_piwik' );

	function wcpiwik_ecommerce_tracking_piwik( $order_id ) {
		global $woocommerce;

		// Don't track admin
		if ( current_user_can('manage_options') )
			return;

		// Call the Piwik ecommerce function if WP-Piwik is configured to add tracking codes to the page
		$wp_piwik_global_settings = get_option( 'wp-piwik_global-settings' );

		// Return if Piwik settings are not here, or if global is not set
		if ( ! isset( $wp_piwik_global_settings['add_tracking_code'] ) || ! $wp_piwik_global_settings['add_tracking_code'] )
			return;
		if ( ! isset( $GLOBALS['wp_piwik'] ) )
			return;

		// Get the order and get tracking code
		$order = new WC_Order( $order_id );
		ob_start();
		?>
		try {
			// Add order items
			<?php if ( $order->get_items() ) foreach( $order->get_items() as $item ) : $_product = $order->get_product_from_item( $item ); ?>

				piwikTracker.addEcommerceItem(
					"<?php echo esc_js( $_product->get_sku() ); ?>",			// (required) SKU: Product unique identifier
					"<?php echo esc_js( $item['name'] ); ?>",					// (optional) Product name
					"<?php
						if ( isset( $_product->variation_data ) )
							echo esc_js( woocommerce_get_formatted_variation( $_product->variation_data, true ) );
					?>",	// (optional) Product category. You can also specify an array of up to 5 categories eg. ["Books", "New releases", "Biography"]
					<?php echo esc_js( $order->get_item_total( $item ) ); ?>,	// (recommended) Product price
					<?php echo esc_js( $item['qty'] ); ?> 						// (optional, default to 1) Product quantity
				);

			<?php endforeach; ?>

			// Track order
			piwikTracker.trackEcommerceOrder(
				"<?php echo esc_js( $order->get_order_number() ); ?>",	// (required) Unique Order ID
				<?php echo esc_js( $order->get_total() ); ?>,			// (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
				false,													// (optional) Order sub total (excludes shipping)
				<?php echo esc_js( $order->get_total_tax() ); ?>,		// (optional) Tax amount
				<?php echo esc_js( $order->get_shipping() ); ?>,		// (optional) Shipping amount
				false 													// (optional) Discount offered (set to false for unspecified parameter)
			);
		} catch( err ) {}
		<?php
		$code = ob_get_clean();
		$woocommerce->add_inline_js( $code );
	}
}