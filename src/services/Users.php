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
        $settings = Plugin::$plugin->getSettings();
        $fieldHandle = $settings->getPhoneNumberFieldHandle();
        if ($fieldHandle === '') {
            return null;
        }

        if (version_compare(Craft::$app->getVersion(), '3.7') === -1) {
            $fieldColumn = 'content.field_' . $fieldHandle;
        } else {
            $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);
            $fieldColumn = 'content.' . ($field->columnPrefix ?? 'field_') . $field->handle . '_'  . $field->columnSuffix;
        }

        return User::find()->where([
            $fieldColumn => $phone
        ])->one();
    }

    public function canBindPhone(string $phone): bool
    {
        $fieldHandle = Plugin::$plugin->getSettings()->phoneNumberField;
        if ($fieldHandle === '') {
            return false;
        }
        return $this->getUserByPhone($phone) === null;
    }

    public function bindPhone(User $user, string $phone, bool $save = true): bool
    {
        $fieldHandle = Plugin::$plugin->getSettings()->getPhoneNumberFieldHandle();
        if ($fieldHandle === '') {
            return false;
        }

        if ($this->getUserByPhone($phone)) {
            return false;
        }

        $user->setFieldValue($fieldHandle, $phone);
        if ($save && !Craft::$app->getElements()->saveElement($user, false)) {
            return false;
        }

        return true;
    }
}