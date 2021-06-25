<?php

namespace panlatent\craft\smslogin\models;

use craft\base\Model;
use DateTime;

/**
 * Class Captcha
 *
 * @package panlatent\craft\smslogin\models
 */
class Captcha extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $token;

    /**
     * @var string
     */
    public $route;

    /**
     * @var DateTime
     */
    public $expireDate;

    /**
     * @var DateTime|null
     */
    public $postDate;

    /**
     * @var DateTime|null
     */
    public $passedDate;

    /**
     * @var DateTime|null
     */
    public $lastTestDate;

    /**
     * @var int
     */
    public $retryPost = 0;

    /**
     * @var int
     */
    public $retryTest = 0;

    /**
     * @var string
     */
    public $reason;

    /**
     * @var DateTime
     */
    public $dateCreated;

    /**
     * @var DateTime
     */
    public $dateUpdated;

    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] =  'expireDate';
        $attributes[] =  'postDate';
        $attributes[] =  'passedDate';
        $attributes[] =  'lastTestDate';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['phone', 'code', 'token', 'route'], 'required'],
            [['phone', 'code', 'token', 'route'], 'string'],
        ]);
    }
}