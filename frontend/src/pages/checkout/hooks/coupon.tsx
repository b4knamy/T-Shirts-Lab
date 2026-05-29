import { useEffect, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { couponsApi } from '../../../services/api';
import type { Coupon } from '../../../types';
import type { ValidateCouponVariables } from '../types';

export function useCoupon(subtotal: number) {
	const [code, setCode] = useState('');
	const [error, setError] = useState<string | null>(null);
	const [appliedCoupon, setAppliedCoupon] = useState<Coupon | null>(null);
	const [appliedCouponSubtotal, setAppliedCouponSubtotal] = useState<number | null>(null);
	const [discountAmount, setDiscountAmount] = useState(0);

	const {
		mutateAsync: validateCoupon,
		isPending: couponLoading,
		reset: resetCouponMutation,
	} = useMutation({
		mutationFn: ({ code, subtotal: currentSubtotal }: ValidateCouponVariables) => {
			return couponsApi.validate(code, currentSubtotal);
		},
		onSuccess: (response, variables) => {
			setError(null);
			setAppliedCoupon(response.data.data.coupon);
			setAppliedCouponSubtotal(variables.subtotal);
			setDiscountAmount(response.data.data.discount);
			toast.success(`Coupon ${response.data.data.coupon.code} applied successfully.`);
		},
		onError: (error: unknown) => {
			const couponRequestError = error as { response?: { data?: { message?: string } } };
			const message = couponRequestError.response?.data?.message || 'Invalid coupon code';

			setError(message);
			setAppliedCoupon(null);
			setAppliedCouponSubtotal(null);
			setDiscountAmount(0);
			toast.error(message);
		},
	});

	useEffect(() => {
		if (!appliedCoupon || appliedCouponSubtotal === null || subtotal === appliedCouponSubtotal) {
			return;
		}

		setCode(appliedCoupon.code);
		setAppliedCoupon(null);
		setAppliedCouponSubtotal(null);
		setDiscountAmount(0);
		setError('Coupon removed because your checkout selection changed. Reapply it for the updated subtotal.');
		toast.error('Coupon removed because your checkout selection changed. Reapply it for the updated subtotal.');
		resetCouponMutation();
	}, [appliedCoupon, appliedCouponSubtotal, resetCouponMutation, subtotal]);

	const handleApplyCoupon = async () => {
		const normalizedCouponCode = code.trim();

		if (!normalizedCouponCode) {
			return;
		}

		setError(null);

		try {
			await validateCoupon({
				code: normalizedCouponCode,
				subtotal,
			});
		} catch {
			// The mutation callbacks map API failures into local UI state.
		}
	};

	const handleRemoveCoupon = () => {
		setAppliedCoupon(null);
		setAppliedCouponSubtotal(null);
		setDiscountAmount(0);
		setCode('');
		setError(null);
		resetCouponMutation();
	};

	return {
		appliedCoupon,
		code,
		error,
		couponLoading,
		discountAmount,
		handleApplyCoupon,
		handleRemoveCoupon,
		setCode,
	};
}
