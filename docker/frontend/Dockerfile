# =============================================================================
# Stage 1: Node dependency install
# =============================================================================
FROM node:20-alpine AS deps

WORKDIR /app

# Copy package files first — layer cache optimization
COPY frontend/package.json frontend/package-lock.json* ./

RUN npm ci --frozen-lockfile

# =============================================================================
# Stage 2: Build (production)
# =============================================================================
FROM deps AS builder

COPY frontend/ .

# Inject build-time env. Runtime env is handled via window.__ENV__ in dev.
ARG VITE_API_BASE_URL
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}

RUN npm run build

# =============================================================================
# Stage 3: Production serving via Nginx
# =============================================================================
FROM nginx:1.25-alpine AS production

COPY --from=builder /app/dist /usr/share/nginx/html
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

# =============================================================================
# Stage 4: Development — Vite dev server (hot reload)
# =============================================================================
FROM node:20-alpine AS development

WORKDIR /app

COPY --from=deps /app/node_modules ./node_modules
COPY frontend/ .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
