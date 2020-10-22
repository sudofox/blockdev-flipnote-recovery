<?php

$timing["start"] = microtime(true);

require_once "./lib/function.findKFHMagic.php";
require_once "./lib/class.kwzParser.php";
require_once "./lib/class.kwzExtractor.php";

if ($argc < 2) {
    error_log("Usage: {$argv[0]} ./path/to/disk.img");
    exit();
}

$blockdev = $argv[1];

if (!file_exists($blockdev)) {
    error_log("Couldn't open $blockdev");
    exit();
}
$offsets = findKFHMagic($blockdev);

$extract_count = 0;

foreach($offsets as $offset) {
    $data = file_get_contents($blockdev, false, NULL, $offset, 3000000);
    $extractor = new kwzExtractor($data);
    $extractor->fixDataSize();
    $meta = $extractor->getMeta();

    $fsid = $meta["current"]["fsid"];

    if (!is_dir("./$fsid")) {
        error_log("Creating folder: $fsid");
        mkdir("./$fsid");
    }

    $filename = $meta["current"]["filename"];

    error_log("Extracting ./$fsid/$filename.kwz (" . $extractor->calcFileSize() . ")");
    file_put_contents("./$fsid/$filename.kwz", $extractor->getFileData());

    $extract_count++;
}

$timing["stop"] = microtime(true);
$timing["diff"] = $timing["stop"] - $timing["start"];

error_log("Extracted $extract_count Flipnotes in " . round($timing["diff"], 2) . " seconds");