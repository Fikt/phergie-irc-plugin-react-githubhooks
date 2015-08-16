<?php
/**
 * Phergie plugin for Listen for GitHub webhooks, announce events on IRC. (http://github.com/Fikt/phergie-irc-plugin-react-githubhooks/wiki)
 *
 * @link https://github.com/fikt/phergie-irc-plugin-react-githubhooks for the canonical source repository
 * @copyright Copyright (c) 2015 Gunnsteinn Þórisson (https://github.com/Gussi)
 * @license https://github.com/Fikt/phergie-irc-plugin-react-githubhooks/blob/master/LICENSE Simplified BSD License
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */

namespace Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Formatter;

/**
 * Standard formatter class.
 *
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class Standard {

    /**
     * Prefix every message with this
     *
     * @param array $payload Event payload directly from GitHub
     */
    static private function prefix($payload) {
        // If it contains a repo, prefix that
        if (!empty($payload['repository'])) {
            return sprintf("[%s] "
                , $payload['repository']['name']
            );
        }

        // If there's no repo, then it's probably something about an organization
        if (!empty($payload['organization'])) {
            return sprintf("[%s] "
                , $payload['organization']['login']
            );
        }

        // Otherwise I have no idea what might be up
        return "[UNKNOWN] ";
    }

    /**
     * Return correctly formatted url
     *
     * @param string $url URL to display
     * @todo Insert URL shortened here
     */
    static private function url($url) {
        return " [$url]";
    }

    /**
     * Pretty much a custom sprintf, prepend prefix and append url
     *
     * @todo Color supprt, a classical one (not an object with an ->color() method)
     * @param array $payload Event payload directly from GitHub
     * @param string $url URL related to this event
     * @param string $string The message itself
     * @param mixed ...$params Parameters printf style
     */
    static private function format($payload, $url, $string, ...$params) {
        return self::prefix($payload) . vsprintf($string, $params) . ($url !== NULL ? self::url($url) : "");
    }

    /**
     * Format ping event
     *
     * @param array $payload Event payload directly from GitHub
     */
    public function ping($payload) {
        return self::format($payload, NULL
            , "Ping received, zen is: %s"
            , $payload['zen']
        );
    }

    /**
     * Format commit_comment event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function commit_comment($payload) {
        return self::format($payload, $payload['comment']['url']
            , "%s commented on commit %s (%s%s)"
            , $payload['comment']['user']['login']
            , \substr($payload['comment']['commit_id'], 0, 7)
            , \substr($payload['comment']['body'], 0, 16)
            , (\strlen($payload['comment']['body']) > 16 ? '...' : '')
        );
    }

    /**
     * Format create event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function create($payload) {
        return self::format($payload, NULL
            , "%s created new %s named %s"
            , $payload['user']['login']
            , $payload['ref_type']
            , ($payload['ref'] !== NULL ? $payload['ref'] : $payload['repository']['full_name'])
        );
    }

    /**
     * Format delete event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function delete($payload) {
        return self::format($payload, NULL
            , "%s removed the %s %s"
            , $payload['user']['login']
            , $payload['ref_type']
            , $payload['ref']
        );
    }

    /**
     * Format deployment event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function deployment($payload) {
        return self::format($payload, NULL
            , "Deploying %s for %s %s"
            , substr($payload['deploymet']['sha'], 0, 7)
            , $payload['deployment']['environment']
            , (!empty($payload['deployment']['description']) ? $payload['deployment']['description'] : '')
        );
    }

    /**
     * Format deployment_status event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function deployment_status($payload) {
        $state = "";
        switch ($payload['deployment_status']['state']) {
            case 'pending':
                $state = "is pending...";
                break;
            case 'success':
                $state = "was successful!";
                break;
            case 'failure':
                $state = "failed!";
                break;
            case 'error':
                $state = "failed with an error!";
                break;
        }
        return self::format($payload, $payload['deployment_status']['target_url']
            , "Deployment for %s %s %s"
            , $payload['deployment']['environment']
            , $state
            , (!empty($payload['deployment_status']['description']) ? $payload['deployment_status']['description'] : '')
        );
    }

    /**
     * Format fork event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function fork($payload) {
        return self::format($payload, $payload['forkee']['html_url']
            , "%s just forked %s to his very own %s!"
            , $payload['forkee']['owner']['login']
            , $payload['repository']['full_name']
            , $payload['forkee']['full_name']
        );
    }

    /**
     * Format gollum event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function gollum($payload) {
        // For some reason $payload['pages'] is an array
        // Even if users can create multiple pages at a time, each should have it's own event imho
        // And maybe it is... I guess we have to see it in practice
        if (count($payload['pages']) == 1) {
            $page = $payload['pages'][0];
            return self::format($payload, $page['html_url']
                , "%s created new wiki entry '%s'"
                , $payload['user']['login']
                , $page['title']
            );
        }

        return self::format($payload, NULL
            , "%s created %d wiki entries"
            , $payload['user']['login']
            , count($payload['pages'])
        );
    }

    /**
     * Format gollum event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function issue_comment($payload) {
        return self::format($payload, $payload['comment']['url']
            , "%s %s comment (%s%s) on issue #%d: %s%s"
            , $payload['comment']['user']['login']
            , $payload['action']
            , \substr($payload['comment']['body'], 0, 16)
            , (\strlen($payload['comment']['body']) > 16 ? '...' : '')
            , $payload['issue']['number']
            , \substr($payload['issue']['title'], 0, 16)
            , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
        );
    }

    /**
     * Format issues event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function issues($payload) {
        switch ($payload['action']) {
            case 'assigned':
                return self::format($payload, $payload['issue']['url']
                    , "%s was assigned issue #%d: %s%s%s"
                    , $payload['assignee']['login']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                    , ($payload['assignee'] != $payload['sender']['login'] ? " by {$payload['sender']['login']}" : "")
                );
                break;
            case 'unassigned':
                return self::format($payload, $payload['issue']['url']
                    , "%s has been unassigned issue #%d: %s%s%s"
                    , $payload['assignee']['login']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                    , ($payload['assignee'] != $payload['sender']['login'] ? " by {$payload['sender']['login']}" : "")
                );
                break;
            case 'labeled':
                return self::format($payload, $payload['issue']['url']
                    , "%s labeled issue #%d: %s%s as %s"
                    , $payload['sender']['login']
                    , $payload['issue']['number']
                    , \substr($payload['issue']['title'], 0, 16)
                    , (\strlen($payload['issue']['title']) > 16 ? '...' : '')
                    , $payload['label']['name']
                );
                break;
            case 'unlabeled':
                return self::format($payload, $payload['issue']['url']
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
                return self::format($payload, $payload['issue']['url']
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
     * @param $payload array Event payload directly from GitHub
     */
    public function member($payload) {
        return self::format($payload, NULL
            , "%s was %s to the repository"
            , $payload['member']['login']
            , $payload['action']
        );
    }

    /**
     * Format membership event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function membership($payload) {
        $action = "";
        switch ($action) {
            case 'added':
                $action = "was added to";
                break;
            case 'removed':
                $action = "has been removed from";
                break;
        }
        return self::format($payload, NULL
            , "%s was %s %s team"
            , $action
            , $payload['team']['name']
        );
    }

    /**
     * Format page_build event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function page_build($payload) {
        return self::format($payload, NULL
            , "Building page: %s"
            , $payload['build']['status']
        );
    }

    /**
     * Format public event
     *
     * @note The function is named _public because PHP can't have methods named "public"
     * @param $payload array Event payload directly from GitHub
     */
    public function _public($payload) {
        return self::format($payload, $payload['repository']['html_url']
            , "This repository has been made publicly available!"
        );
    }

    /**
     * Format pull_request event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function pull_request($payload) {
        switch ($payload['action']) {
            case 'assigned':
                return self::format($payload, $payload['pull_request']['url']
                    , "Pull request #%d: %s%s has been assigned to %s"
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                    , '???' // FIXME: Documentation doesn't state what the "assignee" variable is
                );
                break;
            case 'unassigned':
                return self::format($payload, $payload['pull_request']['url']
                    , "%s has been unassigned from pull request #%d: %s%s"
                    , '???' // FIXME: Documentation doesn't state what the "assignee" variable is
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'labeled':
                return self::format($payload, $payload['pull_request']['url']
                    , "Pull request #%d: %s%s has been labeled as %s"
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                    , '???' // FIXME: Documentation doesn't state what the "labeled" variable is
                );
                break;
            case 'unlabeled':
                return self::format($payload, $payload['pull_request']['url']
                    , "Label %s removed from pull request #%d: %s%s" 
                    , '???' // FIXME: Documentation doesn't state what the "labeled" variable is
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'opened':
                return self::format($payload, $payload['pull_request']['url']
                    , "%s has opened pull request #%d: %s%s"
                    , $payload['pull_request']['user']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'closed':
                return self::format($payload, $payload['pull_request']['url']
                    , "%s has closed pull request #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'reopened':
                return self::format($payload, $payload['pull_request']['url']
                    , "%s has reopened pull request #%d: %s%s"
                    , $payload['sender']['login']
                    , $payload['pull_request']['number']
                    , \substr($payload['pull_request']['title'], 0, 16)
                    , (\strlen($payload['pull_request']['title']) > 16 ? '...' : '')
                );
                break;
            case 'synchronize':
                return self::format($payload, $payload['pull_request']['url']
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
     * Format pull_request_review event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function pull_request_review_comment($payload) {
        return self::format($payload, $payload['comment']['html_url']
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
     * @param $payload array Event payload directly from GitHub
     */
    public function push($payload) {
        return self::format($payload, NULL
            , "%s pushed %d commits"
            , $payload['sender']['login']
            , count($payload['commits'])
        );
    }

    /**
     * Format release event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function release($payload) {
        return self::format($payload, NULL
            , "%s %s release %s"
            , $payload['sender']['login']
            , $payload['action']
            , $payload['release']['tag_name']
        );
    }

    /**
     * Format repository event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function repository($payload) {
        return self::format($payload, NULL
            , "%s created the repository %s"
            , $payload['sender']['login']
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format status event
     *
     * @note I have no idea what this is or what it does...
     * @param $payload array Event payload directly from GitHub
     */
    public function status($payload) {
        return self::format($payload, $payload['target_url']
            , "Commit %s (%s): %s"
            , substr($payload['sha'], 0, 7)
            , $payload['state']
            , $payload['description']
        );
    }

    /**
     * Format team_add event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function team_add($payload) {
        return self::format($payload, NULL
            , "Team %s has been added to %s"
            , $payload['team']['name']
            , $payload['repository']['full_name']
        );
    }

    /**
     * Format watch event
     *
     * @param $payload array Event payload directly from GitHub
     */
    public function watch($payload) {
        return self::format($payload, NULL
            , "We gained a fan, %s just starred us!"
            , $payload['sender']['login']
        );
    }
}
