# OrangePetClinic

Production-grade monorepo scaffold for a pet clinic platform with customer and admin capabilities.

## Architecture Overview

- Frontend: Next.js (App Router), TailwindCSS, Zustand, Axios
- Backend: FastAPI, SQLAlchemy, Pydantic
- Database: Supabase PostgreSQL
- Storage: Supabase Storage
- Payments: Razorpay
- Authentication: JWT, bcrypt, role-based access (`admin`, `customer`)

## Repository Structure

```text
orangepetclinic/
  frontend/
    app/
      admin/
      customer/
    components/
    hooks/
    store/
    lib/
    admin/
    customer/
  backend/
    routers/
    models/
    schemas/
    services/
    database/
    auth/
    middleware/
    utils/
    config/
```

## Backend Notes

- `main.py`: FastAPI app factory and router registration.
- `config/settings.py`: environment-driven application configuration.
- `auth/`: password hashing and JWT token scaffolding.
- `database/`: SQLAlchemy base/session and Supabase client initialization.
- `routers/`: API endpoint placeholders for auth, appointments, products, refunds, admin.
- `services/`: business-service placeholders only (no domain logic yet).

## Frontend Notes

- App Router entrypoint in `app/`.
- `lib/axios.ts`: centralized API client.
- `store/auth-store.ts`: global auth state using Zustand.
- Route placeholders:
  - `/admin`
  - `/customer`

## Environment Variables

- Frontend template: `frontend/.env.example`
- Backend template: `backend/.env.example`
- Optional root template: `.env.example`

## Local Setup

### 1) Frontend

```bash
cd frontend
npm install
npm run dev
```

### 2) Backend

```bash
cd backend
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn main:app --reload --port 8000
```

## Current Status

- Project structure created.
- Dependency manifests and environment templates created.
- API and UI route placeholders created.
- Business logic intentionally not implemented.
