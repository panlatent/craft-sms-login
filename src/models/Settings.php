<?php

namespace panlatent\craft\smslogin\models;

use Craft;
use yii\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $usePhoneNumberAsUsername = false;

    /**
     * @var bool
     */
    public $allowImmediateRegisterOnLogin = false;

    /**
     * @var bool
     */
    public $allowReserveVerifiedLogin = true;

    /**
     * @var string
     */
    public $unregisterReturnUrl = 'login';

    /**
     * @var string
     */
    public $reserveVerifiedLoginSessionParam = '__reserve_verified_login';

    /**
     * @var int
     */
    public $reserveVerifiedLoginTimeout = 1800;

    /**
     * @var bool
     */
    public $registerWithoutPassword = true;

    /**
     * @var bool
     */
    public $registerWithoutEmail = true;

    /**
     * @var string
     * @deprecated
     */
    public $defaultRegisterEmail = 'default@abc.test';

    /**
     * @var string
     */
    public $defaultRegisterEmailDomain = '';

    /**
     * @var
     */
    public $registerUserGroup;

    /**
     * @var string|null
     */
    public $phoneNumberFieldHandle;

    public $siteSendTrigger = '/smslogin/send';

    public $siteValidateTrigger = '/smslogin/validate';

    public $sendRecoveryInterval = 60;

    public function getPhoneNumberFieldHandle(): string
    {
        return Craft::parseEnv($this->phoneNumberFieldHandle) ?: '';
    }

    public function getDefaultRegisterEmailDomain(): string
    {
        $value = $this->defaultRegisterEmailDomain;
        if ($value === '') {
            if (($pos = strpos($this->defaultRegisterEmail, '@')) !== false) {
                $value = substr($this->defaultRegisterEmail, $pos + 1);
            }
        }
        return Craft::parseEnv($value);
    }
}