"""Remote private-data contracts. Run: bin/test python test_remote_internal.py

Upload checks use real rclone against temporary local directories; no remote deployment.
"""

import json
import os
from pathlib import Path
import posixpath
import shlex
import shutil
import subprocess
import sys
import tempfile
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[3] / 'composer-projects/conjin/deployment'))
from remote_internal import validate_internal_dir


@unittest.skipUnless(shutil.which('rclone'), 'rclone required')
class Upload(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='conjin-upload-test-')
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name) / 'remote'
        self.root.mkdir()

    def rclone(self, *args, content=None):
        return subprocess.run(
            ['rclone', '--config', '/dev/null', '--retries', '1'] + list(args),
            input=content, text=True, capture_output=True, check=True,
        ).stdout

    def put(self, name, data):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(data)

    def test_sync_preserves_data_and_preprocessing(self):
        source = Path(self.tmp.name) / 'source'
        (source / 'conjin-dyn-preprocessing').mkdir(parents=True)
        (source / 'conjin-dyn-preprocessing/.htaccess').write_text('Require all denied\n')
        (source / 'conjin-dyn-data').mkdir()
        (source / 'conjin-dyn-data/custom.bin').write_text('stale')
        (source / 'conjin/system/empty').mkdir(parents=True)
        self.put('conjin-dyn-data/custom.bin', 'persistent custom data')
        self.put('conjin-dyn-data/.htaccess', 'Require all denied\n')
        self.put('conjin-dyn-preprocessing/obj/target_root', 'old objects')
        self.put('conjin-dyn-preprocessing/.htaccess', 'Require all denied\n')
        self.rclone('sync', str(source), str(self.root), '--create-empty-src-dirs', '--exclude=/conjin-dyn-data/**', '--exclude=/conjin-dyn-preprocessing/**')
        self.assertEqual((self.root / 'conjin-dyn-data/custom.bin').read_text(), 'persistent custom data')
        self.assertTrue((self.root / 'conjin-dyn-data/.htaccess').exists())
        self.assertEqual((self.root / 'conjin-dyn-preprocessing/obj/target_root').read_text(), 'old objects')
        self.assertTrue((self.root / 'conjin-dyn-preprocessing/.htaccess').exists())
        self.assertTrue((self.root / 'conjin/system/empty').is_dir())


