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
    function let(Env $env)
    {
        // Ensure that unknown variables will throw error per default.
        $env->get(Argument::any(), Argument::any())->willThrow(new \RuntimeException());

        // Set an env for most tests.
        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willReturn('env');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(WaitCommand::class);
    }

    function it_waits(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn($environment)->shouldBeCalled();

        $this->wait('sha', '25', [], $env, $facade, $io)->shouldReturn(0);
    }

    function it_requries_a_platform_site_id(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $env->get('DAIS_PLATFORMSH_ID', Argument::any())->willThrow(new \RuntimeException("The error message"));

        $this->shouldThrow(new \RuntimeException("The error message"))
            ->duringWait('sha', '25', [], $env, $facade, $io);
    }

    function it_should_require_sha(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $this->shouldThrow(new \RuntimeException("Please supply a SHA"))
            ->duringWait('', '25', [], $env, $facade, $io);
    }

    function it_should_require_pr_number(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $this->shouldThrow(new \RuntimeException('Invalid pull request number ""'))
            ->duringWait('sha', '', [], $env, $facade, $io);
    }

    function it_supports_circeci_pr_specification(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn($environment)->shouldBeCalled();

        $this->wait('sha', 'pull/25', [], $env, $facade, $io)->shouldReturn(0);
    }

    function it_replaces_file_placeholders(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $file1 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file1, 'lala %site-url% lolo');
        $file2 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file2, 'banana %site-url% ananas');

        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn(['the-url'])->shouldBeCalled();

        $this->wait('sha', '25', [$file1, $file2], $env, $facade, $io)->shouldReturn(0);

        expect(file_get_contents($file1))->toBe('lala the-url lolo');
        expect(file_get_contents($file2))->toBe('banana the-url ananas');
    }

    function it_replaces_file_route_placeholders(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $file1 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file1, 'lala %site-url% lolo %route-url:1%');
        $file2 = tempnam(sys_get_temp_dir(), 'dais-test-');
        file_put_contents($file2, 'banana %site-url% ananas %route-url:2%');

        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([
            'route-url-1',
            'route-url-2',
        ]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn(['the-url', 'route-url-1', 'route-url-2'])->shouldBeCalled();

        $this->wait('sha', '25', [$file1, $file2], $env, $facade, $io)->shouldReturn(0);

        expect(file_get_contents($file1))->toBe('lala the-url lolo route-url-1');
        expect(file_get_contents($file2))->toBe('banana the-url ananas route-url-2');
    }

    function it_prints_an_error_on_non_existent_files(Env $env, PlatformShFacade $facade, Environment $environment, SymfonyStyle $io)
    {
        $file1 = sys_get_temp_dir() . '/dais-this-file-should-not-exist';
        // Do check that's the case.
        expect(file_exists($file1))->toBe(false);


        $environment->getPublicUrl()->willReturn('the-url');
        $environment->getRouteUrls()->willReturn([]);
        $facade->waitFor('env', 'pr-25', 'sha')->willReturn(['the-url'])->shouldBeCalled();
        $io->error($file1 . ' does not exist.')->shouldBeCalled();

        $this->wait('sha', '25', [$file1], $env, $facade, $io)->shouldReturn(1);

        // Just to check that we didn't fumble.
        expect(file_exists($file1))->toBe(false);
    }
}
