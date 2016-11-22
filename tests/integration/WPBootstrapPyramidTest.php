<?php

use Symfony\Component\Console\Application;
use tad\WPBrowser\Command\WPBootstrapPyramid;

require_once dirname(__FILE__) . '/WPBootstrapTest.php';

class WPBootstrapPyramidTest extends WPBootstrapTest
{
	/**
	 * @var string
	 */
	protected $functionalSuiteConfigFile = 'tests/service.suite.yml';

	/**
	 * @var string
	 */
	protected $acceptanceSuiteConfigFile = 'tests/ui.suite.yml';

	/**
	 * @param Application $app
	 */
	protected function addCommand(Application $app)
	{
		$app->add(new WPBootstrapPyramid('bootstrap'));
	}
}