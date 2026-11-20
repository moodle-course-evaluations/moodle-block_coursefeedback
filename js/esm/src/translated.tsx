import {AnyText} from "./model";
import {createContext, useContext, useEffect, useState} from "react";
import {loadAmdModule} from "./amd";

export const LanguageContext = createContext(["en"]);

export function useAsync<T, F>(fallback: F, callback: () => Promise<T>): T | F {
    const [value, setValue] = useState<T | F>(fallback);
    useEffect(() => {
        callback()
            .then(value => setValue(value))
            .catch(error => setValue(() => {
                throw error;
            }));
    });
    return value;
}

export function Translated({text}: { text: AnyText }): string {
    if (typeof text === "string") {
        return text;
    }

    if (typeof text === "object" && "identifier" in text && "component" in text) {
        return useAsync(
            `${text.identifier}, ${text.component}`,
            () => loadAmdModule("core/str")
                .then(({getString}) => getString(text.identifier, text.component, text.a))
        );
    }

    const preferredLanguages = useContext(LanguageContext);

    for (const lang of preferredLanguages) {
        if (lang in text) {
            return text[lang];
        }
    }

    return Object.values(text)[0];
}