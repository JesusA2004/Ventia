/**
 * Laravel validation errors come back as `{ message, errors: { field: string[] } }`.
 * Callers used to hardcode a single expected field (e.g. `errors.items[0]`),
 * which silently swallows the real message whenever the backend rejects a
 * different field (customer_id, register_id, ...) — the user then only sees
 * a generic fallback even though the server knew exactly why it failed.
 * This reads whichever field errored first, so the real reason always surfaces.
 */
export function firstErrorMessage(payload: unknown, fallback: string): string {
    if (!payload || typeof payload !== 'object') {
        return fallback;
    }

    const body = payload as { message?: string; errors?: Record<string, string[]> };

    if (body.errors) {
        for (const key of Object.keys(body.errors)) {
            const messages = body.errors[key];

            if (Array.isArray(messages) && messages.length > 0) {
                return messages[0];
            }
        }
    }

    return body.message || fallback;
}
