<?php

namespace panlatent\craft\smslogin;

use panlatent\craft\smslogin\services\Senders;
use panlatent\craft\smslogin\services\Sms;
use panlatent\craft\smslogin\services\Users;

/**
 * Trait Services
 *
 * @package panlatent\craft\smslogin
 * @property-read Senders $senders
 * @property-read Sms $sms
 * @property-read Users $users
 */
trait Services
{
    // Public Methods
    // =========================================================================

    public function getSenders(): Senders
    {
        return $this->get('senders');
    }

    public function getSms(): Sms
    {
        return $this->get('sms');
    }

    public function getUsers(): Users
    {
        return $this->get('users');
    }

    private function _setComponents()
    {
        $this->setComponents([
            'senders' => Senders::class,
            'sms' => Sms::class,
            'users' => Users::class,
        ]);
    }
}