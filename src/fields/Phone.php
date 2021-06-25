<?php

namespace panlatent\craft\smslogin\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\helpers\Html;

/**
 * Class Phone
 *
 * @package panlatent\craft\smslogin\fields
 */
class Phone extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('smslogin', 'Phone Number');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return 'string|null';
    }

    public $searchable = true;

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('smslogin/_components/fieldtypes/Phone/input', [
            'id' => Html::id($this->handle),
            'field' => $this,
            'value' => $value,
        ]);
    }
}