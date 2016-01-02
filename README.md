# fikt/phergie-irc-plugin-react-githubhooks

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for GitHub webhooks, listen for events and announce them on IRC.

[![Build Status](https://secure.travis-ci.org/Fikt/phergie-irc-plugin-react-githubhooks.png?branch=master)](http://travis-ci.org/Fikt/phergie-irc-plugin-react-githubhooks)

## What does it do?

This plugin listens for incoming webhooks from GitHub, and announces events on IRC.

These events are not just triggered on push, but also on issue updates, pull requests, comments and more, you can even get status updates from jenkins/travis-ci builds and changes to the wiki.

Check out [Event Type & Payloads](https://developer.github.com/v3/activity/events/types/) in the GitHub API documentation for detailed information about every single event.

## How it works

The plugin listens for incoming hooks on port 8080 (configurable), it then emits an event when it receives one. The standard event handler then acts upon that event, and announces the event on IRC.

## Install & Configuration

See Phergie documentation for more information on [installing and enabling plugins](https://www.phergie.org/users/).

### Phergie plugin configuration

Hook name corresponds to webhook request path.

```php
return [
    // ... connection info goes here

    // Plugin configuration
    'plugins' => [
        // GitHubHooks configuration
        new \Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin([
            'channels'      => ['#some-channel'], // Channel list, broadcast events to these channels
            'events'        => ['*'], // Events to broadcast, '*' for all events.
            'secret'        => 'My super secret shared key', // Optional (but recommended) secret key, used to verify the message is actually from GitHub

            /**
             * Array of webhooks, key corresponds to the webhook request path
             */
            'hooks'         => [
                'githubhooks'   => [], // Use global configuration
                'fikt'          => [
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
