<?php

namespace panlatent\craft\smslogin\services;

use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use DateInterval;
use panlatent\craft\smslogin\db\Table;
use panlatent\craft\smslogin\errors\CaptchaException;
use panlatent\craft\smslogin\events\CaptchaEvent;
use panlatent\craft\smslogin\models\Captcha;
use yii\base\Component;

/**
 * Class Sms
 *
 * @package panlatent\craft\smslogin\services
 */
class Sms extends Component
{
    // Events
    // -------------------------------------------------------------------------
    const EVENT_BEFORE_SAVE_CAPTCHA = 'beforeSaveCaptcha';

    const EVENT_AFTER_SAVE_CAPTCHA = 'afterSaveCaptcha';

    const CAPTCHA_NO_EXISTS = 'captchaNoExists';

    const CAPTCHA_EXPIRED = 'captchaExpired';

    const CAPTCHA_NOT_MATCH = 'captchaNotMatch';

    /**
     * @param string $phone
     * @return Captcha|null
     */
    public function getLastCaptchaByPhone(string $phone): ?Captcha
    {
        $row = $this->_createQuery()
            ->where(['phone' => $phone])
            ->orderBy(['dateCreated' => SORT_DESC])
            ->one();
        if (!$row) {
            return null;
        }
        return new Captcha($row);
    }

    public function postCaptcha(Captcha $captcha, bool $runValidation = true): bool
    {
        if ($captcha->id) {
           throw new CaptchaException("Not post a exists captcha");
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_CAPTCHA)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_CAPTCHA, new CaptchaEvent([
                'captcha' => $captcha,
                'isNew' => true,
            ]));
        }

        if ($runValidation && !$captcha->validate()) {
            Craft::info("Captcha not saved due to validation error.", __METHOD__);
            return false;
        }

        $db = Craft::$app->getDb();

        $transaction = $db->beginTransaction();
        try {
            $now = DateTimeHelper::currentUTCDateTime();
            $db->createCommand()->insert(Table::CAPTCHA, [
                'phone' => $captcha->phone,
                'code' => $captcha->code,
                'token' => $captcha->token,
                'route' => $captcha->route,
                'expireDate' => $now->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s'),
            ])->execute();
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CAPTCHA)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CAPTCHA, new CaptchaEvent([
                'captcha' => $captcha,
                'isNew' => true,
            ]));
        }
        return true;
    }

    public function testCaptcha(string $phone, string $token, string $code, bool $setFlush = false): string
    {
        $res = $this->_createQuery()
            ->where(['phone' => $phone, 'token' => $token])
            ->andWhere(['is', 'passedDate', null])
            ->orderBy(['dateCreated' => SORT_DESC])
            ->one();
        if (!$res) {
            return self::CAPTCHA_NO_EXISTS;
        }

        $expireDate = DateTimeHelper::toDateTime($res['expireDate']);
        if ($expireDate->getTimestamp() < time()) {
            return self::CAPTCHA_EXPIRED;
        }

        if ($res['code'] !== $code) {
            return self::CAPTCHA_NOT_MATCH;
        }

        if ($setFlush) {
            $this->flushCaptcha(new Captcha([
                'phone' => $phone,
                'token' => $token,
                'code' => $code,
            ]));
        }

        return '';
    }

    public function flushCaptcha(Captcha $captcha)
    {
        Craft::$app->getDb()->createCommand()
            ->update(Table::CAPTCHA, [
                'passedDate' => DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s')
            ], [
                'phone' => $captcha->phone,
                'token' => $captcha->token,
                'code' => $captcha->code,
            ])
            ->execute();
    }

    public function getTestCaptchaErrorMessage(string $errorCode): string
    {
        return '';
    }

    public function deleteAllCaptcha(): bool
    {
        return (bool)Craft::$app->getDb()->createCommand()
            ->delete(Table::CAPTCHA)
            ->execute();
    }

    private function _createQuery(): Query
    {
        return (new Query())
            ->select(['id', 'phone', 'code', 'token', 'route', 'expireDate', 'postDate', 'passedDate', 'lastTestDate', 'reason', 'dateCreated', 'dateUpdated'])
            ->from(Table::CAPTCHA);
    }
}