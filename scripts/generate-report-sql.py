#!/usr/bin/env python3
"""
Generate SQL query for aggregating responses to a questionnaire / survey part.

This script requires a CSV file containing the questionnaire structure, which can be exported from Moodle's database using the following SQL query:
```sql
SELECT *
FROM m_block_coursefeedback_surveypart sp
INNER JOIN m_block_coursefeedback_surveyitem si ON sp.id = si.surveypartid AND si.surveyitemtype IN ('dropdown', 'text', 'emoji', 'scalequestion', 'singlechoice', 'multiplechoice')
LEFT JOIN m_block_coursefeedback_surveyitemansweroption ans ON si.id = ans.surveyitemid
WHERE sp.id = :surveypartid;
```
"""

import argparse
from collections.abc import Sequence
import csv
from bisect import insort
from dataclasses import dataclass
from datetime import datetime
from html.parser import HTMLParser
from pathlib import Path
from textwrap import dedent, indent
from typing import Literal, cast


class SpacesToUnderscoresDictReader(csv.DictReader):
    @property
    def fieldnames(self) -> Sequence[str] | None:
        fieldnames = super().fieldnames
        if fieldnames is None:
            return None
        return tuple(name.replace(" ", "_") for name in fieldnames)


class HtmlToTextParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self._text: str = ""

    def handle_data(self, data: str) -> None:
        if not (self._text.endswith(" ") or data.startswith(" ")):
            self._text += " "
        self._text += data

    @property
    def text(self) -> str:
        return self._text


def _html_to_text_summary(html: str) -> str:
    parser = HtmlToTextParser()
    parser.feed(html)
    parser.close()
    return parser.text.split("\n", maxsplit=1)[0].strip()


type SurveyItemType = Literal[
    "dropdown", "text", "emoji", "scalequestion", "singlechoice", "multiplechoice"
]


@dataclass
class AnswerOption:
    id: int
    sortindex: int
    text: str


@dataclass
class SurveyItem:
    id: int
    sortindex: int
    item_type: SurveyItemType
    text_summary: str
    answer_options: list[AnswerOption]

    def __str__(self) -> str:
        if self.answer_options:
            options_str = "".join(f"\n   - {option}" for option in self.answer_options)
        else:
            options_str = "[]"
        return f"SurveyItem(id={self.id}, sortindex={self.sortindex}, item_type={self.item_type}, text_summary={self.text_summary!r}, answer_options={options_str})"


@dataclass
class Questionnaire:
    id: int
    name: str
    items: list[SurveyItem]

    def __str__(self) -> str:
        items_str = "".join(f"\n - {item}" for item in self.items)
        return f"Questionnaire(id={self.id}, name={self.name}, items={items_str})"


def _check_item_type(item_type: str) -> SurveyItemType:
    if item_type not in {
        "dropdown",
        "text",
        "emoji",
        "scalequestion",
        "singlechoice",
        "multiplechoice",
    }:
        raise ValueError(f"Invalid survey item type: {item_type}")
    return cast(SurveyItemType, item_type)


def _read_questionnaire(reader: csv.DictReader[str]) -> Questionnaire:
    items = []
    current_survey_item: SurveyItem | None = None

    record = None
    for record in reader:
        if current_survey_item and current_survey_item.id != int(record["si_id"]):
            # Record belongs to a new survey item.
            insort(items, current_survey_item, key=lambda si: si.sortindex)
            current_survey_item = None

        if current_survey_item is None:
            current_survey_item = SurveyItem(
                id=int(record["si_id"]),
                sortindex=int(record["si_sortindex"]),
                item_type=_check_item_type(record["surveyitemtype"]),
                text_summary=_html_to_text_summary(record["questiontext"]),
                answer_options=[],
            )

        if record["ans_id"]:
            answer_option = AnswerOption(
                id=int(record["ans_id"]),
                sortindex=int(record["ans_sortindex"]),
                text=_html_to_text_summary(record["ans_text"]),
            )

            insort(
                current_survey_item.answer_options,
                answer_option,
                key=lambda ao: ao.sortindex,
            )

    if current_survey_item is not None:
        insort(items, current_survey_item, key=lambda si: si.sortindex)

    if not record:
        raise ValueError("CSV file is does not contain any records.")

    return Questionnaire(id=int(record["sp_id"]), name=record["sp_name"], items=items)


