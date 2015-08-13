<?php
/**
 * Phergie plugin for Listen for GitHub webhooks, announce events on IRC. (http://github.com/Fikt/phergie-irc-plugin-react-githubhooks/wiki)
 *
 * @link https://github.com/fikt/phergie-irc-plugin-react-githubhooks for the canonical source repository
 * @copyright Copyright (c) 2015 Gunnsteinn Þórisson (https://github.com/Gussi)
 * @license https://github.com/Fikt/phergie-irc-plugin-react-githubhooks/blob/master/LICENSE Simplified BSD License
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */

namespace Fikt\Phergie\Irc\Plugin\React\GitHubHooks;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Event\EventInterface as Event;

/**
 * Plugin class.
 *
 * @category Fikt
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Plugin extends AbstractPlugin
{
    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Set default configuration values
        $config = array_merge([
            'channels'      => [],
            'events'        => ['*'],
            'port'          => 8080,
            'webhooks'      => [],
            'secret'        => NULL,
        ], $config);

        // Set default configuration for hooks, or use override values
        $this->hooks = $config['webhooks'];
        foreach ($this->hooks as $name => &$info) {
            $info = array_merge([
                'secret'        => $config['secret'],
                'channels'      => $config['channels'],
                'events'        => $config['events'],
            ], $info);
        }

        // Start webhook listener, wait for events
        $this->webhook_server()->listen($config['port']);
    }

    private function webhook_server()
    {
        // Set up react HTTP server to listen for github webhooks
        $loop = $this->getLoop();
        $socket = new React\Socket\Server($loop);
        $http = new React\Http\Server($socket, $loop);

        $http->on('request', function ($request, $response) {
            $headers = $response->getHeaders();

            // Check if we're actually listening to this hook
            $hook = substr($response->getPath(), 1);
            if (!array_key_exists($this->hooks, $hook)) {
                $response->write(500, ['Content-Type' => 'text/plain']);
                $response->end("Invalid path, please set correct path corresponding to your config\n");
                return;
            }
            $hook = $this->hooks[$hook];

            // Verify we're actually listening for the sent event
            $event = $headers['X-GitHub-Event'];
            if (!in_array($event, $hook['event'])) {
                $response->write(500, ['Content-Type' => 'text/plain']);
                $response->end("Invalid event, this hook is not listening to this event\n");
                return;
            }

            // Okay the request
            $response->write(200, ['Content-Type' => 'text/plain']);
            $response->end("OK\n");

            // Get incoming JSON payload
            $payload = "";
            $request->on('data', function ($data) use (&$payload) {
                $payload .= $data;
            });

            // Wait for the end of data burst
            $request->on('end', function () use (&$payload, $hook, $event, $headers) {

                // Verify signature
                list($algo, $signature) = explode("=", $headers['X-Hub-Signature']);
                if (hash_hmac($algo, $payload, $hook['secret']) != $signature) {
                    // Headers and response already sent out, can't back up now?
                    throw new Exception("Invalid signature"); // TODO: Handle this a bit more gracefully
                }

                // Parse json payload to PHP array
                $payload = json_decode($payload, TRUE);
                if (!$payload) {
                    // Headers and response already sent out, can't back up now?
                    throw new Exception("Could not parse payload"); // TODO: Handle this a bit more gracefully
                }

                // Format and send the event to all relevant channels
                foreach ($hook['channels'] as $channel) {
                    $this->logger->info(sprintf("Send event %s to %s", $event, $channel));
                }
            });
        });

        return $socket;
    }
}
