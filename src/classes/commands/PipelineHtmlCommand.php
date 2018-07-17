<?php
/**
 * Created by PhpStorm.
 * User: henze
 * Date: 15-7-18
 * Time: 14:51
 */

namespace Pipeline\commands;


use nochso\HtmlCompressTwig\Extension;
use Pipeline\factories\PipelineFactory;
use Pipeline\Pipeline;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;

class PipelineHtmlCommand extends Command
{
    /**
     * @var $output OutputInterface
     */
    private $output;
    private $pipelinesHtml;
    private $path = '';
    private $gitlabCiPath = '.gitlab-ci.yml';
    private $openInBrowser = false;
    private $generateHtmlFile = true;
    private $clearCache = true;
    private $only = [];
    private $except = [];
    private $toConsole = false;
    private $pipelines = [];
    private $debugging = false;

    protected function configure()
    {

        $this->setName('gitlab-ci:pipelines')
            ->addOption('show', 'b', InputOption::VALUE_OPTIONAL, 'show output, defaults to browser')
            ->addOption('filename', 'f', InputOption::VALUE_REQUIRED, 'your .gitlab-ci.yml file')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Where to output to')
            ->addOption('only', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Show only for these branches or tags')
            ->addOption('except', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Do not show these branches or tags')
            ->addOption('clear-cache', 'c', null, 'Clear the cache')
            ->addOption('keep-cache', 'k', null, 'Keep cache')
            ->addOption('debug', 'd', null, 'Debugging');

    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->boot($input);


        if ($this->generateHtmlFile) {
            $this->prepareHtml();
            $this->generateHtmlFile();

            if ($this->openInBrowser) {
                $this->openInBrowser();
            }
        }

        if($this->toConsole) {
            $output->write(print_r($this->pipelines));
        }

        if ($this->clearCache) {
            $this->clearCache();
        }
    }

    private function boot(InputInterface $input)
    {
        $this->prepareOptions($input);
        $this->preparePipelines();
    }

    function prepareOptions(InputInterface $input)
    {
        if ($input->getOption('clear-cache')) {
            $this->generateHtmlFile = false;
            $this->clearCache = true;
            return;
        }


        if ($input->hasOption('keep-cache')) {
            $this->clearCache = false;
        }

        if ($input->hasParameterOption(['-d', '--debug'])) {
            $this->debugging = true;
        }


        if ($input->hasParameterOption(['-b', '--show'])) {

            switch($input->getOption('show')) {
                case 'console': $this->toConsole = true;
                    break;
                default: $this->openInBrowser = true;
                    break;
            }

        }

        if ($input->hasOption('filename')) {
            if (file_exists($input->getOption('filename'))) {
                $this->gitlabCiPath = $input->getOption('filename');
            }
        }

        if ($input->hasOption('output')) {
            $this->path = $input->getOption('output');
            $this->clearCache = false;
        } else {
            $this->path = sys_get_temp_dir() . '/' . uniqid('pipelines_') . '.html';
        }

        if ($input->hasOption('only')) {
            $this->only = $input->getOption('only');
        }

        if ($input->hasOption('except')) {
            $this->except = $input->getOption('except');
        }

    }

    private function preparePipelines() {
        $this->pipelines = [];
        /**
         * @var $pipeline Pipeline
         */
        foreach (PipelineFactory::parseFile($this->gitlabCiPath) as $key => $pipeline) {
            if ((empty($this->only) || in_array($pipeline->getName(), $this->only)) && (!in_array($pipeline->getName(), $this->except)))
                $this->pipelines[$key] = $pipeline->toArray();
        }
    }

    private function prepareHtml()
    {

        $vendorDir = __DIR__.'/../../../vendor/';
        if(!file_exists($vendorDir)) {
            $vendorDir = __DIR__.'/../../../../../';
        }
        $twig = new Twig_Environment(new Twig_Loader_Filesystem([
            __DIR__ . '/../../../templates',
           $vendorDir.'twbs/bootstrap/dist',
           $vendorDir.'components',
        ]), [
            'debug'=> $this->debugging
        ]);

        $twig->addExtension(new Extension());

        try {
            $this->pipelinesHtml = $twig->render('pipeline.twig', ['pipelines' => $this->pipelines]);
        } catch (\Twig_Error_Loader $e) {
            $this->output->writeln($e->getMessage());
            exit(2);
        } catch (\Twig_Error_Runtime $e) {
            $this->output->writeln($e->getMessage());
            exit(2);
        } catch (\Twig_Error_Syntax $e) {
            $this->output->writeln($e->getMessage());
            exit(2);
        };
    }

    private function generateHtmlFile()
    {
        file_put_contents($this->path, $this->pipelinesHtml);
    }

    private function openInBrowser()
    {
        exec('DISPLAY=:0;google-chrome ' . $this->path . ' 2> /dev/null');
    }


    private function clearCache()
    {
        $files = glob(sys_get_temp_dir() . '/pipelines_*');
        foreach ($files as $file) {
            unlink($file);
        }

    }


}
