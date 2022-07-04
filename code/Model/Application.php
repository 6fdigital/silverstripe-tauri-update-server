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
  ];

  /**
   * @return Release|null
   */
  public function latestRelease(): ?Release
  {
    return $this->Releases()->sort("Version", "ASC")->last();
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
  public function getArtifact($os): ?Artifact
  {
    //
    $latestRelease = $this->latestRelease();

    return $latestRelease->Artifacts()->filter(["Os" => $os])->first();
  }
}
