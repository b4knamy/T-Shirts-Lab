import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useCheckoutStatus } from '../status';
import { createHookWrapper } from './test_utils';

describe('useCheckoutStatus', () => {
  it('detects a cancelled checkout and reads the snake_case order id', () => {
    const { wrapper } = createHookWrapper({
      route: '/checkout?checkout=cancelled&order_id=order-123',
    });

    const { result } = renderHook(() => useCheckoutStatus(), { wrapper });

    expect(result.current.isCancelledCheckoutStatus).toBe(true);
    expect(result.current.cancelledOrderId).toBe('order-123');
  });

  it('accepts the camelCase orderId param and stays inactive without a cancelled status', () => {
    const cancelled = createHookWrapper({
      route: '/checkout?checkout=canceled&orderId=order-456',
    });
    const active = createHookWrapper({
      route: '/checkout?checkout=success&order_id=order-789',
    });

    const cancelledResult = renderHook(() => useCheckoutStatus(), { wrapper: cancelled.wrapper });
    const activeResult = renderHook(() => useCheckoutStatus(), { wrapper: active.wrapper });

    expect(cancelledResult.result.current.isCancelledCheckoutStatus).toBe(true);
    expect(cancelledResult.result.current.cancelledOrderId).toBe('order-456');
    expect(activeResult.result.current.isCancelledCheckoutStatus).toBe(false);
    expect(activeResult.result.current.cancelledOrderId).toBeNull();
  });
});