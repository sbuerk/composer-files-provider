[![test main](https://github.com/sbuerk/composer-files-provider/actions/workflows/ci.yml/badge.svg)](https://github.com/sbuerk/composer-files-provider/actions/workflows/ci.yml)

# Composer Files Provider

This plugin acts as composer plugin in order to provide files in a installation
aware manner. This means, that it searches in a configured boilerplate folder
structure in a defined order for files, based on installation placeholders. To
see how this works see corresponding section.

The behaviour of this plugin can be influenced by configuration in the `extra`
section of the root `composer.json`. See section `options` for available options.

## How does this work ?

@todo

## Options

@todo

## Available Placeholders

| short  | description                                                             |
|--------|-------------------------------------------------------------------------|
| %s%    | This will be replaced with the corresponding file block 'source'        |
| %t%    | This will be replaced with the configured template folder               |
| %h%    | This will be replaced with the hostname                                 |
| %u%    | This will be replaced with the username                                 |
| %p%    | This will be replaced with the project folder name                      |
| %pp%   | This will be replaced with the parent folder name of the project folder |
| %DDEV% | If processed in DDEV container, this will be replaced with "ddev"       |

## Feedback / Bug reports / Contribution

Bug reports, feature requests and pull requests are welcome in the GitHub
repository: <https://github.com/sbuerk/composer-files-provider>
