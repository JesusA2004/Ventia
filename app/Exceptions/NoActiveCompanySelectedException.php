<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown whenever code needs a concrete company id (e.g. to call
 * SettingsService) but none could be resolved — this only happens for a
 * superadministrator who has not yet picked an active company. Defining
 * render() here means every call site is automatically protected against
 * ever surfacing a 500: Laravel's handler calls it instead of rendering a
 * generic error page.
 */
class NoActiveCompanySelectedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Selecciona una empresa activa para continuar.');
    }

    public function render(Request $request): RedirectResponse
    {
        return redirect()
            ->route('company-selection.index')
            ->with('error', $this->getMessage());
    }
}
