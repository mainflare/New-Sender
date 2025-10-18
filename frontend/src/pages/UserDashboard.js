import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useWorkspace } from '../contexts/WorkspaceContext';
import {
  ChatBubbleLeftRightIcon,
  UserGroupIcon,
  MegaphoneIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  ArrowTrendingUpIcon,
} from '@heroicons/react/24/outline';

export default function UserDashboard() {
  const { user } = useAuth();
  const { currentWorkspace } = useWorkspace();
  const [stats, setStats] = useState({
    totalConversations: 0,
    activeConversations: 0,
    totalContacts: 0,
    totalCampaigns: 0,
    sentMessages: 0,
    deliveredMessages: 0,
  });

  const [recentActivity, setRecentActivity] = useState([]);

  useEffect(() => {
    // Load dashboard data
    loadDashboardData();
  }, [currentWorkspace]);

  const loadDashboardData = async () => {
    try {
      // Mock data for now - replace with actual API calls
      setStats({
        totalConversations: 24,
        activeConversations: 8,
        totalContacts: 156,
        totalCampaigns: 3,
        sentMessages: 1247,
        deliveredMessages: 1189,
      });

      setRecentActivity([
        { id: 1, type: 'message', message: 'New message from John Doe', time: '2 minutes ago' },
        { id: 2, type: 'campaign', message: 'Campaign "Summer Sale" completed', time: '1 hour ago' },
        { id: 3, type: 'contact', message: '5 new contacts imported', time: '3 hours ago' },
      ]);
    } catch (error) {
      console.error('Error loading dashboard data:', error);
    }
  };

  const statCards = [
    {
      name: 'Active Conversations',
      value: stats.activeConversations,
      total: stats.totalConversations,
      icon: ChatBubbleLeftRightIcon,
      color: 'bg-blue-500',
      change: '+12%',
      changeType: 'positive',
    },
    {
      name: 'Total Contacts',
      value: stats.totalContacts,
      icon: UserGroupIcon,
      color: 'bg-green-500',
      change: '+8%',
      changeType: 'positive',
    },
    {
      name: 'Active Campaigns',
      value: stats.totalCampaigns,
      icon: MegaphoneIcon,
      color: 'bg-purple-500',
      change: '+2',
      changeType: 'positive',
    },
    {
      name: 'Messages Sent',
      value: stats.sentMessages,
      icon: ArrowTrendingUpIcon,
      color: 'bg-orange-500',
      change: '+15%',
      changeType: 'positive',
    },
  ];

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          Welcome back, {user?.name || 'User'}!
        </h1>
        <p className="text-gray-600 mt-2">
          Here's what's happening with your WhatsApp marketing today.
        </p>
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
                {stat.total && (
                  <p className="text-sm text-gray-500">of {stat.total} total</p>
                )}
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

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow p-6 mb-8">
        <h2 className="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Link
            to="/conversations"
            className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <ChatBubbleLeftRightIcon className="h-8 w-8 text-blue-500 mr-3" />
            <div>
              <h3 className="font-medium text-gray-900">Start Conversation</h3>
              <p className="text-sm text-gray-500">Send a new message</p>
            </div>
          </Link>
          
          <Link
            to="/campaigns"
            className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <MegaphoneIcon className="h-8 w-8 text-purple-500 mr-3" />
            <div>
              <h3 className="font-medium text-gray-900">Create Campaign</h3>
              <p className="text-sm text-gray-500">Launch a new campaign</p>
            </div>
          </Link>
          
          <Link
            to="/contacts"
            className="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <UserGroupIcon className="h-8 w-8 text-green-500 mr-3" />
            <div>
              <h3 className="font-medium text-gray-900">Manage Contacts</h3>
              <p className="text-sm text-gray-500">Import or organize contacts</p>
            </div>
          </Link>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-xl font-bold text-gray-900 mb-4">Recent Activity</h2>
        <div className="space-y-4">
          {recentActivity.map((activity) => (
            <div key={activity.id} className="flex items-center p-3 bg-gray-50 rounded-lg">
              <div className="flex-shrink-0">
                {activity.type === 'message' && (
                  <ChatBubbleLeftRightIcon className="h-5 w-5 text-blue-500" />
                )}
                {activity.type === 'campaign' && (
                  <MegaphoneIcon className="h-5 w-5 text-purple-500" />
                )}
                {activity.type === 'contact' && (
                  <UserGroupIcon className="h-5 w-5 text-green-500" />
                )}
              </div>
              <div className="ml-3 flex-1">
                <p className="text-sm font-medium text-gray-900">{activity.message}</p>
                <p className="text-sm text-gray-500">{activity.time}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
