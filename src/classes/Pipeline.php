<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 12-7-18
 * Time: 19:04
 */

namespace Pipeline;


use PipeLine\elements\Job;
use Pipeline\elements\Stage;

class Pipeline
{
    private $name = '';
    private $stages = [];

    private $jobs = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addStage(Stage $stage)
    {
        $this->stages[$stage->getName()] = $stage;
    }

    public function addJob(Job $job)
    {
        $this->stages[$job->getStageName()]->addJob($job);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        $stages = [];
        foreach ($this->stages as $stage) {
            $stages[$stage->getName()] = $stage->toArray();
        }

        return [
            'name' => $this->getName(),
            'stages' => $stages
        ];
    }

}