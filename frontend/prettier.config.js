/**
 * Configuración de Prettier para el frontend PlayRKE.
 * Mantiene consistencia con las reglas del proyecto principal.
 */

export default {
  // Configuración base
  semi: false,
  singleQuote: true,
  quoteProps: 'as-needed',
  trailingComma: 'none',
  bracketSpacing: true,
  bracketSameLine: false,
  arrowParens: 'avoid',

  // Configuración de ancho
  printWidth: 100,
  tabWidth: 2,
  useTabs: false,

  // Configuración de saltos de línea
  endOfLine: 'lf',
  proseWrap: 'preserve',

  // Configuración específica para Vue
  vueIndentScriptAndStyle: true,

  // Configuración específica para HTML
  htmlWhitespaceSensitivity: 'css',

  // Plugins
  plugins: ['prettier-plugin-tailwindcss'],

  // Configuración específica por tipo de archivo
  overrides: [
    {
      files: '*.vue',
      options: {
        parser: 'vue'
      }
    },
    {
      files: ['*.ts', '*.tsx'],
      options: {
        parser: 'typescript'
      }
    },
    {
      files: '*.json',
      options: {
        parser: 'json',
        trailingComma: 'none'
      }
    },
    {
      files: '*.md',
      options: {
        parser: 'markdown',
        proseWrap: 'always',
        printWidth: 80
      }
    },
    {
      files: '*.scss',
      options: {
        parser: 'scss'
      }
    }
  ]
}