// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  meta: {
    timestamp: string;
    version: string;
  };
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
}

export interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    statusCode: number;
  };
  meta: {
    timestamp: string;
  };
}
