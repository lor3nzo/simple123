# Security policy

Report vulnerabilities privately to the repository owner using GitHub's private vulnerability reporting when enabled. Avoid posting working exploits or credentials in public issues.

Never commit `config.php`, production databases, private keys, uploads, `storage/` logs, or credentials. Rotate exposed credentials immediately.

Secure both the web server and any reverse proxy to prevent direct access to sensitive configuration, dependency, and runtime files. `.htaccess` does not necessarily block files served by Nginx.

Use HTTPS, update Composer dependencies, validate Google OIDC configuration, and keep database and filesystem backups outside the public webroot.
