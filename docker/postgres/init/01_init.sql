-- =============================================================================
-- PostgreSQL Init Script — SLMS
-- Runs once on first container creation (via docker-entrypoint-initdb.d)
-- =============================================================================

-- Create a separate test database so tests don't touch development data
CREATE DATABASE slms_test
    WITH
    OWNER = slms
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.utf8'
    LC_CTYPE = 'en_US.utf8'
    TEMPLATE = template0;

-- Enable UUID generation extension in both databases
\c slms
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- for LIKE search performance

\c slms_test
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
