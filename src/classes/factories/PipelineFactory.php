<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 12-7-18
 * Time: 19:01
 */

namespace Pipeline\factories;

use Pipeline\elements\Job;
use Pipeline\Pipeline;
use Symfony\Component\Yaml\Yaml;

class PipelineFactory
{
    const RESERVED_WORDS = ['image', 'variables', 'stages', 'cache', 'before_script', 'feature-branch'];

    /**
     * @param string $path
     * @return Pipeline[]
     */
    public static function parseFile(string $path): array
    {
        return self::parse(self::parseYmlFile($path));
    }

    /**
     * @param array $ymlContent
     * @return Pipeline[]
     */
    public static function parse(array $ymlContent): array
    {
        $pipelines = [];
        $variables = [];
        if (isset($ymlContent['stages'])) {
            foreach ($ymlContent['stages'] as $key => $stageName) {
                $stages[] = $stageName;
            }
        }


        if (isset($ymlContent['variables'])) {
            foreach ($ymlContent['variables'] as $key => $variable) {
                $variables[$key] = $variable;
            }
        }

        foreach ($ymlContent as $jobName => $jobSettings) {
            if (self::isValidJobName($jobName)) {
                $only = $jobSettings['only'] ?? [];
                foreach ($only as $ref) {
                    $pipelines[$ref] = $pipelines[$ref] ?? PipelineFactory::make($ref, $stages);
                    $pipelines[$ref]->addJob(
                        JobFactory::make(
                            $jobName,
                            $jobSettings['stage'],
                            $jobSettings['when'] ?? Job::WHEN_ON_SUCCESS,
                            array_merge($variables, $jobSettings['variables'] ?? []),
                            $jobSettings['script'] ?? [],
                            $jobSettings['allow_failure'] ?? null
                        )
                    );
                }
            }
        }


        foreach ($ymlContent as $jobName => $jobSettings) {

            if (self::isValidJobName($jobName)) {
                $except = $jobSettings['except'] ?? [];
                if (empty($jobSettings['only'])) {
                    foreach ($pipelines as $pipeline) {
                        if (!in_array($pipeline->getName(), $except)) {
                            $pipeline->addJob(
                                JobFactory::make(
                                    $jobName,
                                    $jobSettings['stage'],
                                    $jobSettings['when'] ?? Job::WHEN_ON_SUCCESS,
                                    array_merge($variables, $jobSettings['variables'] ?? []),
                                    $jobSettings['script'] ?? [],
                                    $jobSettings['allow_failure'] ?? null
                                )
                            );
                        }
                    }
                    $pipelines = array_reverse($pipelines);
                    $pipelines['feature-branch'] = $pipelines['feature-branch'] ?? PipelineFactory::make('feature branches', $stages);
                    $pipelines = array_reverse($pipelines);
                    $pipelines['feature-branch']->addJob(
                        JobFactory::make(
                            $jobName,
                            $jobSettings['stage'],
                            $jobSettings['when'] ?? Job::WHEN_ON_SUCCESS,
                            array_merge($variables, $jobSettings['variables'] ?? []),
                            $jobSettings['script'] ?? [],
                            $jobSettings['allow_failure'] ?? null
                        )
                    );
                }

            }
        }

        return $pipelines;
    }

    private static function isValidJobName(string $name)
    {
        return !in_array($name, self::RESERVED_WORDS) && strpos($name, '.') === false;
    }

    /**
     * @param string $name
     * @return Pipeline
     */
    public static function make(string $name, array $stages): Pipeline
    {
        $pipeline = new Pipeline($name);
        foreach ($stages as $stage) {
            $pipeline->addStage(StageFactory::make($stage));
        }
        return $pipeline;

    }

    /**
     * Returns the  yml configuration in an array
     *
     * This preprocesses the yaml file first to correct for gitlabs non-standard array-notation (dot-notation)
     *
     * @param string $path
     * @return array
     */
    private static function parseYmlFile(string $path): array
    {
        if(!file_exists($path)) {
            throw new \Exception('file does not exist');
        }
        $ymlContent = file_get_contents($path);

        $ymlContent = preg_replace("/(^\..*):\s{0,}(&(.*)){0,1}/m", "$1|-|$3: $2", $ymlContent);


        $yml = Yaml::parse($ymlContent);

        foreach (($yml ?? []) as $key => $value) {
            if (strpos($key, '.') === 0) {
                $keyName = explode('|-|', $key);
                $reference = $keyName[1] ?? count($yml[$keyName[0]]);
                $yml[$keyName[0]][$reference] = $value;
            }
        }

        return $yml ?? [];
    }
}
