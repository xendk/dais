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

    function it_replaces_file_placeholders(Env $env, PlatformShFacade $facade, SymfonyStyle $io) {
        $file1 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file1, 'lala %site-url% lolo');
        $file2 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file2, 'banana %site-url% ananas');

        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn('the-url')->shouldBeCalled();

        $this->wait([$file1, $file2], $env, $facade, $io);

        expect(file_get_contents($file1))->toBe('lala the-url lolo');
        expect(file_get_contents($file2))->toBe('banana the-url ananas');
    }

    function it_prints_an_error_on_non_existent_files(Env $env, PlatformShFacade $facade, SymfonyStyle $io) {
        $file1 = sys_get_temp_dir() . '/dais-this-file-should-not-exist';
        // Do check that's the case.
        expect(file_exists($file1))->toBe(false);


        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn('the-url')->shouldBeCalled();
        $io->error($file1 . ' does not exist.')->shouldBeCalled();

        $this->wait([$file1], $env, $facade, $io);

        // Just to check that we didn't fumble.
        expect(file_exists($file1))->toBe(false);
    }
}
