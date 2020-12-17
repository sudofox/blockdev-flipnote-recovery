<?php

class kwzExtractor extends kwzParser {


  protected $dataSizeFixed = false;

  public function fixDataSize() {

    // Check that it loaded properly
    if (!isset($this->sections["KMI"])) {
      throw new Exception("Couldn't seek to KMI section");
    }

    if (!$this->dataSizeFixed) {
      $size = $this->calcFileSize();
      $data = substr($this->data, 0, $size);
      $this->load($data);
    }
  }


  public function calcFileSize() {

    // Check if the file loaded properly

    $meta = $this->getMeta();
    $frameMeta = $this->getFrameMeta();
    $soundMeta = $this->getSoundMeta();
    $err = [];
    // Check signature validity
    // Check that the sum of the section + checksum lengths match the data length
    $calcSize = 0;
    foreach ($this->sections as $type => $section) {
      // Hack: the parser produces an absurdly-sized extra layer since we're feeding it extra data
      // So we only include the actual sections in the calculated size.
      if (in_array($type, ["KFH", "KTN", "KMC", "KMI", "KSN"])) {
        $calcSize += $section["length"] + 8;
      }
    }

    return ($calcSize + 256);
  }

  public function getFileData() {
    if (!$this->dataSizeFixed) {
      $this->fixDataSize();
    }
    return $this->data;
  }


  // Only get data from KFH section
  // Used to check if we have a valid KFH header at all

  public function getBareMeta() {
    $this->seekToSection('KFH');
    // Skip past section header and checksum
    $this->seek(12, 1);
    $meta = $this->unpack([
      'creationTimestamp' => 'V',
      'modifiedTimestamp' => 'V',
      'appVersion' => 'V',
      'rootAuthorId' => 'H20',
      'parentAuthorId' => 'H20',
      'currentAuthorId' => 'H20',
      'rootAuthorName' => 'a22',
      'parentAuthorName' => 'a22',
      'currentAuthorName' => 'a22',
      'rootFilename' => 'a28',
      'parentFilename' => 'a28',
      'currentFilename' => 'a28',
      'frameCount' => 'v',
      'thumbIndex' => 'v',
      'flags' => 'v',
      'frameSpeed' => 'C',
      'layerFlags' => 'C',
    ], 204);

    $meta2 = [
      'lock' => ($meta['flags'] & 0x1) === 1,
      'loop' => (($meta['flags'] >> 1) & 0x01) === 1,
      'flags' => $meta['flags'],
      'layer_flags' => $meta['layerFlags'],
      'app_version' => $meta['appVersion'],
      'frame_count' =>  $meta['frameCount'],
      'frame_speed' => $meta['frameSpeed'],
      'thumb_index' => $meta['thumbIndex'],
      'modified' => $meta['modifiedTimestamp'] + 946684800,
      'created' => $meta['creationTimestamp'] + 946684800,
      'root' => [
        'username' => $this->formatUsername($meta['rootAuthorName']),
        'fsid' => $this->formatAuthorId($meta['rootAuthorId']),
        'filename' => $this->formatFilename($meta['rootFilename']),
      ],
      'parent' => [
        'username' => $this->formatUsername($meta['parentAuthorName']),
        'fsid' => $this->formatAuthorId($meta['parentAuthorId']),
        'filename' => $this->formatFilename($meta['parentFilename']),
      ],
      'current' => [
        'username' => $this->formatUsername($meta['currentAuthorName']),
        'fsid' => $this->formatAuthorId($meta['currentAuthorId']),
        'filename' => $this->formatFilename($meta['currentFilename']),
      ],
    ];
    return $meta2;
  }



  public function getEmbeddedJPEG() {
    $start = strpos($this->data, "\xFF\xD8");
    $end   = strpos($this->data, "\xFF\xD9");
    $jpg = substr($this->data, $start, ($end - $start + 2));

    return $jpg;
  }
}
