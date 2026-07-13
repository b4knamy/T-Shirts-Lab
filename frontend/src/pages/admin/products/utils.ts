export function resolveProductError(
  error: unknown,
  fallbackMessage: string,
): string {
  const apiError = error as {
    response?: {
      data?: { message?: string; errors?: Record<string, string[]> };
    };
  };
  const fieldErrors = apiError.response?.data?.errors;

  if (fieldErrors) {
    return Object.values(fieldErrors).flat().join('. ');
  }

  return apiError.response?.data?.message || fallbackMessage;
}
