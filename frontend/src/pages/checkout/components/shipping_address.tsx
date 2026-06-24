import type { ShippingAddressSectionProps } from '../types';

export function ShippingAddressSection({
  errors,
  register,
}: ShippingAddressSectionProps) {
  return (
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
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.street.message}
            </p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Number</label>
          <input
            {...register('shippingAddress.number')}
            className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
          />
          {errors.shippingAddress?.number && (
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.number.message}
            </p>
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
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.neighborhood.message}
            </p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">City</label>
          <input
            {...register('shippingAddress.city')}
            className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
          />
          {errors.shippingAddress?.city && (
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.city.message}
            </p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">State</label>
          <input
            {...register('shippingAddress.state')}
            className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
          />
          {errors.shippingAddress?.state && (
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.state.message}
            </p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">ZIP Code</label>
          <input
            {...register('shippingAddress.zipCode')}
            className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
          />
          {errors.shippingAddress?.zipCode && (
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.zipCode.message}
            </p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Country</label>
          <input
            {...register('shippingAddress.country')}
            className="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:border-accent"
          />
          {errors.shippingAddress?.country && (
            <p className="text-red-500 text-xs mt-1">
              {errors.shippingAddress.country.message}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
