<?php

namespace App\Http\Controllers\Partner;

use App\Enums\PartnerType;
use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class PartnerPortalController extends Controller
{
    /**
     * The bank or funder the signed-in viewer belongs to.
     */
    protected function partner(Request $request): Partner
    {
        $partner = $request->user()->partner;

        abort_if($partner === null, 403);

        return $partner;
    }

    /**
     * Advances this partner banks (for a bank) or funds (for a funder). Nothing else is visible.
     *
     * @return Builder<Advance>
     */
    protected function advances(Partner $partner): Builder
    {
        $column = $partner->type === PartnerType::Bank ? 'bank_partner_id' : 'funder_partner_id';

        return Advance::query()->where("advances.{$column}", $partner->id);
    }
}
