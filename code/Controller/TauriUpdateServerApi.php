<?php

namespace SixF\TUS\Controller;

use Composer\Semver\Comparator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\Assets\File;
use SixF\TUS\Model\Application;
use SixF\TUS\Model\Artifact;
use SixF\TUS\Model\Release;

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

  public function add() {
    //
    if(
      (!array_key_exists("ARTIFACT_DARWIN", $_FILES) &&
        !array_key_exists("ARTIFACT_LINUX", $_FILES) &&
        !array_key_exists("ARTIFACT_WINDOWS", $_FILES)) ||
      !$dataRaw = $this->getRequest()->postVar("DATA")
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
    }

    //
    if (!$application = Application::get()->filter(["Title" => $data->application])->first()) {
      return $this->_respond("Could not find application!", 400);
    }

    //
    if ($application->latestRelease() && !Comparator::greaterThan($data->version, $application->latestRelease()->Version)) {
      return $this->_respond(sprintf("The new version (%s) must be greater than the latest one (%s)!", $data->version, $application->latestRelease()->Version), 400);
    }


    //
    // upload files
    //

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

    $release = new Release();
    $release->Version = $data->version;
    $release->Notes = $data->notes;
    $release->Signature = $data->signature;
    $release->ApplicationID = $application->ID;

    if (!$release->write()) {
      return $this->_respond("Error saving new release!", 400);
    }

    foreach ($artifactFiles as $os => $file ) {
      //
      $artifact = new Artifact();
      $artifact->Os = $os;
      $artifact->FileID = $file->ID;
      $artifact->write();
      //
      $release->Artifacts()->add($artifact);
    }

    return $this->_respond("Release was created successfully");
  }

  protected function _saveAsset($asset): File {
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
      return false;
    }

    return $assetFile;
  }


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
