# Reverse proxy — `ditsim.mbouit.nl`

De Docker-stack publiceert twee poorten op de nuc:

| Poort | Container | Voor |
|-------|-----------|------|
| `8000` | `app` | de Laravel-webapp (HTTP) |
| `8080` | `reverb` | de WebSocket-server (Reverb) |

De reverse proxy zet daar **TLS (https)** voor en stuurt:

- **alle gewone requests** → `127.0.0.1:8000` (de app);
- **de WebSocket-route `/app/...`** → `127.0.0.1:8080` (Reverb), mét de
  Upgrade-headers zodat de WebSocket-verbinding blijft staan.

Zonder die `/app`-route werkt de site (en de styling) prima, maar blijft het
monitoring-dashboard niet realtime — het valt dan terug op periodiek verversen.

> De app↔Reverb-communicatie aan de serverkant loopt rechtstreeks tussen de
> containers (`reverb:8080`), niet via de proxy. De proxy is er alleen voor de
> **browser** → wss.

---

## Optie A — Caddy (eenvoudigst, automatische TLS)

`Caddyfile`:

```caddy
ditsim.mbouit.nl {
    encode gzip

    # WebSocket-verkeer naar de Reverb-container.
    handle /app/* {
        reverse_proxy 127.0.0.1:8080
    }

    # Al het overige naar de Laravel-app.
    handle {
        reverse_proxy 127.0.0.1:8000
    }
}
```

Caddy regelt het Let's Encrypt-certificaat zelf en handelt de WebSocket-upgrade
transparant af. Herladen: `caddy reload` (of herstart de Caddy-service).

---

## Optie B — nginx (met certificaat van certbot/Let's Encrypt)

```nginx
# Map voor de WebSocket-upgrade (eenmalig, in http{}-context of bovenaan).
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

server {
    listen 80;
    server_name ditsim.mbouit.nl;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name ditsim.mbouit.nl;

    ssl_certificate     /etc/letsencrypt/live/ditsim.mbouit.nl/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ditsim.mbouit.nl/privkey.pem;

    # Reverb WebSocket.
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 3600s;
    }

    # Laravel-app.
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Testen + herladen: `nginx -t && systemctl reload nginx`.

---

## Belangrijk dat het klopt

- **`X-Forwarded-Proto: https`** wordt door beide configs meegestuurd. De app
  vertrouwt die header via `trustProxies(at: '*')` in
  [bootstrap/app.php](../bootstrap/app.php), zodat Laravel https herkent.
- De browser verbindt straks met **`wss://ditsim.mbouit.nl`** (poort 443). Dat
  klopt met de build-instellingen in [docker-compose.yml](../docker-compose.yml):
  `VITE_REVERB_HOST=ditsim.mbouit.nl`, `VITE_REVERB_PORT=443`,
  `VITE_REVERB_SCHEME=https`. Wijzig je het domein, herbouw dan de image
  (`docker compose up --build`), want die waarden worden in de JS gebakken.
- Controleer na een deploy in de browser: **paginabron** → de CSS-`<link>` moet
  `https://ditsim.mbouit.nl/build/...` zijn, en in **DevTools → Network → WS**
  hoort een openstaande `wss://ditsim.mbouit.nl/app/...`-verbinding te staan.
