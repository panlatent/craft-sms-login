<?php

namespace panlatent\craft\smslogin\controllers;

use craft\web\Controller;
use panlatent\craft\smslogin\helpers\CaptchaHelper;
use panlatent\craft\smslogin\models\Captcha;
use panlatent\craft\smslogin\Plugin as SmsLogin;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Class SmsController
 *
 * @package panlatent\craft\smslogin\controllers
 */
class SmsController extends Controller
{
    protected $allowAnonymous = ['send', 'validate'];

    public $enableCsrfValidation = false;

    /**
     * @return Response
     */
    public function actionSend(): ?Response
    {
        $this->requirePostRequest();

        $phone = $this->request->getBodyParam('phone');
        $captcha = SmsLogin::$plugin->getSms()->getLastCaptchaByPhone($phone);
        if ($captcha) {
            $recoveryInterval = SmsLogin::$plugin->getSettings()->sendRecoveryInterval;
            if (($interval = time() - $captcha->dateCreated->getTimestamp()) < $recoveryInterval) {
                return $this->asJson([
                    'error' => 'fatigue period',
                    'recovery' => $recoveryInterval - $interval
                ]);
            }
        }

        $captcha = new Captcha();
        $captcha->phone = $phone;
        $captcha->token = $this->request->getBodyParam('token');
        $captcha->route = $this->request->getFullPath();

        $captcha->code = CaptchaHelper::generateCodeNumber6();

        if (!SmsLogin::$plugin->getSms()->postCaptcha($captcha)) {
            return $this->asJson([
                'error' => 'send failed',
            ]);
        }

        $sender = SmsLogin::$plugin->getSenders()->getPrimarySender();
        if (!$sender) {
            throw new InvalidConfigException('Missing sender');
        }
        if (!$sender->send($captcha)) {
            return $this->asJson([
                'error' => 'send failed'
            ]);
        }

        return $this->asJson([]);
    }

    /**
     * @return Response
     */
    public function actionValidate(): Response
    {
        $this->requirePostRequest();

        $phone = $this->request->getBodyParam('username');
        $token =  $this->request->getBodyParam('token');
        $code =  $this->request->getBodyParam('code');

        $err = SmsLogin::$plugin->getSms()->testCaptcha($phone, $token, $code);

        return $this->asJson([
            'error' => $err,
            'errorMessage' => $err,
        ]);
    }
}