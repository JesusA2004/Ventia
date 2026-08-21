<?php

namespace App\Support;

/**
 * Spanish presentation labels for AuditLog.action. The stored slug (e.g.
 * "count_applied") stays as-is — it's a stable internal identifier used for
 * filtering/grouping — this only maps it to what a user reads, centrally,
 * so neither the audit list nor its filter dropdown re-implement the same
 * translation. Mirrors PermissionLabels, which does the same for `module`.
 *
 * Keep in sync with every AuditLogger::log() call site (grep `->log(` under
 * app/Http/Controllers). An action not listed here still renders — see
 * label() — just less politely, so a missed addition never breaks the page.
 */
final class AuditActionLabels
{
    private const LABELS = [
        // Generic CRUD, reused as-is across every module (the module column
        // already says what was affected — see PermissionLabels::group()).
        'created' => 'Creación',
        'updated' => 'Actualización',
        'deleted' => 'Eliminación',
        'duplicated' => 'Duplicación',
        'restored' => 'Restauración',

        // Auth / company context
        'login' => 'Inicio de sesión',
        'deactivated' => 'Usuario desactivado',
        'active_company_changed' => 'Cambio de empresa activa',
        'permissions_updated' => 'Permisos actualizados',

        // Catalog
        'attribute_created' => 'Atributo creado',
        'attribute_updated' => 'Atributo actualizado',
        'attribute_deleted' => 'Atributo eliminado',
        'price_changed' => 'Precio modificado',
        'cost_changed' => 'Costo modificado',

        // Inventory: lots, adjustments, stock counts, transfers
        'lot_created' => 'Lote creado',
        'lot_updated' => 'Lote actualizado',
        'lot_deleted' => 'Lote eliminado',
        'adjusted' => 'Ajuste de inventario',
        'count_started' => 'Conteo iniciado',
        'count_completed' => 'Conteo completado',
        'count_applied' => 'Conteo aplicado',
        'count_cancelled' => 'Conteo cancelado',
        'transfer_created' => 'Transferencia creada',
        'transfer_submitted' => 'Transferencia enviada',
        'transfer_approved' => 'Transferencia aprobada',
        'transfer_shipped' => 'Transferencia despachada',
        'transfer_received' => 'Transferencia recibida',
        'transfer_cancelled' => 'Transferencia cancelada',

        // Sales / POS
        'completed' => 'Venta completada',
        'suspended' => 'Venta suspendida',
        'suspended_deleted' => 'Venta suspendida eliminada',
        'resumed' => 'Venta recuperada',
        'cancelled' => 'Venta cancelada',
        'returned' => 'Devolución registrada',

        // Cash
        'opened' => 'Caja abierta',
        'closed' => 'Caja cerrada',
        'movement' => 'Movimiento de caja',
        'handover_requested' => 'Entrega de caja solicitada',
        'handover_resolved' => 'Entrega de caja resuelta',
    ];

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    /**
     * @param  iterable<string>  $actions
     * @return list<array{value: string, label: string}>
     */
    public static function options(iterable $actions): array
    {
        $options = [];

        foreach ($actions as $action) {
            $options[] = ['value' => $action, 'label' => self::label($action)];
        }

        usort($options, fn (array $a, array $b) => $a['label'] <=> $b['label']);

        return $options;
    }
}
