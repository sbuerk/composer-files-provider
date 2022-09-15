[![test main](https://github.com/sbuerk/composer-files-provider/actions/workflows/ci.yml/badge.svg)](https://github.com/sbuerk/composer-files-provider/actions/workflows/ci.yml)

Composer Files Provider
=======================

This plugin acts as composer plugin in order to provide files in a installation
aware manner. This means, that it searches in a configured boilerplate folder
structure in a defined order for files, based on installation placeholders. To
see how this works see corresponding section.

The behaviour of this plugin can be influenced by configuration in the `extra`
section of the root `composer.json`. See section `options` for available options.

# The mission

We had the need in several projects to provide different files based on some environment specific values,
in a waterfall manner. This means, that the first matching file (`source`) in a defined pattern order will
be used and copied to a defined destination (`target`).

It startet with environment specific `.htaccess` files, but it extended to other part configuration files to
adjust configuration. As we wanted these files in the corresponding git repository to be managed, it startet
with kind of bash scripts, duplicated and adjusted for different file types and added as composer scripts.

As it was kind of painfully to maintain these bash scripts over the several repositories, and special the different
flavours the need for a sharable and maintainable solution with project-based configuration was born. However,
thinking about it there were quite some stones in the way - creating a package with bash scripts would be easy,
but because of the nature of configurable `bin` installation folder this was not quite easy to ensure. And how to
provide an easy configuration on project level ? 

Then the :bulb: popped up - why not using the project root composer.json as configuration place, and instead of
providing bash scripts implementing it as a clean composer plugin. This also enables us to have it tested, linted
and more people's may help or contribute to it.

So far, that was the story of the mission and how this package has been born. May it be of help for you.

# Alternative

We're not aware of other open extensions that try to achieve the same in a similar way. We may have not searched
properly or has been to stupid to find one. Let us know if there is a similar composer plugin.

# Supported Versions

| version | composer versions | php versions            | note                             |
|---------|-------------------|-------------------------|----------------------------------|
| 0.x     | 1.x, 2.x          | 7.2, 7.3, 7.4, 8.0, 8.1 | abandoned - starting development |
| 1.x     | 1.x, 2.x          | 7.2, 7.3, 7.4, 8.0, 8.1 | actively supported               |


# Installation

Simply add this package as a dependency:

```shell
$ composer require sbuerk/composer-files-provider
```

The plugin starts working directly. That means, if you have already provided the needed
configuration will be processed directly. See the Info Command section to get more info
about the current configuration and what may be matched or not.

# Options

Example configuration:

```json
{
  "extra": {
    "sbuerk/composer-files-provider": {
      "template-root": "files-provider/",
      "resolvers": {
        "custom": [
          "%t%/%h%/%u%/%p%/%s",
          "%t%/%h%/%p%/%s",
          "%t%/%u%/%s",
          "%t%/%p%/%s",
          "%t%/default/%s"
        ]
      },
      "files": [
        {
          "label": "env based logo file",
          "source": "images/logo.png",
          "target": "images/logo.png",
          "resolver": "custom"
        },
        {
          "label": ".htaccess",
          "source": "public/.htaccess",
          "target": "public/.htaccess"
        }
      ]
    }
  }
}
```

| option        | optional | description                                                                                                                                                    |
|---------------|----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| template-root | yes      | This defines the template root folder, which will be used to replace the `%t%` placeholder. Defaults to: `file-templates`                                      |
| resolvers     | yes      | Here you can configure custom resolver definition(s), or override the default one.                                                                             |
| files         | no       | If something should be done, at least one file configuration is needed. You can define multiple file definitions, using the same or different resolver stacks. |


## Config: template-root

The `template-root` defines the template folder for the file's lookup. The `%t%` placeholder will be replaced
with the configured `template-root` or the default: `file-templates`.

Note: The path will trimm of slashes on the right side. So if using in path patterns, you have to add `/` yourself
as directory separator.

Example:

```json
{
  "extra": {
    "sbuerk/composer-files-provider": {
      "template-root": "custom-template-base-folder/",
      "resolvers": {},
      "files": []
    }
  }
}
```

## Config: resolvers

You can define custom resolver definitions under `resolvers` in the format:

```json
{
  "extra": {
    "sbuerk/composer-files-provider": {
      "resolvers": {
        "<resolver-name>": [
          "<custom-pattern>",
          "<custom-pattern2"
        ]    
      }
    }
  }
}
```

You can then decide for a file configuration which resolver you want to use.

> :warning: **If you define a "default" resolver, the shipped default resolver definition will be completely overridden.**: Be very careful here!

Default resolver definition (if not overridden):

```json
{
  "extra": {
    "sbuerk/composer-files-provider": {
      "resolvers": {
        "default": [
          "%t%/%h%/%u%/%pp%/%p%/%s%",
          "%t%/%h%/%u%/%p%/%s%",
          "%t%/%h%/%u%/%s%",
          "%t%/%h%/%pp%/%p%/%s%",
          "%t%/%h%/%p%/%s%",
          "%t%/%h%/%s%",
          "%t%/%u%/%pp%/%p%/%s%",
          "%t%/%u%/%p%/%s%",
          "%t%/%u%/%s%",
          "%t%/%pp%/%p%/%s%",
          "%t%/%p%/%s%",
          "%t%/%ddev%/%s%",
          "%t%/default/%s%"
        ]
      }
    }
  }
}
```

## Config: files

| option   | optional | description                                                                                                       |
|----------|----------|-------------------------------------------------------------------------------------------------------------------|
| label    | yes      | If not set, source will be used as label.                                                                         |
| source   | no       | Source path/file pattern, will be used to replace the `%s%` placeholder in the resolver pattern stack.            |
| target   | no       | Target path/file - defines where the first matched file will be written to. Supports placeholders too, if needed. |
| resolver | yes      | Define which resolver should be used. If not available or not provided, `default` resolver will be used.          |

> :information_source: Resolver fallback / default use means, that it uses the shipped default stack, except default resolver has been overridden.
 
> :warning: **Don't commit file with sensitive data (credentials) to your repository**: Be very careful here! So do not use this to provide these kind of files out of your repository.

# Info command

This package extends composer with a command to get some insights in the configuration
and what may happen:

```shell
$ composer files-provider:info
```

which displays something like that:

![](Documentation/cli-info-command-example.png)

## Available Placeholders

| short                                         | description                                                             |
|-----------------------------------------------|-------------------------------------------------------------------------|
| %s%                                           | This will be replaced with the corresponding file block 'source'        |
| %t%                                           | This will be replaced with the configured template folder               |
| %h%                                           | This will be replaced with the hostname                                 |
| %u%                                           | This will be replaced with the username                                 |
| %p%                                           | This will be replaced with the project folder name                      |
| %pp%                                          | This will be replaced with the parent folder name of the project folder |
| %ddev%                                        | If processed in DDEV container, this will be replaced with "ddev"       |
| %env(string:envVariableName[:default value])% | Env variable placeholder with default value support                     |

# Tagging & Releasing

[packagist.org](https://packagist.org/packages/sbuerk/composer-files-provider) is enabled via the casual GitHub hook.
For now, no GitHub releases are planed to be created. 

```shell
# Add/Adjust CHANGELOG Entry (needed to create release commit)
$ git commit -am "[RELEASE] 1.0.10 Allow pattern replacement for file config target"
$ git tag 1.0.10
$ git push
$ git push --tags
```

# Feedback / Bug reports / Contribution

Bug reports, feature requests and pull requests are welcome in the GitHub
repository: <https://github.com/sbuerk/composer-files-provider>
