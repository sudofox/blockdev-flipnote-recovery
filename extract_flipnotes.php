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

foreach ($offsets as $offset) {
    error_log("Starting extraction at offset $offset");
    $data = file_get_contents($blockdev, false, NULL, $offset, 10000000);
    $extractor = new kwzExtractor($data);

    try {
        $extractor->fixDataSize();
    } catch (TypeError | Exception $e) {
        error_log("KWZ load failed - " . $e->getMessage());
        $meta = $extractor->getBareMeta();

        if (!is_dir("./broken")) {
            error_log("Creating folder: ./broken");
            mkdir("./broken");
        }

        $fsid = (isset($meta["current"]["fsid"])) ?  $meta["current"]["fsid"] : null;
        $filename = (isset($meta["current"]["filename"])) ?  $meta["current"]["filename"] : null;


        if (is_null($fsid)) {
            error_log("Couldn't recover any header data");
            continue;
        }

        // Check that we actually have a well-formed kwz filename

        if (!preg_match("/^[cwmfjordvegbalksnthpyxquiz012345]{28}$/", $meta["current"]["filename"])) {
            error_log("No well-formed filename found, skipping");
            continue;
        }


        if (!is_dir("./broken/{$fsid}")) {
            error_log("Creating folder: ./broken/{$fsid}");
            mkdir("./broken/{$fsid}");
        }

        $meta["recovery"]["kfh_offset"] = $offset;
        file_put_contents("./broken/$fsid/{$meta["current"]["filename"]}.json", json_encode($meta, JSON_PRETTY_PRINT));

        // Try to recover embedded JPG frame

        $jpg = $extractor->getEmbeddedJPEG();
        if (strlen($jpg) > 0) {
            file_put_contents("./broken/$fsid/{$meta["current"]["filename"]}.jpg", $jpg);
        }
        continue;
    }

    $meta = $extractor->getMeta();

    $fsid = $meta["current"]["fsid"];

    if (!is_dir("./flipnotes")) {
        error_log("Creating folder: ./flipnotes");
        mkdir("./flipnotes");
    }

    if (!is_dir("./flipnotes/$fsid")) {
        error_log("Creating folder: ./flipnotes/$fsid");
        mkdir("./flipnotes/$fsid");
    }

    $filename = $meta["current"]["filename"];

    error_log("Extracting ./flipnotes/$fsid/$filename.kwz (" . $extractor->calcFileSize() . ")");
    file_put_contents("./flipnotes/$fsid/$filename.kwz", $extractor->getFileData());
    file_put_contents("./flipnotes/$fsid/$filename.json", json_encode($meta, JSON_PRETTY_PRINT));

    $extract_count++;
}

$timing["stop"] = microtime(true);
$timing["diff"] = $timing["stop"] - $timing["start"];

error_log("Extracted $extract_count Flipnotes in " . round($timing["diff"], 2) . " seconds");
