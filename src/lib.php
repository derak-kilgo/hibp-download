<?php

/**
 * Write a message to the log file and optional stdout
 * @param string $message
 * @param $loud
 * @return void
 */
function logMessage(string $message,$loud=false) {
    $message = date('Y-m-d H:i:s') . " - " . trim($message) . "\n";
    if($loud){
        echo $message;
    }
    if(DISABLE_LOG === false) {
        file_put_contents(LOGFILE, $message, FILE_APPEND);
    }
}

/**
 * @param string $needle sha1 hash to search for.
 * @param string $filePath to the binary file.
 * @param bool $loud true for verbose output.
 * @return int The count of occurrences of the hash in the binary file. -1 for not found.
 */
function search(string $needle, string $filePath,$loud=false): int
{
    $needle = strtoupper(trim($needle));

    // Define the size of each entry in bytes
    $entrySize = 24;
    $hashSize = 20; // Size of the SHA1 hash in bytes
    $countSize = 4; // Size of the count (unsigned integer) in bytes

    // Open the file in binary read mode
    $file = fopen($filePath, 'rb');
    if (!$file) {
        logMessage("Error: Unable to open file '$filePath'.\n",$loud);
        exit(1);
    }

    // Get the total size of the file
    $fileSize = filesize($filePath);
    $totalEntries = intdiv($fileSize, $entrySize);

    logMessage("Performing binary search for hash: $needle\n",$loud);

    // Binary search initialization
    $low = 0;
    $high = $totalEntries - 1;
    $found = false;
    $count = -1; // Initialize the count to -1 for not found

    while ($low <= $high && !$found) {
        // Calculate the middle entry
        $mid = intdiv($low + $high, 2);

        // Seek to the middle entry in the file
        logMessage("Seeking to mid $mid\n",$loud);
        fseek($file, $mid * $entrySize);

        // Read the entry (24 bytes)
        $entry = fread($file, $entrySize);

        // Extract the SHA1 hash (first 20 bytes)
        $binaryHash = substr($entry, 0, $hashSize);
        $hexHash = strtoupper(bin2hex($binaryHash));

        // Compare the hash with the needle
        if ($hexHash === $needle) {
            // Extract the count (last 4 bytes, unsigned integer)
            $binaryCount = substr($entry, $hashSize, $countSize);
            $count = unpack('L', $binaryCount)[1]; // 'L' for unsigned 32-bit integer (little-endian)

            logMessage("Found hash: $hexHash, Count: $count\n",$loud);
            $found = true;
            break;
        } elseif ($hexHash < $needle) {
            // Move to the upper half
            $low = $mid + 1;
        } else {
            // Move to the lower half
            $high = $mid - 1;
        }
    }

    if (!$found) {
        logMessage("Hash not found in the file.\n",$loud);
    }

    // Close the file
    fclose($file);

    return $count;
}