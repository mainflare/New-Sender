import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { WorkspaceProvider } from './contexts/WorkspaceContext';

// Auth pages
import Login from './pages/Login';
import Register from './pages/Register';

// Layout
import DashboardLayout from './components/Layout/DashboardLayout';

// Main pages
import Dashboard from './pages/Dashboard';
import WhatsAppSessions from './pages/WhatsAppSessions';
import Campaigns from './pages/Campaigns';
import Contacts from './pages/Contacts';
import Conversations from './pages/Conversations';
import Chatbots from './pages/Chatbots';
import Analytics from './pages/Analytics';
import Settings from './pages/Settings';
import Profile from './pages/Profile';

// Admin pages
import AdminDashboard from './pages/admin/AdminDashboard';
import AdminUsers from './pages/admin/AdminUsers';
import AdminSubscriptions from './pages/admin/AdminSubscriptions';
import AdminSettings from './pages/admin/AdminSettings';

// All components are now properly imported above

// All admin components are now properly imported above

// Protected Route Component
function ProtectedRoute({ children, adminOnly = false }) {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (adminOnly && user.role !== 'super_admin') {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
}

// Public Route Component (redirect if already logged in)
function PublicRoute({ children }) {
  const { user, loading } = useAuth();

  // Debug logging
  console.log('PublicRoute render - user:', user, 'loading:', loading, 'user type:', typeof user, 'user truthy:', !!user);

  // No need for useEffect - we handle redirect directly in render

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  if (user) {
    // Redirect immediately if user is logged in
    console.log('User is logged in, redirecting to root path immediately');
    return <Navigate to="/" replace />;
  }

  console.log('User is not logged in, showing public page');
  return children;
}

function App() {
  return (
    <AuthProvider>
      <WorkspaceProvider>
        <Router>
          <div className="App">
            <Toaster
              position="top-right"
              toastOptions={{
                duration: 4000,
                style: {
                  background: '#363636',
                  color: '#fff',
                },
                success: {
                  duration: 3000,
                  iconTheme: {
                    primary: '#22c55e',
                    secondary: '#fff',
                  },
                },
                error: {
                  duration: 4000,
                  iconTheme: {
                    primary: '#ef4444',
                    secondary: '#fff',
                  },
                },
              }}
            />

            <Routes>
              {/* Public Routes */}
              <Route
                path="/login"
                element={
                  <PublicRoute>
                    <Login />
                  </PublicRoute>
                }
              />
              <Route
                path="/register"
                element={
                  <PublicRoute>
                    <Register />
                  </PublicRoute>
                }
              />

              {/* Protected Routes */}
              <Route
                path="/"
                element={
                  <ProtectedRoute>
                    <DashboardLayout />
                  </ProtectedRoute>
                }
              >
                <Route index element={<Navigate to="/dashboard" replace />} />
                <Route path="dashboard" element={<Dashboard />} />
                <Route path="conversations" element={<Conversations />} />
                <Route path="contacts" element={<Contacts />} />
                <Route path="campaigns" element={<Campaigns />} />
                <Route path="whatsapp" element={<WhatsAppSessions />} />
                <Route path="chatbots" element={<Chatbots />} />
                <Route path="analytics" element={<Analytics />} />
                <Route path="settings" element={<Settings />} />
                <Route path="profile" element={<Profile />} />

                {/* Admin Routes */}
                <Route
                  path="admin"
                  element={
                    <ProtectedRoute adminOnly>
                      <AdminDashboard />
                    </ProtectedRoute>
                  }
                />
                <Route
                  path="admin/users"
                  element={
                    <ProtectedRoute adminOnly>
                      <AdminUsers />
                    </ProtectedRoute>
                  }
                />
                <Route
                  path="admin/subscriptions"
                  element={
                    <ProtectedRoute adminOnly>
                      <AdminSubscriptions />
                    </ProtectedRoute>
                  }
                />
                <Route
                  path="admin/settings"
                  element={
                    <ProtectedRoute adminOnly>
                      <AdminSettings />
                    </ProtectedRoute>
                  }
                />
              </Route>

              {/* Catch all */}
              <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </Routes>
          </div>
        </Router>
      </WorkspaceProvider>
    </AuthProvider>
  );
}

export default App;
