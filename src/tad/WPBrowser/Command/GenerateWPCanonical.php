<?php

namespace tad\WPBrowser\Command;


use tad\WPBrowser\Generator\WPUnit;

class GenerateWPCanonical extends GenerateWPUnit
{
	const SLUG = 'generate:wpcanonical';

	public function getDescription()
	{
		return 'Generates a WPCanonicalTestCase: a WP_Canonical_UnitTestCase extension with Codeception additions.';
	}

	protected function getGenerator($config, $class)
	{
		return new WPUnit($config, $class, '\\Codeception\\TestCase\\WPCanonicalTestCase');
	}
}