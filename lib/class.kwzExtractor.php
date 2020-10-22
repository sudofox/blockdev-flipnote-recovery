<?php

class kwzExtractor extends kwzParser {


  protected $dataSizeFixed = false;

  public function fixDataSize() {
    if (!$this->dataSizeFixed) {
      $size = $this->calcFileSize();
      $data = substr($this->data, 0, $size);
      $this->load($data);
    }
  }


  public function calcFileSize() {

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
}
