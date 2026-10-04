<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ultp_chatgpt_init' );
function ultp_chatgpt_init() {
	if ( ultimate_post()->get_setting( 'ultp_chatgpt' ) == 'true' ) {
		add_filter( 'ultp_settings', 'ultp_get_chatgpt_settings', 10, 1 );
		add_action( 'rest_api_init', 'ultp_chatgpt_register_route' );
	}
}

/**
 * Register the route the editor uses to ask OpenAI.
 *
 * The OpenAI key stays on the server: the editor sends only the prompt, and
 * this route adds the key and the saved model settings.
 */
function ultp_chatgpt_register_route() {
	register_rest_route(
		'ultp/v2',
		'/chatgpt/',
		array(
			'methods'             => 'POST',
			'callback'            => 'ultp_chatgpt_request',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'prompt' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
		)
	);
}

/**
 * Send a prompt to OpenAI with the saved key and settings.
 *
 * Returns OpenAI's `choices` on success, or `error.code` (plus the message for
 * a missing model) on failure, which is the shape the editor already reads.
 * OpenAI's other error messages are not passed on, as some quote part of the key.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function ultp_chatgpt_request( $request ) {
	$api_key = ultimate_post()->get_setting( 'chatgpt_secret_key' );
	if ( empty( $api_key ) ) {
		return rest_ensure_response( array( 'error' => array( 'code' => 'invalid_api_key' ) ) );
	}

	$prompt = trim( wp_check_invalid_utf8( (string) $request->get_param( 'prompt' ) ) );
	if ( '' === $prompt ) {
		return new WP_Error( 'ultp_chatgpt_empty_prompt', __( 'The prompt is empty.', 'ultimate-post' ), array( 'status' => 400 ) );
	}
	$prompt = mb_substr( $prompt, 0, 20000 );

	$model = ultimate_post()->get_setting( 'chatgpt_model' );
	if ( ! in_array( $model, array( 'gpt-3.5-turbo', 'gpt-4', 'text-davinci-002', 'text-davinci-003' ), true ) ) {
		$model = 'gpt-3.5-turbo';
	}
	$timeout = absint( ultimate_post()->get_setting( 'chatgpt_response_time' ) );
	$timeout = $timeout ? min( max( $timeout, 5 ), 120 ) : 35;

	if ( 0 === strpos( $model, 'text-davinci' ) ) {
		$max_tokens = absint( ultimate_post()->get_setting( 'chatgpt_max_tokens' ) );
		$url        = 'https://api.openai.com/v1/completions';
		$body       = array(
			'model'      => $model,
			'prompt'     => $prompt,
			'max_tokens' => $max_tokens ? min( $max_tokens, 4096 ) : 200,
		);
	} else {
		$url  = 'https://api.openai.com/v1/chat/completions';
		$body = array(
			'model'    => $model,
			'messages' => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);
	}

	$response = wp_safe_remote_post(
		$url,
		array(
			'timeout' => $timeout,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return rest_ensure_response( array( 'error' => array( 'code' => 'request_failed' ) ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! empty( $data['choices'] ) && is_array( $data['choices'] ) ) {
		return rest_ensure_response( array( 'choices' => $data['choices'] ) );
	}

	$code  = isset( $data['error']['code'] ) ? sanitize_key( $data['error']['code'] ) : 'request_failed';
	$error = array( 'code' => $code );
	if ( 'model_not_found' === $code && isset( $data['error']['message'] ) ) {
		$error['message'] = sanitize_text_field( $data['error']['message'] );
	}
	return rest_ensure_response( array( 'error' => $error ) );
}

function ultp_get_chatgpt_settings( $config ) {
	$arr = array(
		'ultp_chatgpt' => array(
			'label' => __( 'ChatGPT', 'ultimate-post' ),
			'attr'  => array(
				'chatgpt_heading'       => array(
					'type'  => 'heading',
					'label' => __( 'ChatGPT Settings', 'ultimate-post' ),
				),
				'chatgpt_secret_key'    => array(
					'type'     => 'text',
					'label'    => __( 'OpenAI API Secret Key', 'ultimate-post' ),
					'desc'     => __( 'For using ChatGPT addon, you have to add your OpenAI secret key.', 'ultimate-post' ),
					'link'     => 'https://platform.openai.com/account/api-keys',
					'linkText' => __( 'Get key.', 'ultimate-post' ),
				),
				'chatgpt_model'         => array(
					'type'    => 'select',
					'label'   => __( 'OpenAI Model', 'ultimate-post' ),
					'desc'    => __( 'Choose your preferable OpenAI model', 'ultimate-post' ),
					'options' => array(
						'gpt-3.5-turbo'    => 'gpt-3.5-turbo',
						'text-davinci-002' => 'text-davinci-002',
						'text-davinci-003' => 'text-davinci-003',
						'gpt-4'            => 'gpt-4',
					),
					'default' => 'gpt-3.5-turbo',
				),
				'chatgpt_response_time' => array(
					'type'    => 'number',
					'label'   => __( 'Response Time', 'ultimate-post' ),
					'desc'    => __( 'Choose your preferable OpenAI response time', 'ultimate-post' ),
					'default' => 60,
				),
				'chatgpt_max_tokens'    => array(
					'type'    => 'number',
					'label'   => __( 'Max Tokens', 'ultimate-post' ),
					'desc'    => __( 'Choose your preferable OpenAI max Number of Tokens to be generated by ChatGPT', 'ultimate-post' ),
					'default' => 400,
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
