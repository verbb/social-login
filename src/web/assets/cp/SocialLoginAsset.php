<?php
namespace verbb\sociallogin\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

class SocialLoginAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/sociallogin/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
        ];

        $this->js = [
            'social-login.js',
        ];

        $this->css = [
            'social-login.css',
        ];

        parent::init();
    }
}
