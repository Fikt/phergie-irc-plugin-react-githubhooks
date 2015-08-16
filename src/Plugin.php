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

use Phergie\Irc\ConnectionInterface;
use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;

/**
 * Plugin class.
 *
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Plugin extends \Phergie\Irc\Bot\React\AbstractPlugin
{

    /**
     * Plugin configuration.
     *
     * @var array
     */
    private $config;

    /**
     * List of hooks.
     *
     * @var array
     */
    private $hooks;

    /**
     * List of active connections
     *
     * @var \SplObjectStorage
     */
    private $connections;

    /**
     * Accepts plugin configuration.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Set default configuration values
        $config = array_merge([
            'channels'      => [],
            'events'        => [
                'ping',
                'commit_comment',
                'create',
                'delete',
                'deployment',
                'deployment_status',
                'follow',
                'fork',
                'fork_apply',
                'gollum',
                'issue_comment',
                'issues',
                'member',
                'membership',
                'page_build',
                'public',
                'pull_request',
                'pull_request_review_comment',
                'push',
                'releaase',
                'repository',
                'status',
                'team_add',
                'watch',
            ],
            'port'          => 8080,
            'hooks'         => [],
            'secret'        => FALSE,
            'formatter'     => new Formatter\Standard(),
        ], $config);

        // Set default configuration for hooks, or use override values
        $this->hooks = $config['hooks'];
        foreach ($this->hooks as $name => &$info) {
            $info = array_merge([
                'secret'        => $config['secret'],
                'channels'      => $config['channels'],
                'events'        => $config['events'],
                'formatter'     => $config['formatter'],
            ], $info);
        }

        $this->config = $config;
    }

    /**
     * @see \Phergie\Irc\Client\React\LoopAwareInterface::setLoop
     */
    public function setLoop(LoopInterface $loop) {
        parent::setLoop($loop);

        // We have the loop, initialize listening server
        new Server($this);
    }

    /**
     * @see \Phergie\Irc\Bot\React\EventEmitterAwareInterface::setEventEmitter
     */
    public function setEventEmitter(EventEmitterInterface $emitter) {
        parent::setEventEmitter($emitter);

        // We have the emitter, initialize event handlers
        new Handler($this);
    }

    /**
     * Get plugin configuration.
     *
     * @return array Plugin configuration array.
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @see \Phergie\Irc\Bot\React\PluginInterface::getSubscribedEvents
     */
    public function getSubscribedEvents() {
        return [
            'connect.after.each'        => 'addConnection',
        ];
    }

    /**
     * Get list of hooks.
     *
     * @return array Array of hooks
     */
    public function getHooks() {
        return $this->hooks;
    }

    /**
     * Add connection to connections list
     *
     * @param \Phergie\Irc\ConnectionInterface $connection Connection instance
     */
    public function addConnection(ConnectionInterface $connection) {
        $this->getConnections()->attach($connection);
    }

    /**
     * Get list of connections
     *
     * @return \SplObjectStorage List of connections
     */
    public function getConnections() {
        if (!$this->connections) {
            $this->connections = new \SplObjectStorage();
        }
        return $this->connections;
    }
}
