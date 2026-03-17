from sqlalchemy import Boolean, String
from sqlalchemy.orm import Mapped, mapped_column

from database.base import Base


class TimeSlot(Base):
    __tablename__ = "time_slots"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    slot_label: Mapped[str] = mapped_column(String(120), index=True)
    is_blocked: Mapped[bool] = mapped_column(Boolean, default=False)
