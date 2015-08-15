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
 * Plugin class.
 *
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Plugin extends \Phergie\Irc\Bot\React\AbstractPlugin
{

    /**
     * Plugin configuration
     *
     * @var array
     */
    private $config;

    /**
     * List of hooks
     *
     * @var array
     */
    private $hooks;

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
            'events'        => ['*'],
            'port'          => 8080,
            'hooks'         => [],
            'secret'        => FALSE,
        ], $config);

        // Set default configuration for hooks, or use override values
        $this->hooks = $config['hooks'];
        foreach ($this->hooks as $name => &$info) {
            $info = array_merge([
                'secret'        => $config['secret'],
                'channels'      => $config['channels'],
                'events'        => $config['events'],
            ], $info);
        }

        $this->config = $config;
    }

    /**
     * @see \Phergie\Irc\Client\React\LoopAwareInterface::setLoop
     */
    public function setLoop(\React\EventLoop\LoopInterface $loop) {
        parent::setLoop($loop);

        // We have the loop, initialize listening server
        new Server($this);
    }

    /**
     * @see \Phergie\Irc\Bot\React\EventEmitterAwareInterface::setEventEmitter
     */
    public function setEventEmitter(\Evenement\EventEmitterInterface $emitter) {
        parent::setEventEmitter($emitter);

        // We have the emitter, initialize event handlers
        new Handler($this);
    }

    /**
     * Get plugin configuration
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
        return [];
    }

    public function getHooks() {
        return $this->hooks;
    }
}
