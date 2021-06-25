<?php

namespace panlatent\craft\smslogin;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\web\Application;
use craft\web\twig\variables\CraftVariable;
use panlatent\craft\smslogin\fields\Phone;
use panlatent\craft\smslogin\models\Settings;
use panlatent\craft\smslogin\web\twig\CraftVariableBehavior;
use panlatent\craft\smslogin\web\twig\Extension;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package panlatent\craft\smslogin
 * @property-read Settings $settings
 * @method Settings getSettings()
 */
class Plugin extends \craft\base\Plugin
{
    use Routes, Services, Users;

    // Properties
    // =========================================================================

    /**
     * @var static
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '0.1.0';

    /**
     * @inheritdoc
     */
    public $t9nCategory = 'smslogin';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        static::$plugin = $this;

        $this->_setComponents();
        $this->_registerFieldTypes();
        $this->_registerUserEvents();
        $this->_registerUrlRules();
        $this->_registerVariables();
        $this->_registerTwigExtension();
    }

    /**
     * @inheritdoc
     */
    public function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('smslogin/settings'));
    }

    // Private Methods
    // =========================================================================

    private function _registerFieldTypes()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Phone::class;
        });
    }

    /**
     * Register the plugin template variable.
     */
    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $e) {
            /** @var CraftVariable $variable */
            $variable = $e->sender;
            $variable->attachBehavior('smslogin', CraftVariableBehavior::class);
        });
    }

    /**
     * Register Twig extension.
     */
    private function _registerTwigExtension()
    {
        if (Craft::$app instanceof Application) {
            Craft::$app->getView()->registerTwigExtension(new Extension());
        }
    }
}