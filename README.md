# PHP Config

[![CircleCI](https://circleci.com/gh/skpr/php-config.svg?style=svg)](https://circleci.com/gh/skpr/php-config)

A simple utility for reading skpr config from a directory, and populating
environment variables in PHP applications.

The default skpr config directory is /etc/skpr

## Usage

### Loading config

```php
$config = SkprConfig::create()->load();
$config->get('foo.bar');
```

### Setting environment variables

You can optionally set environment variables from Skpr config.

```php
$config = SkprConfig::create()->load();
$config->putAllEnvs();
```

Keys will be converted to uppercase, and dots are
converted to underscores. For example:

```
getenv('FOO_BAR')
```

You can also provide a list of config keys, to avoid adding all config as environment
variables:

```php
$config = SkprConfig::create()->load();
$config->putEnvs(['my.key1', 'my.key2']);
```

## Testing

Run tests using the following:

`bin/phpunit`

## Code Standards

Run code style checks with the following:

`bin/phpcs`
