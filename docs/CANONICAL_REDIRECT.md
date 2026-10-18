# Optional canonical hostname redirect (Apache)

The public repository deliberately does not redirect to any particular domain.

Once your HTTPS certificates cover both `example.com` and `www.example.com`, insert these rules **immediately after `RewriteEngine On`** in `.htaccess`, replacing `example.com` with your domain:

```apache
RewriteCond %{HTTP_HOST} ^www\.example\.com$ [NC]
RewriteRule ^ https://example.com%{REQUEST_URI} [R=301,L,NE]
```

This preserves the path and query string. First test with a temporary 302 redirect if you are uncertain. Reverse the preference if your canonical site uses `www`.

**Proxy caution:** Hosts that serve static content through Nginx may need hostname redirects and static-file security controls configured at the hosting layer. Never rely exclusively on `.htaccess` to protect secrets. The PHP app emits `Strict-Transport-Security: max-age=300` on its HTTPS responses; static assets may require server-level HSTS configuration. Do not enable `includeSubDomains` or `preload` without verifying every subdomain.
