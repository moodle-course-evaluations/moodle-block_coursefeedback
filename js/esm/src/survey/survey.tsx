import {SurveyPage} from "@moodle/lms/block_coursefeedback/survey/page";
import {useRootSurveyState} from "@moodle/lms/block_coursefeedback/survey/state";
import {LanguageContext} from "@moodle/lms/block_coursefeedback/translated";
import {SurveyVM} from "@moodle/lms/block_coursefeedback/model";

type Props = {
    survey: SurveyVM,
    preferredLanguages: string[]
}

export default function Survey({survey, preferredLanguages}: Props) {
    const surveyState = useRootSurveyState();

    return <LanguageContext value={preferredLanguages}>
        {survey.pages.map(page =>
            <SurveyPage items={page.items} state={surveyState}/>
        )}
    </LanguageContext>;
}
