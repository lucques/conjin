"""Normalize native framework reports; bin/test owns only orchestration."""

from collections import Counter
import json
from pathlib import Path
import xml.etree.ElementTree as ET


def read_cases(path: Path, format: str) -> list[dict]:
    if format == 'junit':
        cases = []
        try:
            document = ET.parse(path).getroot()
        except ET.ParseError as error:
            raise ValueError('Malformed JUnit report: ' + str(error)) from error
        for node in document.iter('testcase'):
            problem = next((c for c in node if c.tag in ('error', 'failure', 'skipped')), None)
            cases.append({'name': node.get('classname', '') + '::' + node.get('name', ''),
                'status': 'passed' if problem is None else 'skipped' if problem.tag == 'skipped' else 'failed',
                'durationSeconds': float(node.get('time', 0)),
                'assertions': int(node.get('assertions', 0)),
                'reason': '' if problem is None else (problem.text or problem.get('message', ''))})
        return cases
    data = json.loads(path.read_text())
    if format == 'json':
        return data['tests']
    cases = []
    def visit(suites, parents=()):
        for suite in suites:
            names = parents + (suite['title'],)
            for spec in suite.get('specs', []):
                for test in spec['tests']:
                    attempts = test.get('results', [])
                    outcome = test.get('status')
                    status = {'expected': 'passed', 'unexpected': 'failed', 'flaky': 'flaky', 'skipped': 'skipped'}.get(outcome, 'failed')
                    cases.append({'name': ' / '.join((*names, spec['title'], test.get('projectName', ''))),
                        'status': status, 'file': spec.get('file', ''), 'line': spec.get('line'),
                        'durationSeconds': sum(r.get('duration', 0) for r in attempts) / 1000,
                        'retries': max(0, len(attempts) - 1),
                        'reason': '\n'.join(e.get('message', '') for r in attempts for e in r.get('errors', []))})
            visit(suite.get('suites', []), names)
    visit(data.get('suites', []))
    for error in data.get('errors', []):
        cases.append({'name': 'Playwright setup', 'status': 'failed', 'durationSeconds': 0, 'reason': error.get('message', str(error))})
    return cases


def counts(cases):
    values = Counter(case['status'] for case in cases)
    return {'total': len(cases), **{key: values[key] for key in ('passed', 'failed', 'skipped', 'flaky')}}


def write_summary(root, suites, cleanup_errors):
    cases = [case for suite in suites for case in suite.get('tests', [])]
    (root / 'summary.json').write_text(json.dumps({'suites': suites, 'testCounts': counts(cases), 'cleanupErrors': cleanup_errors}, indent=4) + '\n')
    document = ET.Element('testsuites')
    for suite in suites:
        node = ET.SubElement(document, 'testsuite', name=suite['suite'])
        properties = ET.SubElement(node, 'properties')
        for key in ('category', 'tool', 'components'):
            ET.SubElement(properties, 'property', name=key, value=str(suite.get(key, '')))
        entries = list(suite.get('tests') or []) or [{'name': suite['suite'], 'status': suite['status'], 'durationSeconds': suite.get('durationSeconds', 0), 'reason': suite.get('reason', '')}]
        if suite['status'] in ('failed', 'blocked') and not any(c['status'] in ('failed', 'blocked', 'flaky') for c in entries):
            entries.append({'name': 'suite execution', 'status': suite['status'], 'reason': suite.get('reason', '')})
        node.set('tests', str(len(entries)))
        node.set('failures', str(sum(c['status'] in ('failed', 'blocked', 'flaky') for c in entries)))
        node.set('skipped', str(sum(c['status'] == 'skipped' for c in entries)))
        for case in entries:
            item = ET.SubElement(node, 'testcase', name=case['name'], classname=suite['suite'], time=str(case.get('durationSeconds', 0)))
            if case['status'] == 'skipped':
                ET.SubElement(item, 'skipped').text = case.get('reason', '')
            elif case['status'] in ('failed', 'blocked', 'flaky'):
                ET.SubElement(item, 'failure', type=case['status']).text = case.get('reason', '')
    if cleanup_errors:
        node = ET.SubElement(document, 'testsuite', name='cleanup', tests='1', failures='1')
        ET.SubElement(ET.SubElement(node, 'testcase', name='project cleanup'), 'failure').text = '\n'.join(cleanup_errors)
    ET.indent(document, space='    ')
    ET.ElementTree(document).write(root / 'junit.xml', encoding='utf-8', xml_declaration=True)
