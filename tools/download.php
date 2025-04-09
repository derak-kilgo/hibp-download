<?php

require_once(__DIR__ . '/../config.php');

echo "Downloading latest Pwned Passwords data files...\n";


$dataDir = HIBP_SHA1_DATA;
$logFile = LOGFILE;

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$maxRetries = 10;
$batchSize = 100;
$timeout = 25;

function downloadBatch(array $batch, string $dataDir, int $timeout, array &$curlHandles): array {
    $mh = curl_multi_init();
    $handles = [];

    foreach ($batch as $range => $retryCount) {
        $url = HIBP_ENDPOINT . "$range";
        $filePath = $dataDir . $range;

        $fp = fopen($filePath, 'w');

        // Reuse existing cURL handle or create a new one
        if (isset($curlHandles[$range])) {
            $ch = $curlHandles[$range];
            curl_reset($ch);
        } else {
            $ch = curl_init();
            $curlHandles[$range] = $ch;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, TOOL_USERAGENT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Keep-Alive']);

        curl_multi_add_handle($mh, $ch);
        $handles[$range] = [
            'handle' => $ch,
            'file'   => $fp,
            'path'   => $filePath,
            'retry'  => $retryCount
        ];
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($status === CURLM_OK) {
            $numfds = curl_multi_select($mh);
            if ($numfds === -1) {
                usleep(100); // avoid busy loop
            }
        }
    } while ($running > 0 && $status === CURLM_OK);

    $failed = [];

    foreach ($handles as $range => $data) {
        $ch = $data['handle'];
        $fp = $data['file'];
        $filePath = $data['path'];

        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($error || $httpCode < 200 || $httpCode >= 300) {
            $failed[$range] = $data['retry'] + 1;
            logMessage("Failed download for range $range (attempt {$failed[$range]}). HTTP $httpCode. Error: $error",true);

            if (file_exists($filePath)) {
                unlink($filePath); // remove corrupted/partial file
            }
        }

        curl_multi_remove_handle($mh, $ch);
        fclose($fp);
    }

    curl_multi_close($mh);

    return $failed;
}

function intToRange(int $i): string {
    return strtoupper(str_pad(dechex($i), 5, '0', STR_PAD_LEFT));
}

function estimateTimeRemaining(int $startTime, int $processed, int $total): string {
    if ($processed === 0) {
        return "Calculating...";
    }
    $elapsedTime = time() - $startTime;
    $timePerRange = $elapsedTime / $processed;
    $remainingTime = $timePerRange * ($total - $processed);
    return gmdate("H:i:s", $remainingTime);
}

// Build list of pending ranges (skip ones already downloaded)
$pendingRanges = [];
$maxRange = 0x100000; // 16^5

for ($i = 0; $i < $maxRange; $i++) {
    $range = intToRange($i);
    $filePath = $dataDir . $range;
    if (!file_exists($filePath) || filesize($filePath) < 1024) {
        $pendingRanges[$range] = 0;
    }
}

$startTime = time();
$total = $maxRange;
$processed = $total - count($pendingRanges);

logMessage("Starting download. Already have $processed of $total ranges.",true);

$curlHandles = [];

while (!empty($pendingRanges)) {
    $batch = array_slice($pendingRanges, 0, $batchSize, true);
    $failed = downloadBatch($batch, $dataDir, $timeout, $curlHandles);

    foreach ($batch as $range => $retryCount) {
        if (!isset($failed[$range])) {
            unset($pendingRanges[$range]);
            $processed++;
        } else {
            if ($failed[$range] >= $maxRetries) {
                logMessage("Giving up on range $range after $maxRetries attempts.",true);
                unset($pendingRanges[$range]);
                $processed++;
            } else {
                $pendingRanges[$range] = $failed[$range];
            }
        }
    }

    logMessage("Processed $processed of $total ranges.",true);
    //sleep(1); // optional delay
}

logMessage("Download complete. All ranges processed.",true);
