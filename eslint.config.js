import js from '@eslint/js';
import globals from 'globals';

/*
 * This language has neither typing nor compilation here, so the linter is one
 * of the two nets that replace the static analysis the backend enjoys: a
 * variable kept without being used, a comparison that converts behind your
 * back, a declaration whose scope spills over, a promise nobody awaits.
 *
 * The same rules as the suite's packages, for the same reason: a rule should
 * not be looser on one side of the border than on the other.
 */
const rules = {
    'no-unused-vars': ['error', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',

        // A `catch` whose error is deliberately dropped says so by naming it
        // `_e`, the same convention as the two above.
        caughtErrorsIgnorePattern: '^_',
    }],
    eqeqeq: ['error', 'always'],
    'no-var': 'error',
    'prefer-const': 'error',
    'no-shadow': 'error',
    'require-await': 'error',
    'no-promise-executor-return': 'error',
};

export default [
    {
        /*
         * `public/` is produced by vite, and `public/vendor/` is published by
         * the suite's packages: linting either would judge compiler output,
         * which says nothing about what anyone wrote here.
         */
        ignores: ['public/**', 'node_modules/**', 'vendor/**', 'packages/**', 'storage/**'],
    },

    js.configs.recommended,

    {
        /* What this application sends to the browser. */
        files: ['resources/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,

                /*
                 * Brought by the suite and by the reactive layer, never
                 * imported here: without these declarations every use would
                 * come back as an unknown symbol and drown the report.
                 */
                Alpine: 'readonly',
                Livewire: 'readonly',
            },
        },
        rules,
    },

    {
        /* The build chain, which runs under node. */
        files: ['vite.config.js', 'eslint.config.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.node,
        },
        rules,
    },
];
