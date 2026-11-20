import {SurveyItemVM} from "@moodle/lms/block_coursefeedback/model";
import {SurveyState} from "@moodle/lms/block_coursefeedback/survey/state";
import {SurveyItem} from "@moodle/lms/block_coursefeedback/survey/item";

export function SurveyPage({items, state}: {
    items: SurveyItemVM[],
    state: SurveyState,
}) {
    return <div className="block_coursefeedback-survey-page">
        {items.map(item => <SurveyItem item={item} state={state}/>)}
    </div>;
}