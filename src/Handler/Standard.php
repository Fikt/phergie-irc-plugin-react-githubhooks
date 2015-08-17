<?php
/**
 * Phergie plugin that listens for GitHub webhooks, announce events on IRC. (http://github.com/Fikt/phergie-irc-plugin-react-githubhooks/wiki)
 *
 * @link https://github.com/fikt/phergie-irc-plugin-react-githubhooks for the canonical source repository
 * @copyright Copyright (c) 2015 Gunnsteinn Þórisson (https://github.com/Gussi)
 * @license https://github.com/Fikt/phergie-irc-plugin-react-githubhooks/blob/master/LICENSE Simplified BSD License
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */

namespace Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Handler;

use Evenement\EventEmitterInterface;
use Fikt\Phergie\Irc\Plugin\React\GitHubHooks\HandlerInterface;
use Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin;

/**
 * Standard handler class
 *
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Standard implements HandlerInterface
{

    /**
     * Parent plugin instance
     *
     * @var \Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin
     */
    private $plugin;

    /**
     * Event emitter used to register callbacks for IRC events of interest to
     * the plugin
     *
     * @var \Evenement\EventEmitterInterface
     */
    protected $emitter;

    /**
     * Sets the event emitter for the implementing class to use.
     *
     * @param \Evenement\EventEmitterInterface $emitter
     * @see \Phergie\Irc\Bot\React\EventEmitterAwareInterface::setEventEmitter
     */
    public function setEventEmitter(EventEmitterInterface $emitter) {
        $this->emitter = $emitter;

        // We have the emitter, now we can listne to events
        $this->listenToEvents();
    }

    /**
     * Listen to events
     */
    public function listenToEvents()
    {
        $emitter = $this->getPlugin()->getEventEmitter();

        foreach ($this->getPlugin()->getHooks() as $hook => $info) {
            foreach ($info['events'] as $event) {
                $emitter->on("githubhooks.$hook.$event", function ($payload) use ($event, $hook, $info) {
                    $method = $event;
                    if ($event == "public") {
                        $method = "_public";
                    }
                    if (!method_exists($this, $method)) {
                        $this->getPlugin()->getLogger()->warning("Set event handler for $hook can not handle the $event event.");
                        return;
                    }
                    $messages = $this->$method($payload);
                    if (!is_array($messages)) {
                        $messages = [$messages];
                    }

                    foreach ($this->getPlugin()->getConnections() as $connection) {
                        $eventqueue = $this->getPlugin()->getEventQueueFactory()->getEventQueue($connection);
                        foreach ($info['channels'] as $channel) {
                            foreach ($messages as $message) {
                                $message = $this->getPlugin()->escapeParam($message);
                                $eventqueue->ircPrivmsg($channel, $message);
                            }
                        }
                    }
                });
            }
        }
    }

    /**
     * Return plugin instance
     *
     * @return Plugin Plugin instance
     */
    private function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Set plugin instance
     *
     * @param Plugin $plugin Plugin instance
     */
    public function setPlugin(Plugin $plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * Return correctly formatted url
     *
     * @param string $url URL to display
     * @todo Insert URL shortened here
     */
    private function url($url)
    {
        return " [ $url ]";
    }

    /**
     * Pretty much a custom sprintf
     *
     * @todo Color supprt, a classical one (not an object with an ->color() method)
     * @param string $url URL related to this event
     * @param string $string The message itself
     * @param mixed ...$params Parameters printf style
     */
    private function format($url, $string)
    {
        $params = array_slice(func_get_args(), 2);
        return vsprintf($string, $params) . ($url !== NULL ? $this->url($url) : "");
    }

    /**
     * Format ping event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::ping()
     */
    public function ping(array $payload)
    {
        return $this->format(NULL
            , "Webhook ping received from %s, looks like this works :) GitHub zen: %s"
            , $payload['repository']['full_name']
            , $payload['zen']
        );
    }

    /**
     * Format commit_comment event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::commit_comment()
     */
    public function commit_comment(array $payload)
    {
        $comment_length = 64;
        return [
            $this->format($payload['comment']['url']
                , "%s commented on commit %s in %s"
                , $payload['comment']['user']['login']
                , \substr($payload['comment']['commit_id'], 0, 7)
                , $payload['repository']['full_name']
            ),
            $this->format(NULL
                , "%s: %s"
                , $payload['comment']['user']['login']
                , \substr($payload['comment']['body'], 0, $comment_length)
                , (\strlen($payload['comment']['body']) > $comment_length ? '...' : '')
            ),
        ];
    }

    /**
     * Format create event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::create()
     */
    public function create(array $payload)
    {
        return $this->format(NULL
            , "%s created new %s named %s in %s"
            , $payload['user']['login']
            , $payload['ref_type']
            , ($payload['ref'] !== NULL ? $payload['ref'] : $payload['repository']['full_name'])
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format delete event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::delete()
     */
    public function delete(array $payload)
    {
        return $this->format(NULL
            , "%s removed the %s %s in %s"
            , $payload['user']['login']
            , $payload['ref_type']
            , $payload['ref']
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format deployment event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::deployment()
     */
    public function deployment(array $payload)
    {
        return $this->format(NULL
            , "Deploying %s for %s %s in %s"
            , substr($payload['deploymet']['sha'], 0, 7)
            , $payload['deployment']['environment']
            , (!empty($payload['deployment']['description']) ? $payload['deployment']['description'] : '')
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format deployment_status event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::deployment_status()
     */
    public function deployment_status(array $payload)
    {
        $state = "";
        switch ($payload['deployment_status']['state']) {
            case 'pending':
                $state = "is pending";
                break;
            case 'success':
                $state = "was successful";
                break;
            case 'failure':
                $state = "failed";
                break;
            case 'error':
                $state = "failed with an error";
                break;
        }
        return $this->format($payload['deployment_status']['target_url']
            , "Deployment for %s %s in %s: %s"
            , $payload['deployment']['environment']
            , $state
            , $payload['repository']['full_name']
            , (!empty($payload['deployment_status']['description']) ? $payload['deployment_status']['description'] : '')
        );
    }

    /**
     * Format fork event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::fork()
     */
    public function fork(array $payload)
    {
        return $this->format($payload['forkee']['html_url']
            , "%s just forked %s to his very own %s!"
            , $payload['forkee']['owner']['login']
            , $payload['repository']['full_name']
            , $payload['forkee']['full_name']
        );
    }

    /**
     * Format gollum event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::gollum()
     */
    public function gollum(array $payload)
    {
        // For some reason $payload['pages'] is an array
        // Even if users can create multiple pages at a time, each should have it's own event imho
        // And maybe it is... I guess we have to see it in practice
        if (count($payload['pages']) == 1) {
            $page = $payload['pages'][0];
            return $this->format($page['html_url']
                , "%s created new wiki entry '%s' for %s"
                , $payload['user']['login']
                , $page['title']
                , $payload['repository']['full_name']
            );
        }

        return $this->format(NULL
            , "%s created %d wiki entries for %s"
            , $payload['user']['login']
            , count($payload['pages'])
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format issue_comment event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::issue_comment()
     */
    public function issue_comment(array $payload)
    {
        return [
            $this->format($payload['comment']['html_url']
                , "%s has commented on issue #%d in %s"
                , $payload['comment']['user']['login']
                , $payload['issue']['number']
                , $payload['repository']['full_name']
            ),
            $this->format($payload['issue']['html_url']
                , "Issue #%d: %s"
                , $payload['issue']['number']
                , $payload['issue']['title']
            ),
            $this->format($payload['issue']['html_url']
                , "%s: %s"
                , $payload['comment']['user']['login']
                , \substr($payload['comment']['body'], 0, 64)
                , (\strlen($payload['comment']['body']) > 64 ? '...' : '')
            ),
        ];
    }

    /**
     * Format public  event
     *
     * @todo Make somewhat intelligent, don't bombard channel with changes
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::issues()
     */
    public function issues(array $payload)
    {
        switch ($payload['action']) {
            case 'assigned':
                return $this->format($payload['issue']['url']
                    , "%s was assigned issue #%d: %s%s%s"
                    , $payload['assignee']['login']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                    , ($payload['assignee'] != $payload['sender']['login'] ? " by {$payload['sender']['login']}" : "")
                );
                break;
            case 'unassigned':
                return $this->format($payload['issue']['url']
                    , "%s has been unassigned issue #%d: %s%s%s"
                    , $payload['assignee']['login']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                    , ($payload['assignee'] != $payload['sender']['login'] ? " by {$payload['sender']['login']}" : "")
                );
                break;
            case 'labeled':
                return $this->format($payload['issue']['url']
                    , "%s labeled issue #%d: %s%s as %s"
                    , $payload['sender']['login']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                    , $payload['label']['name']
                );
                break;
            case 'unlabeled':
                return $this->format($payload['issue']['url']
                    , "%s removed label %s from issue #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['label']['name']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                );
                break;
            case 'opened':
            case 'closed':
            case 'reopened':
                return $this->format($payload['issue']['url']
                    , "%s has %s the issue #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['action']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                );
                break;
        }
    }

    /**
     * Format member event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::member()
     */
    public function member(array $payload)
    {
        return $this->format(NULL
            , "%s was %s to the repository"
            , $payload['member']['login']
            , $payload['action']
        );
    }

    /**
     * Format membership event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::membership()
     */
    public function membership(array $payload)
    {
        $action = "";
        switch ($action) {
            case 'added':
                $action = "was added to";
                break;
            case 'removed':
                $action = "has been removed from";
                break;
        }
        return $this->format(NULL
            , "%s was %s %s team"
            , $action
            , $payload['team']['name']
        );
    }

    /**
     * Format page_build event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::page_build()
     */
    public function page_build(array $payload)
    {
        return $this->format(NULL
            , "Building page: %s"
            , $payload['build']['status']
        );
    }

    /**
     * Format _public event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::_public()
     */
    public function _public(array $payload)
    {
        return $this->format($payload['repository']['html_url']
            , "This repository has been made publicly available!"
        );
    }

    /**
     * Format pull_request event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::pull_request()
     */
    public function pull_request(array $payload)
    {
        switch ($payload['action']) {
            case 'assigned':
                return $this->format($payload['pull_request']['url']
                    , "Pull request #%d: %s%s has been assigned to %s"
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                    , '???' // FIXME: Documentation doesn't state what the "assignee" variable is
                );
                break;
            case 'unassigned':
                return $this->format($payload['pull_request']['url']
                    , "%s has been unassigned from pull request #%d: %s%s"
                    , '???' // FIXME: Documentation doesn't state what the "assignee" variable is
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'labeled':
                return $this->format($payload['pull_request']['url']
                    , "Pull request #%d: %s%s has been labeled as %s"
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                    , '???' // FIXME: Documentation doesn't state what the "labeled" variable is
                );
                break;
            case 'unlabeled':
                return $this->format($payload['pull_request']['url']
                    , "Label %s removed from pull request #%d: %s%s" 
                    , '???' // FIXME: Documentation doesn't state what the "labeled" variable is
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'opened':
                return $this->format($payload['pull_request']['url']
                    , "%s has opened pull request #%d: %s%s"
                    , $payload['pull_request']['user']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'closed':
                return $this->format($payload['pull_request']['url']
                    , "%s has closed pull request #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'reopened':
                return $this->format($payload['pull_request']['url']
                    , "%s has reopened pull request #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'synchronize':
                return $this->format($payload['pull_request']['url']
                    , "%s has synchronized pull request #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            default:
                return NULL; // This shouldn't happen...
                break;
        }
    }

    /**
     * Format pull_request_review_comment event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::pull_request_review_comment()
     */
    public function pull_request_review_comment(array $payload)
    {
        return $this->format($payload['comment']['html_url']
            , "%s has commented on pull request #%d: %s%s (%s%s)"
            , $payload['comment']['user']['login']
            , $payload['pull_request']['number']
            , \substr($payload['pull_request']['title'], 0, 16)
            , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
            , \substr($payload['comment']['body'], 0, 16)
            , (\strlen($payload['comment']['body']) > 16 ? '...' : '')
        );
    }

    /**
     * Format push event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::push()
     */
    public function push(array $payload)
    {
        $output = [];
        $output[] = $this->format($payload['compare']
            , "%s pushed %d %s to %s"
            , $payload['sender']['login']
            , count($payload['commits'])
            , (count($payload['commits']) == 1 ? "commit" : "commits")
            , $payload['repository']['full_name']
        );
        foreach ($payload['commits'] as $commit) {
            if (strpos($commit['message'], "\n\n") !== FALSE) {
                list($message, ) = explode("\n\n", $commit['message'], 2);
            }
            else {
                $message = \substr($commit['message'], 0, 64);
            }
            $output[] = $this->format($commit['url']
                , "Commit %s by %s added %d, removed %d and modified %d files: %s"
                , \substr($commit['id'], 0, 7)
                , $commit['committer']['username']
                , count($commit['added'])
                , count($commit['removed'])
                , count($commit['modified'])
                , $message
            );
        }
        return $output;
    }

    /**
     * Format release event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::release()
     */
    public function release(array $payload)
    {
        return $this->format(NULL
            , "%s %s release %s"
            , $payload['sender']['login']
            , $payload['action']
            , $payload['release']['tag_name']
        );
    }

    /**
     * Format repository event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::repository()
     */
    public function repository(array $payload)
    {
        return $this->format(NULL
            , "%s created the repository %s"
            , $payload['sender']['login']
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format public  event
     *
     * @todo Make more intelligent, travis for some reason reports pending twice
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::status()
     */
    public function status(array $payload)
    {
        return $this->format($payload['target_url']
            , "Commit %s %s: %s"
            , substr($payload['sha'], 0, 7)
            , $payload['state']
            , $payload['description']
        );
    }

    /**
     * Format team_add event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::team_add()
     */
    public function team_add(array $payload)
    {
        return $this->format(NULL
            , "Team %s has been added to %s"
            , $payload['team']['name']
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format watch event
     *
     * @param array $payload Event payload directly from GitHub
     * @see HandlerInterface::watch()
     */
    public function watch(array $payload)
    {
        return $this->format(NULL
            , "We gained a fan, %s just starred %s!"
            , $payload['sender']['login']
            , $payload['repository']['full_name']
        );
    }
}
