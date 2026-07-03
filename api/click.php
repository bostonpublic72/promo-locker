<?php

/*
 * Click is used by the frontend to open the offer and add the information in the database as a Click.
 * */

require_once "../bootstrap.php";
require_once "../services/ServerService.php";
require_once "../services/ValidationService.php";
require_once "../services/DotEnvService.php";
require_once "../database/Session.php";
require_once "../database/Click.php";

(new DotEnvService(__DIR__ . "/../.env"))->load();

$aff_sub4 = ValidationService::affSub4();
$offerId = ValidationService::offerId();
$link = ValidationService::offerLink();

$session = new Session();
$current_session = $session->get(ServerService::getIpAddress(), $aff_sub4);

if (!$current_session) {
    http_response_code(400);
    echo "Failed to get the current session, please try refreshing the previous page and try again";
    die();
}

$click = new Click();
$click->create($offerId, (int)$current_session["id"]);

header("Location: " . $link);
