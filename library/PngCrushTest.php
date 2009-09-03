<?php

require_once 'simpletest/autorun.php';
require_once 'PngCrush.php';

class PngCrushTest extends UnitTestCase
{
    // start with obvious use-case.
    public function testSuccessfulPngCrush()
    {
        $crusher = new PngCrush;
        $testImageDir = dirname(__FILE__) . '/pngcrush-fixtures/';
        $inputFile = $testImageDir . 'purple.png';
        $outputFile = $testImageDir . 'purple-test-output.png';

        $crusher->crush($inputFile, $outputFile);

        $inputInfo  = new SplFileInfo($inputFile);
        $outputInfo = new SplFileInfo($outputFile);
        $this->assertTrue($outputInfo->isFile());
        //$this->assertTrue($inputInfo->getSize() <= $outputInfo->getSize()); 
    }
}