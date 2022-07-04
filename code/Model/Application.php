<?php

namespace SixF\TUS\Model;

use Composer\Semver\Comparator;
use SilverStripe\Assets\File;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;

class Application extends DataObject
{
    private static $singular_name = "Anwendung";

    private static $plural_name = "Anwendungen";

    private static $table_name = "Application";

    private static $db = [
        "Title" => "Varchar(100)",
    ];

    private static $has_many = [
        "Releases" => Release::class,
    ];

    private static $field_labels = [];

    public function latestRelease(): ?Release {
        return $this->Releases()->sort("Version", "ASC")->last();
    }

    public function canUpdate($currentVersion): bool {
        //
        $latestRelease = $this->latestRelease();

        return Comparator::greaterThan($latestRelease->Version, $currentVersion);
    }

    public function getArtifact($os): ?Artifact {
        //
        $latestRelease = $this->latestRelease();

        return $latestRelease->Artifacts()->filter(["Os" => $os])->first();
    }
}
