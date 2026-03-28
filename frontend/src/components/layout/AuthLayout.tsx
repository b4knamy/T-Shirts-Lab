import { Outlet, Link } from 'react-router-dom';

export function AuthLayout() {
  return (
    <div className="min-h-screen bg-surface flex flex-col items-center justify-center px-4">
      <Link to="/" className="mb-8 font-bold text-2xl">
        <span className="text-accent">T-Shirts</span>Lab
      </Link>
      <div className="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <Outlet />
      </div>
    </div>
  );
}
