<?php

namespace SixF\TUS\Manifest;

use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SixF\TUS\Controller\TauriUpdateServerApi;

class UploadFile
{
  protected string $_name;
  protected string $_type;
  protected string $_tmp_name;
  protected int $_error;
  protected int $_size;

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->_name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void
  {
    $this->_name = $name;
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->_type;
  }

  /**
   * @param string $type
   */
  public function setType(string $type): void
  {
    $this->_type = $type;
  }

  /**
   * @return string
   */
  public function getTmpName(): string
  {
    return $this->_tmp_name;
  }

  /**
   * @param string $tmp_name
   */
  public function setTmpName(string $tmp_name): void
  {
    $this->_tmp_name = $tmp_name;
  }

  /**
   * @return string
   */
  public function getError(): int
  {
    return $this->_error;
  }

  /**
   * @param string $error
   */
  public function setError(int $error): void
  {
    $this->_error = $error;
  }

  /**
   * @return string
   */
  public function getSize(): int
  {
    return $this->_size;
  }

  /**
   * @param string $size
   */
  public function setSize(int $size): void
  {
    $this->_size = $size;
  }

  public function raw(): array
  {
    return
      [
        "name" => $this->getName(),
        "type" => $this->getType(),
        "tmp_name" => $this->getTmpName(),
        "error" => $this->getError(),
        "size" => $this->getSize(),
      ];
  }


  public function save(): ?File
  {
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
    $res = $upload->loadIntoFile($this->raw(), $assetFile, "/Uploads");
    $res1 = $assetFile->write();

    // generate the thumbnails for the uploaded file
    AssetAdmin::singleton()->generateThumbnails($assetFile);

    if (!$res || !$res1) {
      return null;
    }

    return $assetFile;
  }
}
