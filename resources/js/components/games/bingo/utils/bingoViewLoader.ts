// js/components/games/bingo/utils/bingoViewLoader.ts
import mountApp from '@/helpers/vueMain';

/**
 * Tipos de vistas disponibles en Bingo
 */
export type BingoViewName = 'Form' | 'GameInfo' | 'Summary';

/**
 * Mapa de vistas dinámicas de Bingo
 */
const views: Record<BingoViewName, () => Promise<any>> = {
    Form: () => import('@/bingo/views/form/Form.vue'),
    GameInfo: () => import('@/bingo/views/game-info/GameInfo.vue'),
    Summary: () => import('@/bingo/views/summary/Summary.vue'),
};

/**
 * Carga y monta una vista de Bingo
 * @param viewName - Nombre de la vista a cargar (fallback: 'GameInfo')
 * @param props - Props para pasar al componente Vue
 */
export async function loadBingoView(
    viewName?: string,
    props?: Record<string, any>
): Promise<void> {
    // Validación del nombre de vista
    const validViewName: BingoViewName =
        viewName && views.hasOwnProperty(viewName) ? (viewName as BingoViewName) : 'GameInfo';

    // Validación de props
    const validProps: Record<string, any> =
        props && typeof props === 'object' && !Array.isArray(props) ? props : {};

    // Obtener función de carga de la vista
    const loadView = views[validViewName];

    if (!loadView) {
        console.error(`[Bingo] Vista "${validViewName}" no está definida.`);
        return;
    }

    try {
        const { default: Component } = await loadView();
        mountApp(Component, validProps);
        console.log(`[Bingo] Vista "${validViewName}" cargada correctamente.`);
    } catch (error) {
        console.error(`[Bingo] Error al cargar la vista "${validViewName}":`, error);
    }
}

/**
 * --------------------------
 * API recomendada
 * --------------------------
 * Este módulo ahora es completamente independiente de window.
 * Ejemplo de uso:
 *
 * import { loadBingoView } from '@/components/games/bingo/utils/bingoViewLoader';
 *
 * // Cargar la vista Form con props
 * await loadBingoView('Form', { userId: 123, gameId: 456 });
 *
 * // Cargar la vista por defecto (GameInfo) sin props
 * await loadBingoView();
 */
