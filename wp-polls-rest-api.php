<?php
	
	defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );
/**
 * Plugin Name: Wp Pool Rest Api
 * Description: Wp Pool Rest Api for be able to used it insde a APP
 * Author:      FunAndProg
 * Author URI:  http://funandprog.fr
 * License:     GNU General Public License v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
/**********************************************************************
*
*                               Poll
*
**********************************************************************/
function get_poll_template_by_me($poll_id, $display_loading = true)
{
    global $wpdb;
    $data = array();
    // Temp Poll Result
    $temp_pollvote = '';
    // Get Poll Question Data
    $poll_question = $wpdb->get_row( $wpdb->prepare( "SELECT pollq_id, pollq_question,  pollq_totalvotes, pollq_active, pollq_timestamp, pollq_expiry, pollq_multiple, pollq_totalvoters FROM $wpdb->pollsq WHERE pollq_id = %d LIMIT 1", $poll_id ) );
    // Poll Question Variables
    $poll_question_text = wp_kses_post( removeslashes( $poll_question->pollq_question ) );
    $poll_question_id = (int) $poll_question->pollq_id;
    $poll_question_totalvotes = (int) $poll_question->pollq_totalvotes;
    $poll_question_totalvoters = (int) $poll_question->pollq_totalvoters;
    $poll_start_date = mysql2date(sprintf(__('%s @ %s', 'wp-polls'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $poll_question->pollq_timestamp));
    $poll_expiry = trim($poll_question->pollq_expiry);
    if(empty($poll_expiry)) {
        $poll_end_date  = __('No Expiry', 'wp-polls');
    } else {
        $poll_end_date  = mysql2date(sprintf(__('%s @ %s', 'wp-polls'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $poll_expiry));
    }
    $poll_multiple_ans = (int) $poll_question->pollq_multiple;
    $template_question = removeslashes(get_option('poll_template_voteheader'));
    $template_question = apply_filters('poll_template_voteheader_markup', $template_question, $poll_question, array(
        '%POLL_QUESTION%' => $poll_question_text,
        '%POLL_ID%' => $poll_question_id,
        '%POLL_TOTALVOTES%' => $poll_question_totalvotes,
        '%POLL_TOTALVOTERS%' => $poll_question_totalvoters,
        '%POLL_START_DATE%' => $poll_start_date,
        '%POLL_END_DATE%' => $poll_end_date,
        '%POLL_MULTIPLE_ANS_MAX%' => $poll_multiple_ans > 0 ? $poll_multiple_ans : 1
    ));
    // Get Poll Answers Data
    list($order_by, $sort_order) = _polls_get_ans_sort();
    $poll_answers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pollsa WHERE polla_qid = %d ORDER BY $order_by $sort_order", $poll_question_id ) );
    //$data['template_question'] = $template_question;
    $data['multiple_ans'] = $poll_multiple_ans;
    $data['question'] = $poll_question->pollq_question;
    $data['id'] = $poll_question->pollq_id;
    $data['answers'] = $poll_answers;
    $data['pollq_totalvotes'] = $poll_question->pollq_totalvotes;
    $poll_active= (int) $poll_question->pollq_active;
		if($poll_active === 1) {
            $data['status'] = 'Open';
        } elseif($poll_active === -1) {
            //_e('Future', 'wp-polls');
            $data['status']= 'Future';
        } else {
            //_e('Closed', 'wp-polls');
            $data['status']= 'Closed';
        }

    return $data;
}
function display_pollresult_by_me($poll_id, $user_voted = '', $display_loading = true) {
    global $wpdb;
    $poll_id = (int) $poll_id;
    // User Voted
    
    
    if( empty( $user_voted ) ) {
        $user_voted = array();
    }
    if ( is_array( $user_voted ) ) {
        $user_voted = array_map( 'intval', $user_voted );
    } else {
        $user_voted = (int) $user_voted;
    }
    // Temp Poll Result
    $temp_pollresult = '';
    // Most/Least Variables
    $poll_most_answer = '';
    $poll_most_votes = 0;
    $poll_most_percentage = 0;
    $poll_least_answer = '';
    $poll_least_votes = 0;
    $poll_least_percentage = 0;
    // Get Poll Question Data
    $poll_question = $wpdb->get_row( $wpdb->prepare( "SELECT pollq_id, pollq_question, pollq_totalvotes, pollq_active, pollq_timestamp, pollq_expiry, pollq_multiple, pollq_totalvoters FROM $wpdb->pollsq WHERE pollq_id = %d LIMIT 1", $poll_id ) );
    // No poll could be loaded from the database
    if (!$poll_question) {
        return removeslashes(get_option('poll_template_disable'));
    }
    // Poll Question Variables
    $poll_question_text = wp_kses_post( removeslashes( $poll_question->pollq_question ) );
    $poll_question_id = (int) $poll_question->pollq_id;
    $poll_question_totalvotes = (int) $poll_question->pollq_totalvotes;
    $poll_question_totalvoters = (int) $poll_question->pollq_totalvoters;
    $poll_question_active = (int) $poll_question->pollq_active;
    $poll_start_date = mysql2date(sprintf(__('%s @ %s', 'wp-polls'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $poll_question->pollq_timestamp));
    $poll_expiry = trim($poll_question->pollq_expiry);
    if(empty($poll_expiry)) {
        $poll_end_date  = __('No Expiry', 'wp-polls');
    } else {
        $poll_end_date  = mysql2date(sprintf(__('%s @ %s', 'wp-polls'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $poll_expiry));
    }
    $poll_multiple_ans = (int) $poll_question->pollq_multiple;
    $template_question = removeslashes(get_option('poll_template_resultheader'));
    $template_question = str_replace("%POLL_QUESTION%", $poll_question_text, $template_question);
    $template_question = str_replace("%POLL_ID%", $poll_question_id, $template_question);
    $template_question = str_replace("%POLL_TOTALVOTES%", $poll_question_totalvotes, $template_question);
    $template_question = str_replace("%POLL_TOTALVOTERS%", $poll_question_totalvoters, $template_question);
    $template_question = str_replace("%POLL_START_DATE%", $poll_start_date, $template_question);
    $template_question = str_replace("%POLL_END_DATE%", $poll_end_date, $template_question);
    if($poll_multiple_ans > 0) {
        $template_question = str_replace("%POLL_MULTIPLE_ANS_MAX%", $poll_multiple_ans, $template_question);
    } else {
        $template_question = str_replace("%POLL_MULTIPLE_ANS_MAX%", '1', $template_question);
    }
    // Get Poll Answers Data
    list($order_by, $sort_order) = _polls_get_ans_result_sort();
    $poll_answers = $wpdb->get_results( $wpdb->prepare( "SELECT polla_aid, polla_answers, polla_votes FROM $wpdb->pollsa WHERE polla_qid = %d ORDER BY $order_by $sort_order", $poll_question_id ) );
    
    $ll = array();
    for ($i=0; $i < count($poll_answers); $i++) { 
        $ll[$i]['per'] = round((($poll_answers[$i]->polla_votes/$poll_question_totalvotes)*100));
        $poll_answers[$i] = array_merge( (array) $poll_answers[$i] , (array) $ll[$i] );
    }
    return  $poll_answers;
}
add_action( 'rest_api_init', function () {
	$args = array(
		'methods' => WP_REST_Server::READABLE,
		'callback' => 'get_all_polls',
	);
	register_rest_route( 'wp/v2', '/polls/', $args );
    register_rest_route( 'wp/v2', '/poll/', 
        array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => 'get_polls',
            ),
            array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'post_polls',
            )
      
        )
    );
});

function get_all_polls(WP_REST_Request $request){
	global $wpdb;
	$polls = $wpdb->get_results( "SELECT * FROM $wpdb->pollsq  ORDER BY pollq_timestamp DESC" );
	$data = array();
	foreach($polls as $poll) {
		$poll_id = (int) $poll->pollq_id;
		$newData['poll'] = get_poll_template_by_me($poll_id, true);
		$newData['result'] = rest_api_display_pollresult($params['id']);
		$newData['voted'] = if_user_voted($poll_id);
		
		$poll_voters = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pollip_user FROM $wpdb->pollsip WHERE pollip_qid = %d AND pollip_user != %s ORDER BY pollip_user ASC", $poll_id, __( 'Guest', 'wp-polls' ) ) );
		
		$newData['poll_voters'] = $poll_voters;
		
		$data[]= $newData;
		
	}
	return $data;
}


function get_polls(WP_REST_Request $request){
	global $wpdb;
	
    $params = $request->get_params();
    // return get_yop_poll_meta(2, 'options', true );
    //$data = array();
    //$data[] = get_poll_template_by_me($params['id']);
    $data['poll'] = get_poll_template_by_me($params['id'], true);
	$data['result'] =  rest_api_display_pollresult($params['id']);
	$data['voted'] = if_user_voted($params['id']);
	
	
	
    return $data;
           
}

### Funcrion: Check Voted By Cookie Or IP
function if_user_voted($poll_id) {

	$poll_logging_method = (int) get_option( 'poll_logging_method' );
	switch($poll_logging_method) {
		// Do Not Log
		case 0:
			return false;
			break;
		// Logged By Cookie
		case 1:
			return check_voted_cookie($poll_id);
			break;
		// Logged By IP
		case 2:
			return  check_voted_ip($poll_id);
			break;
		// Logged By Cookie And IP
		case 3:
			$check_voted_cookie = check_voted_cookie($poll_id);
			if(!empty($check_voted_cookie)) {
				return  $check_voted_cookie;
			} else {
				return false;
			}
			//return check_voted_ip($poll_id);
			break;
		// Logged By Username
		case 4:
			return check_voted_username($poll_id);
			break;
	}

}


function post_polls( WP_REST_Request  $request ){
	$params = $request->get_params();

	$return['params'] = $params;
	
    $params = $request->get_params();
    $poll_id = $params['id'];
    global $wpdb, $user_identity, $user_ID;
    $user_ID = $params['user_id'];
    if( isset( $params['action'] ) && sanitize_key( $params['action'] ) === 'polls') {
	    
        // Load Headers
        
        // Get Poll ID
        $poll_id = (isset($poll_id) ? (int) sanitize_key( $poll_id ) : 0);
        // Ensure Poll ID Is Valid
        if($poll_id === 0) {
            $return['error'] = 'Invalid Poll ID';
            //gexit();
            //return get_poll(0,false)
        }
       
        // Which View
        switch( sanitize_key( $params['view'] ) ) {
            // Poll Vote
            case 'process':
                $poll_aid_array = array_unique( array_map('intval', array_map('sanitize_key', explode( ',', $params["poll_$poll_id"] ) ) ) );
                
                $polla_aids = $wpdb->get_col( $wpdb->prepare( "SELECT polla_aid FROM $wpdb->pollsa WHERE polla_qid = %d", $poll_id ) );
                
                $is_real = count( array_intersect( $poll_aid_array, $polla_aids ) ) === count( $poll_aid_array );
                // The multiple ifs is ugly, I know it.  Feel free to send a PR to fix it
                if( $is_real ) {
                    if($poll_id > 0 && !empty($poll_aid_array) && check_allowtovote()) {
                        $is_poll_open = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pollsq WHERE pollq_id = %d AND pollq_active = 1", $poll_id ) );
                        if ( $is_poll_open > 0 ) {
                            $check_voted = if_user_voted($poll_id);
                            if ( empty( $check_voted ) ) {
                                if (!empty($user_ID)) {
	                                $user = get_userdata( $user_ID );
                                    $pollip_user = $user->user_login;
                                } elseif ( ! empty( $_COOKIE['comment_author_' . COOKIEHASH] ) ) {
                                    $pollip_user = $_COOKIE['comment_author_' . COOKIEHASH];
                                } else {
                                    $pollip_user = __('Guest', 'wp-polls');
                                }
                                $pollip_user = sanitize_text_field( $pollip_user );
                                $pollip_userid = (int) $user_ID;
                                $pollip_ip = get_ipaddress();
                                $pollip_host = @gethostbyaddr($pollip_ip);
                                $pollip_timestamp = current_time('timestamp');
                                // Only Create Cookie If User Choose Logging Method 1 Or 2
                                $poll_logging_method = (int) get_option('poll_logging_method');
                                if ($poll_logging_method === 1 || $poll_logging_method === 3) {
                                    $cookie_expiry = (int) get_option('poll_cookielog_expiry');
                                    if ($cookie_expiry === 0) {
                                        $cookie_expiry = YEAR_IN_SECONDS;
                                    }
                                    setcookie( 'voted_' . $poll_id, implode(',', $poll_aid_array ), $pollip_timestamp + $cookie_expiry, apply_filters( 'wp_polls_cookiepath', SITECOOKIEPATH ) );
                                }
                                $i = 0;
                                foreach ($poll_aid_array as $polla_aid) {
                                    $update_polla_votes = $wpdb->query( "UPDATE $wpdb->pollsa SET polla_votes = (polla_votes + 1) WHERE polla_qid = $poll_id AND polla_aid = $polla_aid" );
                                    if (!$update_polla_votes) {
                                        unset($poll_aid_array[$i]);
                                    }
                                    $i++;
                                }
                                $vote_q = $wpdb->query("UPDATE $wpdb->pollsq SET pollq_totalvotes = (pollq_totalvotes+" . count( $poll_aid_array ) . "), pollq_totalvoters = (pollq_totalvoters + 1) WHERE pollq_id = $poll_id AND pollq_active = 1");
                                if ($vote_q) {
                                    foreach ($poll_aid_array as $polla_aid) {
                                        $wpdb->insert(
                                            $wpdb->pollsip,
                                            array(
                                                'pollip_qid'        => $poll_id,
                                                'pollip_aid'        => $polla_aid,
                                                'pollip_ip'      => $pollip_ip,
                                                'pollip_host'      => $pollip_host,
                                                'pollip_timestamp'  => $pollip_timestamp,
                                                'pollip_user'      => $pollip_user,
                                                'pollip_userid'  => $pollip_userid
                                            ),
                                            array(
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%d'
                                            )
                                        );
                                    }
                                    return $return['success'] ='Thanks you for your vote';
                                    
                                } else {
                                    $return['error'] =  'Unable to update survey vote.';
                                } // End if($vote_a)
                            } else {
                                $return['error'] =   'You Had Already Voted For This Survey.' ;
                            } // End if($check_voted)
                        } else {
                            $return['error'] =  'This Survey is Closed ';
                        }  // End if($is_poll_open > 0)
                    } else {
                        $return['error'] =  'Invalid Survey ';
                    } // End if($poll_id > 0 && !empty($poll_aid_array) && check_allowtovote())
                } else {
                    $return['error'] =  'Invalid Answer for this Survey ';
                } //End if(!isRealAnswer($poll_id,$poll_aid))
                break;
            // Poll Result
            case 'result':
                $return['result'] =  display_pollresult_by_me($poll_id, 0, false);
                break;
        } // End switch($params['view'])
    } // End if(isset($params['action']) && $params['action'] == 'polls')
    
    return $return;
    
}

function rest_api_display_pollresult($poll_id) {
	$user_voted = if_user_voted($poll_id);
	$display_loading = false;
	global $wpdb;
	do_action( 'wp_polls_display_pollresult', $poll_id, $user_voted );
	$poll_id = (int) $poll_id;
	// User Voted
	if( empty( $user_voted ) ) {
		$user_voted = array();
	}
	if ( is_array( $user_voted ) ) {
		$user_voted = array_map( 'intval', $user_voted );
	} else {
		$user_voted = (int) $user_voted;
	}

	// Temp Poll Result
	$temp_pollresult = '';
	// Most/Least Variables
	$poll_most_answer = '';
	$poll_most_votes = 0;
	$poll_most_percentage = 0;
	$poll_least_answer = '';
	$poll_least_votes = 0;
	$poll_least_percentage = 0;
	// Get Poll Question Data
	$poll_question = $wpdb->get_row( $wpdb->prepare( "SELECT pollq_id, pollq_question, pollq_totalvotes, pollq_active, pollq_timestamp, pollq_expiry, pollq_multiple, pollq_totalvoters FROM $wpdb->pollsq WHERE pollq_id = %d LIMIT 1", $poll_id ) );
	// No poll could be loaded from the database
	if (!$poll_question) {
		return removeslashes(get_option('poll_template_disable'));
	}
	// Poll Question Variables
	$poll_question_text = wp_kses_post( removeslashes( $poll_question->pollq_question ) );
	$poll_question_id = (int) $poll_question->pollq_id;
	$poll_question_totalvotes = (int) $poll_question->pollq_totalvotes;
	$poll_question_totalvoters = (int) $poll_question->pollq_totalvoters;
	$poll_question_active = (int) $poll_question->pollq_active;
	$poll_start_date = mysql2date(sprintf(__('%s @ %s', 'wp-polls'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $poll_question->pollq_timestamp));
	$poll_expiry = trim($poll_question->pollq_expiry);
	if(empty($poll_expiry)) {
		$poll_end_date  = __('No Expiry', 'wp-polls');
	} else {
		$poll_end_date  = mysql2date(sprintf(__('%s @ %s', 'wp-polls'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $poll_expiry));
	}
	$poll_multiple_ans = (int) $poll_question->pollq_multiple;
	$template_question = removeslashes(get_option('poll_template_resultheader'));
	$template_question = str_replace("%POLL_QUESTION%", '', $template_question);
	$template_question = str_replace("%POLL_ID%", $poll_question_id, $template_question);
	$template_question = str_replace("%POLL_TOTALVOTES%", $poll_question_totalvotes, $template_question);
	$template_question = str_replace("%POLL_TOTALVOTERS%", $poll_question_totalvoters, $template_question);
	$template_question = str_replace("%POLL_START_DATE%", $poll_start_date, $template_question);
	$template_question = str_replace("%POLL_END_DATE%", $poll_end_date, $template_question);
	if($poll_multiple_ans > 0) {
		$template_question = str_replace("%POLL_MULTIPLE_ANS_MAX%", $poll_multiple_ans, $template_question);
	} else {
		$template_question = str_replace("%POLL_MULTIPLE_ANS_MAX%", '1', $template_question);
	}
	// Get Poll Answers Data
	list($order_by, $sort_order) = _polls_get_ans_result_sort();
	$poll_answers = $wpdb->get_results( $wpdb->prepare( "SELECT polla_aid, polla_answers, polla_votes FROM $wpdb->pollsa WHERE polla_qid = %d ORDER BY $order_by $sort_order", $poll_question_id ) );
	// If There Is Poll Question With Answers
	if($poll_question && $poll_answers) {
		// Store The Percentage Of The Poll
		$poll_answer_percentage_array = array();
		// Is The Poll Total Votes 0?
		$poll_totalvotes_zero = true;
		if($poll_question_totalvotes > 0) {
			$poll_totalvotes_zero = false;
		}
		// Print Out Result Header Template
		$temp_pollresult .= "<div id=\"polls-$poll_question_id\" class=\"wp-polls\">\n";
		$temp_pollresult .= "\t\t$template_question\n";
		foreach($poll_answers as $poll_answer) {
			// Poll Answer Variables
			$poll_answer_id = (int) $poll_answer->polla_aid;
			$poll_answer_text = wp_kses_post( removeslashes($poll_answer->polla_answers) );
			$poll_answer_votes = (int) $poll_answer->polla_votes;
			// Calculate Percentage And Image Bar Width
			if(!$poll_totalvotes_zero) {
				if($poll_answer_votes > 0) {
					$poll_answer_percentage = round((($poll_answer_votes/$poll_question_totalvotes)*100));
					$poll_answer_imagewidth = round($poll_answer_percentage);
					if($poll_answer_imagewidth === 100) {
						$poll_answer_imagewidth = 99;
					}
				} else {
					$poll_answer_percentage = 0;
					$poll_answer_imagewidth = 1;
				}
			} else {
				$poll_answer_percentage = 0;
				$poll_answer_imagewidth = 1;
			}
			// Make Sure That Total Percentage Is 100% By Adding A Buffer To The Last Poll Answer
			$round_percentage = apply_filters( 'wp_polls_round_percentage', false );
			if( $round_percentage ) {
				if ( $poll_multiple_ans === 0 ) {
					$poll_answer_percentage_array[] = $poll_answer_percentage;
					if ( count( $poll_answer_percentage_array ) === count( $poll_answers ) ) {
						$percentage_error_buffer = 100 - array_sum( $poll_answer_percentage_array );
						$poll_answer_percentage += $percentage_error_buffer;
						if ( $poll_answer_percentage < 0 ) {
							$poll_answer_percentage = 0;
						}
					}
				}
			}
			$poll_answer->pourcent = $poll_answer_percentage;
			$poll_answer->label = $poll_answer_text;

			// Let User See What Options They Voted
			if(in_array($poll_answer_id, $user_voted, true)) {
				// Results Body Variables
				$template_answer = removeslashes(get_option('poll_template_resultbody2'));
				$template_answer = str_replace("%POLL_ID%", $poll_question_id, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_ID%", $poll_answer_id, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER%", $poll_answer_text, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_TEXT%", htmlspecialchars(strip_tags($poll_answer_text)), $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_VOTES%", number_format_i18n($poll_answer_votes), $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_PERCENTAGE%", $poll_answer_percentage, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_IMAGEWIDTH%", $poll_answer_imagewidth, $template_answer);
				// Print Out Results Body Template
				$temp_pollresult .= "\t\t$template_answer\n";
			} else {
				// Results Body Variables
				$template_answer = removeslashes(get_option('poll_template_resultbody'));
				$template_answer = str_replace("%POLL_ID%", $poll_question_id, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_ID%", $poll_answer_id, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER%", $poll_answer_text, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_TEXT%", htmlspecialchars(strip_tags($poll_answer_text)), $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_VOTES%", number_format_i18n($poll_answer_votes), $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_PERCENTAGE%", $poll_answer_percentage, $template_answer);
				$template_answer = str_replace("%POLL_ANSWER_IMAGEWIDTH%", $poll_answer_imagewidth, $template_answer);
				// Print Out Results Body Template
				$temp_pollresult .= "\t\t$template_answer\n";
			}
			// Get Most Voted Data
			if($poll_answer_votes > $poll_most_votes) {
				$poll_most_answer = $poll_answer_text;
				$poll_most_votes = $poll_answer_votes;
				$poll_most_percentage = $poll_answer_percentage;
			}
			// Get Least Voted Data
			if($poll_least_votes === 0) {
				$poll_least_votes = $poll_answer_votes;
			}
			if($poll_answer_votes <= $poll_least_votes) {
				$poll_least_answer = $poll_answer_text;
				$poll_least_votes = $poll_answer_votes;
				$poll_least_percentage = $poll_answer_percentage;
			}
		}

	} 
	// Return Poll Result
	$returndata['data'] = $poll_answers;
	return $returndata;
}
