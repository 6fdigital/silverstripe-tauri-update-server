<?php

namespace SixF\TUS\Model;

use SilverStripe\ORM\DataObject;

class Release extends DataObject
{
    private static $table_name = "Release";

    private static $db = [
        "Version" => "Varchar(50)",
        "Notes" => "Text",
        "Signature" => "Text",
    ];

    private static $has_one = [
        "Application" => Application::class
    ];

    private static $has_many = [
        "Artifacts" => Artifact::class,
    ];

    private static $summary_fields = [
        "Title",
    ];

    private static $field_labels = [
        "Title" => "Titel",
        "Version" => "Version",
        "VersionNice" => "Version",
        "Application" => "Anwendung",
        "Artifacts" => "Artifakte",
    ];

    public function Title() {
        return sprintf("%s %s", $this->Application()->Title, $this->VersionNice());
    }

    public function VersionNice() {
        return sprintf("v%s", $this->Version);
    }
}
