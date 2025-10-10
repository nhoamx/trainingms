import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import path from 'path';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const isLocal = env.APP_ENV === 'local';

    const serverConfig = isLocal ? {
        https: {
            key: fs.readFileSync('C:\\Users\\alfredo\\.config\\herd\\config\\valet\\Certificates\\trainingms.test.key'),
            cert: fs.readFileSync('C:\\Users\\alfredo\\.config\\herd\\config\\valet\\Certificates\\trainingms.test.crt'),
        },
        cors: {
            origin: 'https://trainingms.test',
            credentials: true,
        }
    } : {};

    return {
        server: serverConfig,
        resolve: {
            alias: {
                '@': path.resolve(__dirname, './resources/js'),
            },
        },
        plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                }
            }
        })
        ],
    };
});
