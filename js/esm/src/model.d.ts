export type SurveyVM = {
    pages: SurveyPageVM[];
}

export type SurveyComponentVM = {
    items: SurveyItemVM[];
}

export type SurveyPageVM = SurveyComponentVM;

type BaseSurveyItemVM = {
    type: string;
    if?: string;
}

export type NestedSurveyComponentVM = BaseSurveyItemVM & SurveyComponentVM & { type: "nested" };

export type LangString = {
    identifier: string
    component: string
    a?: string | Record<string, unknown>
}

export type MultilangString = Record<string, string>

export type AnyText = string | LangString | MultilangString;

export type SurveyQuestionVM = BaseSurveyItemVM & {
    name: string;
    required: boolean;
    text: AnyText;
};

export type ChoiceOptionVM = {
    value: number;
    text: AnyText;
}

type ChoiceQuestionVM = SurveyQuestionVM & {
    options: ChoiceOptionVM[];
}

export type MultipleChoiceQuestionVM = ChoiceQuestionVM & { type: "multiplechoice" }
export type SingleChoiceQuestionVM = ChoiceQuestionVM & { type: "singlechoice" }

type SurveyItemVM = SingleChoiceQuestionVM | MultipleChoiceQuestionVM | NestedSurveyComponentVM
