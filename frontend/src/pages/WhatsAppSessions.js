import React, { useState, useEffect } from 'react';
import { whatsappAPI } from '../services/api';
import { useWorkspace } from '../contexts/WorkspaceContext';
import toast from 'react-hot-toast';
import {
  PlusIcon,
  QrCodeIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';

export default function WhatsAppSessions() {
  const { currentWorkspace } = useWorkspace();
  const [sessions, setSessions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedSession, setSelectedSession] = useState(null);
  const [qrCode, setQrCode] = useState(null);

  useEffect(() => {
    if (currentWorkspace) {
      loadSessions();
    }
  }, [currentWorkspace]);

  const loadSessions = async () => {
    try {
      setLoading(true);
      const response = await whatsappAPI.getAll();
      setSessions(response.data.data || response.data);
    } catch (error) {
      console.error('Failed to load sessions:', error);
      toast.error('Failed to load WhatsApp sessions');
    } finally {
      setLoading(false);
    }
  };

  const handleConnect = async (sessionId) => {
    try {
      await whatsappAPI.connect(sessionId);
      toast.success('Connecting to WhatsApp...');
      
      // Poll for QR code
      const interval = setInterval(async () => {
        try {
          const response = await whatsappAPI.getQrCode(sessionId);
          if (response.data.qr_code) {
            setQrCode(response.data.qr_code);
            setSelectedSession(sessionId);
            clearInterval(interval);
          } else if (response.data.status === 'connected') {
            clearInterval(interval);
            toast.success('Connected successfully!');
            loadSessions();
          }
        } catch (error) {
          console.error('Error fetching QR code:', error);
        }
      }, 2000);

      // Stop polling after 2 minutes
      setTimeout(() => clearInterval(interval), 120000);
    } catch (error) {
      console.error('Failed to connect:', error);
      toast.error('Failed to connect to WhatsApp');
    }
  };

  const handleDisconnect = async (sessionId) => {
    try {
      await whatsappAPI.disconnect(sessionId);
      toast.success('Disconnected successfully');
      loadSessions();
    } catch (error) {
      console.error('Failed to disconnect:', error);
      toast.error('Failed to disconnect');
    }
  };

  const handleCreateSession = async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
      const data = {
        session_name: formData.get('session_name'),
        type: formData.get('type'),
        workspace_id: currentWorkspace.id,
      };

      if (data.type === 'cloud_api') {
        data.meta_business_id = formData.get('meta_business_id');
        data.access_token = formData.get('access_token');
        data.phone_number = formData.get('phone_number');
      }

      await whatsappAPI.create(data);
      toast.success('Session created successfully');
      setShowCreateModal(false);
      loadSessions();
    } catch (error) {
      console.error('Failed to create session:', error);
      toast.error('Failed to create session');
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'connected':
        return 'bg-green-100 text-green-800';
      case 'disconnected':
        return 'bg-gray-100 text-gray-800';
      case 'qr_code':
      case 'pairing_code':
        return 'bg-yellow-100 text-yellow-800';
      case 'authenticating':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
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
          <h1 className="text-2xl font-bold text-gray-900">WhatsApp Sessions</h1>
          <p className="mt-2 text-sm text-gray-700">
            Manage your WhatsApp Web API and Cloud API connections
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowCreateModal(true)}
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
            Add Session
          </button>
        </div>
      </div>

      {/* Sessions Grid */}
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {sessions.map((session) => (
          <div key={session.id} className="bg-white overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">{session.session_name}</h3>
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(session.status)}`}>
                  {session.status === 'connected' && <CheckCircleIcon className="mr-1 h-4 w-4" />}
                  {session.status === 'disconnected' && <XCircleIcon className="mr-1 h-4 w-4" />}
                  {session.status}
                </span>
              </div>

              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-500">Type:</span>
                  <span className="text-gray-900 font-medium">
                    {session.type === 'web_api' ? 'Web API' : 'Cloud API'}
                  </span>
                </div>
                {session.phone_number && (
                  <div className="flex justify-between">
                    <span className="text-gray-500">Phone:</span>
                    <span className="text-gray-900">{session.phone_number}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-gray-500">Created:</span>
                  <span className="text-gray-900">
                    {new Date(session.created_at).toLocaleDateString()}
                  </span>
                </div>
              </div>

              <div className="mt-6 flex gap-2">
                {session.status === 'disconnected' ? (
                  <button
                    onClick={() => handleConnect(session.id)}
                    className="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                  >
                    <QrCodeIcon className="mr-2 h-4 w-4" />
                    Connect
                  </button>
                ) : (
                  <button
                    onClick={() => handleDisconnect(session.id)}
                    className="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                  >
                    <XCircleIcon className="mr-2 h-4 w-4" />
                    Disconnect
                  </button>
                )}
                <button
                  onClick={() => loadSessions()}
                  className="px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  <ArrowPathIcon className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* QR Code Modal */}
      {qrCode && (
        <div className="fixed z-10 inset-0 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setQrCode(null)} />
            
            <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
              <div>
                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                  <QrCodeIcon className="h-6 w-6 text-green-600" />
                </div>
                <div className="mt-3 text-center sm:mt-5">
                  <h3 className="text-lg leading-6 font-medium text-gray-900">Scan QR Code</h3>
                  <div className="mt-4">
                    <img src={qrCode} alt="QR Code" className="mx-auto" />
                  </div>
                  <p className="mt-4 text-sm text-gray-500">
                    Open WhatsApp on your phone and scan this QR code to connect
                  </p>
                </div>
              </div>
              <div className="mt-5 sm:mt-6">
                <button
                  type="button"
                  onClick={() => setQrCode(null)}
                  className="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Create Session Modal */}
      {showCreateModal && (
        <div className="fixed z-10 inset-0 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setShowCreateModal(false)} />
            
            <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
              <form onSubmit={handleCreateSession}>
                <div>
                  <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Create WhatsApp Session
                  </h3>
                  
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Session Name</label>
                      <input
                        type="text"
                        name="session_name"
                        required
                        className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700">Type</label>
                      <select
                        name="type"
                        required
                        className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"
                      >
                        <option value="web_api">Web API (QR Code)</option>
                        <option value="cloud_api">Cloud API (Official)</option>
                      </select>
                    </div>

                    <div id="cloud-api-fields" className="hidden space-y-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Business ID</label>
                        <input
                          type="text"
                          name="meta_business_id"
                          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Access Token</label>
                        <input
                          type="text"
                          name="access_token"
                          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input
                          type="text"
                          name="phone_number"
                          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                  <button
                    type="submit"
                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm"
                  >
                    Create
                  </button>
                  <button
                    type="button"
                    onClick={() => setShowCreateModal(false)}
                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {sessions.length === 0 && (
        <div className="text-center py-12">
          <QrCodeIcon className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-medium text-gray-900">No WhatsApp sessions</h3>
          <p className="mt-1 text-sm text-gray-500">Get started by creating a new session.</p>
          <div className="mt-6">
            <button
              onClick={() => setShowCreateModal(true)}
              className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
            >
              <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
              Add Session
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

