export type PermissionItem = {
    id: number;
    nombre: string;
    descripcion: string | null;
    grupo: string | null;
};

export type RoleItem = {
    id: number;
    nombre: string;
    descripcion: string | null;
    permissions: PermissionItem[];
    users_count?: number;
};
