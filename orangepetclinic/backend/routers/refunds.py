from fastapi import APIRouter

router = APIRouter()


@router.post("/")
def request_refund():
    return {"message": "Scaffold only. Implement refund request."}


@router.patch("/{refund_id}")
def decide_refund(refund_id: str):
    return {
        "message": "Scaffold only. Implement admin approve/reject.",
        "refund_id": refund_id,
    }
