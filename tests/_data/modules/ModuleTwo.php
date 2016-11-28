<?php
namespace tad\WPBrowser\Tests;

use Codeception\Module;

class ModuleTwo extends Module
{
    public function seeSomething()
    {

    }

    /**
     * @param $name
     *
     * @gherkin then
     */
    public function seeElement($name)
    {

    }

    /**
     * @param string $name
     * @param string $color
     *
     * @gherkin then
     */
    public function seeElementWithColor($name,$color)
    {

    }
}