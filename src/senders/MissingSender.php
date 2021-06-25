<?php

namespace panlatent\craft\smslogin\senders;

use panlatent\craft\smslogin\base\Sender;
use panlatent\craft\smslogin\errors\SenderException;
use panlatent\craft\smslogin\models\Captcha;

/**
 * Class MissingSender
 *
 * @package panlatent\craft\smslogin\senders
 */
final class MissingSender extends Sender
{
    public function send(Captcha $captcha): bool
    {
        throw new SenderException('Missing Sender not send message');
    }

}