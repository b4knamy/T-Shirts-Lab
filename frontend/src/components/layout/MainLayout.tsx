import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { Footer } from './Footer';
import { CartSidebar } from '../common/CartSidebar';
import { PromoBanner } from '../common/PromoBanner';

export function MainLayout() {
  return (
    <div className="min-h-screen w-full flex flex-col">
      <PromoBanner />
      <Header />
      <CartSidebar />
      <main className="flex-1 w-full flex flex-col">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
