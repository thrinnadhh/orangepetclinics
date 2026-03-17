from pydantic import BaseModel


class AppointmentCreate(BaseModel):
    appointment_type: str


class AppointmentUpdate(BaseModel):
    status: str


class AppointmentResponse(BaseModel):
    id: int
    appointment_type: str
    status: str
