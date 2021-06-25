<?php

namespace panlatent\craft\smslogin\web\twig;

use panlatent\craft\smslogin\Plugin as SmsLogin;
use yii\base\Behavior;

/**
 * Class CraftVariableBehavior
 *
 * @package panlatent\craft\smslogin\web\twig
 */
class CraftVariableBehavior extends Behavior
{
    // Properties
    // =========================================================================

    /**
     * @var SmsLogin|null
     */
    public $smslogin;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->smslogin = SmsLogin::$plugin;
    }
}