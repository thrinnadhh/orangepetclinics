from fastapi import APIRouter

router = APIRouter()


@router.get("/dashboard")
def admin_dashboard():
    return {"message": "Scaffold only. Implement admin dashboard metrics."}


@router.post("/slots/block")
def block_time_slot():
    return {"message": "Scaffold only. Implement slot blocking."}
