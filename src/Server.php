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

/**
 * Server class, starts up server listening for webhooks from GitHub and emits events accordingly
 *
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Server {

    /**
     * Parent plugin instance
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Construct server instance, run http/react server and listen on configured port
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $loop = $plugin->getLoop();
        $config = $plugin->getConfig();
        $this->runServer($loop, $config['port']);
    }

    private function runServer($loop, $port = 8080, $ip = '0.0.0.0') {
        // Set up react HTTP server to listen for github webhooks
        $socket = new \React\Socket\Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', function ($request, $response) use ($connections) {
            $headers = $request->getHeaders();
            $path = $request->getPath();
            $hook = substr($path, 1);

            // Basic check if we got event and signature headers
            if (empty($headers['X-GitHub-Event'])) {
                $response->writeHead(500, ['Content-Type' => 'text/plain']);
                $response->end("Missing event header\n");
                $this->plugin->getLogger()->error("Received request with missing event header");
                return;
            }

            if (empty($headers['X-Hub-Signature'])) {
                $response->writeHead(500, ['Content-Type' => 'text/plain']);
                $response->end("Missing signature\n");
                $this->plugin->getLogger()->error("Received request with missing signature header");
                return;
            }

            // Check if the hook actually exists
            if (empty($hook)) {
                $response->writeHead(500, ['Content-Type' => 'text/plain']);
                $response->end("Missing hook name\n");
                $this->plugin->getLogger()->error("Received request with missing hook name, check out the plugin README");
                return;
            }

            $hooks = $this->plugin->getHooks();
            if (!isset($hooks[$hook])) {
                $response->writeHead(500, ['Content-Type' => 'text/plain']);
                $response->end("Missing hook name\n");
                $this->plugin->getLogger()->error("$hook does not match any defined hooks in the plugin configuration, check out the plugin README");
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
            $request->on('end', function () use (&$payload, $headers, $connections, $hook) {
                $raw_payload = $payload;

                // Parse json payload to PHP array
                $payload = json_decode($payload, TRUE);
                if (!$payload) {
                    $this->plugin->getLogger()->error(sprintf("Unable to parse payload: %s", json_last_error_msg()));
                    $this->plugin->getLogger()->debug('Payload: ' . var_export($payload, TRUE));
                    return;
                }

                // Verify signature
                $hooks = $this->plugin->getHooks();
                if (!empty($hooks[$hook]['secret'])) {
                    list($algo, $signature) = explode("=", $headers['X-Hub-Signature']);
                    if (hash_hmac($algo, $raw_payload, $hooks[$hook]['secret']) != $signature) {
                        $this->plugin->getLogger()->error("Invalid signature, void request");
                        return;
                    }
                }
                else {
                    $this->plugin->getLogger()->warning("No secret set for $hook, seriously consider setting one");
                }

                $this->plugin->getEventEmitter()->emit("githubhooks.$hook.{$headers['X-GitHub-Event']}", [$payload]);
            });
        });

        $socket->listen($port, $ip);
    }
}
