import {defineConfig} from 'vite'

// https://vitejs.dev/config/
export default defineConfig(({command}) => {
  return {
    server: {
      host: '0.0.0.0',
      port: 3000,
    },
    alias: {
      alias: [{find: '@', replacement: './app/client/src'}],
    },
    base: './',
    build: {
      outDir: './app/client/dist',
      manifest: true,
      sourcemap: true,
      rollupOptions: {
        input: {
          'main.js': './app/client/src/js/main.js',
          'main.scss': './app/client/src/scss/main.scss',
          'editor.scss': './app/client/src/scss/editor.scss',
        }
      },
    },
    plugins: []
  }
})
