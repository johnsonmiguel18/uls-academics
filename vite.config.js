import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import JavaScriptObfuscator from 'javascript-obfuscator';

// Production builds ("npm run build") obfuscate the site's own scripts (resources/js). This only makes
// the code harder to read: it is still sent to, and runs in, every visitor's browser, so it must never
// hold secrets. Chunks with code from node_modules are left alone. The development server ("npm run dev")
// is not affected, and OBFUSCATE=false npm run build gives a readable build for debugging.
const obfuscatorOptions = {
    compact: true,
    identifierNamesGenerator: 'hexadecimal',
    renameGlobals: false,
    stringArray: true,
    stringArrayEncoding: ['base64'],
    stringArrayThreshold: 0.75,
    // Off on purpose: they slow pages down or break debugging.
    controlFlowFlattening: false,
    deadCodeInjection: false,
    selfDefending: false,
    debugProtection: false,
    disableConsoleOutput: false,
    sourceMap: false,
    target: 'browser',
    seed: 1, // the same source always gives the same output (stable file names, reproducible builds)
};

// Runs last (after Vite's minifier), so the obfuscator's output is what ships. The file name's hash comes from the
// code before this step; with the fixed seed the same source always gives the same file, and a readable debug build
// (same name) behaves the same.
const obfuscateOwnScripts = {
    name: 'obfuscate-own-scripts',
    apply: 'build',
    generateBundle(options, bundle) {
        if (process.env.OBFUSCATE === 'false') {
            return;
        }

        for (const chunk of Object.values(bundle)) {
            const ownCode = chunk.type === 'chunk' && chunk.moduleIds.length > 0
                && chunk.moduleIds.every((id) => id.includes('/resources/js/') && ! id.includes('/node_modules/'));

            if (ownCode) {
                chunk.code = JavaScriptObfuscator.obfuscate(chunk.code, obfuscatorOptions).getObfuscatedCode();
            }
        }
    },
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/site.js'],
            refresh: true,
        }),
        tailwindcss(),
        obfuscateOwnScripts,
    ],
    build: {
        sourcemap: false, // no source maps in production
    },
});
