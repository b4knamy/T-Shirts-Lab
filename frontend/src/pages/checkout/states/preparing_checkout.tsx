import { Loader2 } from 'lucide-react';

export function PreparingCheckoutState() {
  return (
    <div className="w-full max-w-3xl mx-auto px-6 py-24 text-center">
      <Loader2 className="w-10 h-10 animate-spin text-accent mx-auto mb-4" />
      <h1 className="text-2xl font-bold mb-2">Preparing checkout</h1>
      <p className="text-gray-500">Loading your selected items.</p>
    </div>
  );
}