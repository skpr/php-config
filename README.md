# Skpr Config

A simple utility for reading skpr config from a directory, and populating
environment variables.

The default skpr config directory is /etc/skpr

## Usage

```php
SkprConfig::create()->load();
```

Skipper config variables will be converted to uppercase, and dots are
converted to underscores. For example:

`foo.bar => FOO_BAR`

## Testing

Run tests using the following:

`bin/phpunit`

## Code Standards

Run code style checks with the following:

`bin/phpcs`
