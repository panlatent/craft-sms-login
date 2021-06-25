<?php

namespace panlatent\craft\smslogin\senders;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use Craft;
use Darabonba\OpenApi\Models\Config;
use panlatent\craft\smslogin\base\Sender;
use panlatent\craft\smslogin\errors\SenderException;
use panlatent\craft\smslogin\models\Captcha;

/**
 * Class Aliyun
 *
 * @package panlatent\craft\smslogin\senders
 */
class Aliyun extends Sender
{
    const ALIBABA_CLOUD_PROFILE = 'CRAFT_ALIYUN_SMS';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('smslogin', 'Aliyun');
    }

    /**
     * @var string|null
     */
    public $accessKeyId;

    /**
     * @var string|null
     */
    public $accessKeySecret;

    /**
     * @var string|null
     * @deprecated
     */
    public $regionId;

    /**
     * @var string|null
     */
    public $signName;

    /**
     * @var string|null
     */
    public $templateCode;

    /**
     * @var string|null
     */
    public $templateParamTemplate = '{"code": "{{ code }}"}';

    /**
     * @var bool
     * @deprecated
     */
    public $secure = false;

    /**
     * @var Dysmsapi|null
     */
    private $_client;

    /**
     * @return string
     */
    public function getAccessKeyId(): string
    {
        return Craft::parseEnv($this->accessKeyId);
    }

    /**
     * @return string
     */
    public function getAccessKeySecret(): string
    {
        return Craft::parseEnv($this->accessKeySecret);
    }

    /**
     * @return string
     * @deprecated
     */
    public function getRegionId(): string
    {
        return Craft::parseEnv($this->regionId);
    }

    /**
     * @return string
     */
    public function getSignName(): string
    {
        return Craft::parseEnv($this->signName);
    }

    /**
     * @return string
     */
    public function getTemplateCode(): string
    {
        return Craft::parseEnv($this->templateCode);
    }

    /**
     * @return string
     */
    public function getTemplateParamTemplate(): string
    {
        return Craft::parseEnv($this->templateParamTemplate);
    }

    /**
     * @return bool
     * @deprecated
     */
    public function getSecure(): bool
    {
        return Craft::parseEnv($this->secure);
    }

    /**
     * @return Dysmsapi
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $config = new Config([
                "accessKeyId" => $this->getAccessKeyId(),
                "accessKeySecret" => $this->getAccessKeySecret(),
            ]);
            $config->endpoint = "dysmsapi.aliyuncs.com";
            $this->_client = new Dysmsapi($config);
        }
        return $this->_client;
    }

    /**
     * @inheritdoc
     */
    public function send(Captcha $captcha): bool
    {
        $this->beforeSend();

        $sendSmsRequest = new SendSmsRequest([
            "phoneNumbers" => $captcha->phone,
            "signName" => $this->getSignName(),
            "templateCode" => $this->getTemplateCode(),
            "templateParam" => Craft::$app->getView()->renderString($this->getTemplateParamTemplate(), [
                'sender' => $this,
                'captcha' => $captcha,
                'code' => $captcha->code,
            ]),
        ]);

        $result = $this->getClient()->sendSms($sendSmsRequest)->body;
        if ($result->code != 'OK') {
            Craft::error(self::displayName() . ': ' . $result->message);
            $captcha->reason = $result->message;
            throw new SenderException($result->message);
        }

        $this->afterSend();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('smslogin/_components/senders/Aliyun/settings', [
            'sender' => $this,
        ]);
    }
}