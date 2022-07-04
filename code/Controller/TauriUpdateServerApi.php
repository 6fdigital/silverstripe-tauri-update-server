<?php

namespace SixF\TUS\Controller;

use Composer\Semver\Comparator;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;
use SixF\TUS\Model\Application;
use SixF\TUS\Model\Artifact;
use SixF\TUS\Model\Release;

class TauriUpdateServerApi extends Controller
{
  private static $allowed_actions = [
    "add",
  ];


  public function respond($message, $code = 200) {
    //
    $this->getResponse()->addHeader("Content-Type", "application/json");
    $this->getResponse()->setStatusCode($code);

    return json_encode([
      "status" => $code,
      "description" => $message,
    ]);
  }

  public function add() {
    //
    if(
      (!(array_key_exists("ARTIFACT_DARWIN", $_FILES) && $assetDarwin = $_FILES["ARTIFACT_DARWIN"]) &&
        !(array_key_exists("ARTIFACT_LINUX", $_FILES) && $assetLinux = $_FILES["ARTIFACT_LINUX"]) &&
        !(array_key_exists("ARTIFACT_WINDOWS", $_FILES) && $assetWindows = $_FILES["ARTIFACT_WINDOWS"])) ||
      !$dataRaw = $this->getRequest()->postVar("DATA")
    ) {
      return $this->respond("Please specify at least one artifact!", 400);
    }

    if (!$data = json_decode($dataRaw)) {
      return $this->respond("Could not parse release data!", 400);
    }

    if ($assetDarwin !== UPLOAD_ERR_OK || $assetLinux !== UPLOAD_ERR_OK || $assetWindows !== UPLOAD_ERR_OK) {
      // return $this->respond("Received empty files!", 400);
    }

    if (!$application = Application::get()->filter(["Title" => $data->application])->first()) {
      return $this->respond("Could not find application!", 400);
    }


    //
    // upload files
    //


    if ($application->latestRelease() && !Comparator::greaterThan($data->version, $application->latestRelease()->Version)) {
      return $this->respond("The new version must be greater than the latest one!", 400);
    }
    /*
    {
        "version": "1.0.0",
        "notes": "some notes",
        "signature": "signature",
        "application": "LxStats",
        "artifacts": [
            {"os": "darwin", "file": "<blob>" },
            {"os": "linux", "file": "<blob>" },
            {"os": "windows", "file": "<blob>" }
        ]
    }
     */

    Debug::dump($data->version);

    $release = new Release();
    $release->Version = $data->version;
    $release->Notes = $data->notes;
    $release->Signature = $data->signature;
    $release->ApplicationID = $application->ID;

    if (!$release->write()) {
      return $this->respond("Error saving new release!", 400);
    }

    foreach ($data->artifacts as $a) {
      //
      $artifact = new Artifact();
      $artifact->Os = $a->os;
      $artifact->write();
      //
      $release->Artifacts()->add($artifact);
    }



    return $this->respond("Release was created successfully");
  }
}
