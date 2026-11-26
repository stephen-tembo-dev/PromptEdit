export {};

declare global {
    interface Window {
        /** Preline's plugin registry; autoInit wires `data-hs-*` markup on the page. */
        HSStaticMethods?: {
            autoInit: (collection?: string | string[]) => void;
            [key: string]: unknown;
        };
    }
}
