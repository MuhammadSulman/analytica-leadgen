# Analytica Lead Generation MVP — Setup Guide

Follow these steps in order. Each step is small.

---

## Step 1: Create a fresh Laravel project with Breeze (Inertia + Vue)

Run these commands one by one:

```bash
composer create-project laravel/laravel analytica-leadgen
cd analytica-leadgen
composer require laravel/breeze --dev
php artisan breeze:install vue
```

When it asks questions, choose:
- Dark mode support? → No (or Yes, your choice)
- Testing framework? → Pest or PHPUnit, either is fine

This gives you:
- Login / Register pages (already built)
- Vue + Inertia already wired up
- Basic auth system

---

## Step 2: Install Ant Design Vue

```bash
npm install ant-design-vue
```

Then open `resources/js/app.js` and add these two lines:

```js
import Antd from 'ant-design-vue';
import 'ant-design-vue/dist/reset.css';
```

Find the line that says:
```js
.use(plugin)
```

Change it to:
```js
.use(plugin)
.use(Antd)
```

---

## Step 3: Copy the custom files from this package

Copy each file from this zip into your Laravel project, matching the same folder path:

- `database/migrations/2026_01_01_000000_create_leads_table.php` → same path
- `app/Models/Lead.php` → same path
- `app/Http/Controllers/LeadController.php` → same path
- `resources/js/Pages/Landing.vue` → same path
- `resources/js/Pages/Admin/Leads.vue` → same path

---

## Step 4: Add routes

Open `routes/web.php` in your project.

Add the code from `routes/web.php` in this package — it goes near the top and inside the `auth` middleware group (see comments inside the file).

---

## Step 5: Run the database migration

```bash
php artisan migrate
```

This creates the `leads` table.

---

## Step 6: Start the app

```bash
composer run dev
```

This runs Laravel + Vite together.

Open your browser:
- Landing page → `http://localhost:8000/`
- Register an admin account → `http://localhost:8000/register`
- Admin leads panel → `http://localhost:8000/admin/leads` (must be logged in)

---

## What this MVP does

1. **Landing page** (`/`) — public form. Visitor enters name, email, company, message. Submits → saved to database.
2. **Admin panel** (`/admin/leads`) — login required. Shows all leads in a table. You can change each lead's status: New → Contacted → Follow-up → Won → Lost.

---

## Next steps (future, not in this MVP)

- Email notification when a new lead comes in
- List finder tool (auto-search companies)
- Export leads to CSV
- Charts/stats on the dashboard
