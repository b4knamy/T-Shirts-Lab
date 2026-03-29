import { Link, useNavigate } from 'react-router-dom';
import { ShoppingCart, User, Menu, X, Search, LogOut, Package } from 'lucide-react';
import { useState } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { useCart } from '../../hooks/useCart';

export function Header() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const { user, isAuthenticated, signOut } = useAuth();
  const { itemCount, toggle } = useCart();
  const navigate = useNavigate();

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`/products?search=${encodeURIComponent(searchQuery.trim())}`);
      setSearchQuery('');
    }
  };

  return (
    <header className="bg-primary text-white sticky top-0 z-50 shadow-lg backdrop-blur-sm border-b border-white/5">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          {/* Logo */}
          <Link to="/" className="flex items-center gap-2 font-bold text-xl tracking-tight">
            <span className="text-accent">T-Shirts</span>Lab
          </Link>

          {/* Desktop Nav */}
          <nav className="hidden md:flex items-center gap-8">
            <Link to="/products" className="hover:text-accent transition-colors">
              Products
            </Link>
            <Link to="/products?category=men" className="hover:text-accent transition-colors">
              Men
            </Link>
            <Link to="/products?category=women" className="hover:text-accent transition-colors">
              Women
            </Link>
            <Link to="/products?category=kids" className="hover:text-accent transition-colors">
              Kids
            </Link>
          </nav>

          {/* Search Bar */}
          <form onSubmit={handleSearch} className="hidden md:flex items-center flex-1 max-w-md mx-8">
            <div className="relative w-full">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search t-shirts..."
                className="w-full pl-10 pr-4 py-2 rounded-lg bg-primary-light text-white placeholder-gray-400 border border-gray-600 focus:outline-none focus:border-accent"
              />
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            </div>
          </form>

          {/* Right Actions */}
          <div className="flex items-center gap-4">
            {/* Cart */}
            <button
              onClick={toggle}
              className="relative p-2 hover:text-accent transition-colors"
              aria-label="Cart"
            >
              <ShoppingCart className="w-5 h-5" />
              {itemCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-accent text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                  {itemCount}
                </span>
              )}
            </button>

            {/* User Menu */}
            {isAuthenticated ? (
              <div className="hidden md:flex items-center gap-3">
                <Link to="/profile" className="flex items-center gap-2 hover:text-accent transition-colors">
                  <User className="w-5 h-5" />
                  <span className="text-sm">{user?.firstName}</span>
                </Link>
                {user?.role === 'ADMIN' || user?.role === 'SUPER_ADMIN' ? (
                  <Link to="/admin" className="hover:text-accent transition-colors text-sm">
                    <Package className="w-5 h-5" />
                  </Link>
                ) : null}
                <button onClick={signOut} className="hover:text-accent transition-colors" aria-label="Logout">
                  <LogOut className="w-5 h-5" />
                </button>
              </div>
            ) : (
              <Link
                to="/login"
                className="hidden md:inline-flex items-center gap-2 bg-accent hover:bg-accent-light text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                <User className="w-4 h-4" />
                Sign In
              </Link>
            )}

            {/* Mobile Menu Toggle */}
            <button
              onClick={() => setMenuOpen(!menuOpen)}
              className="md:hidden p-2"
              aria-label="Toggle menu"
            >
              {menuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {menuOpen && (
          <div className="md:hidden pb-4 border-t border-gray-700">
            <form onSubmit={handleSearch} className="py-3">
              <div className="relative">
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search t-shirts..."
                  className="w-full pl-10 pr-4 py-2 rounded-lg bg-primary-light text-white placeholder-gray-400 border border-gray-600 focus:outline-none focus:border-accent"
                />
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              </div>
            </form>
            <nav className="flex flex-col gap-3">
              <Link to="/products" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                All Products
              </Link>
              <Link to="/products?category=men" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                Men
              </Link>
              <Link to="/products?category=women" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                Women
              </Link>
              <Link to="/products?category=kids" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                Kids
              </Link>
              <hr className="border-gray-700" />
              {isAuthenticated ? (
                <>
                  <Link to="/profile" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                    My Profile
                  </Link>
                  <Link to="/orders" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                    My Orders
                  </Link>
                  <button onClick={() => { signOut(); setMenuOpen(false); }} className="text-left hover:text-accent transition-colors">
                    Sign Out
                  </button>
                </>
              ) : (
                <Link to="/login" onClick={() => setMenuOpen(false)} className="hover:text-accent transition-colors">
                  Sign In
                </Link>
              )}
            </nav>
          </div>
        )}
      </div>
    </header>
  );
}
