import {FunctionComponent} from "react";
import {SurveyState} from "@moodle/lms/block_coursefeedback/survey/state";
import {SurveyItemVM} from "@moodle/lms/block_coursefeedback/model";
import MultipleChoiceQuestion from "@moodle/lms/block_coursefeedback/survey/items/multiple_choice";

function NotImplemented() {
    return <div>Not implemented!</div>;
}

export type ItemProps<T extends SurveyItemVM> = {
    item: T,
    state: SurveyState,
};
export type ItemComponent<T extends SurveyItemVM> = FunctionComponent<ItemProps<T>>

const componentImpls: {
    [K in SurveyItemVM["type"]]: ItemComponent<Extract<SurveyItemVM, { type: K }>>
} = {
    "multiplechoice": MultipleChoiceQuestion,
    "singlechoice": NotImplemented,
    "nested": NotImplemented,
};

export function SurveyItem<T extends SurveyItemVM>(props: ItemProps<T>) {
    const ItemComponent = componentImpls[props.item.type] as ItemComponent<T>;
    return <ItemComponent {...props}/>;
}