import { writeFile } from 'node:fs/promises';
import { join } from 'node:path';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

/** Enable representative providers for the genuine Craft control-panel login screen. */
export async function seedSocialLoginFixture(context: ScreenshotSetupContext): Promise<void> {
    await writeFile(join(context.installDir, 'config/social-login.php'), `<?php
return [
    'enableCpLogin' => true,
    'enableRegistration' => true,
    'populateProfile' => true,
    'cpLoginTemplate' => '',
    'providers' => [
        'amazon' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'apple' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'auth0' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'bitbucket' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'buddy' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'dribbble' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'dropbox' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'envato' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'eventbrite' => ['enabled' => true, 'clientId' => 'demo-client', 'clientSecret' => 'demo-secret'],
        'facebook' => [
            'enabled' => true,
            'loginEnabled' => true,
            'cpLoginEnabled' => true,
            'clientId' => 'social-login-demo-client',
            'clientSecret' => 'social-login-demo-secret',
            'matchUserSource' => 'email',
            'matchUserDestination' => 'email',
            'fieldMapping' => [
                'username' => '',
                'email' => 'email',
                'fullName' => '',
                'firstName' => 'firstName',
                'lastName' => 'lastName',
                'photo' => 'pictureUrl',
            ],
        ],
        'gitHub' => ['enabled' => true, 'loginEnabled' => true, 'cpLoginEnabled' => true],
        'google' => ['enabled' => true, 'loginEnabled' => true, 'cpLoginEnabled' => true],
        'linkedIn' => ['enabled' => true, 'loginEnabled' => true, 'cpLoginEnabled' => true],
        'microsoft' => ['enabled' => true, 'loginEnabled' => true, 'cpLoginEnabled' => true],
        'twitter' => ['enabled' => true, 'loginEnabled' => true, 'cpLoginEnabled' => true],
        'discord' => ['enabled' => true, 'loginEnabled' => true, 'cpLoginEnabled' => true],
    ],
];
`);
}
