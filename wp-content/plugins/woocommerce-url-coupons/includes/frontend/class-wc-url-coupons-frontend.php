<?php
/**
 * WooCommerce URL Coupons
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce URL Coupons to newer
 * versions in the future. If you wish to customize WooCommerce URL Coupons for your
 * needs please refer to http://docs.woothemes.com/document/url-coupons/ for more information.
 *
 * @package     WC-URL-Coupons/Frontend
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;


/**
 * Frontend class - handles applying coupons and rendering messages
 *
 * @since 2.0.0
 */
class WC_URL_Coupons_Frontend {


	/** @var array of active coupons in format: key: post id of coupon, value: array( 'url' => url, 'redirect' => redirect page ID ) */
	private $active_coupon_urls = array();


	/**
	 * Setup front end class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		// load coupons with unique URLs into transient
		$this->load_coupons();

		add_action( 'wp_loaded', array( $this, 'maybe_apply_coupon' ), 11 );

		// handle applying deferred coupons
		add_action( 'woocommerce_check_cart_items', array( $this, 'maybe_apply_deferred_coupons' ), 0 );

		// maybe hide coupon field
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field' ) );
	}


	/**
	 * Load coupons from options into a 60 minute transient
	 *
	 * @since 1.0.0
	 */
	private function load_coupons() {

		// transient does not exist
		if ( false === ( $coupons = get_transient( 'wc_url_coupons_active_urls' ) ) ) {

			// get active coupons from option
			$this->active_coupon_urls = get_option( 'wc_url_coupons_active_urls' );

			// set 60 minute transient
			set_transient( 'wc_url_coupons_active_urls', $this->active_coupon_urls, HOUR_IN_SECONDS );

		} else {

			// transient exists
			$this->active_coupon_urls = $coupons;
		}
	}


