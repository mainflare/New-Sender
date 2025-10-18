import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import {
  UsersIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  ServerIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
} from '@heroicons/react/24/outline';

export default function AdminDashboard() {
  const { user } = useAuth();
  const [stats, setStats] = useState({
    totalUsers: 0,
    activeUsers: 0,
    totalRevenue: 0,
    monthlyRevenue: 0,
    totalWorkspaces: 0,
    systemHealth: 'healthy',
  });

  const [recentUsers, setRecentUsers] = useState([]);
  const [systemAlerts, setSystemAlerts] = useState([]);

  useEffect(() => {
    loadAdminData();
  }, []);

  const loadAdminData = async () => {
    try {
      // Mock data for now - replace with actual API calls
      setStats({
        totalUsers: 1247,
        activeUsers: 892,
        totalRevenue: 45680,
        monthlyRevenue: 8920,
        totalWorkspaces: 156,
        systemHealth: 'healthy',
      });

      setRecentUsers([
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'user', status: 'active', joinedAt: '2 hours ago' },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'admin', status: 'active', joinedAt: '5 hours ago' },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'user', status: 'inactive', joinedAt: '1 day ago' },
      ]);

      setSystemAlerts([
        { id: 1, type: 'warning', message: 'High memory usage on server 2', time: '10 minutes ago' },
        { id: 2, type: 'info', message: 'Scheduled backup completed', time: '2 hours ago' },
        { id: 3, type: 'success', message: 'New user registration spike detected', time: '4 hours ago' },
      ]);
    } catch (error) {
      console.error('Error loading admin data:', error);
    }
  };

  const statCards = [
    {
      name: 'Total Users',
      value: stats.totalUsers.toLocaleString(),
      icon: UsersIcon,
      color: 'bg-blue-500',
      change: '+12%',
      changeType: 'positive',
    },
    {
      name: 'Active Users',
      value: stats.activeUsers.toLocaleString(),
      icon: CheckCircleIcon,
      color: 'bg-green-500',
      change: '+8%',
      changeType: 'positive',
    },
    {
      name: 'Total Revenue',
      value: `$${stats.totalRevenue.toLocaleString()}`,
      icon: CurrencyDollarIcon,
      color: 'bg-yellow-500',
      change: '+15%',
      changeType: 'positive',
    },
    {
      name: 'Monthly Revenue',
      value: `$${stats.monthlyRevenue.toLocaleString()}`,
      icon: ArrowTrendingUpIcon,
      color: 'bg-purple-500',
      change: '+23%',
      changeType: 'positive',
    },
  ];

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          Admin Dashboard
        </h1>
        <p className="text-gray-600 mt-2">
          System overview and management for {user?.name || 'Admin'}
        </p>
      </div>

      {/* System Health */}
      <div className="bg-white rounded-lg shadow p-6 mb-8">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <ServerIcon className="h-8 w-8 text-green-500 mr-3" />
            <div>
              <h2 className="text-xl font-bold text-gray-900">System Health</h2>
              <p className="text-gray-600">All systems operational</p>
            </div>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
            <span className="text-green-600 font-medium">Healthy</span>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {statCards.map((stat) => (
          <div key={stat.name} className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className={`p-3 rounded-lg ${stat.color}`}>
                <stat.icon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
              </div>
            </div>
            <div className="mt-4">
              <span className={`text-sm font-medium ${
                stat.changeType === 'positive' ? 'text-green-600' : 'text-red-600'
              }`}>
                {stat.change}
              </span>
              <span className="text-sm text-gray-500 ml-1">from last month</span>
            </div>
          </div>
        ))}
      </div>

      {/* Admin Actions */}
      <div className="bg-white rounded-lg shadow p-6 mb-8">
        <h2 className="text-xl font-bold text-gray-900 mb-4">Admin Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Link
            to="/admin/users"
            className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <UsersIcon className="h-8 w-8 text-blue-500 mr-3" />
            <div>
              <h3 className="font-medium text-gray-900">Manage Users</h3>
              <p className="text-sm text-gray-500">View and manage all users</p>
            </div>
          </Link>
          
          <Link
            to="/admin/subscriptions"
            className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <CurrencyDollarIcon className="h-8 w-8 text-green-500 mr-3" />
            <div>
              <h3 className="font-medium text-gray-900">Subscriptions</h3>
              <p className="text-sm text-gray-500">Manage user subscriptions</p>
            </div>
          </Link>
          
          <Link
            to="/admin/settings"
            className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <ServerIcon className="h-8 w-8 text-purple-500 mr-3" />
            <div>
              <h3 className="font-medium text-gray-900">System Settings</h3>
              <p className="text-sm text-gray-500">Configure system settings</p>
            </div>
          </Link>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Recent Users */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Recent Users</h2>
          <div className="space-y-4">
            {recentUsers.map((user) => (
              <div key={user.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div className="flex items-center">
                  <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                    <span className="text-sm font-medium text-gray-700">
                      {user.name.split(' ').map(n => n[0]).join('')}
                    </span>
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">{user.name}</p>
                    <p className="text-sm text-gray-500">{user.email}</p>
                  </div>
                </div>
                <div className="text-right">
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  }`}>
                    {user.status}
                  </span>
                  <p className="text-xs text-gray-500 mt-1">{user.joinedAt}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* System Alerts */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">System Alerts</h2>
          <div className="space-y-4">
            {systemAlerts.map((alert) => (
              <div key={alert.id} className="flex items-start p-3 bg-gray-50 rounded-lg">
                <div className="flex-shrink-0">
                  {alert.type === 'warning' && (
                    <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500" />
                  )}
                  {alert.type === 'info' && (
                    <ClockIcon className="h-5 w-5 text-blue-500" />
                  )}
                  {alert.type === 'success' && (
                    <CheckCircleIcon className="h-5 w-5 text-green-500" />
                  )}
                </div>
                <div className="ml-3 flex-1">
                  <p className="text-sm font-medium text-gray-900">{alert.message}</p>
                  <p className="text-sm text-gray-500">{alert.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
