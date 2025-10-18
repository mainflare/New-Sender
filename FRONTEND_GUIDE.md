# WhatsML Frontend Guide

## 🎨 Frontend Overview

The WhatsML frontend is built with **React** and provides a modern, responsive dashboard for managing your WhatsApp marketing automation.

### Technology Stack
- **React 18** - UI library
- **React Router v6** - Routing
- **Tailwind CSS** - Styling
- **Axios** - API communication
- **Socket.io Client** - Real-time updates
- **React Hot Toast** - Notifications
- **Headless UI** - Accessible components
- **Heroicons** - Icon library

---

## 🚀 Getting Started

### Prerequisites
- Node.js 14+ and npm
- Backend API running on `http://localhost:8000`
- WhatsApp Service running on `http://localhost:3000`

### Installation

```bash
cd frontend
npm install
```

### Configuration

Create a `.env` file in the `frontend` directory:

```env
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_WHATSAPP_SERVICE_URL=http://localhost:3000/api
REACT_APP_SOCKET_URL=http://localhost:3000
```

### Running the Frontend

```bash
# Start development server
npm start

# Build for production
npm run build

# Run tests
npm test
```

The frontend will be available at: **http://localhost:3001**

---

## 📂 Project Structure

```
frontend/
├── public/               # Static files
├── src/
│   ├── components/       # Reusable components
│   │   └── Layout/       # Layout components
│   ├── contexts/         # React contexts (Auth, Workspace)
│   ├── pages/            # Page components
│   │   ├── admin/        # Admin-only pages
│   │   ├── Dashboard.js
│   │   ├── Login.js
│   │   ├── Register.js
│   │   ├── WhatsAppSessions.js
│   │   ├── Campaigns.js
│   │   ├── Contacts.js
│   │   └── ...
│   ├── services/         # API service layer
│   │   └── api.js        # API client and endpoints
│   ├── App.js            # Main app component with routing
│   ├── index.js          # Entry point
│   └── index.css         # Global styles (Tailwind)
└── package.json
```

---

## 🔐 Authentication Flow

### Login Process
1. User enters credentials on `/login`
2. Frontend sends POST to `/api/login`
3. Backend returns JWT token + user data
4. Token stored in `localStorage`
5. User redirected to `/dashboard`

### Protected Routes
- All routes except `/login` and `/register` require authentication
- Admin routes require `role: 'super_admin'`
- Token automatically added to all API requests via Axios interceptor

### Context Providers
- **AuthContext**: Manages user authentication state
- **WorkspaceContext**: Manages current workspace selection

---

## 📄 Main Features & Pages

### 1. **Dashboard** (`/dashboard`)
- Analytics overview
- Quick action cards
- Recent activity feed
- Statistics widgets

### 2. **WhatsApp Sessions** (`/whatsapp`)
- Connect/disconnect WhatsApp accounts
- QR code display for Web API
- Cloud API configuration
- Session status monitoring

### 3. **Campaigns** (`/campaigns`)
- Create bulk and drip campaigns
- Campaign status tracking
- Analytics (sent, delivered, read, failed)
- Schedule and cancel campaigns

### 4. **Contacts** (`/contacts`)
- Contact list management
- Import/export CSV
- WhatsApp number validation
- Search and filter contacts

### 5. **Conversations** (`/conversations`)
- Real-time chat interface (Coming Soon)
- Message threading
- Media support
- Agent assignment

### 6. **Chatbots** (`/chatbots`)
- AI chatbot configuration (Coming Soon)
- Training data management
- NLP model selection (OpenAI/Gemini)

### 7. **Analytics** (`/analytics`)
- Campaign performance metrics (Coming Soon)
- Message analytics
- Conversion tracking

### 8. **Settings** (`/settings`)
- User profile settings (Coming Soon)
- Workspace configuration
- Team member management

---

## 👑 Admin Panel

Admin-only routes (requires `super_admin` role):

### Admin Dashboard (`/admin`)
- System health monitoring
- User statistics
- Revenue overview
- Subscription metrics

### User Management (`/admin/users`)
- View all users (Coming Soon)
- Edit user details
- Toggle user status
- Delete users

### Subscriptions (`/admin/subscriptions`)
- Manage subscription plans (Coming Soon)
- Cancel subscriptions
- View payment history

### System Settings (`/admin/settings`)
- Global configuration (Coming Soon)
- SMTP settings
- Feature toggles

---

## 🎨 UI Components

### Layout Components

#### DashboardLayout
- Responsive sidebar navigation
- Top navigation bar with workspace selector
- User dropdown menu
- Notification bell
- Mobile-responsive hamburger menu

### Reusable Patterns

