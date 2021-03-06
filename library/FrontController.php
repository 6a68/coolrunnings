<?php

class FrontController
{

    public function run()
    {
        $this->initialize();
        $this->processRequestAndGenerateSprite($_GET['absolute']);
    }

    // this should actually check config,
    // and use these settings as defaults
    // if config is unavailable.
    public function initialize()
    {
        $this->setAbsolutelyCool(new AbsolutelyCool);
        $this->setWebRoot('/var/www/html/');
        $this->setRootUrl('http://localhost/');
        return $this;
    }
    
    public function processRequestAndGenerateSprite($request)
    {
        $requestAsArray = $this->decodeRequest($request);
        $localSpritePath = $this->dispatch($requestAsArray);
        $this->optimizeSprite($localSpritePath);
        $this->constructResponseAndEmit($localSpritePath);        
    }

    public function decodeRequest($inputAsJson)
    {
        $inputAsJson = stripslashes($inputAsJson);
        return json_decode($inputAsJson, true);
    }

    protected $webRootDirectory;
    public function setWebRoot($rootDir)
    {
        $this->webRootDirectory = $rootDir;
    }

    protected $rootUrl;
    public function setRootUrl($rootUrl)
    {
        $this->rootUrl = $rootUrl;
    }

    public function responseAsJson($responseAsArray)
    {
        return json_encode($responseAsArray);
    }

    public function constructResponse($responsePath)
    {
        $filteredResponse = str_replace($this->webRootDirectory,
                                    $this->rootUrl,
                                    $responsePath);
        return array('url' => $filteredResponse);
    }

    public function emitJsonResponse($webPathAsArray, $localSpritePath)
    {
        $ac = $this->absolutelyCool;

        // spriteme bug #15: add input file total size, sprite 
        // size, sprite height, sprite width to json output.
        $webPathAsArray['inputSize'] = $ac->getInputSize();
        $webPathAsArray['outputSize'] = $ac->getFilesizeInBytes($localSpritePath);
        $webPathAsArray['spriteHeight'] = $ac->getSpriteHeight();
        $webPathAsArray['spriteWidth']  = $ac->getSpriteWidth();

        // convert into json, and emit!
        $webPathAsJson = $this->responseAsJson($webPathAsArray);
        // trash the buffer
        ob_end_clean();
        $this->sendResponse($webPathAsJson);
    }

    public function emitImageResponse($localSpritePath)
    {
        $imz = new Imagick($localSpritePath);

        // at this point, we have a real live image.
        // so display it
        header("Content-Type: image/png");
        echo $imz;
    }

    public function optimizeSprite($localSpritePath)
    {
        require_once 'PngCrush.php';
        $crusher = new PngCrush;

        try {
            $crusher->crush($localSpritePath, $localSpritePath . ".crushed");
            
            // if crushing succeeds, overwrite original file
            // if not, an exception will be thrown, and these
            //   overwriting commands won't be executed
            
            copy($localSpritePath. ".crushed", $localSpritePath);
            unlink($localSpritePath. ".crushed");
        } catch (Exception $e) {}
    }

    public function constructResponseAndEmit($localSpritePath)
    {
        // replace local with web path, stuff into array, 
        $webPathAsArray = $this->constructResponse($localSpritePath);

        if ($_GET['format'] == 'json') {
            $this->emitJsonResponse($webPathAsArray, $localSpritePath);
        } elseif ($_GET['format'] == 'image') {
            $this->emitImageResponse($localSpritePath);
        }
    }
    protected $absolutelyCool;

    public function setAbsolutelyCool($aCoolObject)
    {
        $this->absolutelyCool = $aCoolObject;
        return $this;
    }

    public function dispatch($inputAsArray)
    {
        $pathToGeneratedSprite = $this->absolutelyCool->runnings($inputAsArray);
        return $pathToGeneratedSprite;
    }

    public function sendResponse($response)
    {
        header('Content-type: application/json');
        echo 'var coolRunnings = ' . $response;
    }
}
