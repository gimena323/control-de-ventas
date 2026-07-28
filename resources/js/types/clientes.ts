export type Cliente = {
    id: number;
    nombre: string;
    apellido: string | null;
    email: string | null;
    telefono: string | null;
    direccion: string | null;
    ci_nit: string;
    ventas_count: number;
    created_at: string;
    updated_at: string;
};
