<?php

namespace tad\WPBrowser\Command;


use tad\WPBrowser\Generator\WPUnit;

class GenerateWPRestApi extends GenerateWPUnit
{
	use Shared\FileSystem;
	use Shared\Config;

	const SLUG = 'generate:wprest';

	public function getDescription()
	{
		return 'Generates a WPRestApiTestCase: a WP_Test_REST_TestCase extension with Codeception additions.';
	}

	protected function getGenerator($config, $class)
	{
		return new WPUnit($config, $class, '\\Codeception\\TestCase\\WPRestApiTestCase');
	}
}