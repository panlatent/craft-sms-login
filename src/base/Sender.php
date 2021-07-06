<?php

namespace panlatent\craft\smslogin\base;

use craft\base\SavableComponent;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use panlatent\craft\smslogin\events\SenderEvent;
use panlatent\craft\smslogin\records\Sender as SenderRecord;

/**
 * Class Sender
 *
 * @package panlatent\craft\smslogin\base
 */
abstract class Sender extends SavableComponent implements SenderInterface
{
    // Events
    // -------------------------------------------------------------------------

    const EVENT_BEFORE_SEND = 'beforeSend';

    const EVENT_AFTER_SEND= 'afterSend';

    // Properties
    // =========================================================================

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string|null
     */
    public $handle;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'handle'], 'required'],
            [['id'], 'integer'],
            [['name', 'handle', 'uid'], 'string'],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => SenderRecord::class],
            [['handle'], HandleValidator::class],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->name;
    }

    public function beforeSend(): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SEND)) {
            $this->trigger(self::EVENT_BEFORE_SEND, new SenderEvent([
                'sSender' => $this
            ]));
        }

        return true;
    }

    public function afterSend()
    {
        if ($this->hasEventHandlers(self::EVENT_AFTER_SEND)) {
            $this->trigger(self::EVENT_AFTER_SEND, new SenderEvent([
                'sSender' => $this
            ]));
        }
    }
}