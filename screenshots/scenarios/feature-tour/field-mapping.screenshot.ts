import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedSocialLoginFixture } from '../../support/fixtures';

export default defineScreenshotScenario({
    id: 'social-login-feature-tour-field-mapping',
    output: 'feature-tour/social-login-field-mapping.png',
    route: '/admin/social-login/settings/providers/edit/facebook#settings-mapping',
    viewport: { width: 1440, height: 900, deviceScaleFactor: 2 },
    setup: seedSocialLoginFixture,
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '#settings-mapping:not(.hidden)', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'User Field Mapping' },
        { type: 'text', text: 'Last Name' },
    ],
    steps: [
        { type: 'evaluate', expression: 'document.activeElement?.blur(); window.scrollTo(0, 0)' },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#main',
        x: 24,
        y: 58,
        width: 792,
        height: 430,
    },
    caption: 'Social Login mapping provider profile values to Craft user attributes in the real Craft 5 settings screen.',
    intent: 'Show how registration data and profile photos can be matched and mapped without implying a remote account connection.',
});
