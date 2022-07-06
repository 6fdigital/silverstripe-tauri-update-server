<?php

namespace SixF\TUS\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PasswordEncryptor;
use SilverStripe\Security\RandomGenerator;

class Token extends DataObject
{
  private static $table_name = "Token";

  private static $db = [
    "Title" => "Varchar(255)",
    "Value" => "Text",
  ];

  private static $has_one = [
    "Application" => Application::class,
  ];

  private static $summary_fields = [
    "Title",
    "Application.Title",
  ];

  /**
   * @return string
   */
  public static function generate_token(): string {
    //
    $generator = new RandomGenerator();
    $tokenString = $generator->randomToken();

    //
    $e = PasswordEncryptor::create_for_algorithm('blowfish'); //blowfish isn't URL safe and maybe too long?
    $salt = $e->salt($tokenString);

    return $e->encrypt($tokenString, $salt);
  }

  /**
   * @return mixed
   */
  public function getCMSFields()
  {
    $f = parent::getCMSFields(); // TODO: Change the autogenerated stub

    // add hint to form-field
    if ($txtValue = $f->dataFieldByName("Value")) {
      $txtValue->setDescription("Save to generate new token");
    }

    return $f;
  }

  /**
   * @return void
   */
  public function onBeforeWrite()
  {
    parent::onBeforeWrite();

    // generate new token if value empty
    if (!$this->Value) {
      //
      $this->Value = self::generate_token();
    }
  }
}
