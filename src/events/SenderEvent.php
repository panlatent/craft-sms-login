<?php

namespace panlatent\craft\smslogin\events;

use panlatent\craft\smslogin\base\SenderInterface;
use yii\base\Event;

/**
 * Class SenderEvent
 *
 * @package panlatent\craft\smslogin\events
 */
class SenderEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var SenderInterface|null (property name conflict basic class)
     */
    public $sSender;

    /**
     * @var bool
     */
    public $isNew = false;
}