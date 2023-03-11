<?php 

defined('ABSPATH') || exit;

function cb_member_search( $args = '' ) {


	$r = wp_parse_args( $args, array(
		'type'				=> 'alphabetical',
		'search_terms'		=> '',
		'exclude'			=> '',
		'search_wildcard'	=> 'both',
		'count_total'		=> 'sql_count_found_rows',
		'per_page'			=> 8,
		'error_type' 		=> 'bool',
	));


	if ( empty( $r['search_terms'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {

			$error_code = 'transactions_search_terms';
			$feedback   = __('We need something to search for! Try looking someone up by their first and/or last name.', 'confetti-bits');

			return new WP_Error( $error_code, $feedback );
		} else {
			return false;
		}
	}

	$member_search = new BP_User_Query( $r );

	$member_search->__construct();

	return $member_search->results;
}

function cb_transactions_new_member_search() {

	if ( !bp_is_post_request() || !cb_is_confetti_bits_component() || ! isset( $_POST['cb_member_search_submit'] ) ) {
		return false;
	}

	$redirect_to = '';
	$feedback    = '';
	$success     = false;

	if ( empty( trim( $_POST['cb_member_search_terms'] ) ) ) {

		$feedback   = __('Your search didn\'t go through – we can\'t seem to locate any vibes associated with "(abject nonexistence)." :/', 'confetti-bits');

	} else {

		$member_transactions = trailingslashit( bp_loggedin_user_domain() ) . cb_get_transactions_slug() . '/#cb-send-bits';

		$exclude_user = ( cb_is_user_site_admin() ? '' : get_current_user_id() );

		$search = cb_member_search(
			array(
				'type' 				=> 'alphabetical',
				'search_terms'		=> trim($_POST['cb_member_search_terms']),
				'exclude'			=> $exclude_user,
				'search_wildcard'	=> 'both',
				'count_total' 		=> 'sql_count_found_rows',
				'per_page'			=> 8,
				'error_type'		=> 'wp_error',
			)
		);

		if ( true === is_array( $search ) ) {

			$search_count = count( $search );

			if ( $search_count === 0 ) {

				$success     = false;
				$feedback    = __(
					'I\'m sorry, I couldn\'t find a gosh darn thing from searching "' .
					$_POST['cb_member_search_terms'] .
					'" :/',
					'confetti-bits'
				);
			}

			if ( $search_count === 1 ) {

				$success     = true;
				$feedback    = __(
					'I found ' . $search_count .
					' lone ranger from looking up "' .
					$_POST['cb_member_search_terms'] .
					'". I hope they fare well in their travels.',
					'confetti-bits'
				);
			}

			if ( $search_count > 1 && $search_count < 8 ) {

				$success     = true;
				$feedback    = __(
					'I found ' .
					$search_count .
					' awesome folks from searching "' .
					$_POST['cb_member_search_terms'] .
					'":',
					'confetti-bits'
				);
			}

			if ( $search_count >= 8 ) {

				$success     = true;
				$feedback    = __(
					'I found a whole bunch of people (more than ' .
					$search_count . ') from searching "' .
					$_POST['cb_member_search_terms'] .
					'". If you can\'t find who you\'re looking for, 
		try typing in a first and last name!',
					'confetti-bits'
				);
			}

			$redirect_to = $member_transactions;
		} else {
			$success  = false;
			$feedback = __( 'Something\'s wonky. Call Dustin.', 'confetti-bits' );
		}
	}

	if ( !empty( $feedback ) ) {

		$type = ( true === $success )
			? 'success'
			: 'error';

		bp_core_add_message( $feedback, $type );
	}

	if ( ! empty( $redirect_to ) ) {
		bp_core_redirect( add_query_arg(array(
			'results' => $type,
			'search_terms' => trim( $_POST['cb_member_search_terms'] )
		), $redirect_to ) );
	}
}
add_action('bp_actions', 'cb_transactions_new_member_search');


function cb_get_member_search_results( $search_results = array() ) {

	if ( !cb_is_confetti_bits_component() || !cb_is_user_confetti_bits() ) {

		return;

	}


	if (isset($_GET['results']) && 'success' === $_GET['results'] && isset($_GET['search_terms'])) {

		$exclude_user = (cb_is_user_site_admin() ? '' : get_current_user_id());

		$search_results = cb_member_search(
			array(
				'type' => 'alphabetical',
				'search_terms' => $_GET['search_terms'],
				'exclude' => $exclude_user,
				'search_wildcard' => 'both',
				'count_total' => 'sql_count_found_rows',
				'per_page' => 8,
				'error_type' 		=> 'wp_error',
			)
		);

		if ( empty( $search_results ) ) {

			return;
		} else {


			foreach ($search_results as $member) {

				$member_id = $member->ID;
				$member_display_name = bp_xprofile_get_member_display_name($member->ID);
				$member_avatar = bp_core_fetch_avatar(
					array(
						'item_id' => $member->ID,
						'object'  => 'user',
						'type'    => 'thumb',
						'width'   => BP_AVATAR_THUMB_WIDTH,
						'height'  => BP_AVATAR_THUMB_HEIGHT,
						'html'    => true,
					)
				);

				echo sprintf(
					'<div class="memberSelect send-bits member-data" 
		data-member-id="%d"
		data-member-display-name="%s"
		id="member-data-%d">
		<div class="cb-search-results-avatar">%s</div>
		<p class="memberName">%s</p>
		</div>',
					$member_id,
					$member_display_name,
					$member_id,
					$member_avatar,
					$member_display_name
				);
			}
		}
	}
}

function cb_search_results() {
	echo cb_get_member_search_results();
}