#### Modal Pattern
```jsx
{showModal && (
  <div className="fixed z-10 inset-0 overflow-y-auto">
    <div className="flex items-end justify-center min-h-screen">
      <div className="fixed inset-0 bg-gray-500 bg-opacity-75" onClick={() => setShowModal(false)} />
      <div className="inline-block bg-white rounded-lg shadow-xl">
        {/* Modal content */}
      </div>
    </div>
  </div>
)}
```

#### Loading State
```jsx
{loading ? (
  <div className="flex items-center justify-center h-64">
    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
  </div>
) : (
  // Content
)}
```

---

## 🔌 API Integration

### API Service Structure

```javascript
// services/api.js
import axios from 'axios';

// Axios instance with base configuration
const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor (adds auth token)
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor (handles 401 errors)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Redirect to login
    }
    return Promise.reject(error);
  }
);
```

### Available API Modules
- `authAPI` - Authentication
- `workspaceAPI` - Workspace management
- `contactAPI` - Contact operations
- `campaignAPI` - Campaign management
- `whatsappAPI` - WhatsApp sessions
- `conversationAPI` - Chat conversations
- `chatbotAPI` - Chatbot configuration
- `templateAPI` - Message templates
- `analyticsAPI` - Analytics data
- `notificationAPI` - Notifications
- `adminAPI` - Admin operations
- `scraperAPI` - Google Maps scraper

---

## 🎯 State Management

### Context API

#### Auth Context
```javascript
const { user, token, login, logout, isSuperAdmin, isAdmin } = useAuth();
```

#### Workspace Context
```javascript
const { 
  workspaces,
  currentWorkspace,
  selectWorkspace,
  createWorkspace,
  updateWorkspace,
  deleteWorkspace 
} = useWorkspace();
```

---

## 🔔 Notifications

Using `react-hot-toast`:

```javascript
import toast from 'react-hot-toast';

// Success
toast.success('Campaign created successfully!');

// Error
toast.error('Failed to send message');

// Loading
const toastId = toast.loading('Processing...');
// Later
toast.success('Done!', { id: toastId });
```

---

## 🎨 Styling Guidelines

### Tailwind Utility Classes

#### Colors
- Primary: `green-600` (buttons, links)
- Success: `green-100/800`
- Error: `red-100/800`
- Warning: `yellow-100/800`
- Info: `blue-100/800`

#### Common Patterns
```jsx
// Button (Primary)
<button className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
  Click Me
</button>

// Card
<div className="bg-white shadow rounded-lg p-6">
  {/* Content */}
</div>

// Input
<input className="block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" />
```

---

## 🧪 Testing

### Running Tests

```bash
npm test
```

### Test Structure
```
frontend/
├── src/
│   ├── __tests__/
│   │   ├── components/
│   │   ├── pages/
│   │   └── services/
│   └── setupTests.js
```

---

## 🚀 Deployment

### Build for Production

```bash
npm run build
```

This creates an optimized build in the `build/` directory.

### Deployment Options

#### Static Hosting (Netlify, Vercel)
```bash
# Deploy build folder to static hosting
npm run build
# Upload build/ directory
```

#### Docker
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --production
COPY . .
RUN npm run build
CMD ["npx", "serve", "-s", "build", "-l", "3001"]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/whatsml/build;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://localhost:8000;
    }
}
```

---

## 🐛 Troubleshooting

### Common Issues

#### "Failed to connect to API"
- Check backend is running on port 8000
- Verify `.env` file has correct `REACT_APP_API_URL`
- Check CORS settings in Laravel backend

#### "401 Unauthorized"
- Clear localStorage: `localStorage.clear()`
- Re-login to get fresh token
- Check token expiration time

#### WhatsApp QR Code not showing
- Ensure WhatsApp service is running on port 3000
- Check WebSocket connection
- Verify session creation in backend

#### Styles not loading
- Run `npm install` to ensure Tailwind is installed
- Check `tailwind.config.js` exists
- Verify PostCSS configuration

---

## 📚 Additional Resources

- [React Documentation](https://react.dev)
- [Tailwind CSS Docs](https://tailwindcss.com)
- [React Router](https://reactrouter.com)
- [Axios](https://axios-http.com)

---

## 🤝 Contributing

When adding new features:

1. Create component in appropriate directory
2. Add route in `App.js`
3. Create API endpoint in `services/api.js`
4. Update navigation in `DashboardLayout.js`
5. Test thoroughly
6. Update this documentation

---

## 📞 Support

For issues and questions:
- Check backend logs: `tail -f /tmp/whatsml-backend.log`
- Check frontend console in browser DevTools
- Review API responses in Network tab

---

**Happy Coding! 🚀**

