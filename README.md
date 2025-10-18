# WhatsML – AI-Based Marketing & Chat Automation & Bulk Sender Tools for WhatsApp (SaaS)

## 🚀 Overview

WhatsML is a comprehensive SaaS platform for WhatsApp marketing automation, AI-powered chatbots, and bulk messaging. Built with Laravel (backend), Node.js (WhatsApp service), and designed for scalability from 10 to 10,000+ users.

## ✨ Key Features

### 🤖 AI Tools & Chatbots
- NLP Chatbot powered by OpenAI GPT & Google Gemini
- Keyword Auto Responders
- Fine-Tuning capabilities
- Smart Templates & Quick Replies

### 📱 WhatsApp Integration
- **WhatsApp Web API** (Unofficial) - QR Code/Pairing Code login
- **WhatsApp Cloud API** (Official Meta API)
- Multi-account support
- Anti-Ban warm-up system
- Real-time live chat interface

### 📊 Campaign Management
- Bulk & Scheduled messaging
- Drip campaigns
- Official & Custom templates
- Multimedia support (Images, Videos, Audio, Documents)
- Campaign analytics & tracking

### 👥 Contact & Audience Management
- Contact list management
- Audience segmentation & grouping
- Tag system
- Import/Export functionality

### 🔍 Lead Generation Tools
- **Google Maps Scraper** - Extract business contacts
- **WhatsApp Number Validator** - Check if numbers are registered
- Lead scoring & tracking

### 💬 Conversation Management
- Real-time chat interface
- Conversation labels & badges
- Comments & notes
- Team member assignment
- Smart search

### 💼 Business Suite
- Multi-workspace support
- Team roles & permissions (Owner, Admin, Manager, Agent)
- Asset Manager for media files
- Activity logs
- Real-time notifications

### 🔌 Developer Tools
- RESTful API
- Webhooks
- API documentation
- Third-party integrations

### 💳 Payment Integration
- Stripe
- PayPal
- Coingate (Crypto payments)

## 📋 System Requirements

- PHP >= 8.2
- Node.js >= 18
- MySQL/MariaDB
- Redis
- Composer
- npm/yarn

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd New-Sender
```

### 2. Backend Setup (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### 3. WhatsApp Service Setup (Node.js)
```bash
cd whatsapp-service
npm install
npm start
```

### 4. Frontend Setup (Coming Soon)
```bash
cd frontend
npm install
npm run dev
```

## 🔧 Configuration

### Backend (.env)
```env
DB_CONNECTION=mysql
DB_DATABASE=whatsml
WHATSAPP_SERVICE_URL=http://localhost:3000
OPENAI_API_KEY=your_key
GOOGLE_GEMINI_API_KEY=your_key
STRIPE_KEY=your_key
STRIPE_SECRET=your_secret
```

### WhatsApp Service (.env)
```env
PORT=3000
LARAVEL_API_URL=http://localhost:8000/api
```

## 📚 Documentation

### Database Schema
- 20 comprehensive tables
- Full relationship mapping
- Soft deletes support
- Optimized indexes

### Models Created
- User, Workspace, WorkspaceMember
- Subscription, Payment
- WhatsappSession, Contact, Audience
- Conversation, Message
- Campaign, Template
- Chatbot, ChatbotTrainingData
- ActivityLog, Asset, AutomationRule
- ConversationLabel, QuickReply
- Notification

### WhatsApp Service API

#### Session Management
```javascript
POST /api/whatsapp/session/create
POST /api/whatsapp/session/destroy
GET /api/whatsapp/session/status/:sessionId
GET /api/whatsapp/session/qr/:sessionId
```

#### Messaging
```javascript
POST /api/messages/send
POST /api/messages/send-bulk
POST /api/messages/send-media
GET /api/messages/conversation/:phone
```

#### Number Validation
```javascript
POST /api/whatsapp/validate-number
POST /api/whatsapp/validate-numbers-bulk
```

## 🏗️ Architecture

```
┌─────────────────┐
│   Frontend      │
│  (React/Vue)    │
└────────┬────────┘
         │
         ├── HTTP/REST
         │
┌────────▼────────────────┐
│   Laravel Backend       │
│  - API Endpoints        │
│  - Business Logic       │
│  - Database Management  │
│  - Queue Management     │
└────────┬────────────────┘
         │
         ├── HTTP/WebSocket
         │
┌────────▼────────────────┐
│  WhatsApp Service       │
│  (Node.js + Socket.io)  │
│  - Session Management   │
│  - Message Handling     │
│  - Real-time Events     │
└────────┬────────────────┘
         │
         ├── whatsapp-web.js
         │
┌────────▼────────────────┐
│   WhatsApp Web          │
└─────────────────────────┘
```

## 🎯 Use Cases

- **SaaS Founders & Startups** - Launch WhatsApp marketing platform
- **Marketing Agencies** - Manage multiple clients
- **Lead Generation Companies** - Automate outreach
- **E-commerce Businesses** - Customer engagement
- **Support Teams** - Multi-agent chat management

## 📊 Subscription Plans

### Free
- 1 Workspace
- 100 Contacts
- 10 Campaigns/month
- 100 Messages/day

### Starter
- 3 Workspaces
- 1,000 Contacts
- 50 Campaigns/month
- 500 Messages/day
- Basic AI Chatbot

### Professional
- 10 Workspaces
- 10,000 Contacts
- Unlimited Campaigns
- 2,000 Messages/day
- Advanced AI Chatbot
- Google Maps Scraper

### Enterprise
- Unlimited Workspaces
- Unlimited Contacts
- Unlimited Campaigns
- Unlimited Messages
- Full AI Features
- API Access
- Priority Support

## 🔒 Security Features

- API authentication with Laravel Sanctum
- Rate limiting
- Anti-ban protection
- Message throttling
- Encrypted credentials
- Activity logging

## 🤝 Contributing

This is a commercial project. For contribution guidelines, please contact the maintainers.

## 📝 License

Proprietary - All rights reserved

## 📧 Support

For support and inquiries, please contact:
- Email: support@whatsml.com
- Website: https://whatsml.com

## 🎯 Roadmap

- [x] Project setup & infrastructure
- [x] Database design & migrations
- [x] WhatsApp Service (Node.js)
- [ ] Authentication system
- [ ] API endpoints
- [ ] Frontend UI/UX
- [ ] Payment integration
- [ ] AI chatbot functionality
- [ ] Google Maps scraper
- [ ] Campaign automation
- [ ] Admin dashboard
- [ ] Documentation
- [ ] Testing
- [ ] Deployment

---

**Version**: 1.0.0  
**Last Updated**: October 2025  
**Status**: 🚧 Active Development

