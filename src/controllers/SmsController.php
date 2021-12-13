<?php

namespace panlatent\craft\smslogin\controllers;

use craft\web\Controller;
use panlatent\craft\smslogin\helpers\CaptchaHelper;
use panlatent\craft\smslogin\models\Captcha;
use panlatent\craft\smslogin\Plugin as SmsLogin;
use panlatent\craft\smslogin\validators\PhoneNumberValidator;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Class SmsController
 *
 * @package panlatent\craft\smslogin\controllers
 */
class SmsController extends Controller
{
    const INVALID_PHONE_NUMBER = 'invalidPhoneNumber';
    const FATIGUE_PERIOD = 'fatiguePeriod';
    const SEND_FAILURE = 'sendFailure';

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['send', 'validate'];

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @return Response
     */
    public function actionSend(): ?Response
    {
        $this->requirePostRequest();

        $phone = $this->request->getBodyParam('phone');
        if (!(new PhoneNumberValidator())->validate($phone)) {
            return $this->_handleSendFailure(self::INVALID_PHONE_NUMBER);
        }

        $captcha = SmsLogin::$plugin->getSms()->getLastCaptchaByPhone($phone);
        if ($captcha) {
            $recoveryInterval = SmsLogin::$plugin->getSettings()->sendRecoveryInterval;
            if (($interval = time() - $captcha->dateCreated->getTimestamp()) < $recoveryInterval) {
                return $this->_handleSendFailure(self::FATIGUE_PERIOD, [
                    'recovery' => $recoveryInterval - $interval,
                ]);
            }
        }

        $captcha = new Captcha();
        $captcha->phone = $phone;
        $captcha->token = $this->request->getBodyParam('token');
        $captcha->route = $this->request->getFullPath();

        $captcha->code = CaptchaHelper::generateCodeNumber6();

        if (!SmsLogin::$plugin->getSms()->postCaptcha($captcha)) {
            return $this->_handleSendFailure(self::SEND_FAILURE);
        }

        $senderHandle = SmsLogin::$plugin->getSettings()->getLoginSender();
        if (!$senderHandle || !($sender = SmsLogin::$plugin->getSenders()->getSenderByHandle($senderHandle))) {
            throw new InvalidConfigException('Missing sender');
        }
        if (!$sender->send($captcha)) {
            return $this->_handleSendFailure(self::SEND_FAILURE);
        }

        return $this->asJson([]);
    }

    /**
     * @return Response
     */
    public function actionValidate(): Response
    {
        $this->requirePostRequest();

        if (SmsLogin::$plugin->getSettings()->usePhoneNumberAsUsername) {
            $phone = $this->request->getBodyParam('username');
        } else {
            $phone = $this->request->getBodyParam('phone');
        }

        $token = $this->request->getBodyParam('token');
        $code = $this->request->getBodyParam('code');

        $err = SmsLogin::$plugin->getSms()->testCaptcha($phone, $token, $code);

        return $this->asJson([
            'error' => $err,
            'errorMessage' => $err,
        ]);
    }

    /**
     * @param string $error
     * @param array $params
     * @return Response|null
     */
    private function _handleSendFailure(string $error, array $params = []): ?Response
    {
        switch ($error) {
            case self::INVALID_PHONE_NUMBER:
                $message = 'invalid phone number';
                $code = 100001;
                break;
            case self::FATIGUE_PERIOD:
                $message = 'fatigue period';
                $code = 100002;
                break;
            case self::SEND_FAILURE:
            default:
                $message = 'send failed';
                $code = 10000;
        }

        return $this->asJson(array_merge([
            'error' => $message,
            'code' => $code,
        ], $params));
    }
}