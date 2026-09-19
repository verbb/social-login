import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedSocialLoginFixture } from '../../support/fixtures';

export default defineScreenshotScenario({
    id: 'social-login-feature-tour-cp-login',
    output: 'feature-tour/social-login-cp-login.png',
    route: '/admin/logout',
    viewport: { width: 1180, height: 920, deviceScaleFactor: 2 },
    async setup(context) {
        await seedSocialLoginFixture(context);
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.login-form-container', state: 'visible', timeout: 30000 },
        { type: 'selector', selector: '.social-login-cp-container .ss-provider', state: 'visible', timeout: 30000 },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                const username = document.querySelector('input[name="username"]');

                if (username instanceof HTMLInputElement) {
                    username.value = '';
                    username.dispatchEvent(new Event('input', { bubbles: true }));
                    username.dispatchEvent(new Event('change', { bubbles: true }));
                }

                document.activeElement?.blur();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'selector',
        selector: '.login-container',
        padding: { top: 50, right: 50, bottom: 50, left: 50 },
    },
    caption: 'Social Login providers offered on Craft’s genuine control-panel login screen.',
    intent: 'Show a representative range of real provider icons integrated into the current Craft 5 login form.',
});
