<?php

if (! function_exists('viewWithData')) {
    /**
     * Retorna una vista pasándole un array desestructurado como variables.
     *
     * @param string $view
     * @param array $data
     * @return \Illuminate\View\View
     */
    function viewWithData(string $view, array $data)
    {
        // Extrae las claves del array para crear variables con esos nombres
        return view($view, $data);
    }
}
