<?php

// Scan block device for KFH Flipnote magic

function findKFHMagic($filename) {

    if (!file_exists($filename)) {
        error_log("Block device or disk image $filename does not exist");
    }

    $searchBytes = hex2bin("4b4648");
    $crossing = 1 - strlen($searchBytes); // - (length - 1); see below

    $buflen = 2000;
    $offsets = [];

    try {
        $f = fopen($filename, "r") or exit("Unable to open block device.");
    } catch (Exception $e) {
        error_log("Unable to open block device $filename: " . $e->getMessage());
    }

    while (!feof($f)) {
        $buf = fread($f, $buflen);
        $checkForMagic = strpos($buf, $searchBytes);
        if ($checkForMagic === false) // strict comparation here. zero can be returned!
        {
            // keep last n-1 bytes, because they can be beginning of required sequence
            $buf = substr($buf, $crossing);
        } else {
            $offset = (ftell($f) - $buflen) + $checkForMagic;
            $offsets[] = $offset;
            unset($buf);
        }
    }

    fclose($f);
    return $offsets;
}
