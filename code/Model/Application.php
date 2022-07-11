<?php

namespace SixF\TUS\Model;

use Composer\Semver\Comparator;
use SilverStripe\Assets\File;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;

class Application extends DataObject
{
  private static $table_name = "Application";

  private static $db = [
    "Title" => "Varchar(100)",
  ];

  private static $has_many = [
    "Releases" => Release::class,
    "Tokens" => Token::class,
  ];

  /**
   * @return Release|null
   */
  public function latestRelease(): ?Release
  {
    return $this->Releases()->sort("Version", "DESC")->first();
  }

  /**
   * @param $currentVersion
   * @return bool
   */
  public function canUpdate($currentVersion): bool
  {
    //
    $latestRelease = $this->latestRelease();

    return Comparator::greaterThan($latestRelease->Version, $currentVersion);
  }

  /**
   * @param $os
   * @return Artifact|null
   */
  public function getArtifact(string $os, string $arch): ?Artifact
  {
    //
    $latestRelease = $this->latestRelease();

    return $latestRelease->Artifacts()->filter(["Os" => $os, "Arch" => $arch])->first();
  }

  /**
   * @param string|null $token
   * @return bool
   */
  public function canCreateRelease(?string $token): bool
  {
    // no token required
    if ($this->Tokens()->Count() === 0) return true;
    // token required but no token given
    if ($this->Tokens()->Count() > 0 && !$token) return false;

    // check if a token exists
    $res = false;
    foreach ($this->Tokens() as $t) {
      //
      if ($t->Value === $token) {
        $res = true;
        break;
      }
    }

    return $res;
  }

  /**
   * @return void
   */
  public function onBeforeDelete()
  {
    parent::onBeforeDelete();

    if ($this->Releases()->Count() <= 0 || $this->Tokens()->Count() <= 0) return;

    foreach ($this->Tokens() as $artifact) {
      $artifact->delete();
    }

    foreach ($this->Tokens() as $token) {
      $token->delete();
    }
  }
}
