from fastapi import APIRouter

router = APIRouter()


@router.post("/register")
def register_user():
    return {"message": "Scaffold only. Implement registration logic."}


@router.post("/login")
def login_user():
    return {"message": "Scaffold only. Implement login logic."}
