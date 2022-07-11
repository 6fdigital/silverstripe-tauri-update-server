<?php

namespace SixF\TUS\Manifest;

use GuzzleHttp\Psr7\UploadedFile;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Core\Config\Config;
use SixF\TUS\Model\Artifact;

class ReleaseManifestArtifact
{
  protected string $_os;
  protected string $_arch;
  protected string $_signature;
  protected string $_field;
  protected File $_file;

  /**
   * @return string
   */
  public function getOs(): string
  {
    return $this->_os;
  }

  /**
   * @param string $os
   */
  public function setOs(string $os): void
  {
    $this->_os = $os;
  }

  /**
   * @return string
   */
  public function getArch(): string
  {
    return $this->_arch;
  }

  /**
   * @param string $arch
   */
  public function setArch(string $arch): void
  {
    $this->_arch = $arch;
  }

  /**
   * @return string
   */
  public function getSignature(): string
  {
    return $this->_signature;
  }

  /**
   * @param string $signature
   */
  public function setSignature(string $signature): void
  {
    $this->_signature = $signature;
  }

  /**
   * @return string
   */
  public function getField(): string
  {
    return $this->_field;
  }

  /**
   * @param string $field
   */
  public function setField(string $field): void
  {
    $this->_field = $field;
  }

  /**
   * @return File
   */
  public function getFile(): File
  {
    return $this->_file;
  }

  /**
   * @param File $field
   */
  public function setFile(File $file): void
  {
    $this->_file = $file;
  }

  /**
   * @return Artifact
   */
  public function getArtifact(): Artifact
  {
    //
    $a = new Artifact();
    $a->Os = $this->getOs();
    $a->Arch = $this->getArch();
    $a->Signature = $this->getSignature();
    $a->FileID = $this->getFile()->ID;

    return $a;
  }

  /**
   * Return the uploaded temp-file directly
   * from the $_FILES array
   * @return mixed
   */
  public function getUploadFile(): ?UploadFile
  {
    // validate field
    if (!array_key_exists($this->getField(), $_FILES) || !$data = $_FILES[$this->getField()]) {
      return null;
    }

    // validate properties
    if (!array_key_exists("name", $data) ||
      !array_key_exists("type", $data) ||
      !array_key_exists("tmp_name", $data) ||
      !array_key_exists("error", $data) ||
      !array_key_exists("size", $data)) {
      return null;
    }

    //
    $file = new UploadFile();
    $file->setName($data["name"]);
    $file->setType($data["type"]);
    $file->setTmpName($data["tmp_name"]);
    $file->setError($data["error"]);
    $file->setSize($data["size"]);

    if ($file->getError() !== UPLOAD_ERR_OK) {
      return null;
    }

    return $file;
  }

  /**
   * @param object $json
   * @return $this|null
   */
  public function parse(object $json): ?ReleaseManifestArtifact
  {
    //
    if (!property_exists($json, "os") ||
      !property_exists($json, "arch") ||
      !property_exists($json, "signature") ||
      !property_exists($json, "field")) {
      return null;
    }

    //
    $this->setOs($json->os);
    $this->setArch($json->arch);
    $this->setSignature($json->signature);
    $this->setField($json->field);

    return $this;
  }
}
