<?php

namespace panlatent\craft\smslogin\controllers;

use Craft;
use craft\web\Controller;
use panlatent\craft\smslogin\base\Sender;
use panlatent\craft\smslogin\base\SenderInterface;
use panlatent\craft\smslogin\Plugin as SmsLogin;
use panlatent\craft\smslogin\senders\Aliyun;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class SendersController
 *
 * @package panlatent\craft\smslogin\controllers
 */
class SendersController extends Controller
{
    /**
     * @param int|null $senderId
     * @param SenderInterface|null $sender
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionEditSender(int $senderId = null, SenderInterface $sender = null): Response
    {
        $senders = SmsLogin::$plugin->getSenders();

        /** @var Sender $sender */
        if ($sender === null) {
            if ($senderId !== null) {
                $sender = $senders->getSenderById($senderId);
                if (!$sender) {
                    throw new NotFoundHttpException();
                }
            } else {
                $sender = $senders->createSender(Aliyun::class);
            }
        }

        $isNewSender = $sender->getIsNew();

        $allSenderTypes = $senders->getAllSenderTypes();
        $senderInstances = [];
        $senderTypeOptions = [];
        foreach ($allSenderTypes as $class) {
            /** @var SenderInterface|string $class */
            $senderInstances[$class] = new $class();
            $senderTypeOptions[] = [
                'label' => $class::displayName(),
                'value' => $class,
            ];
        }

        return $this->renderTemplate('smslogin/settings/senders/_edit', [
            'isNewSender' => $isNewSender,
            'sender' => $sender,
            'senderInstances' => $senderInstances,
            'senderTypes' => $allSenderTypes,
            'senderTypeOptions' => $senderTypeOptions,
        ]);
    }

    /**
     * @return Response|null
     */
    public function actionSaveSender(): ?Response
    {
        $this->requirePostRequest();

        $senders = SmsLogin::$plugin->getSenders();
        $request = Craft::$app->getRequest();
        $type = $request->getBodyParam('type');

        $sender = $senders->createSender([
            'id' => $request->getBodyParam('senderId'),
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'type' => $type,
            'settings' => $request->getBodyParam('types.' . $type, []),
        ]);

        if (!$senders->saveSender($sender)) {
            Craft::$app->getSession()->setError(Craft::t('smslogin', 'Couldn’t save sender.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'sender' => $sender,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('smslogin', 'Sender saved.'));

        return $this->redirect('smslogin/settings/senders/' . $sender->id);
    }

    /**
     * @return Response
     */
    public function actionDeleteSender(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $senders = SmsLogin::$plugin->getSenders();

        $senderId = Craft::$app->getRequest()->getBodyParam('id');
        $sender = $senders->getSenderById($senderId);
        if (!$sender) {
            throw new NotFoundHttpException();
        }

        if (!$senders->deleteSender($sender)) {
            return $this->asJson([
                'success' => false,
            ]);
        }

        return $this->asJson([
            'success' => true,
        ]);
    }
}