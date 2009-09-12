<?php
/* add to mercurial asap. output buffering due to smush.it. */
ob_start();

require_once 'FrontController.php';
require_once 'AbsolutelyCool.php';

class Bootstrap
{
    public static function startup()
    {
        $b = new Bootstrap;
        $b->run();
    }

    public function run()
    {
        $this->initializeSpriteGenerator();
        $this->initializeFrontController();
        $this->localSpritePath = $this->frontController->processRequestAndGenerateSprite($_GET['absolute']);
        $this->constructResponseAndEmit();
    }

    public function initializeSpriteGenerator()
    {
        $ac = new AbsolutelyCool;
        $savePath = $this->createRandomDirectory();
        $ac->setSavePath($savePath . '/');
        $this->absolutelyCool = $ac;
    }

    public function createRandomDirectory()
    {
        $random = rand();
        $hashed = md5($random);
        $shortened = substr($hashed, 1, 10);
        $savePath = dirname(dirname(__FILE__)) . '/public_images/' . $shortened;
        mkdir($savePath);
        return $savePath;
    }    

    public function initializeFrontController()
    {
        $fc = new FrontController;
        $fc->setAbsolutelyCool($this->absolutelyCool);
        $fc->setWebRoot('/var/www/html/');
        $fc->setRootUrl('http://localhost/');
        $this->frontController = $fc;
    }

    private $frontController;
    private $absolutelyCool;

    private $localSpritePath;

    public function constructResponseAndEmit()
    {
        $fc = $this->frontController;
        $ac = $this->absolutelyCool;
        $localSpritePath = $this->localSpritePath;

        // replace local with web path, stuff into array, 
        $webPathAsArray = $fc->constructResponse($localSpritePath);

        // here FC should decide what to do
        // based on the format of the response.
        // This should be pushed into FC.

        if ($_GET['format'] == 'json') {
            $fc->emitJsonResponse($webPathAsArray, $localSpritePath);
        } elseif ($_GET['format'] == 'image') {
            $fc->emitImageResponse($localSpritePath);
        }
    }

}

Bootstrap::startup();
