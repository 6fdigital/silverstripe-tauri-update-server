<?php

namespace SixF\TUS\Model;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;

class Artifact extends DataObject
{
  private static $table_name = "Artifact";

  private static $db = [
    "Os" => "Enum('linux,darwin,windows', 'linux')",
    // "Arch" => "Enum('x86_64,aarch64,i686,armv7','x86_64')",
  ];

  private static $has_one = [
    "File" => File::class,
    "Release" => Release::class,
  ];
  private static $owns = [
    "File",
  ];

  private static $summary_fields = [
    "Os",
  ];

  /**
   * @return false|string
   */
  public function getJson()
  {
    return json_encode([
      "url" => $this->File()->AbsoluteLink(),
      "version" => $this->Release()->Version,
      "notes" => $this->Release()->Notes,
      "pub_date" => date("c", strtotime($this->Created)),
      "signature" => $this->Release()->Signature,
    ]);
  }
}
