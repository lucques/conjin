"""Unit tests of remote directory validation."""

from pathlib import Path
import sys
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[3] / 'composer-projects/conjin/deployment'))
from remote_internal import validate_internal_dir


class DirectoryValidation(unittest.TestCase):
    def test_custom_names(self):
        for name in ('conjin', 'conjin-internal', 'private_data', 'Internal123'):
            self.assertEqual(validate_internal_dir(name), name)

    def test_invalid_and_conflicting_names(self):
        for name in ('', '.', '..', '../private', '/private', 'a/b', 'a b', 'a;echo', 'store', 'modules-css'):
            with self.subTest(name=name), self.assertRaises(ValueError):
                validate_internal_dir(name)
        with self.assertRaises(ValueError):
            validate_internal_dir('private', {'source': 'private/assets'})
        for suffix in ('-dyn-data', '-dyn-preprocessing', '-dyn-preprocessing-new', '-dyn-preprocessing-old'):
            with self.subTest(suffix=suffix), self.assertRaises(ValueError):
                validate_internal_dir('conjin', {'source': 'conjin' + suffix + '/asset'})


if __name__ == '__main__':
    unittest.main()
