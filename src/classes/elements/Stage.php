<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 12-7-18
 * Time: 18:58
 */

namespace Pipeline\elements;


class Stage
{
    private $name;
    private $jobs = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addJob(Job $job)
    {
        $this->jobs[$job->getName()] = $job;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        $jobs = [];
        foreach ($this->jobs as $job) {
            $jobs[] = $job->toArray();
        }
        return $jobs;
    }
}