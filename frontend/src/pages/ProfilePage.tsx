import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { User, Package, LogOut, Mail, Phone, Calendar } from 'lucide-react';
import { useAuth } from '../hooks/useAuth';
import { LoadingSpinner } from '../components/common/LoadingSpinner';

export function ProfilePage() {
  const { user, isLoading, loadProfile, signOut } = useAuth();

  useEffect(() => {
    if (!user) {
      loadProfile();
    }
  }, [user, loadProfile]);

  if (isLoading) return <LoadingSpinner message="Loading profile..." />;

  if (!user) return null;

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <h1 className="text-3xl font-bold mb-8">My Account</h1>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Sidebar */}
        <div className="space-y-2">
          <Link to="/profile" className="flex items-center gap-3 p-3 bg-accent/10 text-accent rounded-lg font-medium">
            <User className="w-5 h-5" /> Profile
          </Link>
          <Link to="/orders" className="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-lg">
            <Package className="w-5 h-5" /> My Orders
          </Link>
          <button onClick={signOut} className="w-full flex items-center gap-3 p-3 hover:bg-gray-100 rounded-lg text-red-500">
            <LogOut className="w-5 h-5" /> Sign Out
          </button>
        </div>

        {/* Profile Info */}
        <div className="md:col-span-2">
          <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
            <h2 className="font-semibold text-lg mb-6">Personal Information</h2>

            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <User className="w-5 h-5 text-gray-400" />
                <div>
                  <p className="text-sm text-gray-500">Full Name</p>
                  <p className="font-medium">{user.firstName} {user.lastName}</p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <Mail className="w-5 h-5 text-gray-400" />
                <div>
                  <p className="text-sm text-gray-500">Email</p>
                  <p className="font-medium">{user.email}</p>
                </div>
              </div>

              {user.phone && (
                <div className="flex items-center gap-3">
                  <Phone className="w-5 h-5 text-gray-400" />
                  <div>
                    <p className="text-sm text-gray-500">Phone</p>
                    <p className="font-medium">{user.phone}</p>
                  </div>
                </div>
              )}

              <div className="flex items-center gap-3">
                <Calendar className="w-5 h-5 text-gray-400" />
                <div>
                  <p className="text-sm text-gray-500">Member Since</p>
                  <p className="font-medium">{new Date(user.createdAt).toLocaleDateString()}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
