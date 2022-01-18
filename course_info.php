<?php

require_once '../../config.php';
require_once __DIR__ . '/lib.php';

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
if (!$server->verifyResourceRequest($request, $response, 'course_info')) {
    return send_invalid_response(403, array('relateduserid' => $userid, 'other' => array('cause' => 'insufficient_scope')));
}

// Validate user exists
$user = $DB->get_record_sql('SELECT id FROM {user} WHERE id=:user_id', ['user_id' => $userid]);
if (!$user) {
    return send_invalid_response(404, array('other' => array('cause' => 'user_not_found')));
}

$course_id = $request->query('course_id');
$external_url = $request->query('external_url');
if ($course_id == null && $external_url == null) {
    return send_invalid_response(400, array('other' => array('cause' => 'missing_course_id')));
}

// Get course information from external_url
$course = $course_id != null ?
    $DB->get_record_sql('SELECT id, fullname, shortname FROM {course} WHERE id=:course_id', ['course_id' => $course_id]) :
    $DB->get_record_sql('SELECT c.id as id, c.fullname as fullname, c.shortname as shortname FROM {course} as c INNER JOIN {url} as u on c.id=u.course WHERE '.$DB->sql_like('externalurl', ':external_url'), ['external_url' => $external_url]);

$course_id = $course->id;

// Ensure user is enrolled in course
$course_enrollment = $DB->get_record_sql(
    'SELECT u.id, c.id FROM mdl_user u INNER JOIN mdl_user_enrolments ue ON ue.userid = u.id INNER JOIN mdl_enrol e ON e.id = ue.enrolid INNER JOIN mdl_course c ON e.courseid = c.id WHERE u.id=:user_id AND c.id=:course_id', ['user_id' => $userid, 'course_id' => $course_id]
);
if (!$course_enrollment) {
    return send_invalid_response(404, array('other' => array('cause' => 'user_not_enrolled_in_course')));
}

// Log user details
$logparams = array('userid' => $userid);
$event = \local_oauth\event\user_info_request::create($logparams);
$event->trigger();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($course);
