<?php

namespace panlatent\craft\smslogin\helpers;

use craft\helpers\StringHelper;

/**
 * Class CaptchaHelper
 *
 * @package panlatent\craft\smslogin\helpers
 */
abstract class CaptchaHelper
{
    const NumberChars = '0123456789';

    public static function generateCodeNumberN(int $length): string
    {
        return StringHelper::randomStringWithChars(self::NumberChars, $length);
    }

    public static function generateCodeNumber4(): string
    {
        return static::generateCodeNumberN(4);
    }

    public static function generateCodeNumber6(): string
    {
        return static::generateCodeNumberN(6);
    }
}