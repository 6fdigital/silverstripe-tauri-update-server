<?php

namespace SixF\TUS\Controller;

use Composer\Semver\Comparator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\Assets\File;
use SixF\TUS\Model\Application;
use SixF\TUS\Manifest\ReleaseManifest;

class TauriUpdateServerApi extends Controller
{
  private static $allowed_actions = [
    "add",
  ];

  /**
   * The file-extensions allowed to upload
   * @var string[]
   */
  private static $allowed_upload_extensions = [
    "deb",
    "rpm",
    "appimage",
    "exe",
    "msi",
    "dmg",
  ];

  public function index()
  {
    return [];
  }

  /**
   * @return false|string
   * @throws \SilverStripe\ORM\ValidationException
   */
  public function add()
  {
    //
    $request = $this->getRequest();
    $response = $this->getResponse();
    $manifestData = $request->postVar("MANIFEST");
    $token = $request->postVar("TOKEN");

    //
    if (!$manifestData) {
      $response->setStatusCode(400);
      return;
    }

    /*
    MANIFEST DEFINITION
    {
        "version": "1.0.0",
        "notes": "some notes",
        "signature": "signature",
        "application": "LxStats",
        "artifacts": [
            {"os": "darwin", "arch": "", "field": "ARTIFACT_DARWIN" },
            {"os": "linux", "arch": "", "file": "ARTIFACT_LINUX" },
            {"os": "windows", "arch": "", "file": "ARTIFACT_WINDOWS" }
        ]
    }
     */
    // try parsing the given manifest
    $manifest = new ReleaseManifest();
    if (!$manifest->parse($manifestData)) {
      return $this->_respond("Could not parse manifest", 400);
    }

    // search for application
    if (!$application = Application::get()->filter(["Title" => $manifest->getApplication()])->first()) {
      return $this->_respond("Could not find application!", 400);
    }

    // check whether the current user are able
    // to create new releases
    if (!$application->canCreateRelease($token)) {
      return $this->_respond("You're not allowed to create releases!", 405);
    }

    // check if new version are greater than
    // current latest one
    if ($application->latestRelease() &&
      !Comparator::greaterThan($manifest->getVersion(), $application->latestRelease()->Version)) {
      return $this->_respond(sprintf("The new version (%s) must be greater than the latest one (%s)!", $manifest->getVersion(), $application->latestRelease()->Version), 400);
    }

    // upload artifacts
    if (!$manifest->uploadArtifacts()) {
      return $this->_respond("Could not upload artifacts!", 400);
    }

    // create release
    if (!$manifest->createRelease($application)) {
      return $this->_respond("Could not create release", 400);
    }

    return $this->_respond("Release was created successfully");
  }

  /**
   * Respond with a simple json object containing
   * the given status-code as well as a message.
   * @param $message
   * @param $code
   * @return false|string
   */
  protected function _respond($message, $code = 200)
  {
    //
    $this->getResponse()->addHeader("Content-Type", "application/json");
    $this->getResponse()->setStatusCode($code);

    return json_encode([
      "status" => $code,
      "message" => $message,
    ]);
  }
}
