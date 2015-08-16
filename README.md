# fikt/phergie-irc-plugin-react-githubhooks

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for GitHub webhooks, listen for events and announce them on IRC.

[![Build Status](https://secure.travis-ci.org/Fikt/phergie-irc-plugin-react-githubhooks.png?branch=master)](http://travis-ci.org/Fikt/phergie-irc-plugin-react-githubhooks)

## What it does

This plugin listens for incoming webhooks from GitHub, and announces events on IRC.

## What events it announces

Pushed commits, that's the most obvious one, but that's not all. It can also announce issue related events, be it open/close/reopen or assigning/labeling. Comments, it annaounces comments to commits, pull requests and issues. Community, it can announce who forks or starred the repository. Continuous integration & deployment, It can even announce if travis-ci or jenkins successfully built your project and deployment progress.

Check out [Event Type & Payloads](https://developer.github.com/v3/activity/events/types/) in the GitHub API documentation for detailed information about available events.

## How it works

The plugin starts an react/http server and listens (by default) on port 8080. When GitHub sends an event the Server class automagically emits an event githubhooks.[hook].[event]. The built in Handler then catches the event and announces it on IRC, if you'd like you can override the default Handler and customize it in your own style. (It's IRC, cool colors are to be expected)

## Install

See Phergie documentation for more information on [installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Phergie configuration

Hook name corresponds to webhook request path.

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
            'secret'        => 'My super secret shared key', // Optional (but recommended) secret key, used to verify the message is actually from GitHub

            /**
             * Array of webhooks, key corresponds to the webhook request path
             */
            'hooks'      => [
                'githubhooks' => [], // Use global configuration
                'fikt' => [
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
