<?php

namespace panlatent\craft\smslogin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Trait Routes
 *
 * @package panlatent\craft\smslogin
 */
trait Routes
{
    /**
     * Register URL Rules.
     */
    private function _registerUrlRules()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) {
            if ($this->getSettings()->siteSendTrigger !== '') {
                $event->rules[$this->getSettings()->siteSendTrigger] = 'smslogin/sms/send';
            }
            if ($this->getSettings()->siteValidateTrigger !== '') {
                $event->rules[$this->getSettings()->siteValidateTrigger] = 'smslogin/sms/validate';
            }
        });
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'smslogin/settings/senders/new' => 'smslogin/senders/edit-sender',
                'smslogin/settings/senders/<senderId:\d+>' => 'smslogin/senders/edit-sender',
                'smslogin/users/save-settings' => 'smslogin/users/save-settings',
            ]);
        });
    }
}