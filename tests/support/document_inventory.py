"""Generate the documented suite inventory and module coverage map from metadata."""

import argparse
import json
from pathlib import Path
import re

REPO = Path(__file__).resolve().parents[2]


def render():
    suites = json.loads((REPO / 'tests/suites.json').read_text())
    text = ['# Test inventory and coverage map', '', 'Generated from `tests/suites.json`, test directories, PHP module groups and deployed spec filenames by `python3 tests/support/document_inventory.py`. The repository-check suite detects stale documentation.', '', '## Registered suites', '', '| Suite | Category | Tool | Components | Prerequisites | Coverage |', '| --- | --- | --- | --- | --- | --- |']
    for suite in suites:
        tool = {'php': 'PHPUnit', 'python': 'unittest', 'browser': 'Playwright', 'http': 'Apache HTTP', 'checks': 'repository checks'}.get(suite['group'], 'Playwright' if suite.get('project') else 'LinkChecker')
        text.append('| ' + ' | '.join([f"`{suite['name']}`", suite['category'], tool, ', '.join(suite['components']), ', '.join(suite.get('requirements', suite['tools'])) or 'Python 3', suite['description']]) + ' |')
    files = []
    text += ['', '## Discovered PHP and Python files', '', 'Frameworks discover these files automatically inside each category. File filters narrow a suite without creating another top-level target. PHP `Group(\'module:…\')` attributes identify dedicated module coverage for the map below.', '', '| Suite | File | Shared modules |', '| --- | --- | --- |']
    for suite in suites:
        if suite['group'] not in ('php', 'python'):
            continue
        pattern = '*Test.php' if suite['group'] == 'php' else 'test_*.py'
        for file in sorted((REPO / 'tests' / suite['group'] / suite['category']).rglob(pattern)):
            modules = re.findall(r"Group\(['\"]module:([^'\"]+)['\"]\)", file.read_text()) if suite['group'] == 'php' else []
            files.append((suite, file, modules))
            text.append(f"| `{suite['name']}` | [{file.name}](../{file.relative_to(REPO)}) | {', '.join(modules) or '—'} |")
    text += ['', '## Shared module map', '', 'This maps dedicated tests to modules; it is not a measured code-coverage percentage or a claim that every behavior is covered. A dash means no dedicated test is mapped at that level. LinkChecker may traverse demo or integration content for a module, but crawling alone does not establish its behavioral coverage. PHP integration cases use real SQLite/filesystem or module machinery with captured external effects. Component cases use routed browser fixtures; end-to-end cases use the deployed application.', '', '| Module | Unit | Integration | Component | End-to-end |', '| --- | --- | --- | --- | --- |']
    framework = REPO / 'composer-projects/conjin'
    for module in sorted(list((framework / 'src/modules-shared').iterdir()) + list((framework / 'ext/modules-shared').iterdir()), key=lambda p: p.name):
        if not module.is_dir(): continue
        cells = {category: [] for category in ('unit', 'integration', 'component', 'e2e')}
        for suite, file, modules in files:
            if module.name in modules:
                cells[suite['category']].append(f'[{file.name}](../{file.relative_to(REPO)})')
        for suite in suites:
            if suite['group'] == 'browser' and module.name in suite['components']:
                target = '../tests/components/' + ('feedback-collector.spec.ts' if module.name == 'feedback-collector' else 'bootstrap-accordion.spec.ts')
                cells['component'].append(f"[{suite['name']}]({target})")
        for level, category in [('http', 'integration'), ('e2e', 'e2e')]:
            for spec in sorted((REPO / 'tests/integration/specs' / level).rglob('*.spec.ts')):
                if spec.name == module.name + '.spec.ts' or (module.name == 'jsxgraph-helpers' and spec.name == 'jsxgraph-axes.spec.ts'):
                    cells[category].append(f'[{spec.parent.parent.name}/{spec.name}](../{spec.relative_to(REPO)})')
        text.append('| ' + module.name + ' | ' + ' | '.join('<br>'.join(cells[c]) or '—' for c in cells) + ' |')
    text += ['', '## Limits and follow-up work', '', '- The map records dedicated suite/spec presence. Read each named case for the exact behavior asserted; an asset-loading test does not imply full module coverage.', '- PHP line/branch coverage is not collected yet. Add an explicitly selected coverage run before setting coverage thresholds; avoid treating a percentage as evidence of correctness.', '- Browser execution currently covers Chromium. Firefox/WebKit coverage should be added if those browsers are part of the supported product matrix.', '- External mail delivery and remote uploads are replaced with captured mail and temporary local rclone endpoints. Their real service integrations require separately configured tests.', '- Runner preparation is tested with fake subprocesses in the ordinary suite. A real `bin/test prepare` followed by `bin/test all` verifies Docker integration; CI performs both.', '']
    return '\n'.join(text)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--check', action='store_true')
    args = parser.parse_args()
    target = REPO / 'doc/test_inventory.md'
    content = render()
    if args.check:
        if not target.is_file() or target.read_text() != content:
            print('Test inventory is stale. Run python3 tests/support/document_inventory.py.')
            return 1
    else:
        target.write_text(content)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
