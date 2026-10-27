<?php

namespace App\Services\Route;

class RouteService
{
    /**
     * Retorna la ruta física base del proyecto,
     * quitando la carpeta 'public' del path.
     *
     * Ejemplo:
     * - /var/www/html/rke-11/public -> /var/www/html/rke-11/
     */
    public function getBasePath() : string
    {
        // Obtener el path absoluto actual (normalmente apunta a /public)
        $publicPath = public_path();

        // Remover '/public' al final para obtener la raíz del proyecto
        return preg_replace('#/public$#', '', $publicPath);
    }

    /**
     * Retorna la URL base sin la carpeta "public",
     * detectando dinámicamente el nombre de la carpeta padre.
     *
     * Ejemplo:
     * - Entrada: http://localhost/rke-11/public
     * - Salida: http://localhost/rke-11/
     */
    public function getBaseUrl() : string
    {
        // En producción, retorna URL fija
        if (app()->environment('production')) {
            return 'https://www.playrke.com/';
        }

        // Obtiene URL base con /public
        $fullUrl = url('/');

        // Parsear URL para extraer partes
        $parsedUrl = parse_url($fullUrl);
        $path = $parsedUrl['path'] ?? '';

        // Quitar "/public" al final si existe
        $basePath = preg_replace('#/public/?$#', '', $path);

        // Reconstruir URL sin "public"
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        return "{$scheme}://{$host}{$port}{$basePath}/";
    }
}
