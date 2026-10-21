"""Contracts of bin/test. Uses fake processes; never builds or starts Docker."""

from contextlib import redirect_stderr, redirect_stdout
import importlib.machinery
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch


source = Path(__file__).resolve().parents[3] / 'bin/test'
loader = importlib.machinery.SourceFileLoader('conjin_test_runner', str(source))
spec = importlib.util.spec_from_loader(loader.name, loader)
runner = importlib.util.module_from_spec(spec)
sys.modules[loader.name] = runner
loader.exec_module(runner)


class TestRunner(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='conjin-runner-tests-')
        self.addCleanup(self.tmp.cleanup)
        self.repo = Path(self.tmp.name)
        (self.repo / 'tests').mkdir()
        data = json.loads((runner.REPO / 'tests/suites.json').read_text())
        files = ['python/unit/test_one.py', 'python/integration/test_two.py']
        files += [str(p.relative_to(runner.REPO / 'tests')) for p in (runner.REPO / 'tests/php').rglob('*Test.php')]
        for name in files:
            file = self.repo / 'tests' / name
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_text('')
        (self.repo / 'tests/suites.json').write_text(json.dumps(data))
        self.suites = runner.registry(self.repo)
        self.instance = runner.Runner(self.repo)
        runner.reset_artifacts(self.instance.artifacts)

    def test_all_includes_every_suite_once(self):
        selected = runner.select_suites(self.suites, 'all')
        self.assertEqual(selected, self.suites)
        self.assertEqual(len(selected), len({s.name for s in selected}))
        fast = runner.select_suites(self.suites, 'fast')
        self.assertEqual({s.group for s in fast}, {'checks', 'python', 'php'})
        self.assertTrue(all(not s.deployment for s in fast))

    def test_file_and_path_filters(self):
        self.assertEqual([s.name for s in runner.select_suites(self.suites, 'python', 'test_two.py', repo=self.repo)], ['integration-unittest'])
        self.assertEqual([s.name for s in runner.select_suites(self.suites, 'php', 'feedback_collector', repo=self.repo)], ['integration-phpunit'])
        selected = runner.select_suites(self.suites, 'php', 'feedback', repo=self.repo)
        self.assertEqual([s.name for s in selected], ['unit-phpunit', 'integration-phpunit'])
        selected = runner.select_suites(self.suites, 'unit-phpunit', 'JSXGraphAxesTest.php', repo=self.repo)
        self.assertEqual([p.name for p in selected[0].files(self.repo, 'JSXGraphAxesTest.php')], ['JSXGraphAxesTest.php'])
        self.assertEqual(runner.normalize_path('/expect-success/http.spec.ts/'), 'expect-success/http.spec.ts')
        for path in ('../other', 'x/./y', '--help'):
            with self.assertRaises(ValueError):
                runner.normalize_path(path)
        for target, path in [('all', 'x'), ('fast', 'x'), ('php', 'missing'), ('unknown', '')]:
            with self.assertRaises(ValueError):
                runner.select_suites(self.suites, target, path, repo=self.repo)

    def test_reset_removes_stale_reports_without_touching_sources(self):
        root = self.instance.artifacts
        (root / 'old').mkdir()
        (root / 'old/screenshot.png').write_text('stale')
        runner.reset_artifacts(root)
        self.assertEqual([p.name for p in root.iterdir()], ['invocation.json'])
        self.assertTrue((self.repo / 'tests/python/unit/test_one.py').exists())
        self.assertFalse((self.repo / 'tests/artifacts').exists())

    def test_checkout_lock_blocks_second_invocation_and_releases(self):
        with runner.checkout_lock(self.instance.cache):
            with self.assertRaises(runner.Blocked):
                with runner.checkout_lock(self.instance.cache):
                    self.fail('Concurrent invocation acquired the lock')
        with runner.checkout_lock(self.instance.cache):
            pass

    def test_missing_tool_is_blocked(self):
        with patch.object(runner.shutil, 'which', return_value=None):
            with self.assertRaisesRegex(runner.Blocked, 'Missing'):
                self.instance.require('php')

    def test_unsupported_php_is_blocked(self):
        runtime = self.instance.php
        with patch.object(runtime, 'require_docker'), patch.object(runtime, 'load_prepared', return_value='sha256:fixture'), patch.object(runtime, 'command', side_effect=['worker-id', '{"id":80233,"version":"8.2.33","extensions":[]}']):
            with self.assertRaisesRegex(runner.Blocked, 'PHP 8.3'):
                self.instance.require('php')

    def php_files(self):
        runtime = self.instance.php
        runtime.context.mkdir(parents=True)
        (runtime.context / 'Dockerfile').write_text('FROM php AS php-test')
        (runtime.framework / 'deployment/dhall').mkdir()
        (runtime.framework / 'deployment/dhall/docker-images.dhall').write_text('{\n    , imagePHP = "php:8.3-apache"\n    , imageAlpine = "alpine:3"\n}')
        (runtime.framework / 'composer.json').write_text('{}')
        (runtime.framework / 'composer.lock').write_text('{}')
        (runtime.framework / 'vendor/composer').mkdir(parents=True)
        (runtime.framework / 'vendor/composer/installed.json').write_text('{}')
        (runtime.framework / 'vendor/bin').mkdir()
        (runtime.framework / 'vendor/bin/phpunit').write_text('fixture')
        runtime.cache.mkdir(parents=True)
        state = {'fingerprint': runtime.fingerprint(), 'image': 'sha256:' + 'a' * 64, 'vendor': runtime.vendor_fingerprint()}
        (runtime.cache / 'state.json').write_text(json.dumps(state))
        return runtime, state

    def test_php_preparation_is_independent_of_css_sources_and_other_images(self):
        runtime, state = self.php_files()
        (self.repo / 'tests/fixture.php').write_text('changed fixture')
        (self.repo / 'tests/fixture.scss').write_text('changed stylesheet')
        images = runtime.framework / 'deployment/dhall/docker-images.dhall'
        images.write_text(images.read_text().replace('alpine:3', 'alpine:4'))
        self.assertEqual(runtime.fingerprint(), state['fingerprint'])
        images.write_text(images.read_text().replace('php:8.3-apache', 'php:8.4-apache'))
        self.assertNotEqual(runtime.fingerprint(), state['fingerprint'])
        baseline = runtime.fingerprint()
        (runtime.framework / 'composer.lock').write_text('{"changed":true}')
        self.assertNotEqual(runtime.fingerprint(), baseline)

    def test_missing_stale_and_removed_php_preparation_are_blocked(self):
        runtime = self.instance.php
        with self.assertRaisesRegex(runner.Blocked, 'prepare php'):
            runtime.load_prepared()
        runtime, state = self.php_files()
        with patch.object(runtime, 'command', return_value='{}'):
            self.assertEqual(runtime.load_prepared(), state['image'])
            (runtime.framework / 'vendor/composer/installed.json').write_text('{"dev":false}')
            with self.assertRaisesRegex(runner.Blocked, 'dependencies changed'):
                runtime.load_prepared()
            (runtime.framework / 'composer.lock').write_text('{"changed":true}')
            with self.assertRaisesRegex(runner.Blocked, 'outdated'):
                runtime.load_prepared()
        (runtime.cache / 'state.json').write_text(json.dumps({**state, 'fingerprint': runtime.fingerprint(), 'vendor': runtime.vendor_fingerprint()}))
        with patch.object(runtime, 'command', side_effect=subprocess.CalledProcessError(1, ['docker'])):
            with self.assertRaisesRegex(runner.Blocked, 'image is missing'):
                runtime.load_prepared()

    def test_php_worker_is_reused_without_host_php_builds_or_network(self):
        runtime = self.instance.php
        runtime.env['PHP'] = '/missing/host/php'
        details = json.dumps({'id': 80332, 'version': '8.3.32', 'extensions': ['dom', 'pdo_sqlite']})
        with patch.object(runtime, 'require_docker'), patch.object(runtime, 'load_prepared', return_value='sha256:fixture'), patch.object(runtime, 'command', side_effect=['worker-id', details]) as command, redirect_stdout(io.StringIO()):
            runtime.require()
            runtime.require_extensions(['pdo_sqlite'])
            runtime.require()
        self.assertEqual(command.call_count, 2)
        launch = command.call_args_list[0].args[0]
        self.assertEqual(launch[launch.index('--network') + 1], 'none')
        self.assertIn('--read-only', launch)
        self.assertIn(f'type=bind,src={self.repo},dst={self.repo},readonly', launch)
        self.assertNotIn('build', launch)
        self.assertEqual(launch[launch.index('--pull') + 1], 'never')
        self.assertEqual(runtime.prefix, ['docker', 'exec', '--workdir', str(self.repo), 'worker-id', 'php'])
        self.assertEqual(runtime.info['version'], '8.3.32')
        with self.assertRaisesRegex(runner.Blocked, 'mbstring'):
            runtime.require_extensions(['mbstring'])

    def test_php_cleanup_runs_after_test_failure_and_preserves_other_containers(self):
        runtime = self.instance.php
        runtime.container = 'owned-worker-id'
        with patch.object(self.instance, 'run_suite', side_effect=subprocess.CalledProcessError(1, ['phpunit'])), patch.object(runtime, 'command') as command, redirect_stdout(io.StringIO()):
            code = self.instance.execute(self.suites[:1])
        self.assertEqual(code, 1)
        command.assert_called_once_with(['docker', 'rm', '--force', '--volumes', 'owned-worker-id'])
        self.assertIsNone(runtime.container)

    def test_php_name_collision_does_not_claim_or_remove_existing_container(self):
        runtime = self.instance.php
        with patch.object(runtime, 'require_docker'), patch.object(runtime, 'load_prepared', return_value='sha256:fixture'), patch.object(runtime, 'command', side_effect=subprocess.CalledProcessError(125, ['docker'])) as command:
            with self.assertRaises(subprocess.CalledProcessError):
                runtime.require()
            runtime.close()
        self.assertEqual(command.call_count, 1)
        self.assertIsNone(runtime.container)

    def test_php_preparation_installs_locked_dependencies_before_recording_state(self):
        runtime, state = self.php_files()
        def command(args, **kwargs):
            return state['image'] if args[:3] == ['docker', 'image', 'inspect'] else ''
        with patch.object(runtime, 'require_docker'), patch.object(runtime, 'command', side_effect=command) as called:
            runtime.prepare()
        calls = [call.args[0] for call in called.call_args_list]
        self.assertEqual(len(calls), 4)
        self.assertIn('php-test', calls[0])
        self.assertIn('--pull', calls[0])
        self.assertEqual(calls[2][-3:], ['install', '--no-interaction', '--prefer-dist'])
        self.assertEqual(calls[3][-1], 'check-platform-reqs')
        self.assertNotIn('--ignore-platform-reqs', str(calls))
        self.assertEqual(json.loads((runtime.cache / 'state.json').read_text()), state)

    def test_php_only_preparation_does_not_prepare_deployments_or_browser(self):
        with patch.object(self.instance.php, 'prepare') as php, patch.object(self.instance, 'prepare_deployment') as deployment, redirect_stdout(io.StringIO()):
            code = self.instance.prepare([], php=True)
        self.assertEqual(code, 0)
        php.assert_called_once_with()
        deployment.assert_not_called()

    def test_component_preparation_reuses_integration_inputs(self):
        with patch.object(runner.Runner, 'prepare', return_value=0) as prepare, patch.object(runner, 'reset_artifacts'), patch.object(runner, 'checkout_lock'):
            self.assertEqual(runner.main(['prepare', 'component-playwright']), 0)
        prepare.assert_called_once_with(['integration'], php=True)

    def test_browser_image_version_must_match_locked_playwright(self):
        context = self.repo / 'tests/integration'
        context.mkdir(parents=True)
        (context / 'package-lock.json').write_text('{"packages":{"node_modules/@playwright/test":{"version":"1.61.0"}}}')
        with patch.object(runner, 'configured_image', return_value='mcr.microsoft.com/playwright:v1.61.0-noble'):
            self.assertEqual(self.instance.playwright_image(), 'mcr.microsoft.com/playwright:v1.61.0-noble')
        with patch.object(runner, 'configured_image', return_value='mcr.microsoft.com/playwright:v1.62.0-noble'):
            with self.assertRaisesRegex(ValueError, 'must match'):
                self.instance.playwright_image()

    def test_components_reuse_playwright_service_and_propagate_failure(self):
        suite = next(s for s in self.suites if s.name == 'component-playwright')
        self.assertNotIn('node', suite.tools)
        self.instance.env['CHROMIUM_PATH'] = '/host/browser'
        self.instance.php.prefix = ['docker', 'exec', 'php-worker', 'php']
        css = self.repo / 'compiled style.css'
        css.write_text('fixture')
        config = {'services': {'playwright': {'image': 'shared-playwright'}}}
        def command(args, **kwargs):
            if args[:2] == ['docker', 'exec']:
                return '<html>rendered fixture</html>'
            if args[:2] == ['docker', 'compose'] and 'run' in args:
                raise subprocess.CalledProcessError(7, args)
            return 0
        with patch.object(self.instance, 'require'), patch.object(self.instance, 'require_php_extensions'), patch.object(self.instance, 'load_prepared', return_value=config), patch.object(self.instance, 'component_css', return_value=css), patch.object(self.instance, 'command', side_effect=command) as called, patch.object(self.instance, 'start') as deployment, redirect_stdout(io.StringIO()):
            self.assertEqual(self.instance.execute([suite], path='feedback-collector.spec.ts'), 7)
        deployment.assert_not_called()
        calls = [call.args[0] for call in called.call_args_list]
        self.assertTrue(all(call[0] == 'docker' for call in calls))
        launch = next(call for call in calls if 'run' in call)
        self.assertIn('--no-deps', launch)
        self.assertIn('--rm', launch)
        self.assertEqual(launch[launch.index('--pull') + 1], 'never')
        self.assertEqual(launch[-1], 'feedback-collector.spec.ts')
        self.assertIn('playwright', launch)
        self.assertNotIn('/host/browser', str(launch))
        self.assertNotIn('docker.sock', str(launch))
        self.assertFalse(any('build' in call or 'up' in call for call in calls))
        folder = self.instance.output_dir(suite)
        self.assertEqual(set(json.loads((folder / 'fixtures.json').read_text())), {'feedback-collector', 'bootstrap-extended', 'bootstrap-alternative'})
        self.assertEqual(calls[-1][-3:], ['down', '--volumes', '--remove-orphans'])
        self.assertEqual(self.instance.results[0]['browserRuntime']['image'], 'shared-playwright')

    def test_component_project_cleanup_runs_after_interruption(self):
        def run(*args):
            self.instance.compose('integration', 'run', '--rm', '--no-deps', 'playwright')
        with patch.object(self.instance, 'run_suite', side_effect=run), patch.object(self.instance, 'command', side_effect=[KeyboardInterrupt, 0]) as command, redirect_stdout(io.StringIO()):
            code = self.instance.execute(self.suites[:1])
        self.assertEqual(code, 130)
        self.assertEqual(command.call_args_list[-1].args[0][-3:], ['down', '--volumes', '--remove-orphans'])
        self.assertFalse(self.instance.started)

    def test_failure_and_blocked_suite_do_not_prevent_later_suites(self):
        selected = self.suites[:3]
        def run(suite, *args):
            if suite == selected[0]:
                raise subprocess.CalledProcessError(7, ['fixture'])
            if suite == selected[1]:
                raise runner.Blocked('Missing fixture prerequisite')
        with patch.object(self.instance, 'run_suite', side_effect=run) as called, redirect_stdout(io.StringIO()):
            code = self.instance.execute(selected)
        self.assertEqual(code, 7)
        self.assertEqual(called.call_count, 3)
        summary = json.loads((self.instance.artifacts / 'summary.json').read_text())
        self.assertEqual([s['status'] for s in summary['suites']], ['failed', 'blocked', 'passed'])

    def test_every_selected_suite_is_executed(self):
        selected = self.suites[:2]
        with patch.object(self.instance, 'run_suite') as called, redirect_stdout(io.StringIO()):
            code = self.instance.execute(selected)
        self.assertEqual(code, 0)
        self.assertEqual([call.args[0] for call in called.call_args_list], selected)
        self.assertEqual([result['status'] for result in self.instance.results], ['passed', 'passed'])

    def test_removed_cli_options_are_rejected_before_touching_reports(self):
        for args in (['php', '--php-runtime', 'native'], ['php', '--grep', 'pattern'], ['all', '--skip', 'smoke'], ['list', '--json']):
            with self.subTest(args=args), patch.object(runner, 'reset_artifacts') as reset, redirect_stderr(io.StringIO()):
                with self.assertRaises(SystemExit) as error:
                    runner.main(args)
                self.assertEqual(error.exception.code, 2)
                reset.assert_not_called()

    def test_interruption_reports_remaining_suites_and_cleans_up(self):
        self.instance.started.add('integration')
        with patch.object(self.instance, 'run_suite', side_effect=KeyboardInterrupt), patch.object(self.instance, 'compose'), patch.object(self.instance, 'stop') as stop, redirect_stdout(io.StringIO()):
            code = self.instance.execute(self.suites[:2])
        self.assertEqual(code, 130)
        self.assertEqual([s['status'] for s in self.instance.results], ['failed', 'blocked'])
        stop.assert_called_once_with('integration')

    def test_cleanup_failure_changes_exit_status(self):
        self.instance.started.add('demo')
        with patch.object(self.instance, 'run_suite'), patch.object(self.instance, 'stop', side_effect=OSError('fixture cleanup failed')), redirect_stdout(io.StringIO()), redirect_stderr(io.StringIO()):
            code = self.instance.execute(self.suites[:1])
        self.assertEqual(code, 1)
        self.assertIn('fixture cleanup failed', self.instance.cleanup_errors[0])

    def test_runtime_uses_prepared_css_and_isolates_mutable_volumes(self):
        config = {
            'name': 'prepared',
            'services': {
                'webserver': {'image': 'prepared-web', 'build': {'context': '/source'}, 'volumes': [{'source': 'css', 'type': 'volume', 'target': '/css'}, {'source': 'data', 'type': 'volume', 'target': '/data'}]},
                'sass-build-example': {'volumes': [{'type': 'volume', 'source': 'css', 'target': '/css/'}]},
            },
            'volumes': {'css': {'name': 'prepared_css'}, 'data': {'name': 'prepared_data'}},
            'networks': {'default': {'name': 'prepared_default'}},
        }
        actual = runner.runtime_compose(config, 'prepared')
        self.assertNotIn('name', actual)
        self.assertNotIn('sass-build-example', actual['services'])
        self.assertNotIn('build', actual['services']['webserver'])
        self.assertEqual(actual['volumes']['css'], {'external': True, 'name': 'prepared_css'})
        self.assertEqual(actual['volumes']['data'], {})
        self.assertEqual(actual['networks']['default'], {})
        self.assertTrue(actual['services']['webserver']['volumes'][0]['read_only'])
        self.assertIn('sass-build-example', config['services'])

    def prepared_config(self):
        return {'services': {
            'webserver': {'environment': {'CONJIN_TEST_PREPROCESS_USER': 'custom-user'}},
            'db': {}, 'volume-init': {},
            'linkchecker': {'environment': {'LINKCHECKER_ORIGIN': 'http://webserver.internal:8080'}},
        }}

    def test_start_reuses_deployment_and_never_builds_or_pulls(self):
        with patch.object(self.instance, 'load_prepared', return_value=self.prepared_config()), patch.object(self.instance, 'compose') as compose:
            self.instance.start('integration')
            self.instance.start('integration')
        calls = [call.args for call in compose.call_args_list]
        self.assertEqual(len(calls), 3)
        self.assertIn('--no-build', calls[1])
        self.assertEqual(calls[2][-2:], ('http://webserver.internal:8080', 'custom-user'))
        self.assertFalse(any('build' in args or '--build' in args for args in calls))

    def test_start_failure_is_not_retried_and_partial_state_is_owned(self):
        with patch.object(self.instance, 'load_prepared', return_value=self.prepared_config()), patch.object(self.instance, 'compose', side_effect=subprocess.CalledProcessError(4, ['docker'])) as compose:
            for _ in range(2):
                with self.assertRaises(runner.Blocked):
                    self.instance.start('integration')
        self.assertEqual(compose.call_count, 1)
        self.assertEqual(self.instance.started, {'integration'})

    def test_missing_and_stale_preparation_are_blocked(self):
        with patch.object(self.instance, 'require'):
            with self.assertRaisesRegex(runner.Blocked, 'No prepared'):
                self.instance.load_prepared('integration')
            directory = self.instance.cache / 'integration'
            directory.mkdir(parents=True)
            (directory / 'state.json').write_text('{"fingerprint":"old", "images":["prepared"]}')
            (directory / 'compose.json').write_text('{"services":{}}')
            with patch.object(runner, 'preparation_fingerprint', return_value='new'):
                with self.assertRaisesRegex(runner.Blocked, 'outdated'):
                    self.instance.load_prepared('integration')

    def test_prepared_inputs_are_checked_once_and_reused(self):
        directory = self.instance.cache / 'integration'
        directory.mkdir(parents=True)
        config = {'services': {}, 'volumes': {'css': {'external': True, 'name': 'prepared_css'}}}
        (directory / 'state.json').write_text('{"fingerprint":"current", "images":["prepared-web"]}')
        (directory / 'compose.json').write_text(json.dumps(config))
        with patch.object(self.instance, 'require'), patch.object(runner, 'preparation_fingerprint', return_value='current'), patch.object(self.instance, 'command') as command:
            self.assertEqual(self.instance.load_prepared('integration'), config)
            self.assertEqual(self.instance.load_prepared('integration'), config)
        self.assertEqual(command.call_count, 2)
        self.assertEqual(command.call_args_list[0].args[0], ['docker', 'image', 'inspect', 'prepared-web'])
        self.assertEqual(command.call_args_list[1].args[0], ['docker', 'volume', 'inspect', 'prepared_css'])

    def test_preparation_builds_explicitly_and_keeps_css_for_runtime(self):
        seed = self.instance.app('demo') / 'deployments/test/target/conjin'
        seed.mkdir(parents=True)
        (seed / 'config.json').write_text('{}')
        (seed / 'users.json').write_text('{}')
        config = {'services': {
            'webserver': {'build': {'context': '/source'}, 'volumes': [{'type': 'volume', 'source': 'css', 'target': '/css'}]},
            'sass-build-example': {'image': 'sass', 'volumes': [{'type': 'volume', 'source': 'css', 'target': '/css'}]},
        }, 'volumes': {'css': {'name': 'prepared_css'}}}
        with patch.object(self.instance, 'require'), patch.object(self.instance, 'command', return_value=json.dumps(config)) as command, patch.object(self.instance, 'compose') as compose, patch.object(runner, 'preparation_fingerprint', return_value='fresh'):
            self.instance.prepare_deployment('demo')
        self.assertEqual(command.call_args_list[0].args[0][1], 'build')
        calls = [call.args for call in compose.call_args_list]
        self.assertEqual(calls[:2], [('demo', 'pull', '--ignore-buildable'), ('demo', 'build')])
        self.assertEqual(calls[-1], ('demo', 'down', '--remove-orphans'))
        self.assertNotIn('--volumes', calls[-1])
        prepared = self.instance.cache / 'demo'
        state = json.loads((prepared / 'state.json').read_text())
        actual = json.loads((prepared / 'compose.json').read_text())
        self.assertEqual(state['fingerprint'], 'fresh')
        self.assertNotIn('build', actual['services']['webserver'])
        self.assertTrue(actual['volumes']['css']['external'])
        self.assertEqual(state['images'], [self.instance.prepared_project + '-demo-webserver:latest'])

    def test_failed_preparation_continues_other_environments(self):
        def prepare(name):
            if name == 'demo':
                raise runner.Blocked('fixture missing tool')
        with patch.object(self.instance, 'prepare_deployment', side_effect=prepare) as called, redirect_stdout(io.StringIO()):
            code = self.instance.prepare(['demo', 'integration'])
        self.assertEqual(code, 1)
        self.assertEqual(called.call_count, 2)
        self.assertEqual([s['status'] for s in self.instance.results], ['blocked', 'passed'])
        self.assertIn('fixture missing tool', (self.instance.cache / 'demo/preparation.log').read_text())

    def test_native_reports_preserve_individual_failures_skips_and_retries(self):
        report = self.repo / 'junit.xml'
        report.write_text('<testsuites><testsuite><testcase classname="C" name="pass"/><testcase name="failure"><failure>bad</failure></testcase><testcase name="skip"><skipped>missing</skipped></testcase></testsuite></testsuites>')
        self.assertEqual([c['status'] for c in runner.read_cases(report, 'junit')], ['passed', 'failed', 'skipped'])
        report.write_text(json.dumps({'suites': [{'title': 'browser', 'specs': [{'title': 'retry', 'tests': [{'status': 'flaky', 'results': [{'duration': 10, 'errors': [{'message': 'first failure'}]}, {'duration': 20}]}]}]}]}))
        case = runner.read_cases(report, 'playwright')[0]
        self.assertEqual((case['status'], case['retries'], case['durationSeconds']), ('flaky', 1, 0.03))

    def test_categories_select_coverage_independently_of_tool(self):
        integration = runner.select_suites(self.suites, 'integration')
        self.assertTrue(all(s.category == 'integration' for s in integration))
        self.assertTrue({'php', 'http', 'deployed'} <= {s.group for s in integration})
        self.assertEqual([s.project for s in runner.select_suites(self.suites, 'integration-http')], ['http'])
        self.assertEqual([s.project for s in runner.select_suites(self.suites, 'e2e-playwright')], ['chromium'])
        with self.assertRaisesRegex(ValueError, 'Unknown test target'):
            runner.select_suites(self.suites, 'integration-playwright')

    def test_misplaced_test_file_is_reported(self):
        (self.repo / 'tests/test_forgotten.py').write_text('')
        with self.assertRaisesRegex(ValueError, 'registered category directory'):
            runner.registry(self.repo)

    def test_new_test_files_are_discovered_without_registry_entries(self):
        source = self.repo / 'tests/suites.json'
        before = source.read_text()
        added = self.repo / 'tests/php/unit/NewBehaviorTest.php'
        added.write_text('')
        selected = runner.select_suites(runner.registry(self.repo), 'php', 'NewBehavior', repo=self.repo)
        self.assertEqual([s.name for s in selected], ['unit-phpunit'])
        self.assertEqual(selected[0].files(self.repo, 'NewBehavior'), [added])
        self.assertEqual(source.read_text(), before)

    def test_each_category_uses_one_framework_invocation(self):
        self.php_files()
        self.instance.php.prefix = ['docker', 'exec', 'worker', 'php']
        with patch.object(self.instance, 'require'), patch.object(self.instance, 'require_php_extensions'), patch.object(self.instance, 'command') as command:
            for suite in self.suites:
                if suite.group in ('php', 'python'):
                    self.instance.run_suite(suite)
        calls = [c.args[0] for c in command.call_args_list]
        self.assertEqual(len(calls), 4)
        php = [c for c in calls if c[0] == 'docker']
        self.assertEqual([c[-2:] for c in php], [['--testsuite', 'unit'], ['--testsuite', 'integration']])
        python = [c for c in calls if '--directory' in c]
        self.assertEqual([c[c.index('--directory') + 1] for c in python], [self.repo / 'tests/python/unit', self.repo / 'tests/python/integration'])

    def test_filtered_php_files_share_one_framework_invocation(self):
        self.php_files()
        self.instance.php.prefix = ['docker', 'exec', 'worker', 'php']
        suite = next(s for s in self.suites if s.name == 'integration-phpunit')
        with patch.object(self.instance, 'require'), patch.object(self.instance, 'require_php_extensions'), patch.object(self.instance, 'command') as command:
            self.instance.run_suite(suite, 'Test.php')
        self.assertEqual(command.call_count, 1)
        files = suite.files(self.repo, 'Test.php')
        self.assertGreater(len(files), 1)
        self.assertEqual(command.call_args.args[0][-len(files):], files)

    def test_unittest_discovers_new_files_and_filters_without_importing_others(self):
        directory = self.repo / 'tests/python/unit'
        (directory / 'test_one.py').write_text('import unittest\nclass One(unittest.TestCase):\n    def test_ok(self):\n        self.assertEqual(2 + 2, 4)\n')
        (directory / 'test_two.py').write_text('import unittest\nclass Two(unittest.TestCase):\n    def test_bad(self):\n        self.fail("fixture failure")\n')
        output = self.repo / 'native.json'
        command = [sys.executable, runner.REPO / 'tests/support/run_unittest.py', '--directory', directory, '--output', output]
        result = subprocess.run(command, capture_output=True, text=True)
        self.assertEqual(result.returncode, 1, result.stderr)
        cases = json.loads(output.read_text())['tests']
        self.assertEqual([c['status'] for c in cases], ['passed', 'failed'])
        (directory / 'test_two.py').write_text('raise RuntimeError("must not import")\n')
        result = subprocess.run([*command, directory / 'test_one.py'], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual([c['name'] for c in json.loads(output.read_text())['tests']], ['test_one.One.test_ok'])

    def test_empty_skipped_or_flaky_coverage_cannot_pass(self):
        suite = self.suites[1]
        for cases, status in [([], 'failed'), ([{'name': 'skip', 'status': 'skipped'}], 'blocked'), ([{'name': 'retry', 'status': 'flaky'}], 'failed')]:
            instance = runner.Runner(self.repo)
            def run(*args):
                report = self.repo / 'native.json'
                report.write_text(json.dumps({'tests': cases}))
                instance.case_report = (report, 'json')
            with patch.object(instance, 'run_suite', side_effect=run), redirect_stdout(io.StringIO()):
                self.assertNotEqual(instance.execute([suite]), 0)
            self.assertEqual(instance.results[0]['status'], status)

    def test_combined_junit_retains_suite_errors_after_passing_cases(self):
        self.instance.results = [{'suite': 'fixture', 'status': 'failed', 'reason': 'Framework warning', 'tests': [{'name': 'passed case', 'status': 'passed'}]}]
        self.instance.summary()
        import xml.etree.ElementTree as ET
        report = ET.parse(self.instance.artifacts / 'junit.xml')
        self.assertEqual(report.find('.//testsuite').get('failures'), '1')
        self.assertIn('Framework warning', report.find('.//failure').text)
        self.assertEqual(len(self.instance.results[0]['tests']), 1)

    def test_linkchecker_failure_retains_html(self):
        suite = next(s for s in self.suites if s.name == 'integration-linkchecker')
        folder = self.instance.artifacts / 'linkchecker'
        folder.mkdir()
        (folder / 'linkchecker-output.html').write_text('failure report')
        with patch.object(self.instance, 'require'), patch.object(self.instance, 'start'), patch.object(self.instance, 'compose', side_effect=subprocess.CalledProcessError(2, ['linkchecker'])):
            with self.assertRaises(subprocess.CalledProcessError):
                self.instance.run_suite(suite)
        self.assertEqual((folder / 'integration.html').read_text(), 'failure report')

    def test_cleanup_only_uses_owned_project_and_preserves_prepared_css(self):
        with patch.object(self.instance, 'compose') as compose:
            self.instance.stop('integration')
        compose.assert_called_once_with('integration', 'down', '--volumes', '--remove-orphans')
        self.assertNotIn('proxy', str(compose.call_args))


if __name__ == '__main__':
    unittest.main()
