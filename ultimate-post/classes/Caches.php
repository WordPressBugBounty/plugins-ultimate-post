<?php
/**
 * Cache Designs/Templates/Starter Sites.
 *
 * @package ULTP\Caches
 * @since v.1.0.0
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Caches class.
 *
 * Handles caching for Ultimate Post plugin.
 *
 * @package ULTP\Caches
 * @since 4.0.0
 */
class Caches {
	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'caches_register_rest_route' ) );
	}

	/**
	 * Register REST API route for fetching or updating templates/starter sites.
	 *
	 * @since v.1.0.0
	 * @return void No return value.
	 */
	public function caches_register_rest_route() {
		register_rest_route(
			'ultp/v2',
			'/fetch_premade_data/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'fetch_premade_data_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_others_posts' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Fetch premade data callback.
	 *
	 * @since 4.0.0
	 * @param ARRAY $request The REST request object.
	 * @return ARRAY Premade data (templates/starter sites).
	 */
	public function fetch_premade_data_callback( $request ) {
		$post = $request->get_params();
		$type = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';

		if ( $type == 'fetch_all_data' ) {
			return $this->fetch_all_data_callback( array() );
		} else {
			try {
				$upload_dir_url = wp_upload_dir();
				$dir            = trailingslashit( $upload_dir_url['basedir'] ) . 'ultp/';

				/* sync after 3 days */
				if ( file_exists( trailingslashit( wp_upload_dir()['basedir'] ) . 'ultp/starter_lists.json' ) &&
					time() - filemtime( trailingslashit( wp_upload_dir()['basedir'] ) . 'ultp/starter_lists.json' ) >= 2 * DAY_IN_SECONDS
				) {
					$this->fetch_all_data_callback( array() );
				}

				if ( $type == 'get_starter_lists_nd_design' ) {
					$data = array();
					foreach ( array( 'starter_lists', 'design' ) as $name ) {
						$value = $this->get_premade_data( $name, $dir );
						if ( is_array( $value ) ) {
							return $value;
						}
						$data[ $name ] = $value;
					}
					return array(
						'success'  => true,
						'success2' => is_admin(),
						'data'     => $data,
					);
				}
				// $type names a file, so only the known cache files are accepted.
				if ( ! in_array( $type, array( 'starter_lists', 'design' ), true ) ) {
					return array(
						'success' => false,
						'message' => __( 'Unknown data type.', 'ultimate-post' ),
					);
				}
				$data = $this->get_premade_data( $type, $dir );
				return is_array( $data ) ? $data : array(
					'success' => true,
					'data'    => $data,
				);
			} catch ( \Throwable $e ) {
				return array(
					'success' => false,
					'message' => $e->getMessage(),
				);
			}
		}
	}

	/**
	 * Read a usable cache, or fetch it without disguising a failed write as success.
	 *
	 * @param string $type Known cache name.
	 * @param string $dir Cache directory.
	 * @return string|array JSON data or error response.
	 */
	private function get_premade_data( $type, $dir ) {
		$data = file_exists( $dir . $type . '.json' ) ? ultimate_post()->get_path_file_contents( $dir . $type . '.json' ) : '';
		return is_string( $data ) && '' !== $data ? $data : $this->reset_premade_json_file( $type );
	}

	/**
	 * Reset data from API.
	 *
	 * @since v.2.4.4
	 * @param ARRAY $request The REST request object.
	 * @return ARRAY Data of the design.
	 */
	public function fetch_all_data_callback( $request ) {
		$result = $this->reset_premade_json_file( 'all' );
		if ( is_array( $result ) && empty( $result['success'] ) ) {
			return $result;
		}
		// Keep the last cache until replacement succeeds; a failed fetch must not erase it.
		return array(
			'success' => true,
			'message' => __( 'Data Fetched!!!', 'ultimate-post' ),
		);
	}

	/**
	 * Get and save source data from the file or API.
	 *
	 * @since v.1.0.0 Updated from 4.0.0
	 * @param STRING $type The type of data to reset.
	 * @return string|array JSON data for a single file, or operation status/error.
	 */
	public function reset_premade_json_file( $type = 'all' ) {
		global $wp_filesystem;

		$errors     = array();
		$file_names = $type == 'all' ? array( 'starter_lists', 'design' ) : array( $type );
		foreach ( $file_names as $key => $name ) {
			if ( $name == 'starter_lists' ) {
				$response = wp_remote_post(
					'https://postxkit.wpxpo.com/wp-json/importer/site_lists',
					array(
						'method'  => 'POST',
						'timeout' => 120,
						'body'    => array(
							'ultp_ver' => ULTP_VER,
						),
					)
				);
			} else {
				$response = wp_remote_post(
					'https://postxkit.wpxpo.com/wp-json/restapi/v2/design',
					array(
						'method'  => 'POST',
						'timeout' => 120,
					)
				);
			}
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_string( wp_remote_retrieve_body( $response ) ) ) {
				$errors[] = __( 'PostX could not fetch its template data. The existing cache was retained.', 'ultimate-post' );
				continue;
			}
			$body = wp_remote_retrieve_body( $response );
			json_decode( $body );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$errors[] = __( 'PostX received invalid template data. The existing cache was retained.', 'ultimate-post' );
				continue;
			}
			$path_url = $this->create_directory_for_premade( $name );
			if ( ! is_string( $path_url ) ) {
				$errors[] = isset( $path_url['message'] ) ? $path_url['message'] : __( 'PostX could not access its data folder.', 'ultimate-post' );
				continue;
			}
			if ( ! $wp_filesystem->put_contents( $path_url . $name . '.json', $body ) ) {
				$errors[] = __( 'PostX could not save its template data. Check the uploads folder permissions and try again.', 'ultimate-post' );
				continue;
			}
			if ( 'all' !== $type ) {
				return $body;
			}
		}
		return array(
			'success' => empty( $errors ),
			'message' => implode( ' ', array_unique( $errors ) ),
		);
	}

	/**
	 * Create a directory in the upload folder.
	 *
	 * @since v.1.0.0 Updated from 4.0.0
	 * @param STRING $type The file name or type.
	 * @return STRING|ARRAY Directory path or error array.
	 */
	public function create_directory_for_premade( $type = '' ) {
		try {
			global $wp_filesystem;
			$upload_dir_url = wp_upload_dir();
			$dir            = trailingslashit( $upload_dir_url['basedir'] ) . 'ultp/';
			if ( ! ultimate_post()->ensure_filesystem( $upload_dir_url['basedir'], true ) ) {
				return array(
					'success' => false,
					'message' => __( 'PostX could not access the WordPress filesystem to save its data files. Check the uploads folder permissions or the server filesystem settings.', 'ultimate-post' ),
				);
			}
			if ( ! $wp_filesystem->is_dir( $dir ) && ! $wp_filesystem->mkdir( $dir ) ) {
				return array(
					'success' => false,
					'message' => __( 'PostX could not create its data folder in the uploads directory. Check the uploads folder permissions.', 'ultimate-post' ),
				);
			}
			if ( ! $wp_filesystem->exists( $dir . $type . '.json' ) && ! $wp_filesystem->put_contents( $dir . $type . '.json', '' ) ) {
				return array(
					'success' => false,
					'message' => __( 'PostX could not write its data file in the uploads directory. Check the uploads folder permissions.', 'ultimate-post' ),
				);
			}
			return $dir;
		} catch ( \Throwable $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
}
