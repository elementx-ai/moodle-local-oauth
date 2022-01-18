<?php

require_once '../../config.php';
require_once __DIR__.'/lib.php';

\core\session\manager::write_close();

$server = oauth_get_server();
$request = OAuth2\Request::createFromGlobals();

if (!$server->verifyResourceRequest($request)) {
    return send_invalid_response(400, array('other' => array('cause' => 'invalid_approval')));
}

$token = $server->getAccessTokenData($request);
if (!isset($token['user_id']) || empty($token['user_id'])) {
    return send_invalid_response(401, array('other' => array('cause' => 'invalid_token')));
}

$userid = $token['user_id'];

// Validate scope is correct
if (!$server->verifyResourceRequest($request, $response, 'user_info')) {
    return send_invalid_response(403, array('relateduserid' => $userid, 'other' => array('cause' => 'insufficient_scope')));
}

// Validate user exists
$user = $DB->get_record_sql('SELECT id,auth,username,idnumber,firstname,lastname,email,lang,city,country,phone1,address,description FROM {user} WHERE id=:user_id', ['user_id' => $userid]);
if (!$user) {
    return send_invalid_response(404, array('other' => array('cause' => 'user_not_found')));
}

$tags = $DB->get_records_sql('SELECT ti.tagid as id, t.rawname as tag FROM {tag_instance} as ti INNER JOIN {tag} as t on t.id=ti.tagid WHERE itemtype=\'user\' AND itemid=:user_id;', ['user_id' => $userid]);
$user->tags = array_values($tags);

// Log user details
$logparams = array('userid' => $userid);
$event = \local_oauth\event\user_info_request::create($logparams);
$event->trigger();

// Send Response
header('Content-Type: application/json; charset=utf-8');
echo json_encode($user);
