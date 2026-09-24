import { useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import AOS from 'aos';
import 'aos/dist/aos.css';
import Header from './Header';
import Footer from './Footer';
import MetaPixel from './MetaPixel';

export default function Layout() {
  const { pathname } = useLocation();

  useEffect(() => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    AOS.init({
      duration: 680,
      easing: 'ease-out-cubic',
      once: true,
      offset: 56,
      disable: reduced,
    });
  }, []);

  // Browsers restore scroll on history navigation, but a fresh route push
  // should start at the top the way a server-rendered page would.
  useEffect(() => {
    window.scrollTo(0, 0);
    AOS.refresh();
  }, [pathname]);

  return (
    <div className="flex min-h-screen flex-col">
      <MetaPixel />
      <Header />
      <main className="flex-1">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}