def _build_select_responses(survey_item: SurveyItem, prefix: str) -> str:
    if survey_item.item_type == "text":
        response_kind = "text"
    else:
        response_kind = "int"

    if survey_item.item_type in {"dropdown", "singlechoice", "multiplechoice"}:
        value_expr = "CASE"
        for option in survey_item.answer_options:
            value_expr += f" WHEN resp.value = {option.id} THEN '{option.text}'"
        value_expr += " ELSE 'Unbekannte Antwort: ' || resp.value END"

        if survey_item.item_type == "multiplechoice":
            value_expr = f"STRING_AGG({value_expr}, ', ' ORDER BY resp.value)"
    elif survey_item.item_type == "scalequestion":
        value_expr = "CASE WHEN resp.value = 0 THEN 'k.A.' ELSE resp.value::text END"
    else:
        value_expr = "resp.value"

    query = dedent(
        f"""
        SELECT surveypartexecutionoptionresponseid AS rs_id, {value_expr} AS value
        FROM {prefix}block_coursefeedback_surveyitem{response_kind}response resp
        WHERE surveyitemid = {survey_item.id}
        """
    ).strip()

    if survey_item.item_type == "multiplechoice":
        query += "\nGROUP BY surveypartexecutionoptionresponseid"

    return query.strip()


@dataclass
class QueryPart:
    cte_name: str
    cte: str

    join: str

    select: str


def _build_query_part_for(
    survey_item: SurveyItem, prefix: str, rs_id_name: str
) -> QueryPart:
    cte_name = f"si_{survey_item.id}_responses"

    cte = f"{cte_name} AS ({_build_select_responses(survey_item, prefix)})"

    truncated_text_summary = survey_item.text_summary[:63]

    return QueryPart(
        cte_name=cte_name,
        cte=cte,
        join=f"{cte_name} ON {cte_name}.rs_id = {rs_id_name}",
        select=f'{cte_name}.value AS "{truncated_text_summary}"',
    )


def _build_sql_query(
    questionnaire: Questionnaire, prefix: str, wwwroot: str, escape_customsql: bool
) -> str:
    item_ctes = []
    item_joins = []
    item_selects = []

    for survey_item in questionnaire.items:
        part = _build_query_part_for(survey_item, prefix, rs_id_name="rs.id")
        item_ctes.append(part.cte)
        item_joins.append(part.join)
        item_selects.append(part.select)

    query = f"-- Generated at {datetime.now()} for questionnaire {questionnaire.name} (id={questionnaire.id})\n"
    if item_ctes:
        query += f"WITH\n{',\n'.join(indent(cte, '    ') for cte in item_ctes)}"

    query += dedent(
        f"""
        SELECT 
            c.shortname AS "Kurs", 
            '{wwwroot}/course/view.php?id=' || c.id AS Kurs_link_url,
            rs.id AS response_set_id, 
        """
    )

    query += indent(",\n".join(item_selects), "    ")

    query += dedent(f"""
        FROM {prefix}block_coursefeedback_surveypartexecutionoptionresp rs
            INNER JOIN {prefix}block_coursefeedback_surveypartexecutionoption slot ON slot.id = rs.surveypartexecutionoptionid
            INNER JOIN {prefix}block_coursefeedback_surveypartexecution spe ON spe.id = slot.surveypartexecutionid
            INNER JOIN {prefix}block_coursefeedback_surveyexecution se ON se.id = spe.surveyexecutionid
            INNER JOIN {prefix}course c ON c.id = se.courseid""")

    for join in item_joins:
        query += f"\n    LEFT JOIN {join}"

    if escape_customsql:
        query = query.replace(";", "%%S%%").replace("?", "%%Q%%")

    query += dedent(f"""
        WHERE spe.surveypartid = {questionnaire.id} AND se.organizationid = :organizationid
        ORDER BY c.shortname, rs.id
    """)

    return query.strip()


parser = argparse.ArgumentParser(formatter_class=argparse.ArgumentDefaultsHelpFormatter)
parser.add_argument("--questionnaire", "-q", required=True, type=Path)
parser.add_argument(
    "--prefix", "-p", default="prefix_", type=str, help="Database table prefix."
)
parser.add_argument(
    "--wwwroot",
    "-w",
    default="%%WWWROOT%%",
    type=str,
    help="Moodle wwwroot URL or report_customsql placeholder.",
)
parser.add_argument(
    "--escape-customsql",
    "-e",
    action="store_true",
    help="Escape reserved characters for use in report_customsql.",
)

if __name__ == "__main__":
    args = parser.parse_args()

    questionnaire_path: Path = cast(Path, args.questionnaire)

    if not questionnaire_path.exists():
        raise FileNotFoundError(
            f"Questionnaire file {questionnaire_path} does not exist."
        )

    with questionnaire_path.open("r", encoding="utf-8-sig") as f:
        reader = SpacesToUnderscoresDictReader(f)
        questionnaire = _read_questionnaire(reader)
        sql = _build_sql_query(
            questionnaire,
            prefix=args.prefix,
            wwwroot=args.wwwroot,
            escape_customsql=args.escape_customsql,
        )

    print(sql)
