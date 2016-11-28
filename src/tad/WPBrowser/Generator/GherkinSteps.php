<?php

namespace tad\WPBrowser\Generator;


use Codeception\Configuration;
use Codeception\Exception\ConfigurationException;
use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\Util\Template;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlockFactory;

class GherkinSteps
{
    protected $template = <<<EOF
<?php  //[STAMP] {{hash}}
namespace {{namespace}}_generated;

// This class was automatically generated by the steppify task
// You should not change it manually as it will be overwritten on next steppify run
// @codingStandardsIgnoreFile

trait {{name}}GherkinSteps{{postfix}}
{
        
    /**
     * [!] Utility method is generated from steppify task.
     *
     * Converts any TableNode found in an array to an array of associative arrays.
     *
     * @param array \$args An array of arguments that should be parsed to convert TableNode
     *              to arrays.
     * @param array \$iterations Passed by reference; will be set to empty array if there
     *              there are no TableNode arguments among the arguments, will be set to
     *              an array of function call arguments if found.
     */
    public function _convertTableNodesToArrays(array \$args, &\$iterations = []) {
        foreach(\$args as \$key => \$value) {
            if(is_a(\$value, 'Behat\Gherkin\Node\TableNode')){
                \$rows = \$value->getRows();
                \$keys = array_shift(\$rows);
                \$array_value = array_map(function(array \$row) use (\$keys) {
                    return array_combine(\$keys,\$row);
                }, \$rows);
                
                \$iterations[] = array_replace(\$args, [\$key => \$array_value]);
            }
        }
        
        return \$args;
    }
    
    {{methods}}
}

EOF;

    protected $methodTemplate = <<<EOF
    /**
     * [!] Method is generated from steppify task. Documentation taken from corresponding module.
     *
     {{gherkinDoc}}
     *
     * @see \{{module}}::{{method}}()
     */
    public function {{action}}({{params}}) {
        \$args = \$this->_convertTableNodesToArrays(func_get_args(), \$iterations);
        
        if(!empty(\$iterations)) {
            \$returnValues = [];
            foreach(\$iterations as \$iteration){
                \$returnValues[] = \$this->getScenario()->runStep(new \Codeception\Step\Action('{{method}}', \$iteration));
            }
            
            return \$returnValues;
        }
        
        return \$this->getScenario()->runStep(new \Codeception\Step\Action('{{method}}', \$args));
    }
EOF;

    /**
     * @var string
     */
    protected $suite;

    /**
     * @var array
     */
    protected $settings;
    protected $di;
    protected $moduleContainer;
    protected $modules;
    protected $actions;

    public function __construct($suite, array $settings = [])
    {
        $this->settings = $settings;
        $this->suite = $suite;

        $this->di = new Di();
        $this->moduleContainer = new ModuleContainer($this->di, $settings);

        $modules = Configuration::modules($this->settings);
        foreach ($modules as $moduleName) {
            $this->moduleContainer->create($moduleName);
        }

        $this->modules = $this->moduleContainer->all();
        $this->actions = $this->moduleContainer->getActions();
    }

    public function produce()
    {
        $namespace = rtrim($this->settings['namespace'], '\\');

        $methods = $this->getMethods();

        return (new Template($this->template))
            ->place('hash', $this->generateHash())
            ->place('namespace', $namespace ? $namespace . '\\' : '')
            ->place('name', ucfirst($this->suite))
            ->place('postfix', $this->settings['postfix'])
            ->place('methods', $methods)
            ->produce();
    }

    protected function generateHash()
    {
        return (md5(serialize($this->suite) . serialize($this->settings)));
    }

