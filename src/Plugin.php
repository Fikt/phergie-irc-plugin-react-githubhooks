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

    private $config;

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
            'secret'        => FALSE,
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

        $this->config = $config;
    }

    public function onConnectBeforeAll(array $connections) {
        $this->webhook_server()->listen($this->config['port'], '0.0.0.0');
    }

    private function webhook_server()
    {
        // Set up react HTTP server to listen for github webhooks
        $loop = $this->getLoop();
        $socket = new \React\Socket\Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', function ($request, $response) {
            $headers = $request->getHeaders();

            // Basic check if we got event and signature headers
            if (empty($headers['X-GitHub-Event'])) {
                $response->writeHead(500, ['Content-Type' => 'text/plain']);
                $response->end("Missing event header\n");
                return;
            }

            if (empty($headers['X-Hub-Signature'])) {
                $response->writeHead(500, ['Content-Type' => 'text/plain']);
                $response->end("Missing signature\n");
                return;
            }

            // Okay the request
            $response->writeHead(200, ['Content-Type' => 'text/plain']);
            $response->end("OK\n");

            // Get incoming JSON payload
            $payload = "";
            $request->on('data', function ($data) use (&$payload) {
                $payload .= $data;
            });

            // Wait for the end of data burst
            $request->on('end', function () use (&$payload, $headers) {
                $raw_payload = $payload;

                // Parse json payload to PHP array
                $payload = json_decode($payload, TRUE);
                if (!$payload) {
                    // Headers and response already sent out, can't back up now?
                    var_dump($payload);
                    var_dump(json_last_error_msg());
                    throw new \Exception("Could not parse payload"); // TODO: Handle this a bit more gracefully
                }

                // Check which repository this event belongs to
                if (empty($payload['repository'])) {
                    throw new \Exception("Missing repository!"); // TODO: Handle it gracefully
                }
                $hook = $payload['repository']['full_name'];
                if (!array_key_exists($hook, $this->hooks)) {
                    throw new \Exception("Not listening to this repository"); // TODO: Handle it gracefully
                }
                $hook = $this->hooks[$hook];

                // Verify signature
                list($algo, $signature) = explode("=", $headers['X-Hub-Signature']);
                if ($hook['secret'] && hash_hmac($algo, $raw_payload, $hook['secret']) != $signature) {
                    // Headers and response already sent out, can't back up now?
                    throw new \Exception("Invalid signature"); // TODO: Handle this a bit more gracefully
                }

                // Check if we're actually listening for the sent event for this repository
                if (!in_array('*', $hook['events']) && !in_array($headers['X-GitHub-Event'], $hook['events'])) {
                    throw new \Exception("Not listening to event"); // TODO: Handle this a bit more gracefully
                    return;
                }

                // Format and send the event to all relevant channels
                $message = $this->format_event($headers['X-GitHub-Event'], $payload);
                foreach ($hook['channels'] as $channel) {
                    $this->logger->info($message);
                }
            });
        });

        return $socket;
    }

    /**
     * Return readable string for event
     *
     * @todo Move the whole thing to a separate class
     */
    private function format_event($event, $payload) {
        switch ($event) {
            case 'ping':
                return sprintf("I've received ping, zen says: %s", $payload['zen']);
                break;
            default:
                return sprintf("I just recieved unknown event named '%s', send help...", $event);
                break;
        }
    }

    public function getSubscribedEvents() {
        return [
            'connect.before.all'        => 'onConnectBeforeAll',
        ];
    }
}
