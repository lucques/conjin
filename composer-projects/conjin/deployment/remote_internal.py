"""Validation for the remote deployment's private directory."""

import re


def validate_internal_dir(name, static_files=()):
    reserved = {
        'content', 'system', 'vendor', 'preprocess', 'store', 'logs',
        'modules', 'modules-shared', 'modules-ext', 'modules-shared-ext',
        'modules-css', 'modules-shared-css', 'modules-ext-css', 'modules-shared-ext-css',
        'auth', 'login', 'logout', 'debug',
    }
    if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9_-]*', name) or name.lower() in reserved:
        raise ValueError('internalDir must be a single directory name using letters, digits, underscores or hyphens, and must not be a reserved Conjin path.')
    destinations = static_files.values() if isinstance(static_files, dict) else static_files
    private_names = {name.lower(), name.lower() + '-dyn-preprocessing', name.lower() + '-dyn-data', name.lower() + '-dyn-preprocessing-new', name.lower() + '-dyn-preprocessing-old'}
    if any(path.strip('/').split('/')[0].lower() in private_names for path in destinations):
        raise ValueError('internalDir conflicts with a staticFiles destination.')
    return name
