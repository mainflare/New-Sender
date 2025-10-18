import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000, // 10 second timeout
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      // Only redirect if not already on login page
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

// Auth APIs
export const authAPI = {
  login: (credentials) => api.post('/login', credentials),
  register: (userData) => api.post('/register', userData),
  logout: () => api.post('/logout'),
  getUser: () => api.get('/user'),
  forgotPassword: (email) => api.post('/forgot-password', { email }),
  resetPassword: (data) => api.post('/reset-password', data),
};

// Workspace APIs
export const workspaceAPI = {
  getAll: () => api.get('/workspaces'),
  create: (data) => api.post('/workspaces', data),
  get: (id) => api.get(`/workspaces/${id}`),
  update: (id, data) => api.put(`/workspaces/${id}`, data),
  delete: (id) => api.delete(`/workspaces/${id}`),
  addMember: (id, memberData) => api.post(`/workspaces/${id}/add-member`, memberData),
  updateMember: (id, memberId, role) => api.put(`/workspaces/${id}/update-member/${memberId}`, { role }),
  removeMember: (id, memberId) => api.delete(`/workspaces/${id}/remove-member/${memberId}`),
};

// Contact APIs
export const contactAPI = {
  getAll: (params) => api.get('/contacts', { params }),
  create: (data) => api.post('/contacts', data),
  get: (id) => api.get(`/contacts/${id}`),
  update: (id, data) => api.put(`/contacts/${id}`, data),
  delete: (id) => api.delete(`/contacts/${id}`),
  import: (file) => {
    const formData = new FormData();
    formData.append('file', file);
    return api.post('/contacts/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  export: () => api.post('/contacts/export', {}, { responseType: 'blob' }),
  validateWhatsapp: (phoneNumber) => api.post('/contacts/validate-whatsapp', { phone_number: phoneNumber }),
  validateWhatsappBulk: (phoneNumbers) => api.post('/contacts/validate-whatsapp-bulk', { phone_numbers: phoneNumbers }),
};

// Audience APIs
export const audienceAPI = {
  getAll: () => api.get('/audiences'),
  create: (data) => api.post('/audiences', data),
  get: (id) => api.get(`/audiences/${id}`),
  update: (id, data) => api.put(`/audiences/${id}`, data),
  delete: (id) => api.delete(`/audiences/${id}`),
  addContacts: (id, contactIds) => api.post(`/audiences/${id}/add-contacts`, { contact_ids: contactIds }),
  removeContacts: (id, contactIds) => api.post(`/audiences/${id}/remove-contacts`, { contact_ids: contactIds }),
};

// WhatsApp Session APIs
export const whatsappAPI = {
  getAll: () => api.get('/whatsapp-sessions'),
  create: (data) => api.post('/whatsapp-sessions', data),
  get: (id) => api.get(`/whatsapp-sessions/${id}`),
  update: (id, data) => api.put(`/whatsapp-sessions/${id}`, data),
  delete: (id) => api.delete(`/whatsapp-sessions/${id}`),
  connect: (id) => api.post(`/whatsapp-sessions/${id}/connect`),
  disconnect: (id) => api.post(`/whatsapp-sessions/${id}/disconnect`),
  getQrCode: (id) => api.get(`/whatsapp-sessions/${id}/qr-code`),
  getStatus: (id) => api.get(`/whatsapp-sessions/${id}/status`),
  sendMessage: (id, data) => api.post(`/whatsapp-sessions/${id}/send-message`, data),
};

// Campaign APIs
export const campaignAPI = {
  getAll: (params) => api.get('/campaigns', { params }),
  create: (data) => api.post('/campaigns', data),
  get: (id) => api.get(`/campaigns/${id}`),
  update: (id, data) => api.put(`/campaigns/${id}`, data),
  delete: (id) => api.delete(`/campaigns/${id}`),
  send: (id) => api.post(`/campaigns/${id}/send`),
  schedule: (id, scheduledAt) => api.post(`/campaigns/${id}/schedule`, { scheduled_at: scheduledAt }),
  cancel: (id) => api.post(`/campaigns/${id}/cancel`),
  getReport: (id) => api.get(`/campaigns/${id}/report`),
};

// Conversation APIs
export const conversationAPI = {
  getAll: (params) => api.get('/conversations', { params }),
  get: (id) => api.get(`/conversations/${id}`),
  getMessages: (id) => api.get(`/conversations/${id}/messages`),
  sendMessage: (id, data) => api.post(`/conversations/${id}/send-message`, data),
  assign: (id, userId) => api.post(`/conversations/${id}/assign`, { user_id: userId }),
  addLabel: (id, labelId) => api.post(`/conversations/${id}/labels`, { label_id: labelId }),
};

// Chatbot APIs
export const chatbotAPI = {
  getAll: () => api.get('/chatbots'),
  create: (data) => api.post('/chatbots', data),
  get: (id) => api.get(`/chatbots/${id}`),
  update: (id, data) => api.put(`/chatbots/${id}`, data),
  delete: (id) => api.delete(`/chatbots/${id}`),
  train: (id, trainingData) => api.post(`/chatbots/${id}/train`, { training_data: trainingData }),
  test: (id, message) => api.post(`/chatbots/${id}/test`, { message }),
  getTrainingData: (id) => api.get(`/chatbots/${id}/training-data`),
};

// Template APIs
export const templateAPI = {
  getAll: () => api.get('/templates'),
  create: (data) => api.post('/templates', data),
  get: (id) => api.get(`/templates/${id}`),
  update: (id, data) => api.put(`/templates/${id}`, data),
  delete: (id) => api.delete(`/templates/${id}`),
  use: (id) => api.post(`/templates/${id}/use`),
};

// Analytics APIs
export const analyticsAPI = {
  getDashboard: (params) => api.get('/analytics/dashboard', { params }),
  getCampaignAnalytics: (params) => api.get('/analytics/campaigns', { params }),
  getMessageAnalytics: (params) => api.get('/analytics/messages', { params }),
  getAdminStats: () => api.get('/analytics/admin'),
};

// Notification APIs
export const notificationAPI = {
  getAll: (params) => api.get('/notifications', { params }),
  getUnreadCount: () => api.get('/notifications/unread-count'),
  markAsRead: (id) => api.post(`/notifications/${id}/read`),
  markAllAsRead: () => api.post('/notifications/read-all'),
  delete: (id) => api.delete(`/notifications/${id}`),
  deleteAll: () => api.delete('/notifications'),
};

// Admin APIs
export const adminAPI = {
  getDashboard: () => api.get('/admin/dashboard'),
  getUsers: (params) => api.get('/admin/users', { params }),
  getUser: (id) => api.get(`/admin/users/${id}`),
  updateUser: (id, data) => api.put(`/admin/users/${id}`, data),
  toggleUserStatus: (id) => api.post(`/admin/users/${id}/toggle-status`),
  deleteUser: (id) => api.delete(`/admin/users/${id}`),
  getSubscriptions: (params) => api.get('/admin/subscriptions', { params }),
  cancelSubscription: (id) => api.post(`/admin/subscriptions/${id}/cancel`),
  getPayments: (params) => api.get('/admin/payments', { params }),
  getSettings: () => api.get('/admin/settings'),
  updateSettings: (data) => api.put('/admin/settings', data),
  getSystemHealth: () => api.get('/admin/system-health'),
  getAuditLogs: (params) => api.get('/admin/audit-logs', { params }),
};

// Activity Log APIs
export const activityLogAPI = {
  getAll: (params) => api.get('/activity-logs', { params }),
  getStatistics: (params) => api.get('/activity-logs/statistics', { params }),
};

// Asset APIs
export const assetAPI = {
  getAll: (params) => api.get('/assets', { params }),
  upload: (file, workspaceId) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('workspace_id', workspaceId);
    return api.post('/assets', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  get: (id) => api.get(`/assets/${id}`),
  delete: (id) => api.delete(`/assets/${id}`),
  getStatistics: (params) => api.get('/assets/stats/workspace', { params }),
};

// Subscription APIs
export const subscriptionAPI = {
  getPlans: () => api.get('/subscriptions/plans'),
  getCurrent: () => api.get('/subscriptions/current'),
  subscribe: (data) => api.post('/subscriptions/subscribe', data),
  change: (data) => api.put('/subscriptions/change', data),
  cancel: () => api.post('/subscriptions/cancel'),
  checkLimits: () => api.get('/subscriptions/limits'),
};

// Payment APIs
export const paymentAPI = {
  createIntent: (amount, currency) => api.post('/payments/create-intent', { amount, currency }),
  process: (data) => api.post('/payments/process', data),
  getHistory: (params) => api.get('/payments/history', { params }),
};

// Google Maps Scraper APIs
export const scraperAPI = {
  searchPlaces: (query, options) => api.post('/scraper/search-places', { query, ...options }),
  getPlaceDetails: (placeId) => api.post('/scraper/place-details', { place_id: placeId }),
  searchNearby: (latitude, longitude, radius, options) => 
    api.post('/scraper/search-nearby', { latitude, longitude, radius, ...options }),
  scrapeAndSave: (query, options) => api.post('/scraper/scrape-and-save', { query, ...options }),
  scrapeSingle: (placeId) => api.post('/scraper/scrape-single', { place_id: placeId }),
};

export default api;

