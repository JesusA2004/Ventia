import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Mirrors the backend's Gate/Policy checks so the UI can hide actions the
 * user cannot perform. This is a UX convenience only — the server remains
 * the source of truth and re-validates every request.
 */
export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth.user);
    const permissions = computed(() => new Set(user.value?.permissions ?? []));
    const isSuperAdmin = computed(() => user.value?.company_id === null);

    function can(permission: string): boolean {
        return isSuperAdmin.value || permissions.value.has(permission);
    }

    return { can, isSuperAdmin, user };
}
