<?php

namespace Dais;

use Platformsh\Client\PlatformClient;

class PlatformShFacade
{
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
        $project = $this->client->getProject($projectId);
        if (!$project) {
            throw new \RuntimeException(sprintf('Project %s not found.', $projectId));
        }

        $environment = $project->getEnvironment($environmentName);
        if (!$environment) {
            throw new \RuntimeException(sprintf('Environment %s not found.', $environment));
        }

        // Environments in the process of being deployed will return the 'dirty' status.
        if (!in_array($environment['status'], ['active', 'dirty'])) {
            throw new \RuntimeException(sprintf('Environment %s not active.', $environmentName));
        }

        $activities = $environment->getActivities(10);
        $waitActivity = null;
        foreach ($activities as $activity) {
            if ($activity['type'] == 'environment.push' &&
                isset($activity['parameters']['github-pr-head']) &&
                $activity['parameters']['github-pr-head'] == $sha) {
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
     * Get a Platform.sh client.
     */
    public static function getClient($apiKey)
    {
        $client = new PlatformClient();
        $client->getConnector()->setApiToken($apiKey, 'exchange');
        return $client;
    }
}
