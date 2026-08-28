<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

/**
 * MDP product codes (Annex I - Products table).
 *
 * This is not exhaustive — Correos may offer additional products
 * depending on your contract. Use the string value directly if
 * your product code is not listed here.
 */
enum ProductCode: string
{
    use HasOptions;

    // Nacional - Paquetería
    case PaqPremium = 'PAFXB';
    case PaqEstandar = 'PAAZE';
    case PaqToday = 'PADXA';
    case PaqRetorno = 'PARC';
    case PaqEmpresa14 = 'S0132';
    case PaqEmpresa24 = 'S0235';
    case PaqLigero = 'S0236';

    // Nacional - Paquetería XL
    case PaqPremiumXL = 'PPFXB';
    case PaqEstandarXL = 'PPAZE';

    // Nacional - Postal
    case CartaCertificada = 'S0148';
    case CartaCertificadaUrgente = 'S0150';
    case Notificaciones = 'S0175';
    case PaqueteAzul = 'PQDOM';

    // Internacional - Paquetería
    case PaqInternacionalExpress = 'POAXAC';
    case PaqInternacionalEstandar = 'POAZE';
    case PaqInternacionalPremium = 'POAFXB';

    // Internacional - Postal (EMS / Postal Exprés)
    case EmsPostalExpres = 'S0076';
    case PaqueteInternacionalEconomico = 'S0108';
    case PaqueteInternacionalPrioritario = 'S0107';

    public function label(): string
    {
        return match ($this) {
            self::PaqPremium => 'Paq Premium',
            self::PaqEstandar => 'Paq Estándar',
            self::PaqToday => 'Paq Today',
            self::PaqRetorno => 'Paq Retorno',
            self::PaqEmpresa14 => 'Paq Empresa 14',
            self::PaqEmpresa24 => 'Paq Empresa 24',
            self::PaqLigero => 'Paq Ligero',
            self::PaqPremiumXL => 'Paq Premium XL',
            self::PaqEstandarXL => 'Paq Estándar XL',
            self::CartaCertificada => 'Carta Certificada',
            self::CartaCertificadaUrgente => 'Carta Certificada Urgente',
            self::Notificaciones => 'Notificaciones',
            self::PaqueteAzul => 'Paquete Azul',
            self::PaqInternacionalExpress => 'Paq Internacional Express',
            self::PaqInternacionalEstandar => 'Paq Internacional Estándar',
            self::PaqInternacionalPremium => 'Paq Internacional Premium',
            self::EmsPostalExpres => 'EMS Postal Exprés',
            self::PaqueteInternacionalEconomico => 'Paquete Internacional Económico',
            self::PaqueteInternacionalPrioritario => 'Paquete Internacional Prioritario',
        };
    }
}
