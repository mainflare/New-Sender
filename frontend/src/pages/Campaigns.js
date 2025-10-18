import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { campaignAPI } from '../services/api';
import { useWorkspace } from '../contexts/WorkspaceContext';
import toast from 'react-hot-toast';
import {
  PlusIcon,
  MegaphoneIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon,
  PlayIcon,
} from '@heroicons/react/24/outline';

export default function Campaigns() {
  const { currentWorkspace } = useWorkspace();
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');

  useEffect(() => {
    if (currentWorkspace) {
      loadCampaigns();
    }
  }, [currentWorkspace, filter]);

  const loadCampaigns = async () => {
    try {
      setLoading(true);
      const params = {
        workspace_id: currentWorkspace.id,
      };
      if (filter !== 'all') {
        params.status = filter;
      }
      const response = await campaignAPI.getAll(params);
      setCampaigns(response.data.data || response.data);
    } catch (error) {
      console.error('Failed to load campaigns:', error);
      toast.error('Failed to load campaigns');
    } finally {
      setLoading(false);
    }
  };

  const handleSendCampaign = async (id) => {
    try {
      await campaignAPI.send(id);
      toast.success('Campaign started!');
      loadCampaigns();
    } catch (error) {
      console.error('Failed to send campaign:', error);
      toast.error('Failed to send campaign');
    }
  };

  const handleCancelCampaign = async (id) => {
    try {
      await campaignAPI.cancel(id);
      toast.success('Campaign cancelled');
      loadCampaigns();
    } catch (error) {
      console.error('Failed to cancel campaign:', error);
      toast.error('Failed to cancel campaign');
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      draft: { bg: 'bg-gray-100', text: 'text-gray-800', icon: ClockIcon },
      scheduled: { bg: 'bg-blue-100', text: 'text-blue-800', icon: ClockIcon },
      sending: { bg: 'bg-yellow-100', text: 'text-yellow-800', icon: PlayIcon },
      completed: { bg: 'bg-green-100', text: 'text-green-800', icon: CheckCircleIcon },
      cancelled: { bg: 'bg-red-100', text: 'text-red-800', icon: XCircleIcon },
    };
    const badge = badges[status] || badges.draft;
    const Icon = badge.icon;

    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.bg} ${badge.text}`}>
        <Icon className="mr-1 h-4 w-4" />
        {status}
      </span>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Campaigns</h1>
          <p className="mt-2 text-sm text-gray-700">
            Create and manage your marketing campaigns
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <Link
            to="/campaigns/create"
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
            Create Campaign
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="flex space-x-4">
            {['all', 'draft', 'scheduled', 'sending', 'completed', 'cancelled'].map((status) => (
              <button
                key={status}
                onClick={() => setFilter(status)}
                className={`px-4 py-2 text-sm font-medium rounded-lg ${
                  filter === status
                    ? 'bg-green-100 text-green-800'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                }`}
              >
                {status.charAt(0).toUpperCase() + status.slice(1)}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Campaigns List */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <ul className="divide-y divide-gray-200">
          {campaigns.length === 0 ? (
            <li className="px-6 py-12 text-center">
              <MegaphoneIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900">No campaigns</h3>
              <p className="mt-1 text-sm text-gray-500">Get started by creating a new campaign.</p>
              <div className="mt-6">
                <Link
                  to="/campaigns/create"
                  className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                >
                  <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                  New Campaign
                </Link>
              </div>
            </li>
          ) : (
            campaigns.map((campaign) => (
              <li key={campaign.id}>
                <div className="px-4 py-4 sm:px-6 hover:bg-gray-50">
                  <div className="flex items-center justify-between">
                    <div className="flex-1">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-green-600 truncate">{campaign.name}</p>
                        <div className="ml-2 flex-shrink-0">
                          {getStatusBadge(campaign.status)}
                        </div>
                      </div>
                      <div className="mt-2 sm:flex sm:justify-between">
                        <div className="sm:flex">
                          <p className="flex items-center text-sm text-gray-500">
                            <MegaphoneIcon className="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" />
                            {campaign.type === 'bulk' ? 'Bulk Campaign' : 'Drip Campaign'}
                          </p>
                        </div>
                        <div className="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                          <p>
                            {campaign.sent_count} / {campaign.total_recipients} sent
                          </p>
                        </div>
                      </div>
                      {campaign.message_content && (
                        <div className="mt-2">
                          <p className="text-sm text-gray-600 line-clamp-2">{campaign.message_content}</p>
                        </div>
                      )}
                      <div className="mt-3 flex items-center gap-4 text-sm text-gray-500">
                        <span className="flex items-center">
                          <CheckCircleIcon className="mr-1 h-4 w-4 text-green-500" />
                          Delivered: {campaign.delivered_count}
                        </span>
                        <span className="flex items-center">
                          <CheckCircleIcon className="mr-1 h-4 w-4 text-blue-500" />
                          Read: {campaign.read_count}
                        </span>
                        <span className="flex items-center">
                          <XCircleIcon className="mr-1 h-4 w-4 text-red-500" />
                          Failed: {campaign.failed_count}
                        </span>
                      </div>
                    </div>
                    <div className="ml-4 flex-shrink-0 flex gap-2">
                      {campaign.status === 'draft' && (
                        <button
                          onClick={() => handleSendCampaign(campaign.id)}
                          className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                        >
                          <PlayIcon className="mr-1 h-4 w-4" />
                          Send
                        </button>
                      )}
                      {(campaign.status === 'sending' || campaign.status === 'scheduled') && (
                        <button
                          onClick={() => handleCancelCampaign(campaign.id)}
                          className="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                          Cancel
                        </button>
                      )}
                      <Link
                        to={`/campaigns/${campaign.id}`}
                        className="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                      >
                        View
                      </Link>
                    </div>
                  </div>
                </div>
              </li>
            ))
          )}
        </ul>
      </div>
    </div>
  );
}

