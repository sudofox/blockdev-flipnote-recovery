<?php

// Scan block device for KFH Flipnote magic

$filename = $argv[1];

$searchBytes = hex2bin("4b4648");
$crossing = 1 - strlen($searchBytes); // - (length - 1); see below

$buflen = 5000;
$magic_found = 0;
$f = fopen($filename, "r") or exit("Unable to open block device.");
while (!feof($f)) {
    $buf = fread($f, $buflen);
    $checkForMagic = strpos($buf, $searchBytes);
    if ($checkForMagic === false) // strict comparation here. zero can be returned!
    {
        // keep last n-1 bytes, because they can be beginning of required sequence
        $buf = substr($buf, $crossing);
    } else {
        $offset = (ftell($f) - $buflen) + $checkForMagic;
        echo "Found KFH at $offset (0x" . dechex($offset) . ")!" . PHP_EOL;
        $magic_found++;
        unset($buf);
    }
}

echo "Found a total of $magic_found KFH magic" . PHP_EOL;
