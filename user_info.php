<?php

require_once '../../config.php';
require_once __DIR__.'/lib.php';

\core\session\manager::write_close();

$server = oauth_get_server();
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $logparams = array('other' => array('cause' => 'invalid_approval'));
    $event = \local_oauth\event\user_info_request_failed::create($logparams);
    $event->trigger();

    $server->getResponse()->send();
    die();
}


$token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());
if (isset($token['user_id']) && !empty($token['user_id'])) {

    $user = $DB->get_record_sql('SELECT id,auth,username,idnumber,firstname,lastname,email,lang,city,country,phone1,address,description FROM {user} WHERE id = :id', ['id' => $token['user_id']]);
    $tags = $DB->get_record_sql('SELECT id,userid,name,rawname FROM {tag} WHERE userid = :id', ['id' => $token['user_id']]);

    if (!$user) {
        $logparams = array('other' => array('cause' => 'user_not_found'));
        $event = \local_oauth\event\user_info_request_failed::create($logparams);
        $event->trigger();

        $response->send();
    }

    $request = OAuth2\Request::createFromGlobals();
    $response = new OAuth2\Response();
    $scopeRequired = 'user_info';
    if (!$server->verifyResourceRequest($request, $response, $scopeRequired)) {
        $logparams = array('relateduserid' => $user->id, 'other' => array('cause' => 'insufficient_scope'));
        $event = \local_oauth\event\user_info_request_failed::create($logparams);
        $event->trigger();

          // if the scope required is different from what the token allows, this will send a "401 insufficient_scope" error
          $response->send();
    }

    $logparams = array('userid' => $user->id);
    $event = \local_oauth\event\user_info_request::create($logparams);
    $event->trigger();
    $user['tags'] = $tags;
    echo json_encode($user);
} else {
    $logparams = array('other' => array('cause' => 'invalid_token'));
    $event = \local_oauth\event\user_info_request_failed::create($logparams);
    $event->trigger();

    $server->getResponse()->send();
}
