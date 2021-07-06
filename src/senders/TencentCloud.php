<?php

namespace panlatent\craft\smslogin\senders;

use Craft;
use panlatent\craft\smslogin\base\Sender;
use panlatent\craft\smslogin\errors\SenderException;
use panlatent\craft\smslogin\models\Captcha;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Sms\V20190711\Models\SendStatus;
use TencentCloud\Sms\V20190711\SmsClient;

/**
 * Class TencentCloud
 *
 * @package panlatent\craft\smslogin\senders
 */
class TencentCloud extends Sender
{
    const SMS_ENDPOINT = 'sms.tencentcloudapi.com';

    /**
     * @var string|null
     */
    public $secretId;

    /**
     * @var string|null
     */
    public $secretKey;

    /**
     * @var string|null
     */
    public $regionId;

    /**
     * @var string|null
     */
    public $sdkAppId;

    /**
     * @var string|null
     */
    public $templateId;

    /**
     * @var string|null
     */
    public $signName;

    /**
     * @var array
     */
    public $templateParams = ['{{ code }}'];

    /**
     * @var SmsClient|null
     */
    private $_client;

    /**
     * @return string|null
     */
    public function getSecretId(): ?string
    {
        return Craft::parseEnv($this->secretId);
    }

    /**
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return Craft::parseEnv($this->secretKey);
    }

    /**
     * @return string|null
     */
    public function getRegionId(): ?string
    {
        return Craft::parseEnv($this->regionId);
    }

    /**
     * @return string|null
     */
    public function getSdkAppId(): ?string
    {
        return Craft::parseEnv($this->sdkAppId);
    }

    /**
     * @return string|null
     */
    public function getTemplateId(): ?string
    {
        return Craft::parseEnv($this->templateId);
    }

    /**
     * @return string|null
     */
    public function getSignName(): ?string
    {
        return Craft::parseEnv($this->signName);
    }

    /**
     * @return SmsClient
     */
    public function getClient(): SmsClient
    {
        if ($this->_client === null) {
            $cred = new Credential($this->getSecretId(), $this->getSecretKey());
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint(self::SMS_ENDPOINT);

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $this->_client = new SmsClient($cred, $this->getRegionId(), $clientProfile);
        }
        return $this->_client;
    }

    /**
     * @inheritdoc
     */
    public function send(Captcha $captcha): bool
    {
        try {
            $templateParams = [];
            foreach ($this->templateParams as $template) {
                if (is_array($template) && isset($template['template'])) {
                    $template = $template['template'];
                }
                $templateParams[] = Craft::$app->getView()->renderString($template, [
                    'sender' => $this,
                    'captcha' => $captcha,
                    'code' => $captcha->code,
                ]);
            }

            $phone = $captcha->phone;
            if (strlen($phone) === 11) {
                $phone = '+86' . $phone;
            }

            $req = new SendSmsRequest();
            $req->setPhoneNumberSet([$phone]);
            $req->setSmsSdkAppid($this->getSdkAppId());
            $req->setTemplateID($this->getTemplateId());
            $req->setTemplateParamSet($templateParams);

            $sign = $this->getSignName();
            if (!empty($sign)) {
                $req->setSign($sign);
            }

            $resp = $this->getClient()->SendSms($req);

            /** @var SendStatus[] $statuses */
            $statuses = $resp->getSendStatusSet();
            $status = reset($statuses);

            if ($status->getCode()!== '') {
                Craft::error(self::displayName() . ': ' . sprintf('[%s]%s', $status->getPhoneNumber(), $status->getMessage()));
                return false;
            }
        } catch(TencentCloudSDKException $e) {
            Craft::error(self::displayName() . ': ' . $e->getMessage());
            throw new SenderException($e->getMessage());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('smslogin/_components/senders/TencentCloud/settings', [
            'sender' => $this,
        ]);
    }
}