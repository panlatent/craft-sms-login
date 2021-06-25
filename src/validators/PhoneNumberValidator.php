<?php

namespace panlatent\craft\smslogin\validators;

use yii\validators\Validator;

/**
 * Class PhoneNumberValidator
 *
 * @package panlatent\craft\smslogin\validators
 */
class PhoneNumberValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateValue($value): string
    {
        if (empty($value)) {
            return 'phone number not empty';
        }
        return '';
    }
}