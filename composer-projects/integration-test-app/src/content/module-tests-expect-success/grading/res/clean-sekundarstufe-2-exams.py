#!/usr/bin/env python3

import json
from decimal import Decimal, ROUND_CEILING, ROUND_HALF_UP
from pathlib import Path


source = Path(__file__).with_name("sekundarstufe-2-exams.json")
destination = Path(__file__).with_name("sekundarstufe-2-exams_cleaned.json")
table = json.loads(source.read_text())

for grades in table["rows"].values():
    for grade, minimum in grades.items():
        rounded = Decimal(str(minimum)).quantize(Decimal("0.1"), rounding=ROUND_HALF_UP)
        cleaned = (rounded * 2).to_integral_value(rounding=ROUND_CEILING) / 2
        grades[grade] = int(cleaned) if cleaned == cleaned.to_integral_value() else float(cleaned)

destination.write_text(json.dumps(table, indent=4, ensure_ascii=False) + "\n")
