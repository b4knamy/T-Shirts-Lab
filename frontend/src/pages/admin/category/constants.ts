import type { CategoryFormData } from './types';

export const CATEGORY_PAGE_LIMIT = 1;

export const EMPTY_CATEGORY_FORM: CategoryFormData = {
  name: '',
  description: '',
  image_url: '',
  is_active: true,
};