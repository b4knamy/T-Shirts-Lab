import { useEffect, useState, useCallback } from 'react';
import { Ticket, Clock, X, ChevronLeft, ChevronRight } from 'lucide-react';
import { couponsApi } from '../../services/api/coupons';
import type { Coupon } from '../../types';

function timeLeft(expiresAt: string): string {
  const diff = new Date(expiresAt).getTime() - Date.now();
  if (diff <= 0) return 'Expired';
  const h = Math.floor(diff / 3_600_000);
  const m = Math.floor((diff % 3_600_000) / 60_000);
  if (h > 48) {
    const d = Math.floor(h / 24);
    return `${d}d left`;
  }
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`;
}

function discountLabel(c: Coupon) {
  return c.type === 'PERCENTAGE' ? `${c.value}% OFF` : `R$ ${Number(c.value).toFixed(2)} OFF`;
}

export function PromoBanner() {
  const [promos, setPromos] = useState<Coupon[]>([]);
  const [idx, setIdx] = useState(0);
  const [visible, setVisible] = useState(true);
  const [, tick] = useState(0);

  useEffect(() => {
    couponsApi.getActivePromos()
      .then((r) => setPromos(r.data.data || []))
      .catch(() => {});
  }, []);

  // auto-rotate every 6s
  useEffect(() => {
    if (promos.length <= 1) return;
    const id = setInterval(() => setIdx((i) => (i + 1) % promos.length), 6000);
    return () => clearInterval(id);
  }, [promos.length]);

  // countdown ticker every 30s
  useEffect(() => {
    const id = setInterval(() => tick((t) => t + 1), 30_000);
    return () => clearInterval(id);
  }, []);

  const prev = useCallback(() => setIdx((i) => (i - 1 + promos.length) % promos.length), [promos.length]);
  const next = useCallback(() => setIdx((i) => (i + 1) % promos.length), [promos.length]);

  if (!visible || promos.length === 0) return null;

  const current = promos[idx];

  return (
    <div className="relative bg-gradient-to-r from-accent via-rose-500 to-pink-500 text-white overflow-hidden">
      {/* Subtle shimmer */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute inset-0 bg-[linear-gradient(110deg,transparent_25%,rgba(255,255,255,.3)_50%,transparent_75%)] animate-[shimmer_3s_infinite]" style={{ backgroundSize: '200% 100%' }} />
      </div>

      <div className="relative max-w-7xl mx-auto px-4 py-2.5 flex items-center justify-center gap-3 text-sm">
        {promos.length > 1 && (
          <button onClick={prev} className="p-0.5 rounded hover:bg-white/20 transition-colors flex-shrink-0">
            <ChevronLeft className="w-4 h-4" />
          </button>
        )}

        <div className="flex items-center gap-2 min-w-0">
          <Ticket className="w-4 h-4 flex-shrink-0" />
          <span className="font-bold tracking-wide">{discountLabel(current)}</span>
          <span className="hidden sm:inline">—</span>
          <span className="hidden sm:inline truncate">{current.description || `Use code ${current.code}`}</span>
          <span className="font-mono bg-white/20 px-2 py-0.5 rounded text-xs font-bold tracking-widest flex-shrink-0">{current.code}</span>
          {current.expires_at && (
            <span className="hidden md:inline-flex items-center gap-1 text-xs text-white/80 flex-shrink-0">
              <Clock className="w-3 h-3" />{timeLeft(current.expires_at)}
            </span>
          )}
        </div>

        {promos.length > 1 && (
          <button onClick={next} className="p-0.5 rounded hover:bg-white/20 transition-colors flex-shrink-0">
            <ChevronRight className="w-4 h-4" />
          </button>
        )}

        <button onClick={() => setVisible(false)} className="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-white/20 transition-colors">
          <X className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Dots indicator */}
      {promos.length > 1 && (
        <div className="flex justify-center gap-1 pb-1.5">
          {promos.map((_, i) => (
            <button key={i} onClick={() => setIdx(i)} className={`w-1.5 h-1.5 rounded-full transition-colors ${i === idx ? 'bg-white' : 'bg-white/40'}`} />
          ))}
        </div>
      )}
    </div>
  );
}
