// resources/js/types/global.d.ts
import type { AppData } from '@/data/index';
import type Echo from 'laravel-echo';

export { };

declare global {
  interface Window {
    Session?: { message: string; type: SweetAlertIcon };
    LaravelErrors?: string[];
    loadDataTables?: boolean;
    appData: AppData;
    vueAppIsReady: () => void;
    _vueMounted?: boolean;
    route: (name: string, params?: any) => string;
    currentBingoView?: string;
    bingoProps?: Record<string, any>;
    Echo: Echo;
    jQuery?: any;
  }
  interface HTMLElement {
    inert?: boolean;
  }
  interface JQuery {
    DataTable(options?: any): any;
  }
}
