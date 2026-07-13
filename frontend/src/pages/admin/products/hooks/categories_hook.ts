import { useQuery } from '@tanstack/react-query';
import { adminApi } from '../../../../services/api/admin';

export function useProductCategories() {
  const query = useQuery({
    queryKey: ['admin-product-categories'],
    queryFn: async () => {
      const response = await adminApi.getCategories();
      return response.data.data;
    },
  });

  return {
    categories: query.data ?? [],
    isLoading: query.isLoading,
  };
}
