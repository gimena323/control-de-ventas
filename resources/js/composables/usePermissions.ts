import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();
    const auth = computed(() => page.props.auth ?? {});

    const can = (permission: string): boolean => {
        if (auth.value.isAdmin || (auth.value.roles ?? []).includes('admin')) {
            return true;
        }

        return (auth.value.permissions ?? []).includes(permission);
    };

    const hasRole = (...roles: string[]): boolean =>
        roles.some((r) => (auth.value.roles ?? []).includes(r));

    const roles = computed(() => auth.value.roles ?? []);
    const permissions = computed(() => auth.value.permissions ?? []);
    const isAdmin = computed(() => auth.value.isAdmin ?? false);

    return { can, hasRole, roles, permissions, isAdmin };
}
