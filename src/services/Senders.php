<?php

namespace panlatent\craft\smslogin\services;

use Craft;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Json;
use panlatent\craft\smslogin\base\Sender;
use panlatent\craft\smslogin\base\SenderInterface;
use panlatent\craft\smslogin\db\Table;
use panlatent\craft\smslogin\errors\SenderException;
use panlatent\craft\smslogin\events\SenderEvent;
use panlatent\craft\smslogin\records\Sender as SenderRecord;
use panlatent\craft\smslogin\senders\Aliyun;
use panlatent\craft\smslogin\senders\MissingSender;
use panlatent\craft\smslogin\senders\TencentCloud;
use Throwable;
use yii\base\Component;

/**
 * Class Senders
 *
 * @package panlatent\craft\smslogin\services
 */
class Senders extends Component
{
    // Events
    // -------------------------------------------------------------------------

    const EVENT_REGISTER_ALL_SENDER_TYPES = 'registerAllSenderTypes';

    const EVENT_BEFORE_SAVE_SENDER = 'beforeSaveSender';

    const EVENT_AFTER_SAVE_SENDER = 'afterSaveSender';

    const EVENT_BEFORE_DELETE_SENDER = 'beforeDeleteSender';

    const EVENT_AFTER_DELETE_SENDER = 'afterDeleteSender';

    // Properties
    // =========================================================================

    /**
     * @var SenderInterface[]|null
     */
    private $_senders;

    // Public Methods
    // =========================================================================

    /**
     * @return string[]
     */
    public function getAllSenderTypes(): array
    {
        $types = [
            Aliyun::class,
            TencentCloud::class
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $types,
        ]);

        $this->trigger(static::EVENT_REGISTER_ALL_SENDER_TYPES, $event);

        return $event->types;
    }

    /**
     * @return SenderInterface[]
     */
    public function getAllSenders(): array
    {
        if ($this->_senders === null) {
            $this->_senders = [];
            foreach ($this->_createQuery()->all() as $row) {
                $this->_senders[] = $this->createSender($row);
            }
        }
        return $this->_senders;
    }

    /**
     * @param int $id
     * @return SenderInterface|null
     */
    public function getSenderById(int $id): ?SenderInterface
    {
        return ArrayHelper::firstWhere($this->getAllSenders(), 'id', $id);
    }

    /**
     * @param string $handle
     * @return SenderInterface|null
     */
    public function getSenderByHandle(string $handle): ?SenderInterface
    {
        return ArrayHelper::firstWhere($this->getAllSenders(), 'handle', $handle);
    }

    /**
     * @param string $uid
     * @return SenderInterface|null
     */
    public function getSenderByUid(string $uid): ?SenderInterface
    {
        return ArrayHelper::firstWhere($this->getAllSenders(), 'uid', $uid);
    }

    /**
     * @return SenderInterface|null
     * @deprecated
     */
    public function getPrimarySender(): ?SenderInterface
    {
        $senders = $this->getAllSenders();
        if (count($senders) === 1) {
            return reset($senders);
        }
        return ArrayHelper::firstWhere($senders, 'primary', true);
    }

    /**
     * @param mixed $config
     * @return SenderInterface
     */
    public function createSender($config): SenderInterface
    {
        try {
            $sender = ComponentHelper::createComponent($config, SenderInterface::class);
        } catch (MissingComponentException $exception) {
            unset($config['type']);
            $sender = new MissingSender($config);
        }
        return $sender;
    }

    /**
     * @param SenderInterface $sender
     * @param bool $runValidation
     * @return bool
     */
    public function saveSender(SenderInterface $sender, bool $runValidation = true): bool
    {
        /** @var Sender $sender */
        $isNew = $sender->getIsNew();

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SENDER)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SENDER, new SenderEvent([
                'sSender' => $sender,
                'isNew' => $isNew,
            ]));
        }

        if (!$sender->beforeSave($isNew)) {
            return false;
        }

        if ($runValidation && !$sender->validate()) {
            Craft::info("Sender not saved due to validation error.", __METHOD__);
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$isNew) {
                $record = SenderRecord::findOne(['id' => $sender->id]);
                if (!$sender) {
                    throw new SenderException("No sender exists with the ID: “{$sender->id}“.");
                }
            } else {
                $record = new SenderRecord();
            }

            $record->name = $sender->name;
            $record->handle = $sender->handle;
            $record->type = get_class($sender);
            $record->settings = Json::encode($sender->getSettings());
            $record->save(false);

            if ($isNew) {
                $sender->id = $record->id;
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        $this->_senders = null;
        $senders = $this->getAllSenders();

        $sender->afterSave($isNew);

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SENDER)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SENDER, new SenderEvent([
                'sSender' => $sender,
                'isNew' => $isNew,
            ]));
        }

        return true;
    }

    /**
     * @param SenderInterface $sender
     * @return bool
     */
    public function deleteSender(SenderInterface $sender): bool
    {
        /** @var Sender $sender */
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_SENDER)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_SENDER, new SenderEvent([
                'sSender' => $sender,
            ]));
        }

        $sender->beforeDelete();

        $db = Craft::$app->getDb();

        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete(Table::SENDERS, [
                'id' => $sender->id,
            ])->execute();

            $transaction->commit();

            $sender->afterDelete();
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SENDER)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SENDER, new SenderEvent([
                'sSender' => $sender,
            ]));
        }

        return true;
    }

    /**
     * @return Query
     */
    private function _createQuery(): Query
    {
        return (new Query())
            ->select(['id', 'name', 'handle', 'type', 'settings', 'uid'])
            ->from(Table::SENDERS);
    }
}