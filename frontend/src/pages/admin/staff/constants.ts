import { Shield, ShieldAlert, ShieldCheck, Users } from 'lucide-react';
import type { RoleBadgeConfig } from './types';

export const ROLE_BADGE: Record<string, RoleBadgeConfig> = {
  SUPER_ADMIN: {
    label: 'Super Admin',
    color: 'bg-purple-100 text-purple-700',
    icon: ShieldAlert,
  },
  ADMIN: {
    label: 'Admin',
    color: 'bg-red-100 text-red-700',
    icon: ShieldCheck,
  },
  MODERATOR: {
    label: 'Moderator',
    color: 'bg-blue-100 text-blue-700',
    icon: Shield,
  },
  CUSTOMER: {
    label: 'Customer',
    color: 'bg-gray-100 text-gray-600',
    icon: Users,
  },
  VENDOR: {
    label: 'Vendor',
    color: 'bg-green-100 text-green-700',
    icon: Users,
  },
};

export const EMPTY_STAFF_FORM = {
  email: '',
  password: '',
  first_name: '',
  last_name: '',
  phone: '',
  role: 'MODERATOR',
};
