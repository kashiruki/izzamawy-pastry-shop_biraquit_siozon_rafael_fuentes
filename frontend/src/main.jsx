import React from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import './styles.css'

const mountEl = document.getElementById('react-header-root')
if (mountEl) {
  const root = createRoot(mountEl)
  root.render(<App mountEl={mountEl} />)
}
