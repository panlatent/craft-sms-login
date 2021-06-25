<?php

namespace panlatent\craft\smslogin\base;

use craft\base\SavableComponentInterface;
use panlatent\craft\smslogin\models\Captcha;

/**
 * Interface Sender
 *
 * @package panlatent\craft\smslogin\base
 */
interface SenderInterface extends SavableComponentInterface
{
    /**
     * @param Captcha $captcha
     * @return bool
     */
    public function send(Captcha $captcha): bool;
}