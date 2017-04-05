<?php
/**
 * Shopify Related Products - Shopping Cart Shortcode
 * @version 1.1.4
 * @package Shopify Related Products - Shopping Cart
 */

class SECP_Shortcode {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.0.9
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  0.0.9
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.0.9
	 */
	public function hooks() {
		add_shortcode( 'shopify', array( $this, 'shortcode' ) );
		add_shortcode( 'shopifym', array( $this, 'shortcodem' ) );
	}

	/**
	 * Enqueue shorcode script
	 *
	 * @since 1.0.3
	 */
	public function enqueue() {

	}

	/**
	 * Shortcode rendering
	 * Just passes arguments to output function.
	 *
	 * @since 0.0.9
	 * @param  array $args Shortcode attributes.
	 * @return string      HTML output.
	 */
	public function shortcode( $args ) {
		return $this->plugin->output->get_button( $args );
	}

	/**
	 * Shortcode manually added rendering
	 * Just passes arguments to output function.
	 *
	 * @since 0.0.9
	 * @param  array $args Shortcode attributes.
	 * @return string      HTML output.
	 */
	public function shortcodem() {
        $custom_fields =  $this->plugin->output->secp_get_custom_fields();
		return $this->plugin->output->do_shortcode( $custom_fields['secp_shortcode'] );
	}
}
