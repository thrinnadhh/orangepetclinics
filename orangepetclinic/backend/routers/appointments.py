from fastapi import APIRouter

router = APIRouter()


@router.get("/")
def list_appointments():
    return {"message": "Scaffold only. Implement appointment listing."}


@router.post("/")
def create_appointment():
    return {"message": "Scaffold only. Implement appointment booking."}


@router.patch("/{appointment_id}")
def update_appointment(appointment_id: str):
    return {
        "message": "Scaffold only. Implement cancel/reschedule.",
        "appointment_id": appointment_id,
    }
