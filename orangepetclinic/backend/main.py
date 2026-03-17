from fastapi import FastAPI

from routers import admin, appointments, auth, health, products, refunds
from config.settings import settings


def create_application() -> FastAPI:
    app = FastAPI(
        title=settings.app_name,
        version=settings.app_version,
        docs_url="/docs",
        redoc_url="/redoc",
        openapi_url="/openapi.json",
    )

    app.include_router(health.router, prefix="/api/v1", tags=["health"])
    app.include_router(auth.router, prefix="/api/v1/auth", tags=["auth"])
    app.include_router(appointments.router, prefix="/api/v1/appointments", tags=["appointments"])
    app.include_router(products.router, prefix="/api/v1/products", tags=["products"])
    app.include_router(refunds.router, prefix="/api/v1/refunds", tags=["refunds"])
    app.include_router(admin.router, prefix="/api/v1/admin", tags=["admin"])

    return app


app = create_application()
