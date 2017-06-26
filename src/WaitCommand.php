<?php

namespace Dais;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WaitCommand
{
    const PLATFORM_ID_ERROR = "Please set the DAIS_PLATFORMSH_ID env var to Platform.sh site.";
    const CIRCLE_SHA1_ERROR = "Could not find a SHA from CircleCI.";
    const CIRCLE_PULL_REQUEST_ERROR = "Could not find a pull request number from CircleCI.";

    /**
     * Invoke the wait command.
     */
    public function __invoke($files, Env $env, PlatformShFacade $facade, InputInterface $input, OutputInterface $output)
    {
        // Sadly, Silly 1.5 can't inject this for us.
        $io = new SymfonyStyle($input, $output);

        $placeholder = '%site-url%';

        $platformId = $env->get('DAIS_PLATFORMSH_ID', self::PLATFORM_ID_ERROR);
        $sha = $env->get('CIRCLE_SHA1', self::CIRCLE_SHA1_ERROR);

        $prNum = '';
        $pr = $env->get('CI_PULL_REQUEST', self::CIRCLE_PULL_REQUEST_ERROR);
        if (preg_match('/(\d+)$/', $pr, $matches)) {
            $prNum = $matches[1];
        }
        if (empty($prNum)) {
            throw new \RuntimeException(self::CIRCLE_PULL_REQUEST_ERROR);
        }

        $url = $facade->waitFor($platformId, 'pr-' . $prNum, $sha);
        $url = rtrim($url, '/');
        foreach ($files as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $content = preg_replace("/$placeholder/", $url, $content);
                file_put_contents($file, $content);
            } else {
                $io->error($file . " does not exist.");
            }
        }
    }
}
