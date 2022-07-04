<?php

namespace SixF\TUS\Controller;

use phpDocumentor\Reflection\Types\Boolean;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Debug;
use SixF\TUS\Model\Application;

class TauriUpdateServer extends Controller
{
    public function index() {
        //
        $params = $this->getRequest()->params();
        $appTitle = Convert::raw2sql($params["Application"]);
        $target = Convert::raw2sql($params["Target"]);
        $currentVersion = Convert::raw2sql($params["CurrentVersion"]);

        if (!$application = Application::get()->filter(["Title" => $appTitle])->first()) {
            $this->getResponse()->setStatusCode("204", "No Content");
            return;
        }

        if (!$application->canUpdate($currentVersion)) {
            $this->getResponse()->setStatusCode("204", "No Content");
            return;
        }

        //
        if (!$artifact = $application->getArtifact($target)) {
            $this->getResponse()->setStatusCode("204", "No Content");
            return;
        }

        //
        $this->getResponse()->addHeader("Content-Type", "application/json");
        $this->getResponse()->setStatusCode("200", "OK");

        return $artifact->getJson();
    }
}
