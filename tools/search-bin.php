<?php

require_once(__DIR__ . '/../config.php');

// Check if the hash to search for is provided as a CLI argument
if ($argc < 2) {
    logMessage("Usage: php search.php <sha1 hash>\n",true);
    exit(1);
}
if(empty($argv[1])) {
    logMessage("Usage: php search.php <sha1 hash>\n",true);
    exit(1);
}
if(strlen($argv[1]) != 40) {
    logMessage("Invalid hash. sha1 hash must be 40 hex characters.\n",true);
    exit(1);
}
if(preg_match('/[^0-9A-F]/i', $argv[1])) {
    logMessage("Invalid hash. sha1 hash must be 40 hex characters.\n",true);
    exit(1);
}

$result = search($argv[1], HIBP_SHA1_BIN, true);

exit($result);