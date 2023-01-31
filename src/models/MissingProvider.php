<?php
namespace verbb\sociallogin\models;

use verbb\sociallogin\base\Provider;

use Craft;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

class MissingProvider extends Provider implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('social-login', 'Missing Provider');
    }


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return '';
    }
}
