FROM node:18-alpine

# Install chromium and dependencies for whatsapp-web.js
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    nodejs \
    yarn

# Tell Puppeteer to skip installing Chrome. We'll be using the installed package.
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Create app directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy application code
COPY . .

# Create directories for WhatsApp sessions
RUN mkdir -p .wwebjs_auth .wwebjs_cache && \
    chmod -R 777 .wwebjs_auth .wwebjs_cache

# Expose port
EXPOSE 3000

# Start the application
CMD ["npm", "start"]


