<?php

namespace SixF\TUS\Manifest;

use SixF\TUS\Model\Application;
use SixF\TUS\Model\Release;

class TUSReleaseManifest
{
  protected string $_version;
  protected string $_notes;
  protected string $_signature;
  protected string $_application;
  protected array $_artifacts;

  /**
   * @return string
   */
  public function getVersion(): string
  {
    return $this->_version;
  }

  /**
   * @param string $version
   */
  public function setVersion(string $version): void
  {
    $this->_version = $version;
  }

  /**
   * @return string
   */
  public function getNotes(): string
  {
    return $this->_notes;
  }

  /**
   * @param string $notes
   */
  public function setNotes(string $notes): void
  {
    $this->_notes = $notes;
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
  public function getApplication(): string
  {
    return $this->_application;
  }

  /**
   * @param string $application
   */
  public function setApplication(string $application): void
  {
    $this->_application = $application;
  }

  /**
   * @return array
   */
  public function getArtifacts(): array
  {
    return $this->_artifacts;
  }

  /**
   * @param array $artifacts
   */
  public function setArtifacts(array $artifacts): void
  {
    $this->_artifacts = $artifacts;
  }

  /**
   * @param TUSReleaseManifestArtifact $artifact
   * @return void
   */
  public function addArtifact(TUSReleaseManifestArtifact $artifact): void
  {
    $this->_artifacts[] = $artifact;
  }

  /**
   * @param string $data
   * @return $this|null
   */
  public function parse(string $data): ?TUSReleaseManifest
  {
    // try parsing the raw post request data
    if (!$json = json_decode($data)) {
      return null;
    }

    // check if all requirements are met
    if (!property_exists($json, "version") ||
      !property_exists($json, "signature") ||
      !property_exists($json, "application") ||
      !property_exists($json, "artifacts") ||
      count($json->artifacts) === 0) {
      return null;
    }

    // create manifest
    // $manifest = new TUSReleaseManifest();
    $this->setVersion($json->version);
    $this->setNotes($json->notes);
    $this->setSignature($json->signature);
    $this->setApplication($json->application);

    // add artifacts
    foreach ($json->artifacts as $artifactObj) {
      //
      $artifact = new TUSReleaseManifestArtifact();

      if (!$artifact->parse($artifactObj)) {
        continue;
      }

      if (!$uploadFile = $artifact->getUploadFile()) {
        continue;
      }

      $this->addArtifact($artifact);
    }

    return $this;
  }

  public function uploadArtifacts(): bool
  {
    $res = false;

    // add artifacts
    foreach ($this->getArtifacts() as $artifact) {
      //
      $uploadFile = $artifact->getUploadFile();

      //
      if ($file = $uploadFile->save()) {
        $artifact->setFile($file);
        $res = true;
      }
    }

    return $res;
  }

  public function createRelease(Application $application): ?Release
  {
    //
    $release = new Release();
    $release->Version = $this->getVersion();
    $release->Notes = $this->getNotes();
    $release->Signature = $this->getSignature();
    $release->ApplicationID = $application->ID;

    //
    if (!$release->write()) {
      return null;
    }

    foreach ($this->getArtifacts() as $artifact) {
      //
      $dbArtifact = $artifact->getArtifact();
      $dbArtifact->write();
      //
      $release->Artifacts()->add($dbArtifact);
    }

    return $release;
  }
}
