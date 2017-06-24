<?php

namespace Dais;

use Symfony\Component\Console\Style\SymfonyStyle;

class WaitCommand
{
    /**
     * Invoke the wait command.
     */
    public function __invoke($file, SymfonyStyle $io)
    {
        $placeholder = '%site-url%';

        if (empty($file) || !file_exists($file)) {
            $io->error('Please supply a file with a ' . $placeholder . ' placeholder to rewrite.');
            return 1;
        }

        $token = trim(getenv('DAIS_PLATFORMSH_KEY'));
        if (empty($token)) {
            $io->error("Please set the DAIS_PLATFORMSH_KEY env var to a valid Platform.sh API key.");
            return 1;
        }

        $platformId = trim(getenv('DAIS_PLATFORMSH_ID'));
        if (empty($platformId)) {
            $io->error("Please set the DAIS_PLATFORMSH_ID env var to Platform.sh site.");
            return 1;
        }

        $sha = trim(getenv('CIRCLE_SHA1'));
        if (empty($sha)) {
            $io->error("Could not find a SHA from CircleCI.");
            return 1;
        }

        $prNum = '';
        if (preg_match('/(\d+)$/', trim(getenv('CI_PULL_REQUEST')), $matches)) {
            $prNum = $matches[1];
        }
        if (empty($prNum)) {
            $io->error("Could not find a pull request number from CircleCI.");
            return 1;
        }

        try {
            $facade = new PlatformShFacade(PlatformShFacade::getClient($token));
            $url = $facade->waitFor($platformId, 'pr-' . $prNum, $sha);
            $url = rtrim($url, '/');
            $content = file_get_contents($file);
            $content = preg_replace("/$placeholder/", $url, $content);
            file_put_contents($file, $content);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }
    }
}
