import React from 'react'
import Header from './components/Header'

export default function App({ mountEl }) {
  const initialCount = mountEl ? mountEl.dataset.cartCount || 0 : 0
  return (
    <Header initialCount={parseInt(initialCount, 10) || 0} />
  )
}
