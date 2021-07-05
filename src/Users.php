<?php

namespace panlatent\craft\smslogin;

use Craft;
use craft\elements\User as UserElement;
use craft\errors\UserGroupNotFoundException;
use craft\events\ModelEvent;
use craft\helpers\Session as SessionHelper;
use craft\helpers\StringHelper;
use craft\validators\UserPasswordValidator;
use craft\web\User;
use yii\base\Event;
use yii\web\UserEvent;

trait Users
{
    private function _registerUserEvents()
    {
        // Login
        Event::on(User::class, User::EVENT_AFTER_LOGIN, function(UserEvent $event) {
            if (Craft::$app->getRequest()->getBodyParam('reserveRegister') === null) {
                return;
            }

            $config = SessionHelper::remove($this->getSettings()->reserveVerifiedLoginSessionParam);
            if (empty($config)) {
                return;
            }

            if (time() - (int)$config['timestamp'] >= $this->getSettings()->reserveVerifiedLoginTimeout) {
                return;
            }

            $this->getUsers()->bindPhone($event->identity, $config['phone']);
        });

        Event::on(UserElement::class, UserElement::EVENT_BEFORE_VALIDATE, function(\yii\base\ModelEvent $event) {
            if (Craft::$app->getRequest()->getBodyParam('reserveRegister') === null) {
                return;
            }
            /** @var UserElement $user */
            $user = $event->sender;

            if ($this->getSettings()->usePhoneNumberAsUsername) {
                $phone = Craft::$app->getRequest()->getBodyParam('username');
            } else {
                $phone = Craft::$app->getRequest()->getBodyParam('phone');
            }

            if (($user->email === '' || $user->email === null) && $this->getSettings()->registerWithoutEmail) {
                $user->email = $phone . '@' . $this->getSettings()->getDefaultRegisterEmailDomain();
            }
            if ($user->newPassword === '' && $this->getSettings()->registerWithoutPassword) {
                $user->newPassword = StringHelper::randomString(UserPasswordValidator::MAX_PASSWORD_LENGTH, true);
            }
        });

        // Register
        Event::on(UserElement::class, UserElement::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
            if (!$event->isNew || Craft::$app->getRequest()->getBodyParam('reserveRegister') === null) {
                return;
            }

            /** @var UserElement $user */
            $user = $event->sender;

            if ($this->getSettings()->usePhoneNumberAsUsername) {
                $phone = Craft::$app->getRequest()->getBodyParam('username');
            } else {
                $phone = Craft::$app->getRequest()->getBodyParam('phone');
            }
            $token = Craft::$app->getRequest()->getBodyParam('token');
            $code = Craft::$app->getRequest()->getBodyParam('code');

            $err = $this->getSms()->testCaptcha($phone, $token, $code);
            if ($err !== '') {
                $user->addError('code', $err);
                $event->isValid = false;
                return;
            }

            if (!$this->getUsers()->canBindPhone($phone)) {
                $user->addError($this->getSettings()->usePhoneNumberAsUsername ? 'username' : $this->getSettings()->getPhoneNumberFieldHandle(), 'phone number already exists');
                $event->isValid = false;
            }
        });

        Event::on(UserElement::class, UserElement::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            if (!$event->isNew || Craft::$app->getRequest()->getBodyParam('reserveRegister') === null) {
                return;
            }

            /** @var UserElement $user */
            $user = $event->sender;
            $phone = Craft::$app->getRequest()->getBodyParam('username');

            if (!$this->getUsers()->bindPhone($user, $phone)) {
                $event->isValid = false;
            }

            $registerUserGroup = $this->getSettings()->registerUserGroup;
            if (!empty($registerUserGroup)) {
                $group = Craft::$app->getUserGroups()->getGroupByUid($registerUserGroup);
                if (!$group) {
                    throw new UserGroupNotFoundException();
                }
                Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);
            }
        });
    }
}