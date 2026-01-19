import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import path from 'path';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const isLocal = env.APP_ENV === 'local';

    // Local HTTPS certificates (optional, only for specific dev environment)
    // These paths are checked for existence before use, won't cause errors if missing
    const certKeyPath = 'C:\\Users\\alfredo\\.config\\herd\\config\\valet\\Certificates\\trainingms.test.key';
    const certPath = 'C:\\Users\\alfredo\\.config\\herd\\config\\valet\\Certificates\\trainingms.test.crt';
    const certsExist = fs.existsSync(certKeyPath) && fs.existsSync(certPath);

    const serverConfig = isLocal && certsExist ?  {
        https: {
            key: fs.readFileSync(certKeyPath),
            cert: fs.readFileSync(certPath),
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
