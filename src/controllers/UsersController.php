<?php

namespace panlatent\craft\smslogin\controllers;

use Craft;
use craft\elements\User;
use craft\events\LoginFailureEvent;
use craft\helpers\Session as SessionHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\ServiceUnavailableHttpException;
use panlatent\craft\smslogin\errors\UserException;
use panlatent\craft\smslogin\Plugin as SmsLogin;
use panlatent\craft\smslogin\services\Sms;
use panlatent\craft\smslogin\validators\PhoneNumberValidator;
use yii\web\Response;

/**
 * Class UsersController
 *
 * @package panlatent\craft\smslogin\controllers
 */
class UsersController extends Controller
{
    /**
     * @event LoginFailureEvent The event that is triggered when a failed login attempt was made
     */
    const EVENT_LOGIN_FAILURE = 'loginFailure';

    const INVALID_PHONE_NUMBER = 'invalidPhoneNumber';

    const UNREGISTER_PHONE_NUMBER = 'unregisterPhoneNumber';

    const REGISTER_FAILED = 'unregisterFailed';

    protected $allowAnonymous = [
        'login',
        'register',
    ];

    /**
     * @return Response|null
     * @throws ServiceUnavailableHttpException
     * @throws \craft\errors\UserNotFoundException
     */
    public function actionLogin(): ?Response
    {
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest()) {
            // Too easy.
            return $this->_handleSuccessfulLogin();
        }

        if (!$this->request->getIsPost()) {
            return null;
        }

        $phone = $this->request->getBodyParam('phone');
        $token = $this->request->getBodyParam('token');
        $code = $this->request->getBodyParam('code');
        $rememberMe = (bool)$this->request->getBodyParam('rememberMe');

        if (!(new PhoneNumberValidator())->validate($phone)) {
            return $this->_handleLoginFailure(self::INVALID_PHONE_NUMBER);
        }

        // Validate phone and code
        $err = SmsLogin::$plugin->getSms()->testCaptcha($phone, $token, $code);
        if ($err !== '') {
            return $this->_handleLoginFailure($err);
        }

        // Check phone number unregister.
        $user = SmsLogin::$plugin->getUsers()->getUserByPhone($phone);
        if (!$user) {
            if (SmsLogin::$plugin->getSettings()->allowImmediateRegisterOnLogin) {
                $user = new User();
                if (!$this->_registerByPhone($user, $phone)) {
                    var_dump($user->getErrors());
                    return $this->_handleLoginFailure(self::REGISTER_FAILED, $user);
                }
            } else {
                if (SmsLogin::$plugin->getSettings()->allowReserveVerifiedLogin) {
                    return $this->_handleReserveVerifiedLogin($phone);
                }
                return $this->_handleLoginFailure(self::UNREGISTER_PHONE_NUMBER);
            }
        }

        // Get the session duration
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        // Try logging them in
        if (!$userSession->login($user, $duration)) {
            // Unknown error
            return $this->_handleLoginFailure(null, $user);
        }

