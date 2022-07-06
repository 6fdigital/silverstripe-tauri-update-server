<?php

namespace SixF\TUS\Controller;

use phpDocumentor\Reflection\Types\Boolean;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Debug;
use SixF\TUS\Model\Application;
use stdClass;

class TauriUpdateServer extends Controller
{
  /**
   * Get params from url and try to find the app and then
   * a proper release-artifact for the given os and arch.
   * @return void
   */
  public function index()
  {
    //
    $params = $this->getRequest()->params();
    $appTitle = Convert::raw2sql($params["Application"]);
    $currentVersion = Convert::raw2sql($params["CurrentVersion"]);

    //
    if (!$osAndArch = $this->_osAndArch($params)) {
      $this->getResponse()->setStatusCode(400, "Unable to parse target-param! Please specify target and arch within the updater endpoints config found in your tauri.conf.json. ({{target}}-{{arch}})");
      return;
    }

    if (!$application = Application::get()->filter(["Title" => $appTitle])->first()) {
      $this->getResponse()->setStatusCode("204", "No Content");
      return;
    }

    if (!$application->canUpdate($currentVersion)) {
      $this->getResponse()->setStatusCode("204", "No Content");
      return;
    }

    //
    if (!$artifact = $application->getArtifact($osAndArch->os, $osAndArch->arch)) {
      $this->getResponse()->setStatusCode("204", "No Content");
      return;
    }

    //
    $this->getResponse()->addHeader("Content-Type", "application/json");
    $this->getResponse()->setStatusCode("200", "OK");

    return $artifact->getJson();
  }

  /**
   * @param $params
   * @return stdClass|null
   */
  protected function _osAndArch($params): ?stdClass {
    // parse target param
    $target = Convert::raw2sql($params["Target"]);
    $targetParts = explode("-", $target);
    if (!$targetParts[0] || !$targetParts[1]) {
      return null;
    }
    //
    $res = new stdClass();
    $res->os = $targetParts[0];
    $res->arch = $targetParts[1];
    return $res;
  }
}
