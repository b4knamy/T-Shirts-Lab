import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useDebounce } from '../useDebounce';

describe('useDebounce', () => {
  it('should return initial value immediately', () => {
    const { result } = renderHook(() => useDebounce('hello', 300));
    expect(result.current).toBe('hello');
  });

  it('should update value only after specified delay', () => {
    vi.useFakeTimers();
    const { result, rerender } = renderHook(
      ({ value, delay }) => useDebounce(value, delay),
      { initialProps: { value: 'hello', delay: 300 } }
    );

    expect(result.current).toBe('hello');

    // Rerender with a new value
    rerender({ value: 'world', delay: 300 });
    expect(result.current).toBe('hello'); // not updated yet

    // Fast-forward time, but not enough
    act(() => {
      vi.advanceTimersByTime(150);
    });
    expect(result.current).toBe('hello'); // still not updated

    // Fast-forward the rest of the time
    act(() => {
      vi.advanceTimersByTime(150);
    });
    expect(result.current).toBe('world'); // updated!

    vi.useRealTimers();
  });

  it('should clear timeout on unmount', () => {
    vi.useFakeTimers();
    const spy = vi.spyOn(global, 'clearTimeout');
    const { unmount } = renderHook(() => useDebounce('hello', 300));

    unmount();
    expect(spy).toHaveBeenCalled();
    vi.useRealTimers();
  });
});
