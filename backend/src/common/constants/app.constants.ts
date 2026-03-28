export const API_PREFIX = 'api/v1';
export const API_VERSION = '1.0.0';

export const CACHE_TTL = {
  PRODUCT: 3600, // 1 hour
  CATEGORY: 86400, // 24 hours
  USER_SESSION: 604800, // 7 days
  CART: 86400, // 24 hours
  SEARCH_RESULTS: 1800, // 30 minutes
  PASSWORD_RESET: 3600, // 1 hour
  RATE_LIMIT: 60, // 1 minute
} as const;

export const PAGINATION = {
  DEFAULT_PAGE: 1,
  DEFAULT_LIMIT: 20,
  MAX_LIMIT: 100,
} as const;
