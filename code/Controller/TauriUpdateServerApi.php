<?php

namespace SixF\TUS\Controller;

use Composer\Semver\Comparator;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SixF\TUS\Model\Application;
use SixF\TUS\Model\Artifact;
use SixF\TUS\Model\Release;
use SixF\TUS\Util;
use function PHPUnit\Framework\isEmpty;

class TauriUpdateServerApi extends Controller
{
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
    //
    $classRaw = Convert::raw2sql($this->getRequest()->param("Action"));
    $class = Util::unsanitise_classname($classRaw);
    $idParam = $this->getRequest()->param("ID");
    $id = $idParam === null ? null : Convert::raw2sql($idParam);

    // TODO: only support release models for now
    if ($class !== Release::class || !class_exists($class)) {
      return $this->_respondWithMessage(sprintf("Not found", $class), 404);
    }

    if ($id && !is_numeric($id)) {
      return $this->_respondWithMessage(sprintf("Not found", $class), 404);
    }

    // handle different HTTP verbs
    if ($this->request->isGET() || $this->request->isHEAD()) {
      return $this->getHandler($class, $id);
    }

    if ($this->request->isPOST()) {
      return $this->postHandler($class);
    }

    if ($this->request->isPUT()) {
      //return $this->putHandler($class, $id);
    }

    if ($this->request->isDELETE()) {
      //return $this->deleteHandler($class, $id);
    }

    return $this->_respondWithMessage("Method not allowed", 405);
  }

  /**
   * @param $class
   * @param string|null $id
   * @return false|string
   */
  protected function getHandler($class, ?string $id) {
    // check for list or single object
    if($id === null) {
      $objs = DataObject::get($class);
      $converted = Util::convertDataList($objs);
    } else {
      //
      $obj = DataObject::get($class)->byID($id);
      $converted = Util::convertDataObject($obj);
    }

    return $this->_respondJson($converted);
  }

  /**
   * @param $class
   * @return false|string
   * @throws \SilverStripe\ORM\ValidationException
   */
  protected function postHandler($class) {
    //
    if(
      (!array_key_exists("ARTIFACT_DARWIN", $_FILES) &&
        !array_key_exists("ARTIFACT_LINUX", $_FILES) &&
        !array_key_exists("ARTIFACT_WINDOWS", $_FILES)) ||
      !$dataRaw = $this->getRequest()->postVar("DATA")
    ) {
      return $this->_respondWithMessage("Please specify at least one artifact!", 400);
    }

    //
    $assetDarwin = $_FILES["ARTIFACT_DARWIN"];
    $assetLinux = $_FILES["ARTIFACT_LINUX"];
    $assetWindows = $_FILES["ARTIFACT_WINDOWS"];
    $artifactFiles = [];

    //
    if ($assetDarwin["error"] !== UPLOAD_ERR_OK && $assetLinux["error"] !== UPLOAD_ERR_OK && $assetWindows["error"] !== UPLOAD_ERR_OK) {
      return $this->_respondWithMessage("Received empty files!", 400);
    }

    //
    if (!$data = json_decode($dataRaw)) {
      return $this->_respondWithMessage("Could not parse release data!", 400);
    }

    //
    if (!$application = Application::get()->filter(["Title" => $data->application])->first()) {
      return $this->_respondWithMessage("Could not find application!", 400);
    }

    //
    if ($application->latestRelease() && !Comparator::greaterThan($data->version, $application->latestRelease()->Version)) {
      return $this->_respondWithMessage(sprintf("The new version (%s) must be greater than the latest one (%s)!", $data->version, $application->latestRelease()->Version), 400);
    }


    //
    // upload files
    //

    //
    if ($assetDarwin["error"] === UPLOAD_ERR_OK) {
      if (!$darwinFile = $this->_saveAsset($assetDarwin)) {
        return $this->_respondWithMessage("Could not upload artifact!", 400);
      }

      $artifactFiles["darwin"] = $darwinFile;
    }

    //
    if ($assetLinux["error"] === UPLOAD_ERR_OK) {
      if (!$linuxFile = $this->_saveAsset($assetLinux)) {
        return $this->_respondWithMessage("Could not upload artifact!", 400);
      }

      $artifactFiles["linux"] = $linuxFile;
    }

    //
    if ($assetWindows["error"] === UPLOAD_ERR_OK) {
      if (!$windowsFile = $this->_saveAsset($assetWindows)) {
        return $this->_respondWithMessage("Could not upload artifact!", 400);
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
      return $this->_respondWithMessage("Error saving new release!", 400);
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

    // return $this->_respondWithMessage("Release was created successfully");
    $converted = Util::convertDataObject($release);
    return $this->_respondJson($converted);
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

  /**
   * @param array $data
   * @param $code
   * @return false|string
   */
  protected function _respondJson(array $data, $code = 200) {
    //
    $this->getResponse()->addHeader("Content-Type", "application/json");
    $this->getResponse()->setStatusCode($code);

    return json_encode($data);
  }

  /**
   * @param $message
   * @param $code
   * @return false|string
   */
  protected function _respondWithMessage($message, $code = 200) {
    return $this->_respondJson([
      "status" => $code,
      "message" => $message,
    ], $code);
  }
}
