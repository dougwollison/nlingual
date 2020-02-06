<?php
/**
 * REST API: nLingual Languages controller
 *
 * @package nLingual
 * @subpackage REST
 * @since 2.9.0
 */

namespace nLingual;

/**
 * For accessing languages via the REST API.
 *
 * @since 2.9.0
 *
 * @see WP_REST_Controller
 */
final class REST_Languages_Controller extends \WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		$this->namespace = 'nlingual';
		$this->rest_base = 'languages';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 2.9.0
	 */
	public function register_routes() {
		// do something
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		// do something
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		// do something
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		// do something
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		// do something
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		// do something
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		// do something
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		// do something
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		// do something
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		// do something
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		// do something
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|object The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request ) {
		// do something
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @since 2.9.0
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		// do something
	}
}
