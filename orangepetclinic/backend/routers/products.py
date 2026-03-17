from fastapi import APIRouter

router = APIRouter()


@router.get("/")
def list_products():
    return {"message": "Scaffold only. Implement product listing."}


@router.post("/")
def create_product():
    return {"message": "Scaffold only. Implement admin product create."}


@router.put("/{product_id}")
def update_product(product_id: str):
    return {"message": "Scaffold only. Implement product update.", "product_id": product_id}


@router.delete("/{product_id}")
def delete_product(product_id: str):
    return {"message": "Scaffold only. Implement product delete.", "product_id": product_id}
