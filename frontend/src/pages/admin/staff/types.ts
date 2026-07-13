import type { Shield } from 'lucide-react';
import type { PaginationMeta } from '../../../components/common/pagination/type';
import type { User } from '../../../types';

export interface RoleBadgeConfig {
  label: string;
  color: string;
  icon: typeof Shield;
}

export interface CreateStaffFormData {
  email: string;
  password: string;
  first_name: string;
  last_name: string;
  phone: string;
  role: string;
}

// Staff Fetching
export interface UseStaffFetchingOptions {
  pagination: PaginationMeta;
  search: string;
  roleFilter: string;
}

// Staff Header
export interface StaffHeaderProps {
  total: number;
  onCreate: () => void;
}

// Staff Filters
export interface StaffFiltersProps {
  search: string;
  roleFilter: string;
  onSearchChange: (value: string) => void;
  onRoleFilterChange: (value: string) => void;
}

// Staff Create Form
export interface StaffCreateFormProps {
  isSuperAdmin: boolean;
  onCreated: () => void;
  onCancel: () => void;
}

// Staff Table
export interface StaffTableProps {
  users: User[];
  isLoading: boolean;
  currentUser: User;
  isSuperAdmin: boolean;
}

// Staff Row
export interface StaffRowProps {
  user: User;
  currentUser: User;
  isSuperAdmin: boolean;
}
