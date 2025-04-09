<?php

require_once(__DIR__ . '/../config.php');

/**
 * This script combines multiple text files containing hashes and counts into a single binary file.
 * Each line in the text files is expected to be in the format "hash:count".
 * 
 * @param string $out The output file handle.
 * @param string $hash The hash string (40 characters).
 * @param int $count The count associated with the hash.
 * @return int The number of bytes written to the binary file.
 */
function writeToBinaryFile($out, string $hash, int $count): int {
    // Convert hash from hex string (40 chars) to binary (20 bytes)
    $hashBinary = pack('H*', $hash);

    // Convert integer to 4-byte unsigned binary
    $countBinary = pack('N', $count); // Network byte order = big-endian

    // Write to the binary file
    fwrite($out, $hashBinary . $countBinary);

    // Return the number of bytes written
    return strlen($hashBinary . $countBinary);
}

function writeToTextFile($out,string $hash, int $count): int {
    // Convert hash from hex string (40 chars) to binary (20 bytes)
    $hashBinary = pack('H*', $hash);

    // Convert integer to 4-byte unsigned binary
    $countBinary = pack('N', $count); // Network byte order = big-endian

    // Write to the binary file
    $bytes = fwrite($out, "$hash:$count\n");

    return $bytes;
}



/**
 * Open a binary file for writing.
 *
 * @param string $outFile The path to the output file.
 * @return resource The file handle for the opened file.
 */
function openOutputBinaryFile(string $outFile) {
    $out = fopen($outFile, 'wb');
    if (!$out) {
        die("Error opening output file: $outFile\n");
    }
    return $out;
}

echo "Assemble files into single binary file.\n";

$target = openOutputBinaryFile(HIBP_SHA1_BIN);
//$target = fopen(__DIR__ . '/hibp_all.txt', 'w');
$files = [];

// Step 1: Collect matching filenames
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $fileinfo) {
    if ($fileinfo->isFile()) {
        $filename = $fileinfo->getFilename();
        if (preg_match('/^[0-9A-F]{5}$/', $filename)) {
            $files[$filename] = $fileinfo->getPathname(); // use name as key to sort later
        }
    }
}

// Step 2: Sort filenames in C locale style
ksort($files, SORT_STRING);

// Step 3: Stream output file line by line with progress updates
$totalFiles = count($files);
if ($totalFiles === 0) {
    die("No matching files found.\n");
}

$processedFiles = 0;
$startTime = microtime(true);

foreach ($files as $filename => $fullpath) {
    $handle = fopen($fullpath, 'r');
    if (!$handle) {
        echo "Failed to open $fullpath\n";
        continue;
    }

    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\r", '', $line); // strip carriage returns
        //fwrite($target, $filename . $line);
        $line = explode(':', $line, 2);
        // Concatenate the filename (first 6 bytes) to the hash
        $fullHash = $filename . $line[0];
        $line[1] = (int) $line[1];
        writeToBinaryFile($target, $fullHash, $line[1]); // write hash and count
        //writeToTextFile($target,$fullHash,$line[1]);
    }

    fclose($handle);
    unset($handle);

    $processedFiles++;
    if ($processedFiles % 500 === 0 || $processedFiles === $totalFiles) { // Update every 500 files or at the end
        $elapsedTime = microtime(true) - $startTime;
        $averageTimePerFile = $elapsedTime / $processedFiles;
        $remainingFiles = $totalFiles - $processedFiles;
        $estimatedTimeRemaining = $remainingFiles * $averageTimePerFile;

        echo sprintf(
            "Processed %d/%d files. Estimated time remaining: %.2f seconds.\n",
            $processedFiles,
            $totalFiles,
            $estimatedTimeRemaining
        );
    }
}

fclose($target);

echo "Done.\n";
