# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

## 0.8.1 - 2019-09-03
### Changed
- HTTPS URLs on environments with Basic Auth now include username and
  password in url.

## 0.8.0 - 2018-11-23
### Added
- Wait for up to a minute if environment or activity doesn't exist yet

## 0.7.4 - 2018-11-23
### Changed
- Support both normal and post-merge builds on Platform.sh

## 0.7.3 - 2018-11-08
### Changed
- Sort route URLs alphabetically, to make their numbers stable.

## 0.7.2 - 2018-10-26
### Changed
- Go back to box-project/box2, we need PHP 5.6 support

## 0.7.1 - 2018-10-26
### Changed
- Fetch box.phar instead of using composer install

## 0.7.0 - 2018-10-26
### Changed
- Use humbug box fork

## 0.6.0 - 2018-10-25
### Changed
- Updated dependencies

## 0.5.0 - 2018-10-25
### Added
- Support for route urls

## 0.4.0 - 2018-10-23
### Added
- Test of file placeholder replacements
- Test coverage reporting

### Changed
- Platform.sh changed the parameter name for the sha
- Code style improvements and refactorings

## 0.3.0 - 2017-06-27
### Changed
- Build process

## 0.2.0 - 2017-06-27
### Added
- CodeClimate and Travis setups
- CHANGELOG.md

### Changed
- Takes zero to may files to change URLs in
- Use Silly CLI framwork
- Use dependency injection
- Downgrade dependencies so it should be 5.6 compatible

## 0.1.0 - 2017-06-27
### Added
- Initial implementation
- Box built phar
