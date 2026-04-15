import type Echo from 'laravel-echo';

export { };

declare global {
  interface Window {
    Session?: { message: string; type: SweetAlertIcon };
    LaravelErrors?: string[];
    loadDataTables?: boolean;
    vueAppIsReady: () => void;
    _vueMounted?: boolean;
    route: (name: string, params?: any) => string;
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
