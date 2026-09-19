import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedSocialLoginFixture } from '../../support/fixtures';

export default defineScreenshotScenario({
    id: 'social-login-feature-tour-providers',
    output: 'feature-tour/social-login-providers.png',
    route: '/admin/social-login/settings/providers',
    viewport: { width: 1440, height: 980, deviceScaleFactor: 2 },
    setup: seedSocialLoginFixture,
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '[id$="providers-vue-admin-table"] table', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Eventbrite' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                document.activeElement?.blur();

                const rows = document.querySelectorAll('[id$="providers-vue-admin-table"] tbody tr');
                rows.forEach((row, index) => {
                    if (index >= 11) {
                        row.style.display = 'none';
                    }
                });

                window.scrollTo(0, 0);
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '[id$="providers-vue-admin-table"] table',
        x: -2,
        y: -2,
        width: 964,
        height: 473,
    },
    caption: 'Social Login’s real Craft 5 provider index showing a representative mix of configured and available identity providers.',
    intent: 'Show the breadth of built-in provider support with genuine icons, handles and configuration states.',
});
