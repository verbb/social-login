import { registerPluginBootstrap } from '@verbb/craft-screenshots/api';

export default registerPluginBootstrap({
    id: 'social-login',
    async setup(context) {
        await context.runCraft(['migrate/up', '--plugin=social-login'], { allowFailure: true });
    },
});
