# fikt/phergie-irc-plugin-react-githubhooks

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for Listen for GitHub webhooks, announce events on IRC..

[![Build Status](https://secure.travis-ci.org/fikt/phergie-irc-plugin-react-githubhooks.png?branch=master)](http://travis-ci.org/fikt/phergie-irc-plugin-react-githubhooks)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "fikt/phergie-irc-plugin-react-githubhooks": "dev-master"
    }
}
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
return [
    'plugins' => [
        // configuration
        new \Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin([



        ])
    ]
];
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.
