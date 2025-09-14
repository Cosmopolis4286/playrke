// resources/js/types/global.d.ts
import type { AppData } from '@/data/index';

export { };

declare global {
  interface Window {
    Session?: { message: string; type: SweetAlertIcon };
    LaravelErrors?: string[];
    loadDataTables?: boolean;
    appData: AppData;
    vueAppIsReady: () => void;
    _vueMounted?: boolean;
    route?: (name: string, params?: any) => string;
    currentBingoView?: string;
    bingoProps?: Record<string, any>;
  }
  interface HTMLElement {
    inert?: boolean;
  }
  interface JQuery {
    DataTable(options?: any): any;
  }
}
