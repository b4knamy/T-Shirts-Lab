import { Link } from 'react-router-dom';

export function NotFoundPage() {
  return (
    <div className="max-w-4xl mx-auto px-4 py-20 text-center">
      <h1 className="text-8xl font-bold text-accent mb-4">404</h1>
      <h2 className="text-2xl font-semibold mb-2">Page Not Found</h2>
      <p className="text-gray-500 mb-8">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <Link
        to="/"
        className="inline-flex bg-accent text-white px-8 py-3 rounded-lg font-semibold hover:bg-accent-light transition-colors"
      >
        Back to Home
      </Link>
    </div>
  );
}
