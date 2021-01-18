<?php

namespace Dais;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WaitCommand
{
    public const PLATFORM_ID_ERROR = "Please set the DAIS_PLATFORMSH_ID env var to Platform.sh site";
    public const MISSING_SHA_ERROR = "Please supply a SHA";
    public const PR_NUM_ERROR = "Invalid pull request number \"%s\"";

    /**
     * Invoke the wait command.
     */
    public function __invoke(
        ?string $sha,
        ?string $prNumber,
        array $files,
        Env $env,
        PlatformShFacade $facade,
        InputInterface $input,
        OutputInterface $output
    ) {
        // Sadly, Silly 1.5 can't inject this for us. So we create it here and
        // move the main logic to another method, so we can test it.
        $io = new SymfonyStyle($input, $output);
        return $this->wait($sha, $prNumber, $files, $env, $facade, $io);
    }

    /**
     * Wait for environment.
     */
    public function wait(
        ?string $sha,
        ?string $prNumber,
        array $files,
        Env $env,
        PlatformShFacade $facade,
        SymfonyStyle $io
    ) {
        $platformId = $env->get('DAIS_PLATFORMSH_ID', self::PLATFORM_ID_ERROR);

        if (empty($sha)) {
            throw new \RuntimeException(self::MISSING_SHA_ERROR);
        }

        if (preg_match('/^[^\d]*(\d+)[^\d]*$/', $prNumber, $matches)) {
            $prNumber = $matches[1];
        } else {
            throw new \RuntimeException(sprintf(self::PR_NUM_ERROR, $prNumber));
        }

        $urls = $facade->waitFor($platformId, 'pr-' . $prNumber, $sha);
        $placeholderUrls = [];

        foreach ($urls as $index => $url) {
            $placeholder = ($index === 0) ? "%site-url%" : "%route-url:$index%";
            $placeholderUrls[$placeholder] = rtrim($url, '/');
        }

        $error = false;
        foreach ($files as $file) {
            try {
                $this->fileReplace($file, $placeholderUrls);
            } catch (\RuntimeException $e) {
                $io->error($e->getMessage());
                $error = true;
            }
        }

        // Set return code for command.
        return $error ? 1 : 0;
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
