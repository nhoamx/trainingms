import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';

export default defineConfig({
    server: {
        https: {
            key: fs.readFileSync('C:\\Users\\alfredo\\.config\\herd\\config\\valet\\Certificates\\trainingms.test.key'),
            cert: fs.readFileSync('C:\\Users\\alfredo\\.config\\herd\\config\\valet\\Certificates\\trainingms.test.crt'),
        },
        cors: {
            origin: 'https://trainingms.test',
            credentials: true,
        }
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
});