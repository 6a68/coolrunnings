<?php

// this class is an absolute-coordinates sprite generator.

class AbsolutelyCool
{

    public function __construct()
    {
        // really need to move the filesystem stuff out
        // of the sprite-generator. it's all confused in here.
        // just need to initialize this, explicitly, somewhere.
        $this->setSavePath(dirname(dirname(__FILE__)) . '/public_images/');
    }
    // not really a good idea to base the API on
    // a bad pun: coolRunnings, so why not AbsolutelyCool->runnings()?
    // but here we are. I'll refactor this later.
    public function runnings($bigInputArray)
    {
        if ($this->appendRandomDir) {
            $this->setRandomSaveDirectory();
        }
        $blankCanvas = $this->generateCanvas($bigInputArray['canvas']);
        $localImages = $this->downloadImages($bigInputArray['images']);
        $sprite = $this->generateSprite($blankCanvas, 
                                        $bigInputArray['images'],
                                        $localImages);
        $commentedSprite = $this->setComments($sprite,
                                    $bigInputArray['canvas']['comments']);
        
        $this->saveSpriteAs($bigInputArray['canvas']['name'], $commentedSprite);
        $spritePath = $this->fileSavePath . $bigInputArray['canvas']['name'] . '.png';
        $this->spriteSize = $this->getFilesizeInBytes($spritePath);
        $this->spriteHeight = $sprite->getImageHeight();
        $this->spriteWidth  = $sprite->getImageWidth();
        return $spritePath;
    }

    protected $appendRandomDir = true;
    public function dontAppendRandomSaveDirectory()
    {
        $this->appendRandomDir = false;
    }

    public function generateCanvas($canvasParameters)
    {
        $canvas = new Imagick();
        $canvas->newImage($canvasParameters['width'],
                          $canvasParameters['height'],
                          $canvasParameters['background-color'],
                          $fileFormat = 'png');
        return $canvas;
    }

    // the file_get_contents serial looping is going to
    // be replaced with a parallel cURL download thing.
    public function getLocalCopyOfImage($url, $localFilename)
    {
        $file = file_get_contents($url);
        $completeLocalPath = $this->fileSavePath . $localFilename;
        $handle = fopen($completeLocalPath, 'w');
        if (fputs($handle, $file)) {
            return $this->fileSavePath . $localFilename;
        }
    }

    public function downloadImages($allImages)
    {
        $localImages = array();
        foreach ($allImages as $imageParameters) {
            $localImages[] = $this->getLocalCopyOfImage($imageParameters['url'],
                                                $localTempFile = md5(microtime()) . '.png');
        }
        return $localImages;
    }

    public function generateSprite(Imagick $canvas, $allImages, $localImages)
    {
        // use the index to get what we need from localImages
        foreach ($allImages as $i => $imageParameters) {
            $this->totalInputSize += $this->getFilesizeInBytes($localImages[$i]);
            $imageToAdd = new Imagick($localImages[$i]);
            $canvas->compositeImage($imageToAdd, 
                                    imagick::COMPOSITE_OVER,
                                    $xOffset = $imageParameters['left'],
                                    $yOffset = $imageParameters['top']);
            $imageToAdd->clear();
            $imageToAdd->destroy();
        }

        return $canvas;
    }


    public function setRandomSaveDirectory()
    {
        $dir = $this->createRandomDirectory();
        $this->setSavePath($dir);
    }

    public function createRandomDirectory()
    {
        $random = rand();
        $hashed = md5($random);
        $shortened = substr($hashed, 1, 10);
// this assumes the base save path is '../public_images/' which 
// defeats the point of separately setting $this->fileSavePath.
        $savePath = $this->fileSavePath . $shortened . '/';
        mkdir($savePath);
        return $savePath;
    }    

    protected $totalInputSize;

    public function getInputSize()
    {
        return $this->totalInputSize;
    }

    public function setComments(Imagick $canvas, $comments)
    {
        $canvas->commentImage($comments);
        return $canvas;
    }

    public function getComments(Imagick $canvas)
    {
        return $canvas->getImageProperty('comment');
    }

    protected $spriteSize;

    public function getSpriteSize()
    {
        return $this->spriteSize;
    }

    protected $spriteHeight;

    public function getSpriteHeight()
    {
        return $this->spriteHeight;
    }

    protected $spriteWidth;

    public function getSpriteWidth()
    {
        return $this->spriteWidth;
    }

    protected $fileSavePath;

    public function setSavePath($path)
    {
        $this->fileSavePath = $path;
    }

    public function saveSpriteAs($filename, Imagick $sprite)
    {
        $filename = $this->fileSavePath . $filename . '.png';
        if (($sprite->writeImage($filename)) !== true) {
            throw new RuntimeException('unable to write sprite to ' . $filename);
        }
    }

    public function getFilesizeInBytes($file)
    {
        $fileInfo = new SplFileInfo($file);
        return $fileInfo->getSize();
    }
}
