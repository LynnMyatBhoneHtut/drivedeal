import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import prettier from 'eslint-config-prettier'
import globals from 'globals'

export default [
  { ignores: ['dist/', 'node_modules/', 'api/', 'uploads/'] },
  js.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  prettier, // let Prettier handle formatting, ESLint handles mistakes
  {
    languageOptions: { globals: { ...globals.browser } },
    rules: {
      // Our plan names components Navbar, Footer, Pagination - single words are fine
      'vue/multi-word-component-names': 'off'
    }
  }
]
