Dais
====

[![Build Status](https://img.shields.io/github/workflow/status/xendk/dais/Run%20tests?style=for-the-badge)](https://github.com/xendk/dais/actions?query=workflow%3A%22Run+tests%22)
[![Code Climate](https://img.shields.io/codeclimate/maintainability/xendk/dais.svg?style=for-the-badge)](https://codeclimate.com/github/xendk/dais)
[![Code Climate](https://img.shields.io/codeclimate/issues/xendk/dais.svg?style=for-the-badge)](https://codeclimate.com/github/xendk/dais)
[![Codecov](https://img.shields.io/codecov/c/github/xendk/dais.svg?style=for-the-badge)](https://codecov.io/gh/xendk/dais/branch/master)

Dais holds back CirclCI/Github Actions builds on GitHub pull requests
until Platform.sh has brought up the corresponding environment.

It is meant as a helper utility for running browser based tests
on CircleCI/Github Actions against a Platform.sh environment.

Prerequisites
-------------

Platform.sh must be setup with GitHub integration and
`--build-pull-requests=true`.

If using CircleCI, it must be set up to only build pull requests.
Otherwise it will build as soon as a branch is pushed, before the pull
request is created, and thus the build environment doesn't get the
pull request variables needed to find the corresponding Platform.sh
environment.

If using Github Actions, the workflow should trigger on
`pull_request`.

Some tests to run. The framework used is not important, as long as it
has a configuration file with the URL to use.

Usage
-----

Set up the `DAIS_PLATFORMSH_KEY` and `DAIS_PLATFORMSH_ID` env
variables on in CI.

Add the phar file to the commands run on CI and give it the
configuration file of the test framework as argument. The file should
have a `%site-url%` placeholder that will be replaced with the URL of
the Platform.sh environment, with any trailing slashes stripped.

If your Platform.sh project uses [Routes](https://docs.platform.sh/configuration/routes.html)
you can refer to route URLs using the pattern `%route-url:[route-index]%`.
`%route-url:1%` is the URL to the first route etc.

Working `.circleci/config.yml`:

``` yaml
version: 2

jobs:
  build:
    docker:
      - image: notnoopci/php:7.1.5-browsers
    working_directory: ~/build
    steps:
      - checkout
      # Set a timezone to avoid PHP notices/errors in date functions.
      - run: |
          echo "date.timezone = UTC" | sudo tee /usr/local/etc/php/conf.d/date.ini
          composer install
          wget https://github.com/xendk/dais/releases/download/0.9.0/dais-0.9.0.phar
          php dais-0.9.0.phar --sha $CIRCLE_SHA1 --pr-number $CI_PULL_REQUEST behat.yml
          ./vendor/bin/behat --format=junit --out=/tmp/test-reports/behat --format=pretty --out=std
      - store_test_results:
          path: /tmp/test-reports/
```

The equivalent command for Github Actions:

```
php dais-0.9.0.phar --sha ${{github.event.pull_request.head.sha}} --pr-number ${{github.event.pull_request.number}} behat.yml
```
