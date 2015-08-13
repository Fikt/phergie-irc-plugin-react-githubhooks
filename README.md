# fikt/phergie-irc-plugin-react-githubhooks

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for GitHub webhooks, listen to events and announce them on IRC.

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

## Phergie configuration

```php
return [
    'plugins' => [
        // configuration
        new \Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin([

            /**
             * Plugin configuration
             */
            'port'          => 8080,    /**< HTTP server port, listen on this port for Github webhooks */

            /**
             * Global repository configuration options, you can override all of these for each repository
             */
            'channels'      => ['#some-channel'], // Channel list, broadcast events to these channels
            'events'        => ['*'], // Events to broadcast, '*' is all events.
            'secret'        => 'My super secret key', // Optional (but recommended) secret key, used to verify the message is actually from GitHub

            /**
             * Array of webhooks, key corresponds to the webhook request path
             */
            'webhooks'      => [
                'fikt/irc-phergie-plugin-react-githubhooks' => [], // Use global configuration
                'fikt/some-private-repo' => [
                    'channels'      => ['#some-private-channel'], // Override global configuration
                    'secret'        => 'My other super secret key', // Override global configuration
                ],
            ],
        ]),
    ],
];
```

## Tests

To run the unit test suite do `composer test` 

## License

Released under the BSD License. See `LICENSE`.
