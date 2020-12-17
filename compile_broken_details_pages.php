<?php

if (!is_dir("./broken")) {
    error_log("No broken Flipnotes to compile data for");
    exit();
}

if (!is_dir("./broken_details")) {
    error_log("Creating folder: ./broken_details");
    mkdir("./broken_details");
}

$users = scandir("./broken");

$data = [];

foreach ($users as $user) {

    $dir = "./broken/$user";

    if (!is_dir($dir)) {
        error_log("Skipping $user...");
        continue;
    }

    $files = glob("./broken/$user/*.json");

    foreach ($files as $file) {
        $filename = basename($file, ".json");

        $data[$user][$filename]["meta"] = json_decode(file_get_contents($file), true);
        if (file_exists("./broken/$user/$filename.jpg")) {
            $data[$user][$filename]["thumb"] = base64_encode(file_get_contents("./broken/$user/$filename.jpg"));
        } else {
            $data[$user][$filename]["thumb"] = false;
        }
    }
}

foreach ($data as $fsid => $user) {
    error_log("Generating report for $fsid");
    $html = <<<EOD
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Broken Flipnote Report</title>
    </head>
    <body>
    <table border>
      <tr>
        <th>Preview image</th>
        <th>Metadata</th>
        <th>SD BlockDev Offset</th>
      </tr>
EOD;

    foreach ($user as $flipnote) {
        $html .= "<tr><td>";
        if ($flipnote["thumb"]) {
            $html .= "<img src=\"data:image/jpeg;base64," . $flipnote["thumb"] . "\">";
        } else {
            $html .= "No thumb extracted";
        }
        $html .= "</td>";
        $html .= "<td><pre>" . json_encode($flipnote["meta"], JSON_PRETTY_PRINT) . "</pre></td>";
        $html .= "<td>" . $flipnote["meta"]["recovery"]["kfh_offset"] . "</td>";
        $html .= "</tr>";
    }


    $html .= <<<EOD
        
</body>
</html>
EOD;

    file_put_contents("./broken_details/{$fsid}.html", $html);
}
