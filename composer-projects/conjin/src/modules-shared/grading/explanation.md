# Grading-table discrepancies

These three grading tables are official. The algorithm for calculating the thresholds is not documented and is therefore tried to be inferred.

## `sekundarstufe-1.json` (originally from `BewertungSekundarstufe1.pdf`)

For 109 maximum points, grade 4 starts at 40%, which is \(109 \cdot 0.4 = 43.6\) points. With half points as the smallest unit, 43.5 is below the threshold, so the minimum attainable score must be rounded upward to 44. The cleaned value 44 is therefore uncontroversial; the PDF's 43.6 is not an attainable half-point score.

## `sekundarstufe-2-tests.json` (originally from `Leistbew-Punktetabelle-Sek2.pdf`)

`processing.php` applies the project's most consistent rule: first round the calculated threshold to two decimal places and then round upward to the next half point. The rationale and policy are documented in the [README](README.md).

For 56 maximum points and grade 14, the configured rule gives \(56 \cdot 0.9733 = 54.5048\). Rounding this to 54.50 and then upward to the next half point gives 54.5, while the official table states 55.

For 92 maximum points and grade 11, the configured rule gives \(92 \cdot 0.8533 = 78.5036\). Rounding this to 78.50 and then upward to the next half point gives 78.5, while the official table states 79.

No consistent rounding rule reproduces the entire official table. The implementation therefore applies the two-decimal rule consistently; its lower thresholds give students the better grade in these two cases. The cleaned JSON replaces only these two official values with the consistently calculated PHP values.

## `sekundarstufe-2-exams.json` (originally from `Bewertungsmassstab_Sek_II_Klausuren.pdf`)

There is no government-provided document for this reference table. Its values are inconsistently rounded, sometimes to one decimal place and sometimes to two. The most consistent cleaning rule is to round the calculated threshold to one decimal place and then round upward to the next half point. This matches `GRADING_SEK_2_EXAM_ROUNDING_PRECISION`.

The earlier direct half-point rounding turned 3.51 into 4 for 13 maximum points and grade 2, while the one-decimal rule gives 3.5 before the half-point ceiling and therefore keeps 3.5. Likewise, 7.02 for 26 maximum points and grade 2 becomes 7.0 and remains 7 instead of becoming 7.5.

`clean-sekundarstufe-2-exams.py` applies one-decimal rounding followed by the half-point ceiling to every value in the original JSON and writes `sekundarstufe-2-exams_cleaned.json`. This cleaning rule corrects the inconsistent source precision without treating the table as an official authority.
