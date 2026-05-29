import { act, renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useCoupon } from '../coupon';
import { createCoupon, createHookWrapper } from './test_utils';

const {
  couponsValidateMock,
  toastErrorMock,
  toastSuccessMock,
} = vi.hoisted(() => ({
  couponsValidateMock: vi.fn(),
  toastErrorMock: vi.fn(),
  toastSuccessMock: vi.fn(),
}));

vi.mock('../../../../services/api', () => ({
  couponsApi: {
    validate: couponsValidateMock,
  },
}));

vi.mock('react-hot-toast', () => ({
  toast: {
    error: toastErrorMock,
    success: toastSuccessMock,
  },
}));

describe('useCoupon', () => {
  beforeEach(() => {
    couponsValidateMock.mockReset();
    toastErrorMock.mockReset();
    toastSuccessMock.mockReset();
  });

  it('applies a valid coupon and exposes the discount state', async () => {
    couponsValidateMock.mockResolvedValue({
      data: {
        data: {
          coupon: createCoupon('SAVE10'),
          discount: 10,
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(() => useCoupon(36), { wrapper });

    act(() => {
      result.current.setCode('SAVE10');
    });

    await act(async () => {
      await result.current.handleApplyCoupon();
    });

    await waitFor(() => {
      expect(result.current.appliedCoupon?.code).toBe('SAVE10');
    });

    expect(couponsValidateMock).toHaveBeenCalledWith('SAVE10', 36);
    expect(result.current.discountAmount).toBe(10);
    expect(result.current.error).toBeNull();
    expect(toastSuccessMock).toHaveBeenCalledWith('Coupon SAVE10 applied successfully.');
  });

  it('exposes the api error when coupon validation fails', async () => {
    couponsValidateMock.mockRejectedValue({
      response: {
        data: {
          message: 'Expired coupon',
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(() => useCoupon(36), { wrapper });

    act(() => {
      result.current.setCode('SAVE10');
    });

    await act(async () => {
      await result.current.handleApplyCoupon();
    });

    await waitFor(() => {
      expect(result.current.error).toBe('Expired coupon');
    });

    expect(result.current.appliedCoupon).toBeNull();
    expect(result.current.discountAmount).toBe(0);
    expect(toastErrorMock).toHaveBeenCalledWith('Expired coupon');
  });

  it('invalidates the coupon when the subtotal changes after it was applied', async () => {
    couponsValidateMock.mockResolvedValue({
      data: {
        data: {
          coupon: createCoupon('SAVE10'),
          discount: 10,
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result, rerender } = renderHook(
      ({ subtotal }) => useCoupon(subtotal),
      {
        initialProps: { subtotal: 36 },
        wrapper,
      },
    );

    act(() => {
      result.current.setCode('SAVE10');
    });

    await act(async () => {
      await result.current.handleApplyCoupon();
    });

    await waitFor(() => {
      expect(result.current.appliedCoupon?.code).toBe('SAVE10');
    });

    rerender({ subtotal: 18 });

    await waitFor(() => {
      expect(result.current.appliedCoupon).toBeNull();
    });

    expect(result.current.code).toBe('SAVE10');
    expect(result.current.error).toBe('Coupon removed because your checkout selection changed. Reapply it for the updated subtotal.');
    expect(result.current.discountAmount).toBe(0);
    expect(toastErrorMock).toHaveBeenCalledWith(
      'Coupon removed because your checkout selection changed. Reapply it for the updated subtotal.',
    );
  });
});