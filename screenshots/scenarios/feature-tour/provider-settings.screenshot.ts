import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedSocialLoginFixture } from '../../support/fixtures';

export default defineScreenshotScenario({
    id: 'social-login-feature-tour-provider-settings',
    output: 'feature-tour/social-login-provider-settings.png',
    route: '/admin/social-login/settings/providers/edit/facebook#settings-general',
    viewport: { width: 1440, height: 900, deviceScaleFactor: 2 },
    setup: seedSocialLoginFixture,
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '#settings-general:not(.hidden)', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Redirect URI' },
        { type: 'text', text: 'Client Secret' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                document.activeElement?.blur();

                const redirectLabel = [...document.querySelectorAll('#settings-general label')]
                    .find((element) => element.textContent?.trim() === 'Redirect URI');
                const redirectInput = redirectLabel?.closest('.field')?.querySelector('input');

                if (redirectInput instanceof HTMLInputElement) {
                    redirectInput.value = 'https://example.com/social-login/auth/callback';
                }

                document
                    .querySelectorAll('#settings-general [id$="-tip"], #settings-general [id$="-warning"]')
                    .forEach((element) => element.style.display = 'none');

                window.scrollTo(0, 0);
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#main',
        x: 24,
        y: 58,
        width: 792,
        height: 450,
    },
    caption: 'Facebook configured through Social Login’s genuine provider settings in Craft 5.',
    intent: 'Show the real per-provider setup, including the callback URI and application credentials.',
});
