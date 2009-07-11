<?php

require_once 'simpletest/autorun.php';
require_once 'AbsolutelyCool.php';

class AbsolutelyCoolTest extends UnitTestCase
{
    public function setUp()
    {
        $this->bluebox = new Imagick('bluebox.png');
    }

    public function tearDown()
    {
        $this->bluebox->clear();
        $this->bluebox->destroy();
    }

    public function testShouldCreateBlankImageGivenOutputParameterArray()
    {
        $output = array('name' => 'myBlankTestImage.png',
                        'height' => '50',
                        'width' => '50',
                        'background-color' => 'blue',
                        'comments' => 'these comments are quite lame');

        $ac = new AbsolutelyCool;
        $outputImage = $ac->generateCanvas($output);

        $imageComparison = $outputImage->compareImages($this->bluebox, 
                                            imagick::METRIC_MEANSQUAREERROR);
        $imageDiffMetric = $imageComparison[1];
        $this->assertTrue($imageDiffMetric == 0);
    }

    public function testShouldCreateCanvasWithCorrectDimensions()
    {
        $output = array('name' => 'myBlankTestImage.png',
                        'height' => '50',
                        'width' => '100',
                        'background-color' => 'blue',
                        'comments' => 'these comments are quite lame');

        $ac = new AbsolutelyCool;
        $outputImage = $ac->generateCanvas($output);

        $this->assertEqual('100', $outputImage->getImageWidth());
        $this->assertEqual('50', $outputImage->getImageHeight());
    }

    public function testShouldPutSpritesOnTopOfBackgroundCanvas()
    {
        // given a background of some size, and a "sprite" 
        // of exactly the same size, if we overlay the sprite
        // on the background, the background should be invisible.
        // so comparing with the original sprite should work.

        // we'll use the blue box as the overlaid thing.
        $redBox = array('height' => '50',
                        'width' => '50',
                        'background-color' => 'red');

        $ac = new AbsolutelyCool;
        $redCanvas = $ac->generateCanvas($redBox);

        $blueImageParameters = array('url' => 'bluebox.png',
                                     'top' => 0,
                                     'left' => 0);

        $sprite = $ac->generateSprite($redCanvas, array($blueImageParameters));
        
        $imageComparison = $sprite->compareImages($this->bluebox,
                                            imagick::METRIC_MEANSQUAREERROR);
        $imageDiffMetric = $imageComparison[1];
        $this->assertTrue($imageDiffMetric == 0);
    }

    public function testShouldPlaceTwoSpritesSideBySide()
    {
        // make a red rectangular canvas, 100 wide and 50 high.
        // overlay two blue squares, 50 x 50, over the rectangle
        // with one having a 50 pixel x-offset. 
        // if the sprite generator is working, the resulting
        // sprite should be identical to a blue rectangle
        // that's 100 wide and 50 high.

        // this test will get us multiple sprites, so probably
        // some kind of loop.

        $redRectangle = array('height' => 50,
                              'width'  => 100,
                              'background-color' => 'red');
        $ac = new AbsolutelyCool;
        $redRectangleCanvas = $ac->generateCanvas($redRectangle);
        
        $blueBox = array('url' => 'bluebox.png',
                         'top' => 0,
                         'left' => 0);
        $otherBlueBox = array('url' => 'bluebox.png',
                              'top' => 0,
                              'left' => 50);

        $sprite = $ac->generateSprite($redRectangleCanvas,
                                        array($blueBox, $otherBlueBox));

        $blueRectangle = new Imagick();
        $blueRectangle->newImage($width = 100, $height = 50, 
                                $backgroundColor = 'blue', $format = 'png');

        $compared = $blueRectangle->compareImages($sprite, 
                                        imagick::METRIC_MEANSQUAREERROR);
        $imageDiffMetric = $compared[1];

        $this->assertTrue($imageDiffMetric == 0);

    }

    public function testShouldBeAbleToSetAndGetSpriteComments()
    {
        $ac = new AbsolutelyCool;
        $canvas = $ac->generateCanvas(array('height' => 50, 
                                            'width' => 50,
                                            'background-color' => 'black'));
        
        $inputComment = 'IT IS YOUR BIRTHDAY.';
        
        $canvas = $ac->setComments($canvas, $comment);
        $returnedComments = $ac->getComments($canvas);
        $this->assertEqual($inputComment, $returnedComment);
    }
}
