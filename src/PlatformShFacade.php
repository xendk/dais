<?php

namespace Dais;

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

    public function waitFor($projectId, $environmentName, $sha)
    {
        $environment = $this->getEnvironment($this->getProject($projectId), $environmentName);

        // Environments in the process of being deployed will return the 'dirty' status.
        if (!in_array($environment['status'], ['active', 'dirty'])) {
            throw new \RuntimeException(sprintf('Environment %s not active.', $environmentName));
        }

        $activities = $environment->getActivities(10);
        $waitActivity = null;
        foreach ($activities as $activity) {
            if ($activity['type'] == 'environment.push' &&
                isset($activity['parameters']['new_commit']) &&
                $activity['parameters']['new_commit'] == $sha) {
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

        return $environment->getPublicUrl();
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
