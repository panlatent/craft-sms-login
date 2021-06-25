<?php

namespace panlatent\craft\smslogin\helpers;

use Craft;
use craft\helpers\StringHelper;

/**
 * Class Html
 *
 * @package panlatent\craft\smslogin\helpers
 */
class Html extends \craft\helpers\Html
{
    /**
     * @param string $action
     * @param array $options
     * @return string
     */
    public static function tokenInput(string $action, array $options = []): string
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();
        $route = Craft::$app->getRequest()->getFullPath();

        $token = '';
        if ($request->getIsPost() && $session->has('__SMSLOGIN_TOKEN_SESS')) {
            $value = $session->get('__SMSLOGIN_TOKEN_SESS');
            if ($value['action'] === $action && $value['route'] === $route) {
                $token = $value['token'];
            }
        }

        if ($token === '') {
            $salt = StringHelper::randomString(8);
            $token = md5($route . $salt . $action);
        }

        $session->set('__SMSLOGIN_TOKEN_SESS', [
            'token' => $token,
            'action' => $action,
            'route' => $route,
        ]);

        return static::hiddenInput('token', $token, $options);
    }
}