import js from '@eslint/js'
import vue from 'eslint-plugin-vue'

export default [
  js.configs.recommended,

  // Keep Vue template rules available for editor tooling. CI runs the JS
  // subset here; Vue/TypeScript SFCs are covered by vue-tsc until the
  // TypeScript ESLint parser/plugin are added as locked workspace deps.
  ...vue.configs['flat/recommended'],

  {
    files: ['**/*.{js,jsx}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        navigator: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        Laravel: 'readonly',
        route: 'readonly',
        Echo: 'readonly',
        Pusher: 'readonly',
        process: 'readonly',
        __dirname: 'readonly',
        require: 'readonly',
        module: 'readonly',
        exports: 'readonly'
      }
    },
    rules: {
      'no-unused-vars': 'off',
      'no-console': 'warn',
      'no-debugger': 'error',
      'prefer-const': 'error',
      'no-var': 'error',
      'object-shorthand': 'error',
      'prefer-template': 'error',
      'template-curly-spacing': ['error', 'never'],
      'arrow-spacing': 'error',
      'comma-dangle': ['error', 'never'],
      'comma-spacing': 'error',
      'comma-style': 'error',
      'key-spacing': 'error',
      'keyword-spacing': 'error',
      'object-curly-spacing': ['error', 'always'],
      'space-before-blocks': 'error',
      'space-in-parens': 'error',
      'space-infix-ops': 'error',
      quotes: ['error', 'single', { allowTemplateLiterals: true }],
      semi: ['error', 'never'],
      indent: ['error', 2, { SwitchCase: 1 }],
      'linebreak-style': ['error', 'unix'],
      'eol-last': 'error',
      'no-trailing-spaces': 'error'
    }
  },

  {
    ignores: [
      'node_modules/**',
      'vendor/**',
      'public/**',
      'storage/**',
      'bootstrap/**',
      '.git/**',
      'dist/**',
      'build/**'
    ]
  }
]
