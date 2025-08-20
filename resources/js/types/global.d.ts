// global.d.ts
import type { AppData } from '@/data/index';

export {};

declare global {
  interface Window {
    appData: AppData;
    vueAppIsReady: () => void;
    _vueMounted?: boolean;
    route?: (name: string, params?: any) => string;

    getGMTOffset: () => string;
    formatUnixTimestamp: (unixTimestamp: number | string, showSeconds?: boolean) => string | undefined;
    displayFormattedDateTime: (unixTimestamp: number) => void;
    displayFormattedDateTimeFromStrings: (date: string, time: string) => void;
  }
}
