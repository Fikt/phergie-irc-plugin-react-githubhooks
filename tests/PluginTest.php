<?php
/**
 * Phergie plugin that listens for GitHub webhooks, announce events on IRC. (http://github.com/Fikt/phergie-irc-plugin-react-githubhooks/wiki)
 *
 * @link https://github.com/fikt/phergie-irc-plugin-react-githubhooks for the canonical source repository
 * @copyright Copyright (c) 2015 Gunnsteinn Þórisson (https://github.com/Gussi)
 * @license https://github.com/Fikt/phergie-irc-plugin-react-githubhooks/blob/master/LICENSE Simplified BSD License
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */

namespace Fikt\Phergie\Irc\Tests\Plugin\React\GitHubHooks;

use Phake;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Event\EventInterface as Event;
use Fikt\Phergie\Irc\Plugin\React\GitHubHooks\Plugin;

/**
 * Tests for the Plugin class.
 *
 * @category Fikt
 * @package Fikt\Phergie\Irc\Plugin\React\GitHubHooks
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{


    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin;
        $this->assertInternalType('array', $plugin->getSubscribedEvents());
    }
}
