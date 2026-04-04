import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { CreditCard, Loader2, ArrowLeft, ShoppingBag, Tag, X, Check } from 'lucide-react';
import { useCart } from '../hooks/useCart';
import { ordersApi, paymentsApi, couponsApi } from '../services/api';
import type { Coupon } from '../types';

const checkoutSchema = z.object({
  shippingAddress: z.object({
    street: z.string().min(1, 'Street is required'),
    number: z.string().min(1, 'Number is required'),
    complement: z.string().optional(),
    neighborhood: z.string().min(1, 'Neighborhood is required'),
    city: z.string().min(1, 'City is required'),
    state: z.string().min(1, 'State is required'),
    zipCode: z.string().min(5, 'ZIP Code is required'),
    country: z.string().min(1, 'Country is required'),
  }),
  customerNotes: z.string().optional(),
});

type CheckoutFormData = z.infer<typeof checkoutSchema>;

export function CheckoutPage() {
  const navigate = useNavigate();
  const { items, total, clear } = useCart();
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Coupon state
  const [couponCode, setCouponCode] = useState('');
  const [couponLoading, setCouponLoading] = useState(false);
  const [couponError, setCouponError] = useState<string | null>(null);
  const [appliedCoupon, setAppliedCoupon] = useState<Coupon | null>(null);
  const [discountAmount, setDiscountAmount] = useState(0);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<CheckoutFormData>({
    resolver: zodResolver(checkoutSchema),
    defaultValues: {
      shippingAddress: { country: 'BR' },
    },
  });

  const handleApplyCoupon = async () => {
    if (!couponCode.trim()) return;
    setCouponLoading(true);
    setCouponError(null);
    try {
      const res = await couponsApi.validate(couponCode.trim(), total);
      setAppliedCoupon(res.data.data.coupon);
      setDiscountAmount(res.data.data.discount);
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setCouponError(e.response?.data?.message || 'Invalid coupon code');
      setAppliedCoupon(null);
      setDiscountAmount(0);
    } finally {
      setCouponLoading(false);
    }
  };

  const handleRemoveCoupon = () => {
    setAppliedCoupon(null);
    setDiscountAmount(0);
    setCouponCode('');
    setCouponError(null);
  };

  const shipping = total >= 50 ? 0 : 9.99;
  const finalTotal = Math.max(0, total - discountAmount + shipping);

  const onSubmit = async (data: CheckoutFormData) => {
    setIsProcessing(true);
    setError(null);

    try {
      // Create order
      const orderResponse = await ordersApi.create({
        items: items.map((item) => ({
          product_id: item.product.id,
          quantity: item.quantity,
          design_id: item.design_id,
        })),
        customer_notes: data.customerNotes,
        ...(appliedCoupon ? { coupon_code: appliedCoupon.code } : {}),
      });

      const order = orderResponse.data.data;

      // Create payment intent
      await paymentsApi.createIntent(order.id, 'brl');

      clear();
      navigate(`/orders/${order.id}`, { state: { justCreated: true } });
    } catch (err: unknown) {
      const e = err as { response?: { data?: { error?: { message?: string } } } };
      setError(e.response?.data?.error?.message || 'Failed to process order. Please try again.');
    } finally {
      setIsProcessing(false);
    }
  };

  if (items.length === 0) {
    return (
      <div className="w-full max-w-4xl mx-auto px-6 py-20 text-center">
        <ShoppingBag className="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h1 className="text-2xl font-bold mb-2">Your cart is empty</h1>
        <p className="text-gray-500 mb-4">Add some products before checking out.</p>
        <Link to="/products" className="inline-flex bg-accent text-white px-6 py-2 rounded-lg hover:bg-accent-light transition-colors">
          Browse Products
        </Link>
      </div>
    );
  }

  return (
    <div className="w-full max-w-6xl mx-auto px-6 py-10">
      <Link to="/products" className="inline-flex items-center gap-2 text-gray-500 hover:text-accent mb-8">
        <ArrowLeft className="w-4 h-4" /> Continue Shopping
      </Link>

      <h1 className="text-3xl font-bold mb-8">Checkout</h1>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Shipping Form */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
              <h2 className="font-semibold text-lg mb-4">Shipping Address</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium mb-1">Street</label>
                  <input
                    {...register('shippingAddress.street')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.street && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.street.message}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Number</label>
                  <input
                    {...register('shippingAddress.number')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.number && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.number.message}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Complement</label>
                  <input
                    {...register('shippingAddress.complement')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                    placeholder="Apt, suite, etc."
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Neighborhood</label>
                  <input
                    {...register('shippingAddress.neighborhood')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.neighborhood && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.neighborhood.message}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">City</label>
                  <input
                    {...register('shippingAddress.city')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.city && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.city.message}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">State</label>
                  <input
                    {...register('shippingAddress.state')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.state && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.state.message}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">ZIP Code</label>
                  <input
                    {...register('shippingAddress.zipCode')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.zipCode && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.zipCode.message}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">Country</label>
                  <input
                    {...register('shippingAddress.country')}
                    className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
                  />
                  {errors.shippingAddress?.country && (
                    <p className="text-red-500 text-xs mt-1">{errors.shippingAddress.country.message}</p>
                  )}
                </div>
              </div>
            </div>

            <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
              <h2 className="font-semibold text-lg mb-4">Additional Notes</h2>
              <textarea
                {...register('customerNotes')}
                rows={3}
                className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent resize-none"
                placeholder="Any special instructions for your order..."
              />
            </div>
          </div>

          {/* Order Summary */}
          <div>
            <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm sticky top-20">
              <h2 className="font-semibold text-lg mb-4">Order Summary</h2>

              <ul className="space-y-3 mb-4">
                {items.map((item) => (
                  <li key={item.product.id} className="flex justify-between text-sm">
                    <span className="text-gray-600">
                      {item.product.name} × {item.quantity}
                    </span>
                    <span className="font-medium">
                      R${(Number(item.product.discount_price || item.product.price) * item.quantity).toFixed(2)}
                    </span>
                  </li>
                ))}
              </ul>

              {/* Coupon Input */}
              {!appliedCoupon ? (
                <div className="mb-4">
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={couponCode}
                      onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                      placeholder="Coupon code"
                      className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent"
                    />
                    <button
                      type="button"
                      onClick={handleApplyCoupon}
                      disabled={couponLoading || !couponCode.trim()}
                      className="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700 disabled:opacity-50 flex items-center gap-1"
                    >
                      {couponLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : <><Tag className="w-4 h-4" /> Apply</>}
                    </button>
                  </div>
                  {couponError && <p className="text-red-500 text-xs mt-1">{couponError}</p>}
                </div>
              ) : (
                <div className="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2 mb-4">
                  <div className="flex items-center gap-2 text-green-700 text-sm">
                    <Check className="w-4 h-4" />
                    <span className="font-medium">{appliedCoupon.code}</span>
                    <span className="text-green-600">applied</span>
                  </div>
                  <button
                    type="button"
                    onClick={handleRemoveCoupon}
                    className="text-gray-400 hover:text-red-500 transition-colors"
                  >
                    <X className="w-4 h-4" />
                  </button>
                </div>
              )}

              <div className="border-t pt-4 space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-500">Subtotal</span>
                  <span>R${total.toFixed(2)}</span>
                </div>
                {discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-green-600">
                    <span>Discount ({appliedCoupon?.code})</span>
                    <span>-R${discountAmount.toFixed(2)}</span>
                  </div>
                )}
                <div className="flex justify-between text-sm">
                  <span className="text-gray-500">Shipping</span>
                  <span className={shipping === 0 ? 'text-green-600' : ''}>
                    {shipping === 0 ? 'Free' : `R$${shipping.toFixed(2)}`}
                  </span>
                </div>
                <div className="flex justify-between font-semibold text-lg pt-2 border-t">
                  <span>Total</span>
                  <span>R${finalTotal.toFixed(2)}</span>
                </div>
              </div>

              <button
                type="submit"
                disabled={isProcessing}
                className="w-full mt-6 bg-accent hover:bg-accent-light text-white py-3.5 rounded-xl font-semibold transition-all duration-200 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-accent/25 hover:shadow-accent/40"
              >
                {isProcessing ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" /> Processing...
                  </>
                ) : (
                  <>
                    <CreditCard className="w-5 h-5" /> Place Order
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}
