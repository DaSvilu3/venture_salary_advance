<?php

namespace App\Enums;

/**
 * The company documents an employer uploads for platform review.
 */
enum DocumentType: string
{
    case CommercialRegistration = 'commercial_registration';
    case VatCertificate = 'vat_certificate';
    case AuthorizedSignatory = 'authorized_signatory';
    case Other = 'other';
}
