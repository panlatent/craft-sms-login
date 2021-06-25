<?php

namespace panlatent\craft\smslogin\services;

use Craft;
use craft\elements\User;
use panlatent\craft\smslogin\Plugin;
use yii\base\Component;

/**
 * Class Users
 *
 * @package panlatent\craft\smslogin\services
 */
class Users extends Component
{
    /**
     * @param string $phone
     * @return User|null
     */
    public function getUserByPhone(string $phone): ?User
    {
        $fieldHandle = Plugin::$plugin->getSettings()->getPhoneNumberFieldHandle();
        if ($fieldHandle === '') {
            return null;
        }

        return User::find()->where([
            'content.field_' . $fieldHandle => $phone
        ])->one();
    }

    public function canBindPhone(string $phone): bool
    {
        $fieldHandle = Plugin::$plugin->getSettings()->getPhoneNumberFieldHandle();
        if ($fieldHandle === '') {
            return false;
        }
        return $this->getUserByPhone($phone) === null;
    }

    public function bindPhone(User $user, string $phone): bool
    {
        $fieldHandle = Plugin::$plugin->getSettings()->getPhoneNumberFieldHandle();
        if ($fieldHandle === '') {
            return false;
        }

        if ($this->getUserByPhone($phone)) {
            return false;
        }

        $user->setFieldValue($fieldHandle, $phone);
        if (!Craft::$app->getElements()->saveElement($user, false)) {
            return false;
        }

        return true;
    }
}