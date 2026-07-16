<?php
/**
 * Database credentials.
 *
 * Priority: environment variables > hardcoded values below.
 *
 * ── For cPanel (Namecheap) deployment ──────────────────────────────────────
 * Option A — set via .htaccess (recommended, no PHP file edits needed):
 *   Add these four lines to the ROOT .htaccess on your server:
 *     SetEnv DB_HOST localhost
 *     SetEnv DB_USER cpanelusername_dbuser
 *     SetEnv DB_PASS yourpassword
 *     SetEnv DB_NAME cpanelusername_komagin_db
 *
 * Option B — edit the hardcoded values below directly on the server.
 *
 * Notes:
 *   - DB_HOST is always 'localhost' on cPanel (not 127.0.0.1)
 *   - DB_USER and DB_NAME must be prefixed with your cPanel account username
 *     e.g.  myaccount_dbuser  /  myaccount_komagin
 * ──────────────────────────────────────────────────────────────────────────
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'komagin_db');
