<?php


namespace app\import;


interface FileImporter {
  /**
   * @param string $filename
   *
   */
  function importFile($filename);
}
