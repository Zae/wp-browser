<?php


use Codeception\Configuration;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use tad\WPBrowser\Command\Steppify;

class SteppifyTest extends \Codeception\Test\Unit
{
    /**
     * @var string
     */
    protected $targetContextFile;

    /**
     * @var string
     */
    protected $targetSuiteConfigFile;

    /**
     * @var string
     */
    protected $suiteConfigBackup;
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var array
     */
    protected $targetSuiteConfig = [];

    /**
     * @var FilesystemIterator
     */
    protected $testModules;

    /**
     * @test
     * it should exist as a command
     */
    public function it_should_exist_as_a_command()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'suite' => 'steppifytest'
        ]);
    }

    /**
     * @param Application $app
     */
    protected function addCommand(Application $app)
    {
        $app->add(new Steppify('steppify'));
    }

    /**
     * @test
     * it should return error message if target suite does not exist
     */
    public function it_should_return_error_message_if_target_suite_does_not_exist()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'suite' => 'notexisting'
        ]);

        $this->assertContains("Suite 'notexisting' does not exist", $commandTester->getDisplay());
    }

    /**
     * @test
     * it should generate an helper steps module
     */
    public function it_should_generate_an_helper_steps_module()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'suite' => 'steppifytest',
        ]);

        $this->assertFileExists($this->targetContextFile);
    }

    /**
     * @test
     * it should generate a trait file
     */
    public function it_should_generate_a_trait_file()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'suite' => 'steppifytest',
        ]);

        $this->assertFalse(trait_exists('_generated\SteppifytestGherkinSteps', false));
        $this->assertFileExists($this->targetContextFile);

        require_once $this->targetContextFile;

        $this->assertTrue(trait_exists('_generated\SteppifytestGherkinSteps', false));
    }

    /**
     * @test
     * it should not generate any method if the suite Tester class contains no methods
     * @depends it_should_generate_a_trait_file
     */
    public function it_should_not_generate_any_method_if_the_suite_tester_class_contains_no_methods()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'suite' => 'steppifytest',
        ]);

        $ref = new ReflectionClass('_generated\SteppifytestGherkinSteps');
        $this->assertEmpty($ref->getMethods());
    }

    /**
     * @test
     * it should generate given, when and then step for method by default
     */
    public function it_should_generate_given_when_and_then_step_for_method_by_default()
    {
        $this->backupSuiteConfig();
        $this->setSuiteModules(['\tad\WPBrowser\Tests\ModuleOne']);

        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'suite' => 'steppifytest',
            '--postfix' => $id
        ]);

        $class = 'SteppifytestGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\SteppifytestGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomething'));

        $doSomethingMethod = $ref->getMethod('step_doSomething');
        $methodDockBlock = $doSomethingMethod->getDocComment();

        $this->assertContains('@Given /I do something/', $methodDockBlock);
        $this->assertContains('@When /I do something/', $methodDockBlock);
        $this->assertContains('@Then /I do something/', $methodDockBlock);
    }

    protected function _before()
    {
        $this->targetContextFile = Configuration::supportDir() . '/_generated/SteppifytestGherkinSteps.php';
        $this->targetSuiteConfigFile = codecept_root_dir('tests/steppifytest.suite.dist.yml');
        $this->suiteConfigBackup = codecept_root_dir('tests/steppifytest.suite.dist.yml.backup');

        $this->testModules = new FilesystemIterator(codecept_data_dir('modules'), FilesystemIterator::CURRENT_AS_PATHNAME);
    }

    protected function _after()
    {
        $pattern = Configuration::supportDir() . '_generated/SteppifytestGherkinSteps*.php';
        foreach (glob($pattern) as $file) {
            unlink($file);
        }

        if (file_exists($this->suiteConfigBackup)) {
            unlink($this->targetSuiteConfigFile);
            rename($this->suiteConfigBackup, $this->targetSuiteConfigFile);
        }
    }

    protected function backupSuiteConfig()
    {
        copy($this->targetSuiteConfigFile, $this->suiteConfigBackup);
        $this->targetSuiteConfig = Yaml::parse(file_get_contents($this->targetSuiteConfigFile));
    }

    protected function setSuiteModules(array $modules)
    {
        $shortModules = array_map(function ($module) {
            $frags = explode('\\', $module);
            return end($frags);
        }, $modules);

        foreach ($this->testModules as $testModule) {
            if (in_array(basename($testModule, '.php'), $shortModules)) {
                require_once $testModule;
            }
        }

        $this->targetSuiteConfig['modules']['enabled'] = $modules;
        file_put_contents($this->targetSuiteConfigFile, Yaml::dump($this->targetSuiteConfig));
    }
}
