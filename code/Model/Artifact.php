<?php

namespace SixF\TUS\Model;

use SilverStripe\Assets\File;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;

class Artifact extends DataObject
{
  private static $table_name = "Artifact";

  private static $oss = ["linux", "darwin", "windows"];

  private static $archs = ["x86_64", "aarch64", "i686", "armv7"];

  private static $db = [
    "Os" => "Varchar(255)",
    "Arch" => "Varchar(255)",
    "Signature" => "Text",
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
    "Arch"
  ];

  /**
   * Returns an array for the values specified in
   * the class option for the given name
   * @param string $optionName
   * @return array
   */
  private static function config_option_array(string $optionName): array {
    //
    if (!$config = Artifact::config()->get($optionName)) {
      return [];
    }

    //
    $res = [];
    foreach ($config as $value) {
      $res[$value] = $value;
    }

    return $res;
  }

  /**
   * @return \SilverStripe\Forms\FieldList
   */
  public function getCMSFields()
  {
    $f = parent::getCMSFields();

    //
    $f->removeByName("ReleaseID");

    // create os drop-down
    $f->addFieldToTab(
      "Root.Main",
      DropdownField::create(
        "Os",
        _t("SixF\TUS\Model\Artifact.db_Os", "Operating System"),
        self::config_option_array("oss")
      ),
      "File",
    );

    // create arch drop-down
    $f->addFieldToTab(
      "Root.Main",
      DropdownField::create(
        "Arch",
        _t("SixF\TUS\Model\Artifact.db_Arch", "Architecture"),
        self::config_option_array("archs")
      ),
      "File",
    );

    return $f;
  }

  /**
   * Return the json for the updater for updating
   * @return false|string
   */
  public function getJson()
  {
    return json_encode([
      "url" => $this->File()->AbsoluteLink(),
      "version" => $this->Release()->Version,
      "notes" => $this->Release()->Notes,
      "pub_date" => date("c", strtotime($this->Created)),
      "signature" => $this->Signature,
    ]);
  }
}
