import React, { useEffect, useState } from 'react'

export default function CartWidget({ initialCount = 0 }) {
  const [count, setCount] = useState(initialCount)

  useEffect(() => {
    const id = setInterval(async () => {
      try {
        const r = await fetch('/api/cart.php?action=get', { cache: 'no-store' })
        if (!r.ok) return
        const data = await r.json()
        if (data && typeof data.count !== 'undefined') setCount(data.count)
      } catch (e) {}
    }, 15000)
    return () => clearInterval(id)
  }, [])

  return (
    <div className="react-cart-widget">
      <a href="/cart.php" aria-label={`Cart with ${count} items`}>
        <span>Cart</span>
        <span className="count">{count}</span>
      </a>
    </div>
  )
}
