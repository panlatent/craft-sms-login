<?php

namespace panlatent\craft\smslogin\web\twig;

use craft\helpers\Template;
use panlatent\craft\smslogin\helpers\Html;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Class Extension
 *
 * @package panlatent\craft\smslogin\web\twig
 */
class Extension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getGlobals()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
//            new TwigFilter('article', [$this, 'articleFilter']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('smsloginTokenInput', [$this, 'smsloginTokenInput']),
        ];
    }

    /**
     * @param string $action
     * @param array $options
     * @return string
     */
    public function smsloginTokenInput(string $action = 'default', array $options = []): Markup
    {
        return Template::raw(Html::tokenInput($action, $options));
    }
}