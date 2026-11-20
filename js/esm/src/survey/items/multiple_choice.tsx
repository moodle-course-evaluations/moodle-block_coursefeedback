import {ItemProps} from "@moodle/lms/block_coursefeedback/survey/item";
import {ChoiceOptionVM, MultipleChoiceQuestionVM} from "@moodle/lms/block_coursefeedback/model";
import BaseSurveyItem from "@moodle/lms/block_coursefeedback/base_survey_item";
import {Translated} from "@moodle/lms/block_coursefeedback/translated";

export default function MultipleChoiceQuestion({item, state}: ItemProps<MultipleChoiceQuestionVM>) {
    function onChange(option: ChoiceOptionVM, checked: boolean) {
        const oldValue = state.get(item.name, "number[]") ?? [];
        if (oldValue.includes(option.value)) {
            if (!checked) {
                state.set(item.name, oldValue.filter(value => value !== option.value));
            }
        } else if (checked) {
            state.set(item.name, [...oldValue, option.value].sort());
        }
    }

    return <BaseSurveyItem questionText={item.text} item={item}>
        {item.options.map(option =>
            <label>
                <input type="checkbox"
                       value={option.value}
                       checked={state.get(item.name, "number[]")?.includes(option.value) ?? false}
                       onChange={(e) => onChange(option, e.target.checked)}/>
                <Translated text={option.text}/>
            </label>
        )}
    </BaseSurveyItem>;
}