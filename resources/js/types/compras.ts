export type CompraDetalle = {
    id: number;
    compra_id: number;
    producto: string;
    cantidad: number;
    precio_unitario: string | number;
    subtotal: string | number;
};

export type Compra = {
    id: number;
    proveedor_id: number;
    user_id: number;
    numero_compra: string;
    total: string | number;
    fecha: string;
    observaciones: string | null;
    proveedor: {
        id: number;
        nombre: string;
    } | null;
    user: {
        id: number;
        name?: string;
        nombres?: string;
        apellido_paterno?: string;
        apellido_materno?: string;
    } | null;
    detalles: CompraDetalle[];
    created_at: string;
    updated_at: string;
};
