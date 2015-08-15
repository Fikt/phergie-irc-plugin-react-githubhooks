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
 * Handler class, handles all events emitted by the GitHubHooks server
 *
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Handler {

    /**
     * Parent plugin instance
     *
     * @var Plugin
     */
    private $plugin;

    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;

        $emitter = $plugin->getEventEmitter();

        foreach ($this->plugin->getHooks() as $hook => $info) {
            # foreach ($info['event'] as $event) {
            foreach (['ping'] as $event) {
                $emitter->on("githubhooks.$hook.$event", function ($payload) use ($event) {
                    var_dump($payload);
                    $this->plugin->getLogger()->debug("Got $event! {$payload['zen']}");
                });
            }
        }
    }
}
