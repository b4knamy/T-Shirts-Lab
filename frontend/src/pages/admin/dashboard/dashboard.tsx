import { DashboardHeader } from './sections/header';
import { useAdminDashboard } from './hooks/fetch_hook';
import { DashboardCards } from './sections/cards';
import { QuickActions } from './sections/quick_actions';

export function AdminDashboard() {
  const { stats, isLoading } = useAdminDashboard();

  return (
    <div>
      <DashboardHeader />

      <DashboardCards stats={stats} isLoading={isLoading} />

      <div className="bg-white border border-gray-100 rounded-2xl p-6">
        <QuickActions />
      </div>
    </div>
  );
}

export default AdminDashboard;
