<?php

namespace spec\Dais;

use Dais\Env;
use Dais\PlatformShFacade;
use Dais\WaitCommand;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Style\SymfonyStyle;

class WaitCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(WaitCommand::class);
    }

    function it_waits_properly(Env $env, PlatformShFacade $facade, SymfonyStyle $io)
    {
        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn('url')->shouldBeCalled();

        // No return value to test.
        $this->wait([], $env, $facade, $io);
    }
}
