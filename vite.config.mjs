import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');
const jsEntries = new Set(['js', 'plugin-kit']);

export default defineConfig(({ mode }) => {
    const isCssBuild = mode === 'css';
    const isPluginKitBuild = mode === 'plugin-kit';

    if (!isCssBuild && !jsEntries.has(mode)) {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    let input;
    let output;

    if (isCssBuild) {
        input = {
            'social-login': resolve(root, 'src/social-login.css'),
            'plugin-kit': resolve(root, 'src/plugin-kit.css'),
        };

        output = {
            assetFileNames: '[name][extname]',
        };
    } else {
        input = resolve(root, `src/${isPluginKitBuild ? 'plugin-kit' : 'social-login'}.js`);

        output = {
            assetFileNames: '[name][extname]',
            codeSplitting: false,
            entryFileNames: '[name].js',
            format: 'iife',
        };
    }

    return {
        root,
        input,
        build: {
            outDir: resolve(root, 'dist'),
            emptyOutDir: isCssBuild,
            assetsDir: '',
            cssMinify: 'esbuild',
            cssTarget: ['chrome61', 'safari10'],
            minify: 'oxc',
            sourcemap: !isCssBuild && !isPluginKitBuild,
            // Plugin Kit's web components need native class fields, so its bundle keeps Vite's modern default target.
            target: isPluginKitBuild ? undefined : 'es2015',
            rolldownOptions: {
                output,
            },
        },
    };
});
