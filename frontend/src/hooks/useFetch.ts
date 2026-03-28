import { useState, useEffect, useCallback } from 'react';

interface UseFetchOptions<T> {
  fetcher: () => Promise<T>;
  immediate?: boolean;
}

interface UseFetchReturn<T> {
  data: T | null;
  isLoading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
}

export function useFetch<T>({ fetcher, immediate = true }: UseFetchOptions<T>): UseFetchReturn<T> {
  const [data, setData] = useState<T | null>(null);
  const [isLoading, setIsLoading] = useState(immediate);
  const [error, setError] = useState<string | null>(null);

  const refetch = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await fetcher();
      setData(result);
    } catch (err: unknown) {
      const e = err as { response?: { data?: { error?: { message?: string } } }; message?: string };
      setError(e.response?.data?.error?.message || e.message || 'Something went wrong');
    } finally {
      setIsLoading(false);
    }
  }, [fetcher]);

  useEffect(() => {
    if (immediate) {
      refetch();
    }
  }, [immediate, refetch]);

  return { data, isLoading, error, refetch };
}
