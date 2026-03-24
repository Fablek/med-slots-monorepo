# 🏥 Telemedi Smart Booking

An elegant, secure, and efficient medical appointment booking system built in a monorepo architecture. The project focuses on **data integrity** and a modern **User Experience**.

---

## 🚀 Quick Start (30 Seconds)

Ensure you have Docker installed, then execute the following commands in your terminal from the project's root directory:

**1. Spin up containers (Backend, Frontend, Database)**
`docker compose up -d`

**2. Install dependencies and load initial data (Fixtures)**
1. `docker compose exec backend composer install`
2. `docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction`
3. `docker compose exec backend php bin/console doctrine:fixtures:load --no-interaction`

**3. Access the application**
* **Frontend:** http://localhost:3010
* **API (Swagger/Hydra):** http://localhost:8010/api

---

## 🛠 Tech Stack

### Backend (The Brain)
* **Symfony 8.0** — Leveraging the latest features of PHP 8.4.
* **API Platform 4** — Full compliance with JSON-LD and Hydra documentation standards.
* **PostgreSQL 16** — Robust database with full transaction support.
* **PHPUnit** — Unit tests for critical business logic.

### Frontend (The Face)
* **React 18 + Vite** — Fast and lightweight user interface.
* **TanStack React Query** — Advanced server state management, caching, and synchronization.
* **Tailwind CSS + Framer Motion** — "Telemedi style" design with smooth animations.
* **Lucide React** — Consistent set of medical icons.

---

## 💎 Key Features & Architecture

### 🔒 Pessimistic Locking (Concurrency Control)
In medical systems, "double-booking" is a critical issue. I implemented **Pessimistic Write Locking** (`SELECT FOR UPDATE`) at the database level during the slot booking process. This makes the system 100% immune to race conditions. Even with a high volume of simultaneous booking attempts for the same slot, the database guarantees consistency, and subsequent users receive a clear **409 Conflict** error.

### 🤖 Agentic Workflow Orchestration
The project was developed using an advanced AI agent orchestration, ensuring high engineering standards:
* **@orchestrator**: Managed the high-level 6-step roadmap and monorepo consistency.
* **@php-expert & @react-ninja**: Responsible for clean design pattern implementation in their respective domains.
* **@qa-specialist**: Generated test scenarios for the booking path and data formatting.

---

## 🧪 Testing

To verify the application's stability, run:

**Backend (PHPUnit):**
`docker compose exec backend php bin/phpunit`

**Frontend (Vitest):**
`cd frontend && npm test`

---

## 📂 Project Structure
* **backend/** — API source code (Symfony 8).
* **frontend/** — UI source code (React).
* **docker-compose.yml** — Full environment orchestration.