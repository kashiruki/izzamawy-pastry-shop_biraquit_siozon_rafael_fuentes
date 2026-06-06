import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// Build outputs to ../js/react so PHP pages can include built files from /js/react
export default defineConfig({
  plugins: [react()],
  build: {
    outDir: '../js/react',
    emptyOutDir: true,
    rollupOptions: {
      input: '/frontend/index.html',
      output: {
        entryFileNames: 'app.js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name][extname]'
      }
    }
  },
  root: '.'
})
