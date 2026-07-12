export interface DashboardStats {
  total_products: number;
  total_orders: number;
  revenue: number;
  pending_orders: number;
}

export type DashboardCardProps = {
  stats: DashboardStats;
  isLoading: boolean;
};