        return $this->_handleSuccessfulLogin();
    }

    /**
     * @return Response|null
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $params = $this->request->getBodyParams();

        $settings = SmsLogin::$plugin->getSettings();
        $settings->phoneNumberFieldHandle = $params['phoneNumberFieldHandle'];
        $settings->usePhoneNumberAsUsername = (bool)$params['usePhoneNumberAsUsername'];
        $settings->allowImmediateRegisterOnLogin = (bool)$params['allowImmediateRegisterOnLogin'];
        $settings->unregisterReturnUrl = $params['unregisterReturnUrl'];
        $settings->defaultRegisterEmail = $params['defaultRegisterEmail'];
        $settings->registerUserGroup = $params['registerUserGroup'];

        if (!empty($settings->registerUserGroup) &&!Craft::$app->getUserGroups()->getGroupByUid($settings->registerUserGroup)) {
            Craft::$app->getSession()->setError(Craft::t('smslogin', 'Couldn’t save settings.'));
            return null;
        }

        if (!Craft::$app->getPlugins()->savePluginSettings(SmsLogin::$plugin, $settings->toArray())) {
            Craft::$app->getSession()->setError(Craft::t('smslogin', 'Couldn’t save settings.'));
            return null;
        }
        Craft::$app->getSession()->setNotice(Craft::t('smslogin', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    // Private Methods
    // =========================================================================

    /**
     * Handles a failed login attempt.
     *
     * @param string|null $authError
     * @param User|null $user
     * @return Response|null
     * @throws ServiceUnavailableHttpException
     */
    private function _handleLoginFailure(string $authError = null, User $user = null): ?Response
    {
        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        $message = $this->_getErrorMessage($authError);

        // Fire a 'loginFailure' event
        $event = new LoginFailureEvent([
            'authError' => $authError,
            'message' => $message,
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_LOGIN_FAILURE, $event);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'errorCode' => $authError,
                'error' => $event->message,
            ]);
        }

        $this->setFailFlash($event->message);

        Craft::$app->getUrlManager()->setRouteParams([
            'phone' => $this->request->getBodyParam('phone'),
            'token' => $this->request->getBodyParam('token'),
            'rememberMe' => (bool)$this->request->getBodyParam('rememberMe'),
            'errorCode' => $authError,
            'errorMessage' => $event->message,
        ]);

        return null;
    }

    /**
     * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
     * logged in.
     *
     * @return Response
     */
    private function _handleSuccessfulLogin(): Response
    {
        // Get the return URL
        $userSession = Craft::$app->getUser();
        $returnUrl = $userSession->getReturnUrl();

        // Clear it out
        $userSession->removeReturnUrl();

        // If this was an Ajax request, just return success:true
        if ($this->request->getAcceptsJson()) {
            $return = [
                'success' => true,
                'returnUrl' => $returnUrl,
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }

            return $this->asJson($return);
        }

        return $this->redirectToPostedUrl($userSession->getIdentity(), $returnUrl);
    }

    private function _handleReserveVerifiedLogin(string $phone): Response
    {
        SessionHelper::set(SmsLogin::$plugin->getSettings()->reserveVerifiedLoginSessionParam, [
            'phone' => $phone,
            'timestamp' => time(),
        ]);

        $route = SmsLogin::$plugin->getSettings()->unregisterReturnUrl;

        if ($this->request->getAcceptsJson()) {
            $return = [
                'success' => true,
                'returnUrl' => UrlHelper::siteUrl($route)
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }

            return $this->asJson($return);
        }

        return $this->redirect(UrlHelper::siteUrl($route));
    }

    /**
     * @param User $user
     * @param string $phone
     * @return bool
     * @throws UserException
     */
    private function _registerByPhone(User $user, string $phone): bool
    {
        $user->email = $phone . '@' . SmsLogin::$plugin->getSettings()->getDefaultRegisterEmailDomain();
        $user->username = $phone;
        $user->setScenario(User::SCENARIO_REGISTRATION);
        if (!SmsLogin::$plugin->getUsers()->canBindPhone($phone)) {
            throw new UserException("{$phone} already exists");
        }
        if (!SmsLogin::$plugin->getUsers()->bindPhone($user, $phone, false)) {
            return false;
        }
        if (!Craft::$app->getElements()->saveElement($user)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $errorCode
     * @return string
     */
    private function _getErrorMessage(string $errorCode): string
    {
        switch ($errorCode) {
            case self::INVALID_PHONE_NUMBER:
                return Craft::t('smslogin', 'Invalid phone number');
            case self::UNREGISTER_PHONE_NUMBER:
                return Craft::t('smslogin', 'Unregister phone number');
            case Sms::CAPTCHA_NO_EXISTS:
                return Craft::t('smslogin', 'Captcha no exists');
            case Sms::CAPTCHA_EXPIRED:
                return Craft::t('smslogin', 'Captcha expired');
            case sms::CAPTCHA_NOT_MATCH:
                return Craft::t('smslogin', 'Captcha does not match');
            case self::REGISTER_FAILED:
                return Craft::t('smslogin', 'Register failed');
            default:
                return Craft::t('smslogin', 'Invalid phone or captcha');
        }
    }
}