<?php

namespace tad\WPBrowser\Command;


use tad\WPBrowser\Generator\WPUnit;

class GenerateWPXMLRPC extends GenerateWPUnit
{
	use \Codeception\Command\Shared\FileSystem;
	use \Codeception\Command\Shared\Config;

	const SLUG = 'generate:wpxmlrpc';

	public function getDescription()
	{
		return 'Generates a WPXMLRPCTestCase: a WP_XMLRPC_UnitTestCase extension with Codeception additions.';
	}

	protected function getGenerator($config, $class)
	{
		return new WPUnit($config, $class, '\\Codeception\\TestCase\\WPXMLRPCTestCase');
	}
}