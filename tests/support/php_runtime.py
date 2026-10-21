"""PHP interpreter preparation and invocation-owned Docker execution."""

import hashlib
import json
import os
import re
import subprocess


class Blocked(RuntimeError):
    """Selected coverage cannot execute with the available prerequisites."""


def configured_image(repo, name):
    # Shared literal settings can be read without running deployment generators.
    config = (repo / 'composer-projects/conjin/deployment/dhall/docker-images.dhall').read_text()
    match = re.search(r'^\s*,\s*' + re.escape(name) + r'\s*=\s*"([^"\r\n]+)"', config, re.MULTILINE)
    if not match:
        raise ValueError(f'{name} must be a literal image reference in docker-images.dhall')
    return match[1]


class PhpRuntime:
    def __init__(self, repo, env, command, require_docker, project, prepared_project, progress):
        self.repo = repo
        self.env = env
        self.command = command
        self.require_docker = require_docker
        self.project = project
        self.prepared_project = prepared_project
        self.progress = progress
        self.framework = repo / 'composer-projects/conjin'
        self.context = self.framework / 'deployment/docker/images/conjin-server'
        self.cache = repo / '.cache/test-runner/php'
        self.container = None
        self.prefix = None
        self.info = {'kind': 'docker'}
        self.extensions = set()

    def base_image(self):
        return configured_image(self.repo, 'imagePHP')

    def fingerprint(self):
        digest = hashlib.sha256(('php-runtime-v1:' + self.base_image()).encode())
        for file in (self.context / 'Dockerfile', self.framework / 'composer.json', self.framework / 'composer.lock'):
            digest.update(file.read_bytes())
        return digest.hexdigest()

    def vendor_fingerprint(self):
        return hashlib.sha256((self.framework / 'vendor/composer/installed.json').read_bytes()).hexdigest()

    def prepare(self):
        self.progress('Checking Docker prerequisites...')
        self.require_docker()
        self.cache.mkdir(parents=True, exist_ok=True)
        state = self.cache / 'state.json'
        state.unlink(missing_ok=True)
        image = self.prepared_project + '-php:latest'
        base_image = self.base_image()
        self.progress(f'Building the PHP test image from {base_image}; checking image updates and reusing cached layers...')
        self.command(['docker', 'build', '--pull', '--target', 'php-test', '--build-arg', 'BASE_IMAGE=' + base_image, '--tag', image, self.context])
        image_id = self.command(['docker', 'image', 'inspect', '--format', '{{.Id}}', image], capture=True).strip()
        vendor = self.framework / 'vendor'
        vendor.mkdir(exist_ok=True)
        composer_cache = self.cache / 'composer'
        composer_cache.mkdir(exist_ok=True)
        # Only dependency installation gets network access and writable vendor.
        # Composer runs on the same interpreter as PHPUnit, with platform checks.
        command = [
            'docker', 'run', '--rm', '--pull', 'never', '--user', f'{os.getuid()}:{os.getgid()}',
            '--mount', f'type=bind,src={self.repo},dst={self.repo},readonly',
            '--mount', f'type=bind,src={vendor},dst={vendor}',
            '--mount', f'type=bind,src={composer_cache},dst=/composer',
            '--env', 'COMPOSER_HOME=/composer', '--env', 'COMPOSER_NO_DEV=0',
            '--workdir', self.framework, '--entrypoint', 'composer', image_id,
        ]
        self.progress('Installing locked Composer dependencies, using cached downloads when available...')
        self.command([*command, 'install', '--no-interaction', '--prefer-dist'])
        self.progress('Verifying installed dependencies against the PHP version and extensions...')
        self.command([*command, 'check-platform-reqs'])
        state.write_text(json.dumps({'fingerprint': self.fingerprint(), 'image': image_id, 'vendor': self.vendor_fingerprint()}, indent=4) + '\n')

    def load_prepared(self):
        try:
            state = json.loads((self.cache / 'state.json').read_text())
            if not isinstance(state, dict) or not re.fullmatch(r'sha256:[0-9a-f]{64}', state.get('image', '')):
                raise ValueError('Invalid prepared image ID')
            if state.get('fingerprint') != self.fingerprint():
                raise Blocked('Prepared PHP inputs are outdated. Run bin/test prepare php.')
            if state.get('vendor') != self.vendor_fingerprint() or not (self.framework / 'vendor/bin/phpunit').is_file():
                raise Blocked('Prepared PHP dependencies changed or are missing. Run bin/test prepare php.')
        except (OSError, ValueError, TypeError) as error:
            raise Blocked('No prepared PHP runtime or dependencies. Run bin/test prepare php.') from error
        try:
            self.command(['docker', 'image', 'inspect', state['image']], capture=True)
        except subprocess.CalledProcessError as error:
            raise Blocked('Prepared PHP image is missing. Run bin/test prepare php.') from error
        return state['image']

    def require(self):
        if self.prefix is not None:
            return
        self.require_docker()
        image = self.load_prepared()
        phpunit_cache = self.repo / '.cache/phpunit'
        phpunit_cache.mkdir(parents=True, exist_ok=True)
        artifacts = self.repo / 'test-artifacts'
        artifacts.mkdir(exist_ok=True)
        name = self.project + '-php'
        # Capture the actual ID: cleanup must never remove an existing
        # container merely because it happens to have the requested name.
        self.container = self.command([
            'docker', 'run', '--detach', '--pull', 'never', '--name', name,
            '--network', 'none', '--read-only', '--user', f'{os.getuid()}:{os.getgid()}',
            '--tmpfs', '/tmp:rw,nosuid,nodev,mode=1777',
            '--mount', f'type=bind,src={self.repo},dst={self.repo},readonly',
            '--mount', f'type=bind,src={artifacts},dst={artifacts}',
            '--mount', f'type=bind,src={phpunit_cache},dst={phpunit_cache}',
            '--workdir', self.repo, '--entrypoint', 'sleep', image, 'infinity',
        ], capture=True).strip()
        prefix = ['docker', 'exec', '--workdir', str(self.repo), self.container, 'php']
        self.info.update(image=image, container=self.container)
        details = json.loads(self.command([*prefix, '-r', 'echo json_encode(["version" => PHP_VERSION, "id" => PHP_VERSION_ID, "extensions" => get_loaded_extensions()]);'], capture=True))
        self.info['version'] = details['version']
        if details['id'] < 80300:
            raise Blocked('PHP 8.3 or newer is required; update imagePHP and run bin/test prepare php')
        self.extensions = set(details['extensions'])
        self.prefix = prefix
        print(f'PHP runtime: Docker, PHP {details["version"]} ({image})', flush=True)

    def require_extensions(self, extensions):
        self.require()
        missing = set(extensions) - self.extensions
        if missing:
            raise Blocked('Missing PHP extensions: ' + ', '.join(sorted(missing)))

    def close(self):
        if self.container:
            self.command(['docker', 'rm', '--force', '--volumes', self.container])
            self.container = None
            self.prefix = None
