<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 12-7-18
 * Time: 21:38
 */

namespace Pipeline\factories;


use Pipeline\elements\Job;

class JobFactory
{
    public static function make(string $name, string $stage, string $when, array $variables, array $scriptLines, bool $allowedToFail = null): Job
    {
        $job = new Job($name);
        $job->setStageName($stage);


        $job->setExecuteWhen($when);
        if(!is_null($allowedToFail)) {
            $job->setAllowFailure($allowedToFail);
        }

        foreach($variables as $variable=>$value) {
            $job->addVariable($variable, $value);
        }

        foreach($scriptLines as $scriptLine) {
            $job->addScriptLine($scriptLine);
        }

        return $job;
    }

}