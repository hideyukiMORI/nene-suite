import path from 'node:path'
import { fileURLToPath } from 'node:url'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

const dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ mode }) => {
  // Local dev port 5188 — fixed in CLAUDE.md portfolio port registry.
  // API proxy target reads SUITE_HTTP_PORT (default 8800, also fixed in CLAUDE.md).
  const projectEnv = loadEnv(mode, path.resolve(dirname, '..'), '')
  const appPort = projectEnv['SUITE_HTTP_PORT'] ?? '8800'
  const target = `http://localhost:${appPort}`

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(dirname, './src'),
        '@tests': path.resolve(dirname, './tests'),
      },
    },
    server: {
      port: 5188,
      proxy: {
        '/api': { target, changeOrigin: true },
        '/health': { target, changeOrigin: true },
      },
    },
  }
})
