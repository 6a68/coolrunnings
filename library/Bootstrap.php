<?php
/* add to mercurial asap. output buffering due to smush.it. */
ob_start();

require_once 'FrontController.php';
require_once 'AbsolutelyCool.php';

class Bootstrap
{

    public function initializeFrontControllerAndSpriteGenerator()
    {
        // I am reasonably sure this is how it works:

            // given a front controller
            
            $fc = new FrontController;
            
        /*****************************************
         *
         * start by inserting an absolutelyCool
         * instance into the front controller
         *
         */
            $ac = new AbsolutelyCool;

            // I guess we have to set AbsolutelyCool path separately.
            // here we introduce the random directory.
            $random = rand();
            $hashed = md5($random);
            $shortened = substr($hashed, 1, 10);
            $savePath = dirname(dirname(__FILE__)) . '/public_images/' . $shortened;
            mkdir($savePath);
            //$ac->setSavePath(dirname(dirname(__FILE__)) . '/public_images/' . $shortened . '/');
            $ac->setSavePath($savePath . '/');

        // Front Controller config and stuff

            $fc->setAbsolutelyCool($ac);

            $fc->setWebRoot('/var/www/html/');
            $fc->setRootUrl('http://localhost/');

        $this->frontController = $fc;
        $this->absolutelyCool = $ac;
    }

    private $frontController;
    private $absolutelyCool;

    public static function startup()
    {
        $b = new Bootstrap;
        $b->run();
    }

    public function processRequest()
    {
        $fc = $this->frontController;
        // Finally we get to the part where 
        // the front controller is translating
        // HTTP input into a form ac can understand

        // all these steps should be pushed from
        // bootstrap into some kind of FC "dispatch" method

            // now: get request and funnel to AC to generate sprite
            // ac returns path where sprite was saved
            $requestAsArray = $fc->decodeRequest($_GET['absolute']);
            $localSpritePath = $fc->dispatch($requestAsArray);
        $this->localSpritePath = $localSpritePath;
    }

    private $localSpritePath;


    public function optimizeSprite()
    {
        $localSpritePath = $this->localSpritePath;

            // now inserting pngcrush wrapper
            require_once 'PngCrush.php';
            $crusher = new PngCrush;
            try {
                $crusher->crush($localSpritePath, $localSpritePath . ".crushed");
                // if crushing succeeds, overwrite original file
                copy($localSpritePath. ".crushed", $localSpritePath);
                unlink($localSpritePath. ".crushed");
            } catch (Exception $e) {}
    }

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
                // convert into json, and emit!
                $webPathAsJson = $fc->responseAsJson($webPathAsArray);
                // trash the buffer
                ob_end_clean();
                $fc->sendResponse($webPathAsJson);
            } elseif ($_GET['format'] == 'image') {
                // trash the buffer
                ob_end_clean();
                    // make an image out of our URL
                if (!isset($webPathAsArray['url'])) {
                    die('error, no url generated. please play again.');
                }
                try {
                        // get local copy of image and save
                        // over the earlier one.
                    $localLocation = $ac->getLocalCopyOfImage($webPathAsArray['url'],
                                                              $localSpritePath);
                        // imagick needs local files. hence this whole song and dance.
                    $imz = new Imagick($localSpritePath);
                } catch (Exception $e) {
                    die('error, image url inaccessible. err msg was ' . 
                        $e->getMessage() .
                        '. thanks, please play again.');
                }

                // at this point, we have a real live image.
                // so display it
                header("Content-Type: image/png");
                echo $imz;
            }
    }

    public function run()
    {
        $this->initializeFrontControllerAndSpriteGenerator();

        $fc = $this->frontController;
        $ac = $this->absolutelyCool;

        $this->processRequest();
    
        $localSpritePath = $this->localSpritePath;

        $this->optimizeSprite();

        $this->constructResponseAndEmit();

    }
}

Bootstrap::startup();
