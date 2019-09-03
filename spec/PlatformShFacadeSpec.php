<?php

namespace spec\Dais;

require_once('vendor/autoload.php');

use Dais\PlatformShFacade;
use PhpSpec\ObjectBehavior;
use Platformsh\Client\Model\Activity;
use Platformsh\Client\Model\Environment;
use Platformsh\Client\Model\Project;
use Platformsh\Client\PlatformClient;
use Prophecy\Argument;

class PlatformShFacadeSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(PlatformShFacade::getClient('dummy'));
    }

    function it_is_initializable(PlatformClient $client)
    {
        $this->beConstructedWith($client);
        $this->shouldHaveType(PlatformShFacade::class);
    }

    function it_throws_error_if_project_not_found(PlatformClient $client)
    {
        $this->beConstructedWith($client);

        $this->shouldThrow(\RuntimeException::class)->duringWaitFor('project_id', 'env', 'sha');
    }

    function it_throws_error_if_environment_not_found(PlatformClient $client, Project $project)
    {
        $client->getProject('project_id')->willReturn($project);

        // Use zero timeout to avoid retrying.
        $this->beConstructedWith($client, 0);

        $this->shouldThrow(\RuntimeException::class)->duringWaitFor('project_id', 'env', 'sha');
    }

    function it_waits_for_environment(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(false);
        $activity->wait()->willReturn();
        $environment->offsetGet('status')->willReturn('dirty');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);

        // Let getEnvironment return null the first time it's called.
        $project->getEnvironment('env')->will(function ($env) use ($environment, $project) {
            $project->getEnvironment('env')->willReturn($environment);
            return null;
        });
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url']);
    }

    function it_throws_error_if_environment_is_inactive(PlatformClient $client, Project $project, Environment $environment)
    {
        $environment->offsetGet('status')->willReturn('inactive');
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->shouldThrow(\RuntimeException::class)->duringWaitFor('project_id', 'env', 'sha');
    }

    function it_throws_error_if_activity_not_found(PlatformClient $client, Project $project, Environment $environment)
    {
        $environment->getActivities(10)->willReturn([]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->shouldThrow(\RuntimeException::class)->duringWaitFor('project_id', 'env', 'sha');
    }

    function it_finds_the_activity(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url']);
    }

    function it_waits_for_the_activity(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');

        // Let getActivities return nothing the first time it's called.
        $environment->getActivities(10, 'environment.push')->will(function () use ($activity, $environment) {
            $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
            return [];
        });
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url']);
    }

    function it_finds_the_activity_for_post_merge_envs(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['github-pr-head' => 'sha', 'new_commit' => 'wrong sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url']);
    }

    function it_returns_route_urls(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn(['route-url-1', 'route-url-2']);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url', 'route-url-1', 'route-url-2']);
    }

    function it_returns_urls_with_basic_auth(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('http://the-url');
        $environment->getRouteUrls()->willReturn(['http://route-url-1', 'https://route-url-2.tld']);
        $environment->getData()->willReturn([
            'http_access' => [
                'is_enabled' => true,
                'basic_auth' => ['user' => 'pw'],
            ],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);


        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['http://the-url', 'http://route-url-1', 'https://user:pw@route-url-2.tld']);
    }

    function it_returns_route_urls_in_alphabetical_order(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn(['www.the-url', 'api.the-url']);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url', 'api.the-url', 'www.the-url']);
    }

    function it_waits_on_incomplete_activity(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['new_commit' => 'sha']);
        $activity->isComplete()->willReturn(false);
        $activity->wait()->willReturn();
        $environment->offsetGet('status')->willReturn('dirty');
        $environment->getActivities(10, 'environment.push')->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $environment->getData()->willReturn([
            'http_access' => ['is_enabled' => false],
        ]);
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn(['the-url']);
    }

}
