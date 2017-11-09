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
    $poll_question = $wpdb->get_row( $wpdb->prepare( "SELECT pollq_id, pollq_question, pollq_totalvotes, pollq_active, pollq_timestamp, pollq_expiry, pollq_multiple, pollq_totalvoters FROM $wpdb->pollsq WHERE pollq_id = %d LIMIT 1", $poll_id ) );
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
    $poll_answers = $wpdb->get_results( $wpdb->prepare( "SELECT polla_aid, polla_qid, polla_answers, polla_votes FROM $wpdb->pollsa WHERE polla_qid = %d ORDER BY $order_by $sort_order", $poll_question_id ) );
    //$data['template_question'] = $template_question;
    $data['multiple_ans'] = $poll_multiple_ans;
    $data['question'] = $poll_question->pollq_question;
    $data['id'] = $poll_question->pollq_id;
    $data['answers'] = $poll_answers;
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

function get_all_polls(){
	global $wpdb;
	$polls = $wpdb->get_results( "SELECT * FROM $wpdb->pollsq  ORDER BY pollq_timestamp DESC" );
	$data = [];
	foreach($polls as $poll) {
		$poll_id = (int) $poll->pollq_id;
		$data[] = get_poll_template_by_me($poll_id, true);
	}
	return $data;
}


function get_polls(WP_REST_Request $request){
    $params = $request->get_params();
    // return get_yop_poll_meta(2, 'options', true );
    //$data = array();
    $data['poll'] = get_poll_template_by_me($params['id']);
    $data['poll_banner'] = 'poll_banner';
    return $data;
           
}
function post_polls( $request ){
    $params = $request->get_params();
    $poll_id = $params['id'];
    global $wpdb, $user_identity, $user_ID;
    if( isset( $params['action'] ) && sanitize_key( $params['action'] ) === 'polls') {
        // Load Headers
        
        // Get Poll ID
        $poll_id = (isset($poll_id) ? (int) sanitize_key( $poll_id ) : 0);
        // Ensure Poll ID Is Valid
        if($poll_id === 0) {
            _e('Invalid Poll ID', 'wp-polls');
            gexit();
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
                            $check_voted = check_voted($poll_id);
                            if ( empty( $check_voted ) ) {
                                if (!empty($user_identity)) {
                                    $pollip_user = $user_identity;
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
                                    return new WP_REST_Response(array('message'=> __('تم اضافة رائيك لللاستطلاع بنجاح'.$poll_id, 'wp-polls')));
                                    // return display_pollresult_by_me($poll_id, $poll_aid_array, false);
                                    
                                } else {
                                    return new WP_Error( 'rest_poll_error', __('Unable To Update Poll Total Votes And Poll Total Voters. Poll ID #'.$poll_id, 'wp-polls'),  array( 'status' => 400 ) );
                                } // End if($vote_a)
                            } else {
                                return new WP_Error( 'rest_poll_error', __('You Had Already Voted For This Poll. Poll ID #'.$poll_id, 'wp-polls'),  array( 'status' => 400 ) );
                            } // End if($check_voted)
                        } else {
                            return new WP_Error( 'rest_poll_error', __('Poll ID #'.$poll_id.' is closed', 'wp-polls'),  array( 'status' => 400 ) );
                        }  // End if($is_poll_open > 0)
                    } else {
                        return new WP_Error( 'rest_poll_error', __('Invalid Poll ID. Poll ID  #'.$poll_id, 'wp-polls'),  array( 'status' => 400 ) );
                    } // End if($poll_id > 0 && !empty($poll_aid_array) && check_allowtovote())
                } else {
                    return new WP_Error( 'rest_poll_error', __('Invalid Answer to Poll ID #'.$poll_id, 'wp-polls'),  array( 'status' => 400 ) );
                } //End if(!isRealAnswer($poll_id,$poll_aid))
                break;
            // Poll Result
            case 'result':
                return display_pollresult_by_me($poll_id, 0, false);
                break;
        } // End switch($params['view'])
    } // End if(isset($params['action']) && $params['action'] == 'polls')
    
}