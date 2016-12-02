<?php

namespace tad\WPBrowser\Command;


use Codeception\Command\ActorGenerator;
use Codeception\Command\Shared\Config;
use Codeception\Command\Shared\FileSystem;
use Codeception\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use tad\WPBrowser\Generator\GherkinSteps;

class Steppify extends Command
{
    use Config;
    use FileSystem;

    protected function configure()
    {
        $this->addArgument('suite', InputArgument::REQUIRED, 'The source suite from the Gherkin steps.')
            ->addOption('postfix', null, InputOption::VALUE_REQUIRED, 'A postfix that should be appended to the the trait file name', '')
            ->addOption('steps-config', null, InputOption::VALUE_REQUIRED, 'The configuration file that should be used to generate the Gherkin steps',
                '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $suite = $input->getArgument('suite');
        $postfix = $input->getOption('postfix');

        if (!in_array($suite, $this->getSuites(null))) {
            $output->writeln("<info>Suite '{$suite}' does not exist.</info>");
            return -1;
        }

        $settings = $this->getSuiteConfig($suite, null);

        $settings['postfix'] = $postfix;

        $settings['steps-config'] = $this->getStepsGenerationConfig($input);

        $generator = new GherkinSteps($suite, $settings);

        $output->writeln("<info>Generating Gherkin steps from suite '{$suite}...'</info>");

        $content = $generator->produce();

        $file = $this->buildPath(
                Configuration::supportDir() . '_generated',
                $settings['class_name']
            ) . ucfirst($suite) . 'GherkinSteps' . $postfix;
        $file .= '.php';

        return $this->save($file, $content);
    }

    /**
     * @param InputInterface $input
     * @return array|mixed
     */
    protected function getStepsGenerationConfig(InputInterface $input)
    {
        $stepsConfigFile = empty($input->getOption('steps-config')) ?
            Configuration::testsDir() . 'steppify.yml' :
            $input->getOption('steps-config');

        $stepsConfig = file_exists($stepsConfigFile) ?
            Yaml::parse(file_get_contents($stepsConfigFile))
            : [];

        return $stepsConfig;
    }
}