-- Habilita a extensão pgvector no banco padrão (rodado no primeiro boot via
-- /docker-entrypoint-initdb.d). Idempotente: não falha se já existir.
CREATE EXTENSION IF NOT EXISTS vector;
