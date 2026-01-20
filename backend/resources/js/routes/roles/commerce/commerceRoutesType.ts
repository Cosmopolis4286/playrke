import type { commerceRoutes } from "../../commerceRoutes.js"

/** Todas las rutas del módulo */
export type CommerceRoutes = typeof commerceRoutes

/** Nombre de ruta */
export type CommerceRouteName = keyof CommerceRoutes

/** Solo rutas administrativas: aquellas que empiezan con `/admin/commerce` */
export type CommerceAdminRouteName = {
    [K in CommerceRouteName]: CommerceRoutes[K] extends `/admin/commerce${string}` ? K : never
}[CommerceRouteName]

/** Solo rutas públicas: las que NO son administrativas */
export type CommercePublicRouteName = Exclude<CommerceRouteName, CommerceAdminRouteName>
