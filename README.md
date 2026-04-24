# Eduvidra

Eduvidra is a professional SaaS Learning Management System (LMS) designed for streamlined education management. It features a robust multi-role architecture for administrators and students.

## Tech Stack

- Backend: PHP 8.4 + Laravel 11
- Frontend: Vue 3 (Inertia.js) + Tailwind CSS + Vite
- Database: PostgreSQL 17
- Environment: Docker Compose
- Deployment Target: RunCloud

## Local Setup (Docker Compose)

Follow these steps to get your local development environment running using your existing Docker workflow.

### 1. Clone & Initialize

```bash
git clone https://gitlab.com/art-maghakyan/eduvidra.git
cd eduvidra
cp .env.example .env
```

### 2. Install Dependencies

Since PHP 8.4 is required, use a temporary container to install composer dependencies:

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs
```

### 3. Build & Start Containers

```bash
docker compose up -d --build
```

### 4. Finalize Backend & Database

Generate the app key, link storage for branding assets, and run migrations:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed
```

### 5. Frontend Setup

Install NPM packages and start the Vite development server:

```bash
docker compose exec app npm install
docker compose exec app npm run dev
```

The app will be available at: `http://localhost`

### 6. Test CI/CD cahce new push
