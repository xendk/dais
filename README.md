Dais
====

[![Build Status](https://travis-ci.org/xendk/dais.svg?branch=master)](https://travis-ci.org/xendk/dais)
[![Code Climate](https://codeclimate.com/github/xendk/dais/badges/gpa.svg)](https://codeclimate.com/github/xendk/dais)
[![Issue Count](https://codeclimate.com/github/xendk/dais/badges/issue_count.svg)](
https://codeclimate.com/github/xendk/dais)
[![Test Coverage](https://codeclimate.com/github/xendk/dais/badges/coverage.svg)](https://codeclimate.com/github/xendk/dais/coverage)

Dais holds back CirclCI builds on GitHub pull requests until
Platform.sh has brought up the corresponding environment.

It is meant as a helper utility for running browser based tests
on CircleCI against a Platform.sh environment.

Prerequisites
-------------

Platform.sh must be setup with GitHub integration and
`--build-pull-requests=true`.

CircleCI must be set up to only build pull requests. Otherwise it will
build as soon as a branch is pushed, before the pull request is
created, and thus the build environment doesn't get the pull request
variables needed to find the corresponding Platform.sh environment.

Some tests to run. The framework used is not important, as long as it
has a configuration file with the URL to use.

Usage
-----

Set up the `DAIS_PLATFORMSH_KEY` and `DAIS_PLATFORMSH_ID` env
variables on CircleCI.

Add the phar file to the commands run on CircleCI and give it the
configuration file of the test framework as argument. The file should
have a %site-url% placeholder that will be replaced with the URL of
the Platform.sh environment, with any trailing slashes stripped.

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
          wget https://github.com/xendk/dais/releases/download/0.3.0/dais-0.3.0.phar
          php dais-0.2.0.phar behat.yml
          ./vendor/bin/behat --format=junit --out=/tmp/test-reports/behat --format=pretty --out=std
      - store_test_results:
          path: /tmp/test-reports/
```

