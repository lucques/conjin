"""Run standard unittest cases and emit structured results for bin/test."""

import argparse
import json
from pathlib import Path
import sys
import time
import unittest


class Result(unittest.TextTestResult):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.cases = []
        self.started = {}
        self.subtests = set()

    def startTest(self, test):
        self.started[test.id()] = time.monotonic()
        super().startTest(test)

    def record(self, test, status, reason='', parent=None):
        self.cases.append({'name': test.id(), 'status': status, 'reason': reason,
            'durationSeconds': round(time.monotonic() - self.started.get((parent or test).id(), time.monotonic()), 4)})

    def addSuccess(self, test):
        super().addSuccess(test)
        if test.id() not in self.subtests:
            self.record(test, 'passed')

    def addFailure(self, test, err):
        super().addFailure(test, err)
        self.record(test, 'failed', self._exc_info_to_string(err, test))

    def addError(self, test, err):
        super().addError(test, err)
        self.record(test, 'failed', self._exc_info_to_string(err, test))

    def addSkip(self, test, reason):
        super().addSkip(test, reason)
        self.record(test, 'skipped', reason)

    def addExpectedFailure(self, test, err):
        super().addExpectedFailure(test, err)
        self.record(test, 'skipped', 'Expected failure: ' + self._exc_info_to_string(err, test))

    def addUnexpectedSuccess(self, test):
        super().addUnexpectedSuccess(test)
        self.record(test, 'failed', 'Unexpected success')

    def addSubTest(self, test, subtest, err):
        super().addSubTest(test, subtest, err)
        self.subtests.add(test.id())
        self.record(subtest, 'failed' if err else 'passed', self._exc_info_to_string(err, test) if err else '', test)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--directory', required=True, type=Path)
    parser.add_argument('--output', required=True, type=Path)
    parser.add_argument('files', nargs='*', type=Path)
    args = parser.parse_args()
    loader = unittest.TestLoader()
    directory = args.directory.resolve()
    if args.files:
        sys.path.insert(0, str(directory))
        names = ['.'.join(file.resolve().relative_to(directory).with_suffix('').parts) for file in args.files]
        suite = loader.loadTestsFromNames(names)
    else:
        suite = loader.discover(str(directory), pattern='test_*.py')
    result = unittest.TextTestRunner(verbosity=2, resultclass=Result).run(suite)
    args.output.write_text(json.dumps({'tests': result.cases}, indent=4) + '\n')
    return 0 if result.wasSuccessful() and result.testsRun and not result.skipped and not result.expectedFailures else 1


if __name__ == '__main__':
    raise SystemExit(main())
