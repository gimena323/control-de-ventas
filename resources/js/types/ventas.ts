export type VentaDetalle = {
    id: number;
    venta_id: number;
    producto_id: number | null;
    producto: string;
    cantidad: number;
    precio_unitario: string | number;
    descuento: string | number;
    subtotal: string | number;
    producto_relacion?: {
        id: number;
        nombre: string;
    } | null;
};

export type Venta = {
    id: number;
    cliente_id: number;
    user_id: number;
    numero_venta: string;
    total: string | number;
    fecha: string;
    observaciones: string | null;
    estado: string;
    anulada_en: string | null;
    anulada_por_user_id: number | null;
    cliente: {
        id: number;
        nombre: string;
        apellido: string | null;
        ci_nit?: string;
        telefono?: string;
        email?: string;
    } | null;
    user: {
        id: number;
        name?: string;
        nombres?: string;
        apellido_paterno?: string;
        apellido_materno?: string;
    } | null;
    anulada_por?: {
        id: number;
        name?: string;
        nombres?: string;
        apellido_paterno?: string;
        apellido_materno?: string;
    } | null;
    detalles: VentaDetalle[];
    created_at: string;
    updated_at: string;
};
