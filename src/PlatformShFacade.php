<?php

namespace Dais;

use Platformsh\Client\Model\Activity;
use Platformsh\Client\PlatformClient;
use Platformsh\Client\Model\Project;

class PlatformShFacade
{
    const PLATFORM_KEY_ERROR = "Please set the DAIS_PLATFORMSH_KEY env var to a valid Platform.sh API key.";

    /**
     * Platform.sh client.
     *
     * @var PlatformClient
     */
    protected $client;

    /**
     * Create new facade for client.
     */
    public function __construct(PlatformClient $client)
    {
        $this->client = $client;
    }

    /**
     * Wait for environment to be ready and return a list of urls for the environment.
     *
     * The first entry is the public url for the environment.
     * Any following entries are urls to routes for the environment sorted alphabetically.
     */
    public function waitFor($projectId, $environmentName, $sha)
    {
        $environment = $this->getEnvironment($this->getProject($projectId), $environmentName);

        // Environments in the process of being deployed will return the 'dirty' status.
        if (!in_array($environment['status'], ['active', 'dirty'])) {
            throw new \RuntimeException(sprintf('Environment %s not active.', $environmentName));
        }

        $activities = $environment->getActivities(10, 'environment.push');
        $waitActivity = null;
        foreach ($activities as $activity) {
            if ($this->getSha($activity) == $sha) {
                $waitActivity = $activity;
                break;
            }
        }

        if (!$waitActivity) {
            throw new \RuntimeException(sprintf('Activity for sha %s not found.', $sha));
        }

        if (!$waitActivity->isComplete()) {
            $waitActivity->wait();
        }

        $routeUrls = $environment->getRouteUrls();
        // Platform.sh returns urls in a unpredictable order. Sort it alphabetically to make it predictable.
        sort($routeUrls);
        return array_merge(
            [$environment->getPublicUrl()],
            $routeUrls
        );
    }

    /**
     * Get a Platform.sh project.
     */
    protected function getProject($projectId)
    {
        $project = $this->client->getProject($projectId);
        if (!$project) {
            throw new \RuntimeException(sprintf('Project %s not found.', $projectId));
        }

        return $project;
    }

    /**
     * Get an environment from a project.
     */
    protected function getEnvironment(Project $project, $environmentName)
    {
        $environment = $project->getEnvironment($environmentName);
        if (!$environment) {
            throw new \RuntimeException(sprintf('Environment %s not found.', $environment));
        }

        return $environment;
    }

    /**
     * Returns the Git SHA which an activity corresponds to.
     */
    protected function getSha(Activity $activity) {
        if (isset($activity['parameters']['github-pr-head'])) {
            // Environments are built post-merge so we have to compare the PR head.
            return $activity['parameters']['github-pr-head'];
        }
        if (isset($activity['parameters']['new_commit'])) {
            // Environments are built as normal so we compare the new commit.
            return $activity['parameters']['new_commit'];
        }
    }

    /**
     * Get a Platform.sh client.
     */
    public static function getClient($apiKey)
    {
        $client = new PlatformClient();
        $client->getConnector()->setApiToken($apiKey, 'exchange');
        return $client;
    }

    /**
     * Get a Platform.sh client, configured by environment variables.
     */
    public static function fromEnv(Env $env)
    {
        $token = $env->get('DAIS_PLATFORMSH_KEY', self::PLATFORM_KEY_ERROR);

        return new PlatformShFacade(self::getClient($token));
    }
}
