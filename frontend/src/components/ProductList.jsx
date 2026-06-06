import React from 'react'

export default function ProductList({ initialCount = 0 }) {
  // Placeholder: progressive enhancement will replace server list when enabled
  return (
    <div className="react-product-list" aria-hidden="true">
      <p>{initialCount} products available</p>
    </div>
  )
}
