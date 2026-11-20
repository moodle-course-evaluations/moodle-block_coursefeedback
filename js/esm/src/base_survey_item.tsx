import {PropsWithChildren} from "react";
import {AnyText, SurveyItemVM, SurveyQuestionVM} from "./model";
import {Translated} from "./translated";
import Pix from "@moodle/lms/block_coursefeedback/pix";

type Props = PropsWithChildren<{
    item: SurveyItemVM,
    questionText: AnyText,
    layout?: "2-rows" | "2-columns"
}>

export default function BaseSurveyItem({item, questionText, layout = "2-rows", children}: Props) {
    const classes = `block_coursefeedback-surveyitem block_coursefeedback-${item.type} layout-${layout}`;

    const required = (item as SurveyQuestionVM).required ?? false;

    return <div className={classes}>
        <div className="question-area coursefeedback-questiontext">
            <Translated text={questionText}/>
            {required && <Pix className="text-danger" name="req"/>}
        </div>

        <div className="answer-area">
            {children}
        </div>
    </div>;
}