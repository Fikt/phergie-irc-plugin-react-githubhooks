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

use Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin;
use Phergie\Irc\Bot\React\EventEmitterAwareInterface;

/**
 * Handler interface, handle all webhook events
 */
interface HandlerInterface extends EventEmitterAwareInterface
{

    /**
     * Set plugin instance
     *
     * @param Plugin $plugin Plugin instance
     */
    public function setPlugin(Plugin $plugin);

    /**
     * Format ping event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function ping(array $payload);

    /**
     * Format commit_comment event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function commit_comment(array $payload);

    /**
     * Format create event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function create(array $payload);

    /**
     * Format delete event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function delete(array $payload);

    /**
     * Format deployment event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function deployment(array $payload);

    /**
     * Format deployment_status event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function deployment_status(array $payload);

    /**
     * Format fork event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function fork(array $payload);

    /**
     * Format gollum event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function gollum(array $payload);

    /**
     * Format issue_comment event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function issue_comment(array $payload);

    /**
     * Format issues event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function issues(array $payload);

    /**
     * Format member event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function member(array $payload);

    /**
     * Format membership event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function membership(array $payload);

    /**
     * Format page_build event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function page_build(array $payload);

    /**
     * Format public event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function _public(array $payload);

    /**
     * Format pull_request event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function pull_request(array $payload);

    /**
     * Format pull_request_review_comment event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function pull_request_review_comment(array $payload);

    /**
     * Format push event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function push(array $payload);

    /**
     * Format release event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function release(array $payload);

    /**
     * Format repository event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function repository(array $payload);

    /**
     * Format status event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function status(array $payload);

    /**
     * Format team_add event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function team_add(array $payload);

    /**
     * Format watch event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function watch(array $payload);
}
