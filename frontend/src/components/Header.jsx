import React, { useEffect, useState } from 'react'

export default function Header({ initialCount = 0 }) {
  const [count, setCount] = useState(initialCount)

  useEffect(() => {
    // keep server-rendered count element in sync
    const serverEl = document.getElementById('cartCount')
    if (serverEl) serverEl.textContent = count
  }, [count])

  useEffect(() => {
    // poll cart count occasionally to stay in sync
    let mounted = true
    async function fetchCount() {
      try {
        const r = await fetch('/api/cart.php?action=get', { cache: 'no-store' })
        if (!r.ok) return
        const data = await r.json()
        if (mounted && data && typeof data.count !== 'undefined') {
          setCount(data.count)
        }
      } catch (e) {
        // ignore
      }
    }
    fetchCount()
    const id = setInterval(fetchCount, 15000)
    return () => { mounted = false; clearInterval(id) }
  }, [])

  return (
    <div className="react-header-widget" aria-hidden="true">
      <a href="/cart.php" className="react-cart-link" aria-label={`Cart with ${count} items`}>
        <span className="react-cart-icon">🛒</span>
        <span className="react-cart-count">{count}</span>
      </a>
    </div>
  )
}
