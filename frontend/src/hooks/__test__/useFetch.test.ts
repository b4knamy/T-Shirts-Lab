import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useFetch } from '../useFetch';

describe('useFetch', () => {
  it('should fetch data immediately by default', async () => {
    const mockData = { id: 1, name: 'T-shirt' };
    const fetcher = vi.fn().mockResolvedValue(mockData);

    const { result } = renderHook(() => useFetch({ fetcher }));

    expect(result.current.isLoading).toBe(true);
    expect(result.current.data).toBeNull();
    expect(result.current.error).toBeNull();

    await act(async () => {
      await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(result.current.isLoading).toBe(false);
    expect(result.current.data).toEqual(mockData);
    expect(result.current.error).toBeNull();
    expect(fetcher).toHaveBeenCalledTimes(1);
  });

  it('should not fetch data on mount if immediate is false', async () => {
    const mockData = { id: 1, name: 'T-shirt' };
    const fetcher = vi.fn().mockResolvedValue(mockData);

    const { result } = renderHook(() => useFetch({ fetcher, immediate: false }));

    expect(result.current.isLoading).toBe(false);
    expect(result.current.data).toBeNull();
    expect(result.current.error).toBeNull();
    expect(fetcher).not.toHaveBeenCalled();

    let refetchPromise: Promise<void>;
    act(() => {
      refetchPromise = result.current.refetch();
    });

    expect(result.current.isLoading).toBe(true);

    await act(async () => {
      await refetchPromise;
    });

    expect(result.current.isLoading).toBe(false);
    expect(result.current.data).toEqual(mockData);
    expect(result.current.error).toBeNull();
    expect(fetcher).toHaveBeenCalledTimes(1);
  });

  it('should capture and format fetcher errors correctly', async () => {
    const errorMessage = 'API Error';
    const fetcher = vi.fn().mockRejectedValue(new Error(errorMessage));

    const { result } = renderHook(() => useFetch({ fetcher }));

    await act(async () => {
      await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(result.current.isLoading).toBe(false);
    expect(result.current.data).toBeNull();
    expect(result.current.error).toBe(errorMessage);
  });

  it('should parse nested backend response error messages correctly', async () => {
    const apiError = {
      response: {
        data: {
          error: {
            message: 'Nested Backend Error message',
          },
        },
      },
    };
    const fetcher = vi.fn().mockRejectedValue(apiError);

    const { result } = renderHook(() => useFetch({ fetcher }));

    await act(async () => {
      await new Promise((resolve) => setTimeout(resolve, 0));
    });

    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBe('Nested Backend Error message');
  });
});
