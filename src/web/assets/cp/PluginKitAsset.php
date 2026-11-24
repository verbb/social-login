<?php
namespace verbb\sociallogin\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

/**
 * Plugin Kit's state panel, for the pages that show it.
 */
class PluginKitAsset extends AssetBundle
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
            'plugin-kit.js',
        ];

        $this->css = [
            'plugin-kit.css',
        ];

        parent::init();
    }
}
