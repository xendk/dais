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
        // Sadly, Silly 1.5 can't inject this for us. So we create it here and
        // move the main logic to another method, so we can test it.
        $io = new SymfonyStyle($input, $output);
        return $this->wait($files, $env, $facade, $io);
    }

    /**
     * Wait for environment.
     */
    public function wait($files, Env $env, PlatformShFacade $facade, SymfonyStyle $io)
    {

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

        $urls = $facade->waitFor($platformId, 'pr-' . $prNum, $sha);
        $placeholderUrls = [];
        foreach ($urls as $index => $url) {
            $placeholder = ($index === 0) ? "%site-url%" : "%route-url:$index%";
            $placeholderUrls[$placeholder] = rtrim($url, '/');
        }

        foreach ($files as $file) {
            try {
                $this->fileReplace($file, $placeholderUrls);
            } catch (\RuntimeException $e) {
                $io->error($e->getMessage());
            }
        }
    }

    /**
     * Replace placeholders in file.
     *
     * Placeholders must be an map of placeholder strings and their corresponding values.
     */
    protected function fileReplace($file, array $placeholderMap)
    {
        if (!file_exists($file)) {
            throw new \RuntimeException($file . " does not exist.");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException("Could not read " . $file . ".");
        }

        $content = strtr($content, $placeholderMap);
        if (file_put_contents($file, $content) === false) {
            throw new \RuntimeException("Error writing " . $file . ".");
        }
    }
}
