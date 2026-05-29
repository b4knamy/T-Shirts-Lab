import type { FieldErrors, UseFormRegister } from 'react-hook-form';
import { z } from 'zod';
import type { CartItem, Coupon } from '../../types';
import { checkoutSchema } from './schemas';

// Checkout
export type SelectedCheckoutItem = CartItem & { checkoutQuantity: number };
export type CheckoutFormData = z.infer<typeof checkoutSchema>;

export interface CheckoutDraftItem {
	productId: string;
	quantity: number;
}

export interface CheckoutDraftState {
	items: CheckoutDraftItem[];
	draftInitialized: boolean;
}

// Coupon
export interface ValidateCouponVariables {
	code: string;
	subtotal: number;
}

// Submission
export interface UseCheckoutSubmissionOptions {
	cancelledOrderId: string | null;
	couponCode?: string;
	selectedItems: SelectedCheckoutItem[];
}

// Order Summary
export interface OrderSummaryCoupon {
	appliedCoupon: Coupon | null;
	code: string;
	error: string | null;
	couponLoading: boolean;
	discountAmount: number;
	handleApplyCoupon: () => Promise<void>;
	handleRemoveCoupon: () => void;
	setCode: (value: string) => void;
}

export interface OrderSummaryDraft {
	selectedItems: SelectedCheckoutItem[];
	shipping: number;
	subtotal: number;
}

export interface OrderSummarySubmission {
	isProcessing: boolean;
	isRedirecting: boolean;
}

export interface OrderSummarySectionProps {
	coupon: OrderSummaryCoupon;
	draft: OrderSummaryDraft;
	submission: OrderSummarySubmission;
	finalTotal: number;
}

// Additional Notes
export interface AdditionalNotesSectionProps {
	register: UseFormRegister<CheckoutFormData>;
}

// Shipping Address
export interface ShippingAddressSectionProps {
	errors: FieldErrors<CheckoutFormData>;
	register: UseFormRegister<CheckoutFormData>;
}

// Cancelled Checkout
export interface CancelledCheckoutStateProps {
	cancelledOrderId: string | null;
	error: string | null;
	isProcessing: boolean;
	isRedirecting: boolean;
	onRetryCheckout: () => void;
}

// No Items Selected
export interface NoItemsSelectedStateProps {
	onChooseCartItems: () => void;
}