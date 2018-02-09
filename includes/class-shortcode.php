<?php
/**
 * Shopify Related Products Shortcode
 * @version 0.0.9
 * @package Shopify Related Products
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
}
