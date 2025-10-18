import React, { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../services/api';
import toast from 'react-hot-toast';

// Helper function to safely get data from localStorage
const safeGetFromStorage = (key, defaultValue = null) => {
  try {
    const item = localStorage.getItem(key);
    if (!item || item === 'null' || item === 'undefined') {
      return defaultValue;
    }
    return key === 'user' ? JSON.parse(item) : item;
  } catch (error) {
    console.error(`Error reading ${key} from localStorage:`, error);
    return defaultValue;
  }
};

// Helper function to safely set data to localStorage
const safeSetToStorage = (key, value) => {
  try {
    if (value === null || value === undefined) {
      localStorage.removeItem(key);
    } else {
      const stringValue = key === 'user' ? JSON.stringify(value) : value;
      localStorage.setItem(key, stringValue);
    }
  } catch (error) {
    console.error(`Error writing ${key} to localStorage:`, error);
  }
};

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
    const savedUser = safeGetFromStorage('user');
    console.log('AuthProvider initial user state:', savedUser);
    return savedUser;
  });
  const [loading, setLoading] = useState(true);
  const [token, setToken] = useState(() => {
    const savedToken = safeGetFromStorage('token');
    console.log('AuthProvider initial token state:', savedToken);
    return savedToken;
  });
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

  // Debug: Track user state changes
  useEffect(() => {
    console.log('AuthContext user state changed:', user);
  }, [user]);

  // Debug: Track token state changes
  useEffect(() => {
    console.log('AuthContext token state changed:', token);
  }, [token]);

  const loadUser = async () => {
    try {
      const response = await authAPI.getUser();
      setUser(response.data);
      // Update localStorage with fresh user data
      safeSetToStorage('user', response.data);
    } catch (error) {
      console.error('Failed to load user:', error);
      // Clear invalid token without calling logout to avoid infinite loop
      safeSetToStorage('token', null);
      safeSetToStorage('user', null);
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
    console.log('Login function called with credentials:', credentials);
    try {
      console.log('Calling authAPI.login...');
      const response = await authAPI.login(credentials);
      console.log('Full API response:', response);
      console.log('Response data:', response.data);
      
      const { token, user } = response.data;
      
      console.log('Extracted token:', token);
      console.log('Extracted user:', user);
      
      console.log('Saving to localStorage...');
      safeSetToStorage('token', token);
      safeSetToStorage('user', user);
      
      console.log('Setting React state...');
      setToken(token);
      setUser(user);
      
      console.log('State setters called, checking localStorage...');
      console.log('localStorage token:', localStorage.getItem('token'));
      console.log('localStorage user:', localStorage.getItem('user'));
      
      // Force a re-render to ensure state is updated
      setTimeout(() => {
        console.log('Checking state after timeout - user:', user, 'token:', token);
        console.log('Current React state - user:', user, 'token:', token);
      }, 100);
      
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
      
      safeSetToStorage('token', token);
      safeSetToStorage('user', user);
      
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
      safeSetToStorage('token', null);
      safeSetToStorage('user', null);
      safeSetToStorage('currentWorkspaceId', null);
      setToken(null);
      setUser(null);
      toast.success('Logged out successfully');
    }
  };

  const clearAuth = () => {
    safeSetToStorage('token', null);
    safeSetToStorage('user', null);
    safeSetToStorage('currentWorkspaceId', null);
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


