<?php

/*
 * Postback is used by the OGAds API to update a Click.
 * If the conversions required is completed we then send an API request to SMMGlobe to send the requested follower amount.
 * */

require_once "../bootstrap.php";
require_once "../services/ServerService.php";
require_once "../services/ConversionService.php";
require_once "../services/SMMGlobeService.php";
require_once "../services/DotEnvService.php";
require_once "../database/Session.php";
require_once "../database/Click.php";

(new DotEnvService(__DIR__ . "/../.env"))->load();

header("Content-Type: application/json");

function getPostbackAllowedIps(): array
{
    $raw = getenv("POSTBACK_ALLOWED_IPS");
    if ($raw === false || trim($raw) === "") {
        return [];
    }

    return array_values(array_filter(array_map("trim", explode(",", $raw))));
}

$authorizedIps = getPostbackAllowedIps();
if ($authorizedIps === []) {
    echo json_encode([
        "success" => false,
        "error" => "Postback IP allowlist is not configured"
    ]);
    die();
}

if (!in_array(ServerService::getIpAddress(), $authorizedIps, true)) {
    echo json_encode([
        "success" => false,
        "error" => "You're not authorized to access this page"
    ]);
    die();
}

if (!isset($_GET["aff_sub4"]) || !isset($_GET["ip"]) || !isset($_GET["offer_id"])) {
    echo json_encode([
        "success" => false,
        "error" => "You failed to provide the required parameters"
    ]);
    die();
}

$offerId = filter_var($_GET["offer_id"], FILTER_VALIDATE_INT);
if ($offerId === false || $offerId < 1) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid offer_id"
    ]);
    die();
}

$session = new Session();
$pdo = $session->connect();
$pdo->beginTransaction();

try {
    $affSub4 = (string)$_GET["aff_sub4"];
    $sessionIp = (string)$_GET["ip"];

    $current_session = $session->getForUpdateByAffSub4($affSub4);
    if (!$current_session) {
        $current_session = $session->getForUpdate($sessionIp, $affSub4);
    }
    if (!$current_session) {
        $pdo->rollBack();
        echo json_encode([
            "success" => false,
            "error" => "Failed to fetch the session information"
        ]);
        die();
    }

    if ($current_session["ip_address"] !== $sessionIp) {
        $session->syncIpAddress((int)$current_session["id"], $sessionIp);
    }

    if ($session->isFulfilled($current_session)) {
        $pdo->commit();
        echo json_encode([
            "success" => true
        ]);
        die();
    }

    $click = new Click();
    $click->markCompleted($offerId, (int)$current_session["id"]);

    $completedCount = $click->countDistinctCompleted((int)$current_session["id"]);
    $conversionsRequired = ConversionService::followersToConversions((int)$current_session["followers"]);
    if ($completedCount < $conversionsRequired) {
        $pdo->commit();
        echo json_encode([
            "success" => false,
            "error" => "Not enough offers have been completed"
        ]);
        die();
    }

    $smmGlobe = new SMMGlobeService();
    $order = $smmGlobe->addOrder(
        $current_session["platform"],
        $current_session["username"],
        (int)$current_session["followers"]
    );
    $session->markFulfilled((int)$current_session["id"], (string)$order->order);

    $pdo->commit();
    echo json_encode([
        "success" => true
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response = [
        "success" => false,
        "error" => "Failed to order the followers"
    ];
    if (getenv("APP_DEBUG") === "true") {
        $response["debug"] = $e->getMessage();
    }
    echo json_encode($response);
}
