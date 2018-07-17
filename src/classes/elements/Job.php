<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 12-7-18
 * Time: 21:32
 */

namespace Pipeline\elements;


class Job
{
    const WHEN_MANUAL = 'manual';
    const WHEN_ON_SUCCESS = 'on_success';
    const WHEN_ON_FAILURE = 'on_failure';
    const WHEN_ALWAYS = 'always';
    private $name;
    private $stageName;
    private $allowFailure = false;
    private $when = 'on_success';
    private $script = [];
    private $variables = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getStageName(): string
    {
        return $this->stageName;
    }

    public function setStageName(string $stageName)
    {
        $this->stageName = $stageName;
    }

    public function setExecuteWhen(string $when)
    {
        $this->when = $when;
        if ($when === self::WHEN_MANUAL) {
            $this->setAllowFailure(true);
        }
    }

    public function toArray(): array
    {
        return array(
            'name' => $this->getName(),
            'allow_failure' => $this->allowFailure(),
            'when' => $this->getExecuteWhen(),
            'variables' => $this->getRawVariables(),
            'parsedVariables' => $this->getVariables(),
            'script' => $this->getRawScript(),
            'parsedScript' => $this->getScript()
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function allowFailure(): bool
    {
        return $this->allowFailure;
    }

    public function setAllowFailure(bool $allowFailure)
    {
        $this->allowFailure = $allowFailure;
    }

    public function getExecuteWhen(): string
    {
        return $this->when;
    }


    public function getVariables(): array
    {
        $variables = [];
        foreach ($this->getRawVariables() as $variable => $value) {
            $variables[$variable] = $this->translateWithVariables($value);
        }

        return $variables;
    }

    private function translateWithVariables(string $text, bool $useParsed = false)
    {
        $variables = $useParsed?$this->getVariables():$this->getRawVariables();
        preg_match_all("/\\$\{{0,1}([^\\s\W}]*)\}{0,1}/", $text, $output_array);
        $keys = array_unique($output_array[0]);
        $values = array_unique($output_array[1]);
        foreach ($values as $key => $value) {
            if (array_key_exists($value, $variables)) {
                $values[$key] = $variables[$value];
            } else {
                unset($keys[$key]);
                unset($values[$key]);
            }

        }

        return str_replace($keys, $values, $text);

    }

    public function getRawVariables(): array
    {
        return $this->variables;
    }

    public function getScript(): array
    {
        $script = [];
        foreach ($this->getRawScript() as $scriptLine) {
            $script[] = $this->translateWithVariables($scriptLine, true);
        }

        return $script;
    }

    public function getRawScript(): array
    {
        return $this->script;
    }

    public function addScriptLine(string $scriptLine)
    {
        $this->script[] = $scriptLine;
    }

    public function addVariable(string $variable, string $value)
    {
        $this->variables[$variable] = $value;
    }


}
