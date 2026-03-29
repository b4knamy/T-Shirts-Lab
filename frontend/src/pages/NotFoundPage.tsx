import { Link } from 'react-router-dom';

export function NotFoundPage() {
  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
      <h1 className="text-9xl font-extrabold text-accent mb-4 tracking-tight">404</h1>
      <h2 className="text-2xl font-semibold mb-2">Page Not Found</h2>
      <p className="text-gray-500 mb-8 max-w-md mx-auto">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <Link
        to="/"
        className="inline-flex bg-accent text-white px-8 py-3.5 rounded-xl font-semibold hover:bg-accent-light transition-all duration-200 shadow-lg shadow-accent/25 hover:shadow-accent/40 hover:-translate-y-0.5"
      >
        Back to Home
      </Link>
    </div>
  );
}
