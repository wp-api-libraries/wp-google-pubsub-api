<?php
/**
 * Library for accessing the Google PubSub API on WordPress
 *
 * @package WP-API-Libraries\WP-Google-PubSub-API
 */

/*
 * Plugin Name: Google PubSub API
 * Plugin URI: https://wp-api-libraries.com/
 * Description: Perform API requests.
 * Author: WP API Libraries
 * Version: 1.0.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/imforza
 * GitHub Branch: master
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'WPGooglePubSubAPI' ) ) {

	/**
	 * A WordPress API library for accessing the Google PubSub API.
	 *
	 * @version 1.1.0
	 * @link https://cloud.google.com/pubsub/docs/overview API Documentation
	 * @package WP-API-Libraries\WP-Google-PubSub-API
	 * @author Santiago Garza <https://github.com/sfgarza>
	 * @author imFORZA <https://github.com/imforza>
	 */
	class WPGooglePubSubAPI {

		/**
		 * API Key.
		 *
		 * @var string
		 */
		protected $access_token;

		/**
		 * PubSub topic.
		 *
		 * @var string
		 */
		protected $topic;

		/**
		 * GCP project name.
		 *
		 * @var string
		 */
		protected $project;

		/**
		 * PubSub BaseAPI Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri = 'https://pubsub.googleapis.com/v1/';

		/**
		 * Route being called.
		 *
		 * @var string
		 */
		protected $route = '';


		/**
		 * Class constructor.
		 *
		 * @param string $access_token  Auth token.
		 */
		public function __construct( $access_token, $project, $topic ) {
			$this->access_token = $access_token;
			$this->project = $project;
			$this->topic   = $topic;
		}

		/**
		 * Prepares API request.
		 *
		 * @param  string $route   API route to make the call to.
		 * @param  array  $args    Arguments to pass into the API call.
		 * @param  array  $method  HTTP Method to use for request.
		 * @return self            Returns an instance of itself so it can be chained to the fetch method.
		 */
		protected function build_request( $route, $args = array(), $method = 'GET' ) {
			// Start building query.
			$this->set_headers();
			$this->args['method'] = $method;
			$this->route = "projects/{$this->project}/topics/{$this->topic}$route";

			// Generate query string for GET requests.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $args ), $route );
			} elseif ( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $args );
			} else {
				$this->args['body'] = $args;
			}

			$this->args['timeout'] = 20;

			return $this;
		}


		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function fetch() {
			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );

			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			$this->clear();
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-postmark-api' ), $code ), $body );
			}

			return $body;
		}

		public function set_topic( $topic ){

		}


		/**
		 * Set request headers.
		 */
		protected function set_headers() {
			// Set request headers.
			$this->args['headers'] = array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . $this->access_token,
			);
		}

		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->args = array();
			$this->query_args = array();
		}

		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}

		/**
		 * Publish
		 *
		 *
		 * @api POST
		 * @access public
		 * @param string $data  Post data to send. Note, either the data field or attributes fields must have content for call to work.
		 * @return array        Updated user info.
		 */
		public function publish( $messages = array() ) {

			foreach($messages as &$message ){
				if( isset( $message['data']) ){
					$message['data'] = (( is_array( $message['data'] ) ) ? json_encode( $message['data'] ) : $message['data']);
					$message['data'] = rtrim( strtr( base64_encode( $message['data'] ), '+/', '-_'), '=');
				}
			}

			return $this->build_request( ":publish", array( 'messages' => $messages ), 'POST' )->fetch();
		}

	}
}
