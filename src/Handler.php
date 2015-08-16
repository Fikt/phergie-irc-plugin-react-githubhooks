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

    /**
     * Construct handler class
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $this->listenToEvents();
    }

    /**
     * Listen to events
     */
    public function listenToEvents() {
        $emitter = $this->getPlugin()->getEventEmitter();

        foreach ($this->getPlugin()->getHooks() as $hook => $info) {
            foreach ($info['events'] as $event) {
                $emitter->on("githubhooks.$hook.$event", function ($payload) use ($event, $hook, $info) {
                    if ($event == "public") {
                        // FIXME: Remove when PHP 7 becomes stable.
                        $event = "_public";
                    }
                    if (!method_exists($info['formatter'], $event)) {
                        $this->getPlugin()->getLogger()->warning("Set event formatter for $hook can not handle the $event event.");
                        return;
                    }
                    $message = $info['formatter']->$event($payload);
                    $message = $this->getPlugin()->escapeParam($message);

                    foreach ($this->getPlugin()->getConnections() as $connection) {
                        $eventqueue = $this->getPlugin()->getEventQueueFactory()->getEventQueue($connection);
                        foreach ($info['channels'] as $channel) {
                            $eventqueue->ircPrivmsg($channel, $message);
                        }
                    }
                });
            }
        }
    }

    /**
     * Return plugin instance
     * @return Plugin Plugin instance
     */
    private function getPlugin() {
        return $this->plugin;
    }
}
