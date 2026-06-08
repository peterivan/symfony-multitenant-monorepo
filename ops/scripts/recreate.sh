#!/bin/sh

set -eu

central_db="${POSTGRES_DB:-central}"

psql_base="psql -v ON_ERROR_STOP=1 --username ${POSTGRES_USER} --dbname postgres"

echo "Dropping non-system databases in the cluster..."
$psql_base <<'SQL'
SELECT format(
  'DROP DATABASE IF EXISTS %I WITH (FORCE);',
  datname
)
FROM pg_database
WHERE datistemplate = false
  AND datname NOT IN ('postgres', 'template0', 'template1')
ORDER BY datname;
\gexec
SQL

echo "Recreating ${central_db} database..."
$psql_base --command "DROP DATABASE IF EXISTS ${central_db} WITH (FORCE);"
$psql_base --command "CREATE DATABASE ${central_db};"
