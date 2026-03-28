import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { Footer } from './Footer';
import { CartSidebar } from '../common/CartSidebar';

export function MainLayout() {
  return (
    <>
      <Header />
      <CartSidebar />
      <main className="flex-1">
        <Outlet />
      </main>
      <Footer />
    </>
  );
}
