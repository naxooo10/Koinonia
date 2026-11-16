<?php

namespace App\Support;

/**
 * Política única de contraseñas de Koinonía.
 *
 * Se usa al crear usuarios, cuando el administrador restablece una
 * contraseña y cuando cada persona cambia la suya desde su perfil.
 */
class ReglasContrasena
{
    public const LARGO_MINIMO = 8;

    public const AYUDA = 'Debe tener al menos 8 caracteres e incluir una letra y un número.';

    public static function reglas(): array
    {
        return [
            'string',
            'min:' . self::LARGO_MINIMO,
            'max:200',
            'regex:/\pL/u',
            'regex:/\d/',
        ];
    }

    public static function mensajes(string $campo = 'password'): array
    {
        return [
            "{$campo}.required" => 'Ingresa una contraseña.',
            "{$campo}.min" => 'La contraseña debe tener al menos 8 caracteres.',
            "{$campo}.max" => 'La contraseña no puede superar los 200 caracteres.',
            "{$campo}.regex" => 'La contraseña debe incluir al menos una letra y un número.',
            "{$campo}.confirmed" => 'La confirmación no coincide con la nueva contraseña.',
            "{$campo}.different" => 'La nueva contraseña debe ser distinta de la actual.',
        ];
    }
}
