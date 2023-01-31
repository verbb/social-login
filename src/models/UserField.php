<?php
namespace verbb\sociallogin\models;

use craft\base\Model;

class UserField extends Model
{
    // Constants
    // =========================================================================

    public const TYPE_STRING = 'string';
    public const TYPE_NUMBER = 'number';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATECLASS = 'dateclass';
    public const TYPE_ARRAY = 'array';
    public const TYPE_FILE_UPLOAD = 'fileUpload';


    // Properties
    // =========================================================================

    public ?string $handle = null;
    public ?string $name = null;
    public ?string $type = null;
    public ?string $required = null;
    public array $options = [];


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        if ($this->type) {
            return $this->type;
        }

        return self::TYPE_STRING;
    }

}