    /**
     * @return string
     * @gherkin given, when, then
     */
    protected function getMethods()
    {
        // generate the method template
        $methods = [];
        foreach ($this->actions as $method => $module) {
            if (!class_exists($module)) {
                $module = '\\Codeception\\Module\\' . $module;
                if (!class_exists($module)) {
                    throw new ConfigurationException("Module '{$module}' does not exist.");
                }
            }

            if ($this->shouldSkipMethod($module, $method)) {
                continue;
            }

            $methods[] = (new Template($this->methodTemplate))
                ->place('module', ltrim($module, '\\'))
                ->place('method', $method)
                ->place('gherkinDoc', $this->getGherkingDoc($module, $method))
                ->place('action', 'step_' . $method)
                ->place('params', $this->getParams($module, $method))
                ->produce();
        }

        return implode(PHP_EOL, $methods);
    }

    /**
     * @param $steps
     * @param $method
     * @return string
     */
    protected function generateGherkinStepsNotations($steps, $method)
    {
        $lines = [];
        foreach ($steps as $step) {
            $words = array_map('strtolower', preg_split('/(?=[A-Z_])/', $method));
            $lines[] = sprintf('* @%s /I %s/', ucfirst(trim($step)), preg_quote(implode(' ', $words)));
        }
        $doc = implode(PHP_EOL . "\t ", $lines);
        return $doc;
    }

    /**
     * @param string $module
     * @param string $method
     *
     * @return string
     */
    protected function getGherkingDoc($module, $method)
    {
        $docBlockFactory = DocBlockFactory::createInstance();
        $docComment = (new \ReflectionMethod($module, $method))->getDocComment();
        $steps = ['given', 'when', 'then'];

        if (empty($docComment)) {
            $gherkinDoc = $this->generateGherkinStepsNotations($steps, $method);
            return $gherkinDoc;
        } else {
            $docBlock = $docBlockFactory->create($docComment);
            $gherkinTags = $docBlock->getTagsByName('gherkin');

            if (!empty($gherkinTags)) {
                /** @var Generic $gherkingTag */
                $gherkingTag = reset($gherkinTags);
                $steps = preg_split('/\\s*,\\s*/', $gherkingTag->getDescription()->render());
            }
        }

        return $this->generateGherkinStepsNotations($steps, $method);
    }

    /**
     * @param string $module
     * @param string $method
     *
     * @return string
     */
    protected function getParams($module, $method)
    {
        $method = new \ReflectionMethod($module, $method);

        $params = $method->getParameters();

        if (empty($params)) {
            return '';
        }

        return implode(', ', array_map([$this, 'getEntryForParameter'], $params));
    }

    /**
     * @param \ReflectionParameter $parameter
     *
     * @return string
     */
    protected function getEntryForParameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType() ? $parameter->getType() : $parameter->getClass();

        if (!empty($type)) {
            $type = $type->isBuiltin() && $type->__toString() === 'array' ? '\Behat\Gherkin\Node\TableNode' : $type->__toString();
        }

        $name = $parameter->getName();
        $defaultValue = $parameter->isOptional() ? $parameter->getDefaultValue() : false;

        if (empty($defaultValue) && empty($type)) {
            return sprintf('$%s', $name);
        } elseif (empty($defaultValue)) {
            return sprintf('%s $%s', $type, $name);
        } else {
            $defaultValue = is_string($defaultValue) ? "'" . $defaultValue . "'" : $defaultValue;
            return sprintf('%s $%s = %s', $type, $name, $defaultValue);
        }
    }

    protected function shouldSkipMethod($module, $method)
    {
        $docBlockFactory = DocBlockFactory::createInstance();
        $docBlock = (new \ReflectionMethod($module, $method))->getDocComment();

        if (empty($docBlock)) {
            return false;
        }

        $docBlock = $docBlockFactory->create($docBlock);
        $gherkinTags = $docBlock->getTagsByName('gherkin');

        if (empty($gherkinTags)) {
            return false;
        }

        /** @var Generic $tag */
        $tag = $gherkinTags[0];
        return preg_match('/(N|n)(o|O)/', $tag->getDescription()->render());
    }
}
