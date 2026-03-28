.PHONY: build up down restart logs shell db-shell migrate seed fresh pull deploy

# ── Local / VPS commands ──────────────────────────────────────────────────────
build:
	docker compose build --no-cache

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart app

logs:
	docker compose logs -f app

logs-all:
	docker compose logs -f

# ── App container shell ───────────────────────────────────────────────────────
shell:
	docker compose exec app bash

# ── Database shell ────────────────────────────────────────────────────────────
db-shell:
	docker compose exec db mysql -u$${DB_USERNAME} -p$${DB_PASSWORD} $${DB_DATABASE}

# ── Artisan shortcuts ─────────────────────────────────────────────────────────
migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

tinker:
	docker compose exec app php artisan tinker

# ── Cache management ──────────────────────────────────────────────────────────
cache-clear:
	docker compose exec app php artisan optimize:clear

cache-warm:
	docker compose exec app php artisan optimize

# ── Queue ─────────────────────────────────────────────────────────────────────
queue-restart:
	docker compose exec app php artisan queue:restart

# ── Deployment (VPS) ─────────────────────────────────────────────────────────
pull:
	git pull origin main

deploy: pull
	docker compose build --no-cache app
	docker compose up -d --no-deps app
	docker compose exec app php artisan optimize:clear
	docker compose exec app php artisan optimize
	@echo "✅ Deploy complete"

# ── SSL (Let's Encrypt) ───────────────────────────────────────────────────────
ssl-init:
	@echo "Step 1: Start app on HTTP only (comment out HTTPS block in nginx first)"
	docker compose up -d app
	@echo "Step 2: Issue certificate..."
	docker run --rm \
		-v /etc/letsencrypt:/etc/letsencrypt \
		-v opticedge_credit_certbot_webroot:/var/www/certbot \
		certbot/certbot certonly \
		--webroot -w /var/www/certbot \
		-d credit.opticedgeafrica.net \
		--email ops@opticedgeafrica.net \
		--agree-tos --no-eff-email
	@echo "Step 3: Restart app with full HTTPS config"
	docker compose restart app
	@echo "✅ SSL certificate issued"

ssl-renew:
	docker compose exec certbot certbot renew --quiet
	docker compose exec app nginx -s reload
