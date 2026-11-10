export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    nombres?: string | null;
    apellido_paterno?: string | null;
    apellido_materno?: string | null;
    telefono?: string | null;
    role?: string | null;
    status?: string | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    roles: string[];
    permissions: string[];
    isAdmin: boolean;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
