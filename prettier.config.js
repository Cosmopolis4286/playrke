export default {
  // Print width
  printWidth: 100,
  
  // Tab settings
  tabWidth: 2,
  useTabs: false,
  
  // Semicolons
  semi: false,
  
  // Quotes
  singleQuote: true,
  quoteProps: 'as-needed',
  
  // JSX
  jsxSingleQuote: true,
  
  // Trailing commas
  trailingComma: 'none',
  
  // Brackets
  bracketSpacing: true,
  bracketSameLine: false,
  
  // Arrows
  arrowParens: 'avoid',
  
  // Range
  rangeStart: 0,
  rangeEnd: Infinity,
  
  // Parser
  requirePragma: false,
  insertPragma: false,
  
  // Prose wrap
  proseWrap: 'preserve',
  
  // HTML
  htmlWhitespaceSensitivity: 'css',
  
  // Vue
  vueIndentScriptAndStyle: false,
  
  // End of line
  endOfLine: 'lf',
  
  // Embedded language formatting
  embeddedLanguageFormatting: 'auto',
  
  // Single attribute per line
  singleAttributePerLine: false,
  
  // Plugin configuration
  plugins: ['prettier-plugin-tailwindcss'],
  
  // Overrides for specific file types
  overrides: [
    {
      files: '*.vue',
      options: {
        parser: 'vue',
        vueIndentScriptAndStyle: true
      }
    },
    {
      files: '*.json',
      options: {
        tabWidth: 2,
        semi: false,
        trailingComma: 'none'
      }
    },
    {
      files: '*.md',
      options: {
        proseWrap: 'always',
        printWidth: 80
      }
    },
    {
      files: '*.css',
      options: {
        singleQuote: false
      }
    }
  ]
}