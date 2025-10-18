import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { adminAPI } from '../../services/api';
import {
  UserGroupIcon,
  BuildingOfficeIcon,
  CreditCardIcon,
  ChartBarIcon,
  CheckCircleIcon,
  XCircleIcon,
} from '@heroicons/react/24/outline';

export default function AdminDashboard() {
  const [stats, setStats] = useState(null);
  const [systemHealth, setSystemHealth] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      const [dashboardResponse, healthResponse] = await Promise.all([
        adminAPI.getDashboard(),
        adminAPI.getSystemHealth(),
      ]);
      setStats(dashboardResponse.data);
      setSystemHealth(healthResponse.data);
    } catch (error) {
      console.error('Failed to load admin dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const statCards = [
    {
      name: 'Total Users',
      value: stats?.total_users || 0,
      icon: UserGroupIcon,
      color: 'bg-blue-500',
      link: '/admin/users',
    },
    {
      name: 'Active Users',
      value: stats?.active_users || 0,
      icon: CheckCircleIcon,
      color: 'bg-green-500',
      link: '/admin/users',
    },
    {
      name: 'Total Workspaces',
      value: stats?.total_workspaces || 0,
      icon: BuildingOfficeIcon,
      color: 'bg-purple-500',
      link: '/admin/users',
    },
    {
      name: 'Active Subscriptions',
      value: stats?.active_subscriptions || 0,
      icon: CreditCardIcon,
      color: 'bg-indigo-500',
      link: '/admin/subscriptions',
    },
    {
      name: 'Total Revenue',
      value: `$${(stats?.total_revenue || 0).toLocaleString()}`,
      icon: ChartBarIcon,
      color: 'bg-yellow-500',
      link: '/admin/payments',
    },
    {
      name: 'Total Subscriptions',
      value: stats?.total_subscriptions || 0,
      icon: BuildingOfficeIcon,
      color: 'bg-pink-500',
      link: '/admin/subscriptions',
    },
  ];

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="md:flex md:items-center md:justify-between">
        <div className="flex-1 min-w-0">
          <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Admin Dashboard
          </h2>
          <p className="mt-1 text-sm text-gray-500">
            System overview and management
          </p>
        </div>
      </div>

      {/* System Health */}
      <div className="bg-white shadow rounded-lg p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">System Health</h3>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="flex items-center">
            <span className="text-sm font-medium text-gray-500">Database:</span>
            <span className={`ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
              systemHealth?.database_status === 'Connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`}>
              {systemHealth?.database_status === 'Connected' ? (
                <CheckCircleIcon className="mr-1 h-4 w-4" />
              ) : (
                <XCircleIcon className="mr-1 h-4 w-4" />
              )}
              {systemHealth?.database_status}
            </span>
          </div>
          <div className="flex items-center">
            <span className="text-sm font-medium text-gray-500">Environment:</span>
            <span className="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {systemHealth?.app_env}
            </span>
          </div>
          <div className="flex items-center">
            <span className="text-sm font-medium text-gray-500">PHP Version:</span>
            <span className="ml-2 text-sm text-gray-900">{systemHealth?.php_version}</span>
          </div>
          <div className="flex items-center">
            <span className="text-sm font-medium text-gray-500">Laravel:</span>
            <span className="ml-2 text-sm text-gray-900">{systemHealth?.laravel_version}</span>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {statCards.map((stat) => (
          <Link
            key={stat.name}
            to={stat.link}
            className="relative bg-white pt-5 px-4 pb-6 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
          >
            <dt>
              <div className={`absolute ${stat.color} rounded-md p-3`}>
                <stat.icon className="h-6 w-6 text-white" aria-hidden="true" />
              </div>
              <p className="ml-16 text-sm font-medium text-gray-500 truncate">{stat.name}</p>
            </dt>
            <dd className="ml-16 pb-6 flex items-baseline sm:pb-7">
              <p className="text-2xl font-semibold text-gray-900">{stat.value}</p>
            </dd>
          </Link>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
              to="/admin/users"
              className="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400"
            >
              <div className="flex-shrink-0">
                <UserGroupIcon className="h-8 w-8 text-blue-600" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900">Manage Users</p>
                <p className="text-xs text-gray-500">View all users</p>
              </div>
            </Link>

            <Link
              to="/admin/subscriptions"
              className="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400"
            >
              <div className="flex-shrink-0">
                <CreditCardIcon className="h-8 w-8 text-indigo-600" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900">Subscriptions</p>
                <p className="text-xs text-gray-500">Manage plans</p>
              </div>
            </Link>

            <Link
              to="/admin/payments"
              className="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400"
            >
              <div className="flex-shrink-0">
                <ChartBarIcon className="h-8 w-8 text-green-600" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900">Payments</p>
                <p className="text-xs text-gray-500">View history</p>
              </div>
            </Link>

            <Link
              to="/admin/settings"
              className="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400"
            >
              <div className="flex-shrink-0">
                <CheckCircleIcon className="h-8 w-8 text-purple-600" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900">Settings</p>
                <p className="text-xs text-gray-500">System config</p>
              </div>
            </Link>
          </div>
        </div>
      </div>

      {/* Latest Activity */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">Latest Activity</h3>
          <div className="flow-root">
            <ul className="-mb-8">
              {stats?.latest_activity?.map((activity, idx) => (
                <li key={activity.id}>
                  <div className={`relative ${idx !== stats.latest_activity.length - 1 ? 'pb-8' : ''}`}>
                    {idx !== stats.latest_activity.length - 1 && (
                      <span
                        className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                        aria-hidden="true"
                      />
                    )}
                    <div className="relative flex space-x-3">
                      <div>
                        <span className="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white">
                          <CheckCircleIcon className="h-5 w-5 text-white" aria-hidden="true" />
                        </span>
                      </div>
                      <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                        <div>
                          <p className="text-sm text-gray-500">
                            {activity.description || activity.action}
                          </p>
                        </div>
                        <div className="text-right text-sm whitespace-nowrap text-gray-500">
                          <time>{new Date(activity.created_at).toLocaleString()}</time>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>
              )) || (
                <li className="text-sm text-gray-500 text-center py-4">No recent activity</li>
              )}
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
}