	/**
	 * Applies discount by checking request URI against array of coupon URLs
	 * If there's a match, apply the discount and redirect to the page specified on the coupons page
	 *
	 * @since 1.0
	 */
	public function maybe_apply_coupon() {

		// bail if no URL coupons exist
		if ( ! is_array( $this->active_coupon_urls ) || 0 === count( $this->active_coupon_urls ) ) {
			return;
		}

		// bail out to prevent adding more products to cart in some circumstances
		if ( is_ajax() ) {
			return;
		}

		// form URL
		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// remove WP site URL to get request URI, this is used instead of the pure REQUEST_URI, as sites can
		// be hosted inside a sub-directory, which will be removed with this method
		$url = strtolower( str_replace( home_url( '/', 'http' ), '', $url ) );

		// save query vars
		parse_str( $_SERVER['QUERY_STRING'], $query_vars );

		// check if URL exists in coupons
		foreach ( $this->active_coupon_urls as $coupon_id => $coupon ) {

			// skip if coupon does not have unique url - avoids a rare redirect issue
			if ( ! isset( $coupon['url'] ) ) {
				continue;
			}

			$coupon_code = get_the_title( $coupon_id );

			// if uri starts with coupon URL
			if ( ! strncmp( $url, strtolower( $coupon['url'] ), strlen( $coupon['url'] ) ) ) {

				// check that coupon has not already been applied
				if ( ! WC()->cart->has_discount( $coupon_code ) ) {

					$coupon_code = get_the_title( $coupon_id );

					// add products to the cart
					if ( is_array( $coupon['products'] ) && count( $coupon['products'] ) >= 1 ) {
						// do not remove, before 2.1.5 this returned non empty `array( 0 => 0 )`
						if ( 0 !== $coupon['products'][0] ) {
							$this->add_product_to_cart( $coupon['products'] );
						}
					}

					// apply the discount
					$applied = WC()->cart->add_discount( $coupon_code );

					// if the coupon couldn't be applied, defer it if allowed
					if ( ! $applied ) {

						if ( $coupon['defer'] ) {

							// start a session if needed
							if ( ! WC()->session->has_session() ) {
								WC()->session->set_customer_session_cookie( true );
							}

							// defer applying the coupon until it's valid
							$this->defer_apply( $coupon_id, $coupon_code );
						}

					} else {

						// if the coupon applied successfully and there's not
						// currently a session, start the customer session so the
						// coupon persists until the customer adds an item to the cart
						if ( ! WC()->session->has_session() ) {

							WC()->session->set_customer_session_cookie( true );
						}
					}
				}

				$redirect = $this->get_coupon_redirect_url( $url, $coupon );

				// bail if not redirecting
				if ( empty( $redirect ) ) {
					return;
				}

				// add query vars back so things like google analytics campaign tracking works
				if ( ! empty( $query_vars ) ) {
					$redirect = add_query_arg( $query_vars, $redirect );
				}

				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}


	/**
	 * Defer applying a URL coupon until it's valid
	 *
	 * @since 2.0.0
	 * @param $coupon_id
	 * @param $coupon_code
	 */
	protected function defer_apply( $coupon_id, $coupon_code ) {

		// get already deferred coupons
		$deferred_coupons = WC()->session->get( 'deferred_url_coupons', array() );

		// get the coupon error message
		$coupon = new WC_Coupon( $coupon_code );
		$coupon->is_valid();
		$coupon_error = $coupon->get_error_message();

		// remove the core error notice as we'll be replacing it
		$this->maybe_remove_error_notices( $coupon_error );

		// defer notices
		$coupon_added         = sprintf(
			/* translators: Placeholders: %s - error message */
			__( 'Coupon added but not yet applied: <br /> %s', 'woocommerce-url-coupons' ), $coupon_error );
		$coupon_already_added = sprintf(
			/* translators: Placeholders: %s - error message */
			__( 'Coupon already added but not yet applied: <br /> %s', 'woocommerce-url-coupons' ), $coupon_error );
		$deferred_notice      = isset( $deferred_coupons[ $coupon_id ] )
			? $coupon_already_added
			: $coupon_added;

		$deferred_coupons[ $coupon_id ] = array(
			'code'   => $coupon_code,
			'notice' => $deferred_notice,
		);

		// Save to session
		WC()->session->set( 'deferred_url_coupons', $deferred_coupons );

		// Add the notice if it doesn't already exist
		if ( ! wc_has_notice( $deferred_notice, 'notice' ) ) {

			// Prevent redundant notices if $coupon_added is queued to be displayed already
			if ( $coupon_already_added === $deferred_notice && ! wc_has_notice( $coupon_added, 'notice' ) ) {
				wc_add_notice( $coupon_already_added, 'notice' );
			} elseif ( $coupon_added === $deferred_notice ) {
				wc_add_notice( $coupon_added, 'notice' );
			}
		}
	}


	/**
	 * Maybe apply previously deferred coupons, this is hooked into the cart
	 * item check so it should only occur on the cart/checkout pages
	 *
	 * Note that if the customer then takes some action to make the coupon invalid
	 * (e.g. changing the cart total for a minimum spend coupon), the coupon will
	 * not* be re-applied and the customer will need to visit the URL in order
	 * to apply it again.
	 *
	 * @since 2.0.0
	 */
	public function maybe_apply_deferred_coupons() {

		$deferred_coupons = WC()->session->get( 'deferred_url_coupons' );

		if ( empty( $deferred_coupons ) ) {
			return;
		}

		// blank coupon error messages so the associated error notices can be removed
		add_filter( 'woocommerce_coupon_error', '__return_empty_string' );

		// Try and apply the coupon regardless of the "Hide coupon field" setting
		remove_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field' ) );

		foreach ( $deferred_coupons as $id => $coupon ) {

			// Check for old session storage format for backwards compatibility
			if ( ! is_array( $coupon ) ) {
				$coupon = array(
					'code'   => $coupon,
					'notice' => '',
				);
			}

			// apply the coupon
			if ( WC()->cart->add_discount( $coupon['code'] ) ) {

				// remove it if successful
				$this->remove_deferred_notice( $coupon['notice'] );

				unset( $deferred_coupons[ $id ] );
			}
		}

		// Restore the "Hide coupon field" setting
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field' ) );

		// housekeeping
		if ( empty( $deferred_coupons ) ) {
			unset( WC()->session->deferred_url_coupons );
		} else {
			WC()->session->set( 'deferred_url_coupons', $deferred_coupons );
		}

		// remove error notices for failed attempts
		$this->maybe_remove_error_notices();
	}


	/**
	 * Maybe remove a specific and/or blank error notices from the WC notice queue,
	 *
	 * @since 2.0.0
	 * @param string|null $error specific notice text to remove
	 */
	protected function maybe_remove_error_notices( $error = null ) {

		$notices = wc_get_notices();

		// nothing to do if no errors present
		if ( empty( $notices['error'] ) ){
			return;
		}

		// blank specific error
		if ( $error && false !== ( $key = array_search( $error, $notices['error'] ) ) ) {
			$notices['error'][ $key ] = '';
		}

		// remove all blank error notices
		$notices['error'] = array_filter( (array) $notices['error'], 'strlen' );

		WC()->session->set( 'wc_notices', $notices );
	}


	/**
	 * Remove a specific coupon deferment notice from the WC notice queue.
	 *
	 * @since 2.1.3
	 * @param string $notice The specific notice text to remove.
	 */
	protected function remove_deferred_notice( $notice ) {

		$notices = wc_get_notices();

		// If no notices exist, bail
		if ( empty( $notices['notice'] ) ) {
			return;
		}

		// Remove the matching notice if found
		if ( false !== ( $key = array_search( $notice, $notices['notice'] ) ) ) {
			unset( $notices['notice'][ $key ] );
		}

		WC()->session->set( 'wc_notices', $notices );
	}


	/**
	 * Get the redirect URL for a given URL coupon
	 *
	 * @since 2.0.0
	 * @param $url
	 * @param $coupon
	 * @return bool|null|string|void|\WP_Error
	 */
	protected function get_coupon_redirect_url( $url, $coupon ) {

		// don't redirect if none was set
		if ( 0 === $coupon['redirect'] ) {
			return false;
		}

		// redirect to given page
		switch ( $coupon['redirect_page_type'] ) {

			case 'page':
				$redirect = ( -1 === $coupon['redirect'] ) ? home_url() : get_permalink( $coupon['redirect'] );
			break;

			case 'product':

				$product  = wc_get_product( $coupon['redirect'] );
				$redirect = $product->get_permalink();

			break;

			case 'category':
			case 'post_tag':
			case 'product_cat':
			case 'product_tag':
				$redirect = get_term_link( $coupon['redirect'], $coupon['redirect_page_type'] );
			break;

			default:
				$redirect = get_permalink( $coupon['redirect'] );
			break;
		}

		// default to homepage if errors occur
		if ( ! $redirect || is_wp_error( $redirect ) ) {
			$redirect = home_url();
		}

		// don't redirect if unique uri is the same as the redirect uri
		if ( str_replace( '/', '', $url ) === str_replace( '/', '', parse_url( $redirect, PHP_URL_PATH ) ) ) {
			return null;
		}

		return $redirect;
	}


	/**
	 * Adds the given product IDs to the customer's cart
	 *
	 * @since 1.0
	 * @param array $product_ids
	 */
	private function add_product_to_cart( $product_ids ) {

		foreach ( $product_ids as $product_id ) {

			$product = wc_get_product( absint( $product_id ) );

			if ( ! is_object( $product ) ) {
				continue;
			}

			// Variable product
			if ( ! empty( $product->variation_id ) ) {

				// Get variation data (attributes) for variable product
				$attributes = str_replace( 'attribute_', '', $product->get_variation_attributes() );

				// Add to cart validation
				if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product->id, 1, $product->variation_id, $attributes ) ) {
					continue;
				}

				WC()->cart->add_to_cart( $product->id, 1, $product->variation_id, $attributes );

			// Simple product
			} else {

				// Should be simple product,
				// unless admin made a mistake and selected a variation parent,
				// in which case don't add it
				if ( ! $product->is_type( 'variable' ) ) {

					// Add to cart validation
					if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product->id, 1 ) ) {
						continue;
					}

					WC()->cart->add_to_cart( $product->id );
				}
			}
		}
	}


	/**
	 * Hide coupon code field based on user settings
	 *
	 * @see wc_coupons_enabled()
	 * @see WC_Cart::remove_coupon()
	 *
	 * @since 1.2
	 * @param bool $maybe_enabled enabled/disabled state of coupons
	 * @return bool filtered enabled/disabled state
	 */
	public function hide_coupon_field( $maybe_enabled ) {

		/** @see WC_AJAX::add_ajax_events() */
		$actions = array(
			'wp_ajax_woocommerce_remove_coupon',
			'wp_ajax_nopriv_woocommerce_remove_coupon',
			'wc_ajax_remove_coupon',
		);

		// small workaround to allow removal of a coupon while clicking
		// on the remove coupon link while hiding the coupon field
		// in cart or checkout pages
		$removing_coupon = false;

		/** @see WC_AJAX::remove_coupon() */
		foreach ( $actions as $action ) {

			if ( doing_action( $action ) ) {

				$removing_coupon = true;
				break;
			}
		}

		// sanity check: just return the default value if we are removing a coupon
		if ( ! $removing_coupon ) {

			// otherwise, handle the return value based on settings
			if ( is_cart() && 'yes' === get_option( 'wc_url_coupons_hide_coupon_field_cart' ) ) {

				$maybe_enabled = false;

				// allow WC to auto-remove invalid coupons even if the admin has opted not to display the field
				// the field will only persist for one page load
				foreach ( WC()->cart->get_applied_coupons() as $code ) {

					$coupon = new WC_Coupon( $code );

					try {
						$maybe_enabled = ! $coupon->is_valid();
					} catch ( Exception $e ) {
						$maybe_enabled = false;
					}
				}
			}

			if ( is_checkout() && 'yes' === get_option( 'wc_url_coupons_hide_coupon_field_checkout' ) ) {
				$maybe_enabled = false;
			}
		}

		return $maybe_enabled;
	}


}
