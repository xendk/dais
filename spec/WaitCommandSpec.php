<?php

namespace spec\Dais;

use Dais\Env;
use Dais\PlatformShFacade;
use Dais\WaitCommand;
use PhpSpec\ObjectBehavior;
use Platformsh\Client\Model\Environment;
use Prophecy\Argument;
use Symfony\Component\Console\Style\SymfonyStyle;

class WaitCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(WaitCommand::class);
    }

    function it_waits_properly(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn($environment)->shouldBeCalled();

        // No return value to test.
        $this->wait([], $env, $facade, $io);
    }

    function it_replaces_file_placeholders(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io) {
        $file1 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file1, 'lala %site-url% lolo');
        $file2 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file2, 'banana %site-url% ananas');

        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn(['the-url'])->shouldBeCalled();

        $this->wait([$file1, $file2], $env, $facade, $io);

        expect(file_get_contents($file1))->toBe('lala the-url lolo');
        expect(file_get_contents($file2))->toBe('banana the-url ananas');
    }

    function it_replaces_file_route_placeholders(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io) {
        $file1 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file1, 'lala %site-url% lolo %site-url:1%');
        $file2 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file2, 'banana %site-url% ananas %site-url:2%');

        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([
          'route-url-1',
          'route-url-2',
        ]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn(['the-url', 'route-url-1', 'route-url-2'])->shouldBeCalled();

        $this->wait([$file1, $file2], $env, $facade, $io);

        expect(file_get_contents($file1))->toBe('lala the-url lolo route-url-1');
        expect(file_get_contents($file2))->toBe('banana the-url ananas route-url-2');
    }

    function it_prints_an_error_on_non_existent_files(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io) {
        $file1 = sys_get_temp_dir() . '/dais-this-file-should-not-exist';
        // Do check that's the case.
        expect(file_exists($file1))->toBe(false);


        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
        $env->get('CIRCLE_SHA1', Argument::any())->willReturn('sha');
        $env->get('CI_PULL_REQUEST', Argument::any())->willReturn('pull/25');
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn(['the-url'])->shouldBeCalled();
        $io->error($file1 . ' does not exist.')->shouldBeCalled();

        $this->wait([$file1], $env, $facade, $io);

        // Just to check that we didn't fumble.
        expect(file_exists($file1))->toBe(false);
    }
}
