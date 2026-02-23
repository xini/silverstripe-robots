# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

## [6.1.2]

* Fix check for existing controller in Security check
* simplyfy check when testing for fromholdio/silverstripe-errored
* add check for silverstripe/silverstripe-errorpage

## [6.1.1]

* Fix check for existing controller

## [6.1.0]

* Allow different object to SiteConfig as config obj, defined by the object that has the extension applied to it
* Add extension hook for cmsfield
* Fix check for getFilterFieldName

## [6.0.0]

* upgrade to Silverstripe 6

## [5.1.3]

* fix rendering of robots content in CMS when template has been overwritten in theme

## [5.1.2]

* code cleanup, PHP 8.1 compatibility

## [5.1.1]

* fix routes for configured multistes

## [5.1.0]

* add compatibility for configured multistes

## [5.0.1]

* fix PHP 8.1 compatibility issue

## [5.0.0]

* upgrade to Silverstripe 5

## [4.1.3]

* fix typo

## [4.1.2]

* fix requirements (add unclecheese/display-logic)

## [4.1.1]

* exclude folder pages from DisallowedPages

## [4.1.0]

* add workaround for fromholdio/silverstripe-errored to update robots tag for error pages

## [4.0.2]

* check for owner and controller before calling extensions
* exclude redirector and redirection pages from DisallowedPages

## [4.0.1]

* exclude Multisites Sites from DisallowedPages

## [4.0.0]

* rename .env `ROBOTS_MODE` to `FORCE_ROBOTS_MODE` and `ConfigExtension::robots_mode` to `ConfigExtension::force_robots_mode` for clarification
* enable robots tag output in dev and test environments
* add `DisallowedPages` to allowed robots.txt output, based on Google Sitemap settings

## [3.1.1]

* fix default values when upgrading from version 1.x

## [3.1.0]

* fix use statements
* add extension hook for robots string for current controller

## [3.0.0]

* add robots meta tag for all pages based on env type, wilr/silverstripe-googlesitemaps module and page specific config values

## [2.1.0]

* fix content for disallowed content
* add output preview in CMS for allow and disallow

## [2.0.1]

* remove default robots.txt content field from multisites site CMSFields

## [2.0.0]

* module rewrite

## [1.1.0]

* switch disallow config from CMS to .env/yml

## [1.0.0]

* initial release
