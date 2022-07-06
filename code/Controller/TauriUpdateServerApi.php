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
use SixF\TUS\Model\Artifact;
use SixF\TUS\Model\Release;
use SixF\TUS\Manifest\TUSReleaseManifest;

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
    "dmg",
    "deb",
    "rpm",
    "exe"
  ];

  public function index() {
    return[];
  }

  /**
   * @return false|string
   * @throws \SilverStripe\ORM\ValidationException
   */
  public function add() {
    //
    $request = $this->getRequest();
    $response = $this->getResponse();
    $manifestData = $this->getRequest()->postVar("MANIFEST");
    $token = $this->getRequest()->postVar("TOKEN");

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
    $manifest = new TUSReleaseManifest();
    if (!$manifest->parse($manifestData)) {
      return $this->_respond("Could not parse manifest", 400);
    }

    /*
    Debug::dump($_FILES["ARTIFACT_DARWIN"]);

    //
    if(
        (!array_key_exists("ARTIFACT_DARWIN", $_FILES) &&
        !array_key_exists("ARTIFACT_LINUX", $_FILES) &&
        !array_key_exists("ARTIFACT_WINDOWS", $_FILES)) ||
        !$dataRaw
    ) {
        return $this->_respond("Please specify at least one artifact!", 400);
    }

    //
    $assetDarwin = $_FILES["ARTIFACT_DARWIN"];
    $assetLinux = $_FILES["ARTIFACT_LINUX"];
    $assetWindows = $_FILES["ARTIFACT_WINDOWS"];
    $artifactFiles = [];

    //
    if ($assetDarwin["error"] !== UPLOAD_ERR_OK && $assetLinux["error"] !== UPLOAD_ERR_OK && $assetWindows["error"] !== UPLOAD_ERR_OK) {
        return $this->_respond("Received empty files!", 400);
    }

    //
    if (!$data = json_decode($dataRaw)) {
        return $this->_respond("Could not parse release data!", 400);
    }*/

    //
    if (!$application = Application::get()->filter(["Title" => $manifest->getApplication()])->first()) {
      return $this->_respond("Could not find application!", 400);
    }

    //
    if (!$application->canCreateRelease($token)) {
      return $this->_respond("You're not allowed to create releases!", 405);
    }

    //
    if ($application->latestRelease() &&
      !Comparator::greaterThan($manifest->getVersion(), $application->latestRelease()->Version))
    {
      return $this->_respond(sprintf("The new version (%s) must be greater than the latest one (%s)!", $manifest->getVersion(), $application->latestRelease()->Version), 400);
    }

    /*
    //
    if ($assetDarwin["error"] === UPLOAD_ERR_OK) {
        if (!$darwinFile = $this->_saveAsset($assetDarwin)) {
            return $this->_respond("Could not upload artifact!", 400);
        }

        $artifactFiles["darwin"] = $darwinFile;
    }
    //
    if ($assetLinux["error"] === UPLOAD_ERR_OK) {
        if (!$linuxFile = $this->_saveAsset($assetLinux)) {
            return $this->_respond("Could not upload artifact!", 400);
        }

        $artifactFiles["linux"] = $linuxFile;
    }
    //
    if ($assetWindows["error"] === UPLOAD_ERR_OK) {
        if (!$windowsFile = $this->_saveAsset($assetWindows)) {
            return $this->_respond("Could not upload artifact!", 400);
        }

        $artifactFiles["windows"] = $windowsFile;
    }*/

    if (!$manifest->uploadArtifacts()) {
      return $this->_respond("Could not upload artifacts!", 400);
    }

    if (!$release = $manifest->createRelease($application)) {
      return $this->_respond("Could not create release", 400);
    }

    return $this->_respond("Release was created successfully");
  }

  /*
  protected function _saveAsset($asset): ?File {
      //
      $allowedUploadExtensions = Config::inst()->get(TauriUpdateServerApi::class, "allowed_upload_extensions");
      // create a custom validator to ensure
      // correct media type uploads
      $uploadValidator = new Upload_Validator();
      $uploadValidator->setAllowedExtensions($allowedUploadExtensions);

      //
      $assetFile = File::create();
      $upload = Upload::create();
      $upload->setValidator($uploadValidator);

      //
      $res = $upload->loadIntoFile($asset, $assetFile, "/Uploads");
      $res1 = $assetFile->write();

      // generate the thumbnails for the uploaded file
      AssetAdmin::singleton()->generateThumbnails($assetFile);

      if (!$res || !$res1) {
          return null;
      }

      return $assetFile;
  }*/


  protected function _respond($message, $code = 200) {
    //
    $this->getResponse()->addHeader("Content-Type", "application/json");
    $this->getResponse()->setStatusCode($code);

    return json_encode([
      "status" => $code,
      "message" => $message,
    ]);
  }
}
