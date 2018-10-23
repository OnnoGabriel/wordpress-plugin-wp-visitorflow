<?php
/**
 * REST API setup
 *
 * @package WP-VisitorFlow
 */

// Register REST route for app registration
register_rest_route( 'wp-visitorflow/v1', '/register', array(
	// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
	'methods'  => WP_REST_Server::READABLE, // = "GET"
	// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
	'callback' => 'wp_visitorflow_rest_register',
	// Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
	'args' => wp_visitorflow_rest_get_register_arguments(),
) );

// Register REST route for data access
register_rest_route( 'wp-visitorflow/v1', '/stats', array(
	'methods'  => WP_REST_Server::READABLE,
	'callback' => 'wp_visitorflow_rest_stats',
	'args' => wp_visitorflow_rest_get_stats_arguments(),
) );

// Register REST route for favicon access
register_rest_route( 'wp-visitorflow/v1', '/favicon', array(
	'methods'  => WP_REST_Server::READABLE,
	'callback' => 'wp_visitorflow_rest_favicon',
	'args' => wp_visitorflow_rest_get_stats_arguments(),
) );


/**
 * Callback function for REST route:
 * App Registration
 */
function wp_visitorflow_rest_register( $request ) {

	$wpvfConfig = WP_VisitorFlow_Config::getInstance();

    if ( isset( $request['token'] ) && strlen($request['token']) == $wpvfConfig->getSetting('app_token_length') ) {

		$granted_tokens = $wpvfConfig->getSetting('app_granted_tokens');


		// Device already in list?
		$request_device_hash = serialize(array(
			$request['uuid'],
			$request['model'],
			$request['platform'],
			$request['manufacturer']
		));
		foreach($granted_tokens as $token => $value) {
			$this_device_hash = serialize(array(
				$value['uuid'],
				$value['model'],
				$value['platform'],
				$value['manufacturer']
			));
			if ($request_device_hash == $this_device_hash) {
				return new WP_Error( 'rest_invalid', esc_html__( 'device there', 'wp-visitorflow' ), array( 'status' => 400 ) );
			}
		}

		if (! array_key_exists( $request['token'], $granted_tokens)) {
			$new_granted_token = array(
				'register_timestamp' => time(),
				'password' => $wpvfConfig->getSetting('app_new_password'),
				'uuid' => $request['uuid'],
				'model' => $request['model'],
				'platform' => $request['platform'],
				'version' => $request['version'],
				'manufacturer' => $request['manufacturer']
			);
			$granted_tokens[$request['token']] = $new_granted_token;
			$wpvfConfig->setSetting('app_granted_tokens', $granted_tokens, 1);
		}

		return rest_ensure_response( 'access granted'  );
    }

    return new WP_Error( 'rest_invalid', esc_html__( 'Parameters required.', 'wp-visitorflow' ), array( 'status' => 400 ) );
}

/**
 * Get arguments for app registration
 */
