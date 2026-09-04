<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Lang;

/**
 * NOTA: Laravel 11 (bootstrap/app.php con Application::configure()) ya NO
 * auto-registra este Handler como hacían las versiones ≤10 — render()/
 * dontReport() de una subclase de ExceptionHandler nunca se invocan por el
 * framework a menos que se conecten explícitamente. Toda la lógica real de
 * manejo de excepciones vive en bootstrap/app.php's withExceptions(), que es
 * el punto real donde Laravel 11 resuelve excepciones.
 *
 * Esta clase se conserva solo como contenedor de resolveMessage() (traduce
 * un ERROR_CODE de dominio vía lang/{locale}/errors.php), invocado
 * directamente desde bootstrap/app.php. El status HTTP de cada
 * DomainException ya NO se resuelve aquí — cada excepción lo declara al
 * construirse (ver App\Exceptions\Domain\DomainException::$httpStatus);
 * antes vivía en un método resolveStatusCode() con un match plano que se
 * desincronizaba silenciosamente de las clases reales (dominios completos
 * quedaban sin mapear y caían al 400 genérico sin que nada lo detectara).
 */
class Handler extends ExceptionHandler
{
    /**
     * Resuelve el mensaje traducido del error. Si el "código" ya es texto
     * humano directo (varias clases de dominio lo usan así en vez de un
     * ERROR_CODE real), Lang::has() confirma que la clave NO existe y
     * devolvemos $errorCode tal cual.
     *
     * No se puede usar `trans(...) ?: $errorCode` (versión anterior): trans()
     * con una clave inexistente no devuelve '' ni null, devuelve la clave
     * completa tal cual se le pasó ("errors.{$errorCode}") — el operador ?:
     * nunca se activaba, así que todo mensaje humano directo (sin traducción
     * en errors.php) le mostraba al usuario el string "errors.El mensaje
     * real aquí." en vez del mensaje real.
     */
    public function resolveMessage(string $errorCode) : string
    {
        $key = "errors.{$errorCode}";

        return Lang::has($key, app()->getLocale())
            ? trans($key, [], app()->getLocale())
            : $errorCode;
    }
}
