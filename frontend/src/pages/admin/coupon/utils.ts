import type { Coupon } from '../../../types';
import type { CouponFormValues, CouponStatusTag } from './types';

export function formatCouponDate(value?: string | null) {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function getCouponStatus(coupon: Coupon): CouponStatusTag {
  if (!coupon.is_active) {
    return {
      label: 'Inactive',
      cls: 'bg-gray-50 text-gray-500 border-gray-200',
    };
  }

  if (coupon.expires_at && new Date(coupon.expires_at) < new Date()) {
    return {
      label: 'Expired',
      cls: 'bg-red-50 text-red-600 border-red-200',
    };
  }

  if (coupon.starts_at && new Date(coupon.starts_at) > new Date()) {
    return {
      label: 'Scheduled',
      cls: 'bg-blue-50 text-blue-600 border-blue-200',
    };
  }

  return {
    label: 'Active',
    cls: 'bg-green-50 text-green-700 border-green-200',
  };
}

export function toLocalInput(value?: string | null) {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  return (
    date.getFullYear() +
    '-' +
    String(date.getMonth() + 1).padStart(2, '0') +
    '-' +
    String(date.getDate()).padStart(2, '0') +
    'T' +
    String(date.getHours()).padStart(2, '0') +
    ':' +
    String(date.getMinutes()).padStart(2, '0')
  );
}

export function couponValueLabel(coupon: Coupon) {
  if (coupon.type === 'PERCENTAGE') {
    return `${coupon.value}%`;
  }

  return `R$ ${Number(coupon.value).toFixed(2)}`;
}

export function buildCouponPayload(form: CouponFormValues): Partial<Coupon> {
  const payload: Partial<Coupon> = {
    code: form.code,
    type: form.type,
    value: Number(form.value),
    is_active: form.is_active,
    is_public: form.is_public,
  };

  if (form.description?.trim()) {
    payload.description = form.description.trim();
  }

  if (form.min_order_amount) {
    payload.min_order_amount = Number(form.min_order_amount);
  }

  if (form.max_discount_amount) {
    payload.max_discount_amount = Number(form.max_discount_amount);
  }

  if (form.usage_limit) {
    payload.usage_limit = Number(form.usage_limit);
  }

  if (form.per_user_limit) {
    payload.per_user_limit = Number(form.per_user_limit);
  }

  if (form.starts_at) {
    payload.starts_at = new Date(form.starts_at).toISOString();
  }

  if (form.expires_at) {
    payload.expires_at = new Date(form.expires_at).toISOString();
  }

  return payload;
}
