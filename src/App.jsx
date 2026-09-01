import { Routes, Route } from 'react-router-dom';

import Layout from './components/Layout';
import RequireAuth from './components/RequireAuth';
import Toast from './components/Toast';

import Home from './pages/Home';
import Shop from './pages/Shop';
import Stationery from './pages/Stationery';
import Product from './pages/Product';
import Cart from './pages/Cart';
import Checkout from './pages/Checkout';
import PaymentResult from './pages/PaymentResult';
import KlumpPay from './pages/KlumpPay';
import Contact from './pages/Contact';
import Legal from './pages/Legal';
import BlogIndex from './pages/BlogIndex';
import BlogPost from './pages/BlogPost';
import NotFound from './pages/NotFound';

import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import VerifyEmail from './pages/auth/VerifyEmail';
import ForgotPassword from './pages/auth/ForgotPassword';
import ResetPassword from './pages/auth/ResetPassword';

import Account from './pages/account/Account';
import OrderDetail from './pages/account/OrderDetail';
import Wishlist from './pages/account/Wishlist';

import AdminLayout from './pages/admin/AdminLayout';
import AdminLogin from './pages/admin/AdminLogin';
import Dashboard from './pages/admin/Dashboard';
import AdminOrders from './pages/admin/Orders';
import AdminOrderDetail from './pages/admin/OrderDetail';
import AdminProducts from './pages/admin/Products';
import AdminProductForm from './pages/admin/ProductForm';
import AdminCategories from './pages/admin/Categories';
import AdminBlogs from './pages/admin/Blogs';
import AdminBlogForm from './pages/admin/BlogForm';

export default function App() {
  return (
    <>
      <Routes>
        <Route element={<Layout />}>
          <Route index element={<Home />} />
          <Route path="shop" element={<Shop />} />
          <Route path="stationery" element={<Stationery />} />
          <Route path="book/:id" element={<Product />} />
          <Route path="cart" element={<Cart />} />
          <Route path="contact" element={<Contact />} />
          <Route path="blog" element={<BlogIndex />} />
          <Route path="blog/:slug" element={<BlogPost />} />
          <Route path="privacy" element={<Legal slug="privacy" />} />
          <Route path="terms" element={<Legal slug="terms" />} />
          <Route path="shipping" element={<Legal slug="shipping" />} />

          <Route path="login" element={<Login />} />
          <Route path="register" element={<Register />} />
          <Route path="verify-email" element={<VerifyEmail />} />
          <Route path="forgot-password" element={<ForgotPassword />} />
          <Route path="reset-password" element={<ResetPassword />} />

          <Route path="payment/result" element={<PaymentResult />} />
          <Route path="payment/klump" element={<KlumpPay />} />

          <Route element={<RequireAuth />}>
            <Route path="checkout" element={<Checkout />} />
            <Route path="account" element={<Account />} />
            <Route path="wishlist" element={<Wishlist />} />
            <Route path="orders/:id" element={<OrderDetail />} />
          </Route>

          <Route path="*" element={<NotFound />} />
        </Route>

        <Route path="/admin/login" element={<AdminLogin />} />
        <Route path="/admin" element={<AdminLayout />}>
          <Route index element={<Dashboard />} />
          <Route path="orders" element={<AdminOrders />} />
          <Route path="orders/:id" element={<AdminOrderDetail />} />
          <Route path="products" element={<AdminProducts />} />
          <Route path="products/new" element={<AdminProductForm />} />
          <Route path="products/:id/edit" element={<AdminProductForm />} />
          <Route path="categories" element={<AdminCategories />} />
          <Route path="blogs" element={<AdminBlogs />} />
          <Route path="blogs/new" element={<AdminBlogForm />} />
          <Route path="blogs/:id/edit" element={<AdminBlogForm />} />
        </Route>
      </Routes>

      <Toast />
    </>
  );
}