@unittest.skipUnless(shutil.which('dhall-to-json'), 'dhall-to-json required')
class Artifacts(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        repo = Path(__file__).resolve().parents[3]
        dhall = repo / 'composer-projects/conjin/deployment/dhall'
        with tempfile.TemporaryDirectory(prefix='conjin-dhall-') as tmp:
            expression = f'''let T = {dhall}/types.dhall
        let t = {dhall}/tools.dhall
        let a = {dhall}/artifacts-remote.dhall
        let tagged = {dhall}/types-tagged.dhall
        let c = t.makeDefaultRemoteDepl "test" "/conjin" "/app" "/deployment"
            T.Authentication::{{ staticLoginWithoutUserName = False, staticUsers2passwords = [] : T.PasswordList }}
            T.Authorization::{{=}} True ([] : List T.SharedBareModule) ([] : List T.LocalBareModule) ([] : List T.Module)
            "example.test" "/htdocs" "/" {{ name = "test", dir = "/htdocs", configPath = "/rclone.conf" }}
        let l = t.makeDefaultLocalDepl "test" "/conjin" "/app" "/deployment"
            c.depl.authentication c.depl.authorization (None T.LocalDb) True
            ([] : List T.SharedBareModule) ([] : List T.LocalBareModule) ([] : List T.Module)
        let test = {{ depl = l.depl, playwrightTestsDir = None Text, db = None T.TestDb, store = None T.TestStore, preprocessUser = "preprocess", linkchecker = l.linkchecker }}
        in {{ default = a.makeArtifacts c, custom = a.makeArtifacts (c with internalDir = "private-data"), tagged = tagged.tagRemoteDepl c,
            relative = a.makeArtifacts (c with pathBase = ".."),
            relativeCustom = a.makeArtifacts ((c with pathBase = "..") with internalDir = "private-data"),
            local = ({dhall}/artifacts-local.dhall).makeArtifacts l,
            test = ({dhall}/artifacts-test.dhall).makeArtifacts test }}'''
            env = dict(os.environ, XDG_CACHE_HOME=tmp)
            result = subprocess.run(['dhall-to-json'], input=expression, text=True, capture_output=True, env=env)
            if result.returncode:
                raise RuntimeError(result.stderr)
            cls.data = json.loads(result.stdout)

    def test_default_and_custom_remote_layout(self):
        data = self.data
        for kind, name in [('default', 'conjin'), ('custom', 'private-data'), ('relative', 'conjin'), ('relativeCustom', 'private-data')]:
            artifact = data[kind]
            config = artifact['config-json']
            framework = f'/htdocs/{name}/framework'
            for field, expected in {
                'path_base': f'/htdocs/{name}',
                'path_preprocess': f'/htdocs/{name}-dyn-preprocessing',
                'path_store': f'/htdocs/{name}-dyn-data/store',
                'errorlog_dir': f'/htdocs/{name}-dyn-data/logs/error',
            }.items():
                self.assertEqual(posixpath.normpath(posixpath.join(framework, config[field])), expected, (kind, field))
            services = artifact['docker-compose-yml']['services']
            upload = services['upload']
            assert upload['command'].count(f'--exclude=/{name}-dyn-data/**') == 1
            for suffix in ('-dyn-preprocessing', '-dyn-preprocessing-new', '-dyn-preprocessing-old'):
                assert f'--exclude=/{name}{suffix}/**' in upload['command']
            assert 'rclone copy ' not in upload['command']
            assert 'rclone copyto' in upload['command']
            assert f'/files/htdocs/{name}-dyn-data/.htaccess' in upload['command']
            assert any(vol['target'] == f'/files/htdocs/{name}/.htaccess' for vol in upload['volumes'])
            assert not any(vol['target'] == f'/files/htdocs/{name}' for vol in upload['volumes'])
            assert not any(vol['target'] in ['/files/htdocs/content', '/files/htdocs/system', '/files/htdocs/vendor', '/files/htdocs/store', '/files/htdocs/preprocess', '/files/htdocs/logs/error'] for vol in upload['volumes'])
            assert f'test:/htdocs/{name}-dyn-data/store' in services['backup-store']['command']
            assert f'test:/htdocs/{name}-dyn-data/logs/error' in services['backup-errorlog']['command']
        assert data['tagged']['internalDir'] == 'conjin'
        for kind, compose_key in [('local', 'docker-compose-yml'), ('test', 'docker-compose-test-yml')]:
            assert data[kind]['config-json']['path_base'] == '..'
            volumes = data[kind][compose_key]['services']['webserver']['volumes']
            targets = {volume['target'] for volume in volumes}
            assert '/files/conjin-preprocessing-runtime' in targets
            assert data[kind]['config-json']['path_preprocess'] == '../../conjin-preprocessing-runtime/conjin-dyn-preprocessing'
            assert {'/files/conjin/content', '/files/conjin/system', '/files/conjin/vendor'} <= targets
            assert not {'/files/htdocs/content', '/files/htdocs/system', '/files/htdocs/vendor', '/files/htdocs/config.json', '/files/htdocs/users.json'} & targets
            assert {'/files/conjin/framework', '/files/conjin/config.json', '/files/conjin/users.json', '/files/htdocs/index.php', '/files/htdocs/module-resource.php'} <= targets

    @unittest.skipUnless(shutil.which('rclone'), 'rclone required')
    def test_upload_initializes_empty_runtime_directories(self):
        for kind, name in [('default', 'conjin'), ('custom', 'private-data')]:
            with self.subTest(kind=kind), tempfile.TemporaryDirectory(prefix='conjin-initial-upload-') as tmp:
                source = Path(tmp) / 'source'
                remote = Path(tmp) / 'remote'
                empty_dirs = [f'{name}/content/empty', f'{name}/system/empty']
                for directory in empty_dirs + [f'{name}-dyn-data']:
                    (source / directory).mkdir(parents=True)
                (source / f'{name}-dyn-data/.htaccess').write_text('Require all denied\n')
                command = self.data[kind]['docker-compose-yml']['services']['upload']['command']
                for phase in shlex.split(command)[1].split(' && '):
                    args = [arg.replace('/files/htdocs', str(source)).replace('test:/htdocs', str(remote)) for arg in shlex.split(phase)]
                    result = subprocess.run(
                        args[:1] + ['--config', '/dev/null', '--retries', '1'] + args[1:],
                        text=True, capture_output=True,
                    )
                    self.assertEqual(result.returncode, 0, result.stderr)
                for directory in empty_dirs + [f'{name}-dyn-data/store', f'{name}-dyn-data/logs/error']:
                    self.assertTrue((remote / directory).is_dir(), directory)
                self.assertEqual((remote / f'{name}-dyn-data/.htaccess').read_text(), 'Require all denied\n')

    @unittest.skipUnless(shutil.which('rclone'), 'rclone required')
    def test_upload_removes_only_content_dev_trees(self):
        for kind, name in [('default', 'conjin'), ('custom', 'private-data')]:
            with self.subTest(kind=kind), tempfile.TemporaryDirectory(prefix='conjin-content-upload-') as tmp:
                source = Path(tmp) / 'source'
                remote = Path(tmp) / 'remote'
                content = f'{name}/content'

                def put(root, path, data):
                    target = root / path
                    target.parent.mkdir(parents=True, exist_ok=True)
                    target.write_text(data)

                dev_dirs = ['dev', 'target/dev', 'target/res/dev', 'target/res/images/deep/dev', '.hidden/dev', 'with spaces/dev']
                for directory in dev_dirs:
                    put(source, f'{content}/{directory}/new.txt', 'local development file')
                    put(source, f'{content}/{directory}/nested/dev/.secret', 'nested development file')
                    put(remote, f'{content}/{directory}/old.txt', 'previously uploaded file')
                put(remote, f'{content}/removed-target/res/dev/old.txt', 'remote only development file')
                empty_dev_dirs = ['empty/dev', 'remote-only/dev']
                (source / content / empty_dev_dirs[0]).mkdir(parents=True)
                for directory in empty_dev_dirs:
                    (remote / content / directory).mkdir(parents=True)

                kept = [
                    f'{content}/{path}' for path in (
                        'index.php', 'target/res/image.svg', 'Dev/notes.txt', 'development/notes.txt',
                        'mydev/notes.txt', 'dev.txt', 'target/dev.txt', 'files/dev', '.hidden/keep.txt',
                    )
                ] + [f'{name}/system/dev/keep.txt', f'{name}/modules-local/example/dev/keep.txt', 'dev/public.txt']
                for path in kept:
                    put(source, path, 'current content')
                    put(remote, path, 'outdated remote content')
                (source / content / 'empty/keep').mkdir(parents=True)
                put(remote, f'{content}/obsolete.txt', 'removed content')
                put(remote, f'{name}/system/obsolete.php', 'removed code')
                put(source, f'{name}-dyn-preprocessing/.htaccess', 'Require all denied\n')
                put(remote, f'{name}-dyn-preprocessing/obj/stale', 'old preprocessing output')
                put(source, f'{name}-dyn-data/.htaccess', 'Require all denied\n')
                persistent = [
                    f'{name}-dyn-preprocessing-new/obj/stale', f'{name}-dyn-preprocessing-old/obj/stale',
                    f'{name}-dyn-data/store/dev/data.bin', f'{name}-dyn-data/logs/error/dev/error.log',
                    f'{name}-dyn-data/custom.bin', f'{name}/vendor/lucques/conjin/dev/package.txt',
                ]
                for path in persistent:
                    put(source, path, 'local placeholder')
                    put(remote, path, 'persistent remote data')

                command = self.data[kind]['docker-compose-yml']['services']['upload']['command']
                shell_args = shlex.split(command)
                self.assertEqual(shell_args[0], '-c')
                # Run each generated phase with only the endpoints redirected to
                # temporary local directories. Check for transient uploads too.
                for phase in shell_args[1].split(' && '):
                    args = [arg.replace('/files/htdocs', str(source)).replace('test:/htdocs', str(remote)) for arg in shlex.split(phase)]
                    self.assertEqual(args[0], 'rclone')
                    result = subprocess.run(
                        args[:1] + ['--config', '/dev/null', '--retries', '1'] + args[1:],
                        text=True, capture_output=True,
                    )
                    self.assertEqual(result.returncode, 0, result.stderr)
                    for directory in dev_dirs:
                        self.assertFalse((remote / content / directory / 'new.txt').exists(), phase)
                        self.assertFalse((remote / content / directory / 'nested/dev/.secret').exists(), phase)

                for directory in dev_dirs + empty_dev_dirs + ['removed-target/res/dev']:
                    self.assertFalse((remote / content / directory).exists(), directory)
                for path in kept:
                    self.assertEqual((remote / path).read_text(), 'current content', path)
                for path in persistent:
                    self.assertEqual((remote / path).read_text(), 'persistent remote data', path)
                self.assertTrue((remote / content / 'empty/keep').is_dir())
                self.assertFalse((remote / content / 'obsolete.txt').exists())
                self.assertFalse((remote / f'{name}/system/obsolete.php').exists())
                self.assertEqual((remote / f'{name}-dyn-preprocessing/obj/stale').read_text(), 'old preprocessing output')
                self.assertEqual((remote / f'{name}-dyn-data/.htaccess').read_text(), 'Require all denied\n')
                for directory in dev_dirs:
                    self.assertEqual((source / content / directory / 'new.txt').read_text(), 'local development file')


if __name__ == '__main__':
    unittest.main()