function wp_visitorflow_rest_get_register_arguments() {
    $args = array();
    $args['token'] = array(
        'description' => esc_html__( 'Token', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_new_token_arg_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    $args['uuid'] = array(
        'description' => esc_html__( 'Device UUID', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_data_arg_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    $args['model'] = array(
        'description' => esc_html__( 'Model', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_data_arg_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    $args['platform'] = array(
        'description' => esc_html__( 'Platform', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_data_arg_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    $args['version'] = array(
        'description' => esc_html__( 'Version', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_data_arg_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    $args['manufacturer'] = array(
        'description' => esc_html__( 'Manufacturer', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_data_arg_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    return $args;
}



/**
 * Validate New Token (app registration)
 *
 * @param  mixed            $value   Value of the 'filter' argument.
 * @param  WP_REST_Request  $request The current request object.
 * @param  string           $param   Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */
function wp_visitorflow_rest_new_token_arg_validate_callback( $value, $request, $param ) {
    // If the 'token' argument is not a string return an error.
    if ( ! is_string( $value ) ) {
        return new WP_Error( 'rest_invalid_param', esc_html__( 'The filter argument must be a string.', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }

	$wpvfConfig = WP_VisitorFlow_Config::getInstance();

	if ( strlen($value) != $wpvfConfig->getSetting('app_token_length') ) {
        return new wp_error( 'rest_invalid_param', esc_html__( 'invalid token', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }
	if ( $value != $wpvfConfig->getSetting('app_new_token') ) {
        return new WP_Error( 'rest_invalid_param', esc_html__( 'invalid token', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }
	if ( time() > $wpvfConfig->getSetting('app_new_token_expire_timestamp') ) {
        return new WP_Error( 'rest_invalid_param', esc_html__( 'expired token', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }
}

/**
 * Validate Strings (app registration)
 *
 * @param  mixed            $value   Value of the 'filter' argument.
 * @param  WP_REST_Request  $request The current request object.
 * @param  string           $param   Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */

function wp_visitorflow_rest_data_arg_validate_callback( $value, $request, $param ) {
    // If the 'data' argument is not a string return an error.
    if ( ! is_string( $value ) ) {
        return new WP_Error( 'rest_invalid_param', esc_html__( 'invalid string', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }
}


/**
 * Callback function for REST route:
 * Data Access
 */
function wp_visitorflow_rest_stats( $request ) {
	$wpvfConfig = WP_VisitorFlow_Config::getInstance();
    if ( isset( $request['token'] )
	  && strlen($request['token']) == $wpvfConfig->getSetting('app_token_length') ) {

		// Get $db_info
		WP_VisitorFlow_Analysis::init();
		WP_VisitorFlow_Analysis::updateDBInfo();

		$db_info = $wpvfConfig->getSetting('db_info');

		// Get DB object and table names from visitorflow class
		$db = WP_VisitorFlow_Database::getDB();
		$aggregation_table  =WP_VisitorFlow_Database::getTableName('aggregation');

		// Get data for today
		$today = $wpvfConfig->getDatetime('Y-m-d');
		$todays_data = WP_VisitorFlow_Database::getData( $today );

		// Get data from tables
		$chart_data = array();
		$results = $db->get_results($db->prepare("SELECT date, value AS count
												  FROM $aggregation_table
												  WHERE type='visits'
												  AND date>=subdate('%s', interval 30 day)
												  ORDER BY date ASC;",
												  $wpvfConfig->getDatetime() )
									);
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		if ( isset($todays_data['visits']) ) {
			$data[$today] = $todays_data['visits'];
		}
		array_push($chart_data, array('label' => __('Visits', 'wp-visitorflow'),
									  'data' => $data) );

		$results = $db->get_results($db->prepare("SELECT date, value AS count
												  FROM $aggregation_table
												  WHERE type='views-all'
												  AND date>=subdate('%s', interval 30 day)
												  ORDER BY date ASC;",
												  $wpvfConfig->getDatetime() )
									);
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		if ( isset($todays_data['views-all']) ) {
			$data[$today] = $todays_data['views-all'];
		}
		array_push($chart_data, array('label' => __('Page views', 'wp-visitorflow'),
									  'data' => $data) );

		return rest_ensure_response(  json_encode( array('dbinfo' => $db_info,
														 'chartdata' => $chart_data), JSON_UNESCAPED_SLASHES) );


    }

    return new WP_Error( 'rest_invalid', esc_html__( 'Parameters required.', 'wp-visitorflow' ), array( 'status' => 400 ) );
}


/**
 * Get arguments for data access
 */
function wp_visitorflow_rest_get_stats_arguments() {
    $args = array();
    $args['token'] = array(
        'description' => esc_html__( 'Token', 'wp-visitorflow' ),
        'type'        => 'string',
        'required'    => true,
        'validate_callback' => 'wp_visitorflow_rest_token_validate_callback',
        'sanitize_callback' => 'wp_visitorflow_rest_data_arg_sanitize_callback',
    );
    return $args;
}


/**
 * Validate Token
 *
 * @param  mixed            $value   Value of the 'filter' argument.
 * @param  WP_REST_Request  $request The current request object.
 * @param  string           $param   Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */
function wp_visitorflow_rest_token_validate_callback( $value, $request, $param ) {
    // If the 'data' argument is not a string return an error.
    if ( ! is_string( $value ) ) {
        return new WP_Error( 'rest_invalid_param', esc_html__( 'The filter argument must be a string.', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }

    $wpvfConfig = WP_VisitorFlow_Config::getInstance();
	$granted_tokens = $wpvfConfig->getSetting('app_granted_tokens');
	$access = false;
	foreach($granted_tokens as $token => $data) {
		if ($token == $value) {
			$access = true;
		}
	}
	if ( ! $access ) {
        return new WP_Error( 'rest_invalid_param', esc_html__( 'invalid token', 'wp-visitorflow' ), array( 'status' => 400 ) );
    }

}


/**
 * Sanitize a request argument based on details registered to the route.
 *
 * @param  mixed            $value   Value of the 'filter' argument.
 * @param  WP_REST_Request  $request The current request object.
 * @param  string           $param   Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */
function wp_visitorflow_rest_data_arg_sanitize_callback( $value, $request, $param ) {
    // It is as simple as returning the sanitized value.
    return sanitize_text_field( $value );
}

/**
 * Callback function for REST route:
 * Get Favicon URL
 */
function wp_visitorflow_rest_favicon( $request ) {
   	if (function_exists("get_site_icon_url") ) {
		return rest_ensure_response(  json_encode( array('favicon_url' => get_site_icon_url()), JSON_UNESCAPED_SLASHES) );
    }
    return new WP_Error( 'rest_invalid', esc_html__( 'WP Version too old.', 'wp-visitorflow' ), array( 'status' => 400 ) );
}



?>
