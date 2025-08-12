// global.d.ts
export { }; // Hace este archivo un módulo

declare global {
  interface Window {
    ziggyPreloadPromise?: Promise<any>;
    ziggyReady?: boolean;
    route?: (name: string, params?: any) => string;

    getGMTOffset: () => string;
    formatUnixTimestamp: (unixTimestamp: number | string, showSeconds?: boolean) => string | undefined;
    displayFormattedDateTime: (unixTimestamp: number) => void;
    displayFormattedDateTimeFromStrings: (date: string, time: string) => void;
  }
}
