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
        $this->beConstructedWith(PlatformShFacade::getClient(trim(file_get_contents('api-token'))));
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

        $this->beConstructedWith($client);

        $this->shouldThrow(\RuntimeException::class)->duringWaitFor('project_id', 'env', 'sha');
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
        $activity->offsetGet('type')->willReturn('environment.push');
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['github-pr-head' => 'sha']);
        $activity->isComplete()->willReturn(true);
        $environment->offsetGet('status')->willReturn('active');
        $environment->getActivities(10)->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn('the-url');
    }

    function it_waits_on_incomplete_activity(PlatformClient $client, Project $project, Environment $environment, Activity $activity)
    {
        $activity->offsetGet('type')->willReturn('environment.push');
        $activity->offsetExists('parameters')->willReturn(true);
        $activity->offsetGet('parameters')->willReturn(['github-pr-head' => 'sha']);
        $activity->isComplete()->willReturn(false);
        $activity->wait()->willReturn();
        $environment->offsetGet('status')->willReturn('dirty');
        $environment->getActivities(10)->willReturn([$activity]);
        $environment->getPublicUrl()->willReturn('the-url');
        $project->getEnvironment('env')->willReturn($environment);
        $client->getProject('project_id')->willReturn($project);

        $this->beConstructedWith($client);

        $this->waitFor('project_id', 'env', 'sha')->shouldReturn('the-url');
    }
}
