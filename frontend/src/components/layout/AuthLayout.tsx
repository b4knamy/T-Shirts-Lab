import { Outlet, Link } from 'react-router-dom';

export function AuthLayout() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-surface to-surface-dark flex flex-col items-center justify-center px-4">
      <Link to="/" className="mb-8 font-bold text-2xl tracking-tight">
        <span className="text-accent">T-Shirts</span>Lab
      </Link>
      <div className="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        <Outlet />
      </div>
    </div>
  );
}
