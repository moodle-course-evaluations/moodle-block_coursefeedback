import {useState} from "react";

type GetReturnType = {
    "string": string
    "number": number
    "boolean": boolean
    "string[]": string[]
    "number[]": number[]
}
type SurveyStateValue = GetReturnType[keyof GetReturnType]

export interface SurveyState {
    get<T extends keyof GetReturnType>(key: string, type: T): GetReturnType[T] | null;

    set(key: string, value: SurveyStateValue): void;

    useNested(key: string): SurveyState;
}

type RawSurveyState = {
    readonly [K: string]: Readonly<SurveyStateValue> | RawSurveyState
}

function createSurveyState(
    rawState: RawSurveyState,
    rawSet: (state: RawSurveyState) => void,
): SurveyState {
    return {
        get<T extends keyof GetReturnType>(key: string, type: T): GetReturnType[T] | null {
            const value = rawState[key];
            if (value == undefined) {
                return null;
            }

            if (typeof value === type) {
                return value as GetReturnType[T];
            }

            if (type.endsWith("[]") && Array.isArray(value)) {
                const elementType = type.replace("[]", "");
                if (value.every(element => typeof element === elementType)) {
                    return value as GetReturnType[T];
                }
            }

            // Ignore the value of the wrong type.
            return null;
        },

        set(key, value) {
            const oldValue = rawState[key];
            if (value === oldValue) {
                return;
            }
            if (typeof value === "object" && !Array.isArray(value)) {
                throw new Error(`Can't overwrite nested survey state at key '${key}' with plain value.`);
            }

            rawSet(Object.freeze({
                ...rawState,
                [key]: Array.isArray(value) ? Object.freeze(value.slice()) : value,
            }));
        },

        useNested(key: string) {
            let rawNestedState = rawState[key];
            if (typeof rawNestedState !== "object" || Array.isArray(rawNestedState)) {
                rawNestedState = {};
            }

            return createSurveyState(
                rawNestedState as RawSurveyState,
                newNestedState => rawSet(Object.freeze({
                    ...rawState,
                    [key]: newNestedState,
                }))
            );
        }
    };
}

export function useRootSurveyState(): SurveyState {
    const [state, setState] = useState<RawSurveyState>(Object.freeze({}));
    return createSurveyState(state, setState);
}