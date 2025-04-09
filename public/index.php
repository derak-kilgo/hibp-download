<?php
require_once(__DIR__ . '/../config.php');

header('Content-Type: application/json');

if(empty($_REQUEST['hash'])){
    http_response_code(400);
    echo json_encode(['error' => 'No hash provided.']);
    exit;
};
if(strlen($_REQUEST['hash']) != 40) {
    http_response_code(400);
    logMessage("Invalid hash. sha1 hash must be 40 hex characters.\n",true);
    echo json_encode(['error' => 'Invalid hash length.']);
    exit;
}
if(preg_match('/[^0-9A-F]/i', $_REQUEST['hash'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid hash content.']);
    exit(1);
}

$result = search($_REQUEST['hash'], HIBP_SHA1_BIN, false);
echo json_encode(['result' => $result]);