declare var requirejs: (deps: string[], callback: (module: any) => void, errback: (error: unknown) => void) => void;

type KnownModules = {
    "core/str": {
        getString: (identifier: string, component: string, a?: string | Record<string, unknown>) => Promise<string>
    },
    "core/templates": {
        renderPix: (key: string, component: string, title?: string) => Promise<string>,
    }
};

export function loadAmdModule<K extends keyof KnownModules>(module: K): Promise<KnownModules[K]> {
    return new Promise((resolve, reject) => {
        requirejs([module], resolve, () => reject(`Could not load ${module}`));
    });
}
