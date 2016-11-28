<?php

namespace tad\WPBrowser\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use tad\WPBrowser\Generator\WPUnit;


/**
 * Generates skeleton for unit test as in classical PHPUnit.
 *
 * * `wpcept g:wpunit unit UserTest`
 * * `wpcept g:wpunit unit User`
 * * `wpcept g:wpunit unit "App\User`
 *
 */
class GenerateWPUnit extends Command
{
	use \Codeception\Command\Shared\FileSystem;
	use \Codeception\Command\Shared\Config;

	const SLUG = "generate:wpunit";

	public function getDescription()
	{
		return 'Generates a WPTestCase: a WP_UnitTestCase extension with Codeception additions.';
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$suite = $input->getArgument('suite');
		$class = $input->getArgument('class');

		$config = $this->getSuiteConfig($suite, $input->getOption('config'));

		$path = $this->buildPath($config['path'], $class);

		$filename = $this->completeSuffix($this->getClassName($class), 'Test');
		$filename = $path . $filename;

		$gen = $this->getGenerator($config, $class);

		$res = $this->save($filename, $gen->produce());
		if (!$res) {
			$output->writeln("<error>Test $filename already exists</error>");
			exit;
		}

		$output->writeln("<info>Test was created in $filename</info>");
	}

	/**
	 * @param $config
	 * @param $class
	 *
	 * @return WPUnit
     */
	protected function getGenerator($config, $class)
	{
		return new WPUnit($config, $class, '\\Codeception\\TestCase\\WPTestCase');
	}

	protected function configure()
	{
		$this->setDefinition(array(

			new InputArgument('suite', InputArgument::REQUIRED, 'suite where tests will be put'),
			new InputArgument('class', InputArgument::REQUIRED, 'class name'),
			new InputOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Use custom path for config'),
		));
		parent::configure();
	}

}

