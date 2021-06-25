<?php

namespace panlatent\craft\smslogin\events;

use panlatent\craft\smslogin\models\Captcha;
use yii\base\Event;

/**
 * Class CaptchaEvent
 *
 * @package panlatent\craft\smslogin\events
 */
class CaptchaEvent extends Event
{
    /**
     * @var Captcha|null
     */
    public $captcha;

    /**
     * @var bool
     */
    public $isNew = false;
}