<?php

namespace App\Enums;

enum EvaluationInstrument: string
{
    case ReferenciaI = 'referencia_i';
    case ReferenciaIII = 'referencia_iii';
    case Cisneros = 'cisneros';
    case Likert = 'likert';

    public function label(): string
    {
        return match ($this) {
            self::ReferenciaI => 'Guía I — Acontecimentos Traumáticos Severos',
            self::ReferenciaIII => 'Referencia III — Factores de Riesgo Psicosocial',
            self::Cisneros => 'Escala Cisneros — Violencia Laboral',
            self::Likert => 'Clima Laboral',
        };
    }

    /** Expected question count for completeness validation */
    public function questionCount(): int
    {
        return match ($this) {
            self::ReferenciaI => 14,
            self::ReferenciaIII => 72,
            self::Cisneros => 44,
            self::Likert => 23,
        };
    }
}
