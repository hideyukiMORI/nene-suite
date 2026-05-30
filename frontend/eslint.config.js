import path from 'node:path'
import { fileURLToPath } from 'node:url'
import js from '@eslint/js'
import eslintConfigPrettier from 'eslint-config-prettier'
import importPlugin from 'eslint-plugin-import'
import jsxA11y from 'eslint-plugin-jsx-a11y'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import globals from 'globals'
import tseslint from 'typescript-eslint'

const dirname = path.dirname(fileURLToPath(import.meta.url))

// Entity internals are private — features/pages must import via entities/*/index.ts only.
const entityInternalFiles = [
  './src/entities/*/api-types.ts',
  './src/entities/*/mapper.ts',
  './src/entities/*/queries.ts',
  './src/entities/*/mutations.ts',
  './src/entities/*/query-keys.ts',
  './src/entities/*/ids.ts',
  './src/entities/*/model.ts',
  './src/entities/*/enum.ts',
]

const importZones = [
  // Features and pages consume entities through their barrel; never raw HTTP/DTOs.
  { target: './src/features', from: entityInternalFiles },
  { target: './src/features', from: './src/shared/api' },
  { target: './src/pages', from: entityInternalFiles },
  { target: './src/pages', from: './src/shared/api' },
  // Shared UI primitives stay presentational.
  { target: './src/shared/ui', from: './src/entities' },
  { target: './src/shared/ui', from: './src/features' },
  { target: './src/shared/ui', from: './src/shared/api' },
]

export default tseslint.config(
  {
    ignores: ['dist', 'node_modules', 'coverage', 'src/shared/api/schema.gen.ts'],
  },
  {
    extends: [js.configs.recommended, ...tseslint.configs.strictTypeChecked],
    files: ['src/**/*.{ts,tsx}', 'tests/**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.browser,
      parserOptions: {
        project: ['./tsconfig.app.json'],
        tsconfigRootDir: dirname,
      },
    },
    plugins: {
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
      'jsx-a11y': jsxA11y,
      import: importPlugin,
    },
    settings: {
      'import/resolver': {
        typescript: {
          project: './tsconfig.app.json',
        },
      },
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
      ...jsxA11y.configs.recommended.rules,
      'import/no-restricted-paths': ['error', { zones: importZones }],
    },
  },
  {
    files: ['**/*.test.ts', '**/*.test.tsx'],
    rules: {
      '@typescript-eslint/no-unsafe-call': 'off',
      '@typescript-eslint/no-unsafe-member-access': 'off',
      '@typescript-eslint/no-unsafe-argument': 'off',
      '@typescript-eslint/no-unsafe-assignment': 'off',
    },
  },
  {
    // Test harness/helpers are not HMR boundaries.
    files: ['tests/**/*.{ts,tsx}'],
    rules: {
      'react-refresh/only-export-components': 'off',
    },
  },
  {
    files: ['vite.config.ts', 'vitest.config.ts', 'eslint.config.js'],
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.node,
    },
  },
  eslintConfigPrettier,
)
