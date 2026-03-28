import { Link } from 'react-router-dom';

export function Footer() {
  return (
    <footer className="bg-primary text-gray-300 mt-auto">
      <div className="max-w-7xl mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Brand */}
          <div>
            <Link to="/" className="font-bold text-xl text-white">
              <span className="text-accent">T-Shirts</span>Lab
            </Link>
            <p className="mt-3 text-sm text-gray-400">
              Custom t-shirts with unique designs. Express yourself with high-quality apparel.
            </p>
          </div>

          {/* Shop */}
          <div>
            <h3 className="font-semibold text-white mb-3">Shop</h3>
            <ul className="space-y-2 text-sm">
              <li><Link to="/products" className="hover:text-accent transition-colors">All Products</Link></li>
              <li><Link to="/products?category=men" className="hover:text-accent transition-colors">Men</Link></li>
              <li><Link to="/products?category=women" className="hover:text-accent transition-colors">Women</Link></li>
              <li><Link to="/products?category=kids" className="hover:text-accent transition-colors">Kids</Link></li>
            </ul>
          </div>

          {/* Support */}
          <div>
            <h3 className="font-semibold text-white mb-3">Support</h3>
            <ul className="space-y-2 text-sm">
              <li><Link to="/faq" className="hover:text-accent transition-colors">FAQ</Link></li>
              <li><Link to="/shipping" className="hover:text-accent transition-colors">Shipping & Returns</Link></li>
              <li><Link to="/contact" className="hover:text-accent transition-colors">Contact Us</Link></li>
              <li><Link to="/size-guide" className="hover:text-accent transition-colors">Size Guide</Link></li>
            </ul>
          </div>

          {/* Newsletter */}
          <div>
            <h3 className="font-semibold text-white mb-3">Stay Updated</h3>
            <p className="text-sm text-gray-400 mb-3">Get exclusive deals and new arrivals.</p>
            <form className="flex gap-2">
              <input
                type="email"
                placeholder="Your email"
                className="flex-1 px-3 py-2 bg-primary-light rounded-lg text-white placeholder-gray-500 border border-gray-600 focus:outline-none focus:border-accent text-sm"
              />
              <button
                type="submit"
                className="bg-accent hover:bg-accent-light text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                Subscribe
              </button>
            </form>
          </div>
        </div>

        <div className="border-t border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-sm text-gray-500">
            © {new Date().getFullYear()} T-Shirts Lab. All rights reserved.
          </p>
          <div className="flex gap-6 text-sm text-gray-500">
            <Link to="/privacy" className="hover:text-accent transition-colors">Privacy Policy</Link>
            <Link to="/terms" className="hover:text-accent transition-colors">Terms of Service</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
