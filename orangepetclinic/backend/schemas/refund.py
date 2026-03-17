from pydantic import BaseModel


class RefundRequest(BaseModel):
    payment_ref: str
    reason: str


class RefundDecision(BaseModel):
    status: str


class RefundResponse(BaseModel):
    id: int
    payment_ref: str
    status: str
