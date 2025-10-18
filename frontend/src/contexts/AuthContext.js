import React, { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../services/api';
import toast from 'react-hot-toast';

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(() => {
    // Try to get user from localStorage on initial load
    const savedUser = localStorage.getItem('user');
    return savedUser ? JSON.parse(savedUser) : null;
  });
  const [loading, setLoading] = useState(true);
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [isLoadingUser, setIsLoadingUser] = useState(false);

  useEffect(() => {
    if (token && !isLoadingUser) {
      setIsLoadingUser(true);
      loadUser();
    } else if (!token) {
      // If no token, clear user and stop loading
      setUser(null);
      setLoading(false);
    }

    // Fallback: stop loading after 5 seconds to prevent infinite loading
    const timeout = setTimeout(() => {
      if (loading) {
        setLoading(false);
        setIsLoadingUser(false);
      }
    }, 5000);

    return () => clearTimeout(timeout);
  }, [token, isLoadingUser, loading]);

  const loadUser = async () => {
    try {
      const response = await authAPI.getUser();
      setUser(response.data);
      // Update localStorage with fresh user data
      localStorage.setItem('user', JSON.stringify(response.data));
    } catch (error) {
      console.error('Failed to load user:', error);
      // Clear invalid token without calling logout to avoid infinite loop
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setToken(null);
      setUser(null);
      
      // If it's a network error, show a helpful message
      if (error.code === 'ECONNABORTED' || error.message.includes('Network Error')) {
        console.warn('Backend API is not accessible. Please ensure the backend is running.');
      }
    } finally {
      setLoading(false);
      setIsLoadingUser(false);
    }
  };

  const login = async (credentials) => {
    try {
      const response = await authAPI.login(credentials);
      const { token, user } = response.data;
      
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      setToken(token);
      setUser(user);
      
      toast.success('Login successful!');
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Login failed';
      toast.error(message);
      return { success: false, error: message };
    }
  };

  const register = async (userData) => {
    try {
      const response = await authAPI.register(userData);
      const { token, user } = response.data;
      
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      setToken(token);
      setUser(user);
      
      toast.success('Registration successful!');
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Registration failed';
      toast.error(message);
      return { success: false, error: message };
    }
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('currentWorkspaceId');
      setToken(null);
      setUser(null);
      toast.success('Logged out successfully');
    }
  };

  const clearAuth = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('currentWorkspaceId');
    setToken(null);
    setUser(null);
    setLoading(false);
    setIsLoadingUser(false);
  };

  const isSuperAdmin = () => user?.role === 'super_admin';
  const isAdmin = () => ['super_admin', 'admin'].includes(user?.role);

  const value = {
    user,
    token,
    loading,
    login,
    register,
    logout,
    clearAuth,
    isSuperAdmin,
    isAdmin,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};


