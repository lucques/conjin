"""Isolated Apache/PHP checks using an existing image; no build or network access.

Requires the integration fixture's generated test config and users.json.
Only task-owned containers and temporary data are created and cleaned up.
"""

import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time
import sys

repo = Path(__file__).resolve().parents[1]
framework = repo / 'composer-projects/conjin'
app = repo / 'composer-projects/integration-test-app'
sys.path.insert(0, str(framework / 'deployment'))
from common import phase_build_htaccess, phase_build_public_entrypoints

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--image', required=True, help='prepared Conjin webserver image')
parser.add_argument('--config-dir', required=True, type=Path, help='directory containing generated config.json and users.json')
arguments = parser.parse_args()
image = arguments.image
seed = arguments.config_dir.resolve()
for filename in ('config.json', 'users.json'):
    if not (seed / filename).is_file():
        parser.error(f'Missing prepared configuration: {seed / filename}')
count = 0
cases = []
report = Path(os.environ.get('TEST_ARTIFACTS_DIR', str(repo / 'test-artifacts/http-layouts'))) / 'results.json'
for layout, urlbase, internal in [('local', '/', None), ('remote', '/', 'conjin'), ('remote-nested', '/nested/', 'custom-private')]:
    started = time.monotonic()
    case = {'name': layout, 'status': 'passed', 'reason': ''}
    with tempfile.TemporaryDirectory(prefix='conjin-private-layout-') as tmp:
        files = Path(tmp)
        files.chmod(0o755)
        web = files / 'htdocs' / urlbase.strip('/')
        web.mkdir(parents=True, exist_ok=True)
        private = files / 'conjin' if internal is None else web / internal
        private.mkdir(parents=True)
        (private / '.htaccess').write_text('Require all denied\n')
        runtime = files / 'conjin-preprocessing-runtime/conjin-dyn-preprocessing' if internal is None else web / (internal + '-dyn-preprocessing')
        runtime.parent.mkdir(parents=True, exist_ok=True)
        runtime.parent.chmod(0o777)
        runtime.mkdir(mode=0o777)
        runtime.chmod(0o777)
        data_dir = files / 'conjin-dyn-data' if internal is None else web / (internal + '-dyn-data')
        data_dir.mkdir()
        (data_dir / '.htaccess').write_text('Require all denied\n')
        (runtime / '.htaccess').write_text('Require all denied\n')
        store = data_dir / 'store'
        store.mkdir(mode=0o777)
        store.chmod(0o777)
        (private / 'content/res').mkdir(parents=True)
        (private / 'content/res/example.txt').write_text('AUTHORIZED CONTENT RESOURCE')
        (private / 'content/notes.md').write_text('PRIVATE CONTENT SOURCE')
        (private / 'content/index.php').write_text("<? $preprocess = function (TargetPreprocessContext $c) { $c->activate_template('template-generic'); $c->activate_module('subpages-all'); }; $process = function (Target $target) { echo 'PRIVATE LAYOUT HOME'; };")
        shutil.copytree(app / 'src/content/login-profile', private / 'content/login-profile')
        (private / 'content/login-profile/child/res').mkdir()
        (private / 'content/login-profile/child/res/secret.txt').write_text('SECRET AUTHORIZED RESOURCE')
        shutil.copytree(app / 'src/system', private / 'system')
        config = json.loads((seed / 'config.json').read_text())
        config.update(path_base='/files/' + str(private.relative_to(files)), path_preprocess='/files/' + str(runtime.relative_to(files)), path_store='/files/' + str(store.relative_to(files)), url_base=urlbase, errorlog_dir=None, https=False, host='localhost')
        mounts = ['-v', str(files) + ':/files', '-v', str(framework / 'src/conjin') + ':/files/' + str((private/'framework').relative_to(files)) + ':ro', '-v', str(app/'vendor') + ':' + config['path_base'] + '/vendor:ro']
        for location in config['module_2_location'].values():
            shared, external, name = location['isShared'], location['isExternal'], location['dirName']
            source = (framework if shared else app) / ('ext' if external else 'src') / ('modules-shared' if shared else 'modules') / name
            target = config['path_base'] + '/modules' + ('-shared' if shared else '') + ('-ext' if external else '') + '/' + name
            mounts += ['-v', str(source) + ':' + target + ':ro']
        for shared in [False, True]:
            for external in [False, True]:
                group = 'modules' + ('-shared' if shared else '') + ('-ext' if external else '')
                config['module_2_location'][group + '-fixture'] = {'dirName': 'fixture-assets', 'isShared': shared, 'isExternal': external}
                for suffix in ['', '-css']:
                    owner = private / (group + suffix) / 'fixture-assets'
                    (owner / 'res').mkdir(parents=True)
                    if suffix:
                        (owner / 'print.css').write_text('body { color: black; }')
                        (owner / 'print.css.map').write_text('{}')
                        (owner / 'source.scss').write_text('PRIVATE SCSS')
                        (owner / 'leak.css').symlink_to('processing.php')
                    (owner / 'processing.php').write_text('<? echo "PRIVATE MODULE CODE";')
                    (owner / 'res/test.js').write_text('window.fixture = true;')
                    (owner / 'res/test.css').write_text('body { color: black; }')
                    (owner / 'res/test.wasm').write_bytes(b'\0asm\x01\0\0\0')
                    (owner / 'res/space & name.txt').write_text('SPECIAL FILENAME')
                    (owner / 'res/secret.PHP').write_text('<? echo "DO NOT EXECUTE";')
                    (owner / 'res/escape.txt').symlink_to('../processing.php')
                    (owner / 'res/.secret').write_text('HIDDEN')
        site_config = dict(config)
        if internal is None:
            site_config.update(path_base='..', path_preprocess='../../conjin-preprocessing-runtime/conjin-dyn-preprocessing', path_store='../../conjin-dyn-data/store')
        else:
            # Exercise the actual generator; absolute fixture paths hid the
            # regression for deployments configured with pathBase = "..".
            expression = f'({framework}/deployment/dhall/artifacts-remote.dhall).makeRuntimePaths "{internal}"'
            paths = json.loads(subprocess.check_output(['dhall-to-json'], input=expression, text=True))
            site_config.update(paths)
            errorlog = data_dir / 'logs/error'
            errorlog.mkdir(parents=True, mode=0o777)
            errorlog.chmod(0o777)
        (private / 'config.json').write_text(json.dumps(site_config))
        shutil.copy(seed / 'users.json', private / 'users.json')
        # Generate the actual shared htaccess for this URL base.
        stage = files / 'stage'
        (stage/'htdocs').mkdir(parents=True)
        (stage / 'conjin').mkdir()
        phase_build_public_entrypoints(stage, '../conjin/framework' if internal is None else internal + '/framework')
        for entrypoint in ('index.php', 'module-resource.php'):
            shutil.copy(stage / 'htdocs' / entrypoint, web / entrypoint)
        phase_build_htaccess(stage, framework, urlbase, False, False, False, False, {})
        shutil.copy(stage/'htdocs/.htaccess', web/'.htaccess')
        # Content changes during the failure checks must take effect immediately.
        (files / 'test.ini').write_text('opcache.enable=0\n')
        mounts += ['-v', str(files / 'test.ini') + ':/usr/local/etc/php/conf.d/zz-layout-test.ini:ro']
        name = f'conjin-private-check-{os.getpid()}-{layout}'
        subprocess.run(['docker', 'run', '-d', '--rm', '--pull', 'never', '--network', 'none', '-e', 'CONJIN_ENABLE_INTERNAL_AUTH=1', '--name', name] + mounts + [image], check=True, capture_output=True)
        def request(path, cookie=None, method='GET', headers=None):
            code = '''$opts = json_decode($argv[2], true); $c = curl_init("http://127.0.0.1:" . $opts["port"] . $argv[1]); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $opts["method"], CURLOPT_HTTPHEADER => $opts["headers"], CURLOPT_HEADER => true]); if ($opts["method"] === "HEAD") curl_setopt($c, CURLOPT_NOBODY, true); $r = curl_exec($c); $n = curl_getinfo($c, CURLINFO_HEADER_SIZE); echo json_encode([curl_getinfo($c, CURLINFO_HTTP_CODE), substr($r, $n), substr($r, 0, $n)]);'''
            h = list(headers or [])
            if cookie: h.append('Cookie: ' + cookie)
            out = subprocess.check_output(['docker', 'exec', name, 'php', '-r', code, path, json.dumps({'port':8080 if cookie else 80, 'method': method, 'headers':h})], text=True)
            return json.loads(out)
        try:
            for i in range(40):
                result = request(urlbase+'module-resource.php')
                if result[0] == 404: break
                time.sleep(.1)
            result = request(urlbase+'preprocess/', 'user=preprocess')
            assert result[0] == 200 and 'Fatal error' not in result[1], (layout, 'preprocess', result)
            result = request(urlbase)
            assert result[0] == 200 and 'PRIVATE LAYOUT HOME' in result[1], (layout, 'home', result)
            assert 'Cache-Control: no-store' in result[2], result
            result = request(urlbase, headers=['If-Modified-Since: Wed, 01 Jan 2031 00:00:00 GMT'])
            assert result[0] == 200 and 'Last-Modified:' not in result[2], result
            count += 3
            previous = (runtime / 'obj/target_root').read_bytes()
            content_script = private / 'content/index.php'
            original_script = content_script.read_text()
            content_script.write_text("<? $preprocess = function (TargetPreprocessContext $c) { throw new RuntimeException('deliberate generation failure'); };")
            try:
                failed = request(urlbase+'preprocess/', 'user=preprocess')
                assert failed[0] == 500, (layout, 'failed generation', failed)
            finally:
                content_script.write_text(original_script)
            assert (runtime / 'obj/target_root').read_bytes() == previous
            assert runtime.exists()
            assert not Path(str(runtime) + '-new').exists()
            result = request(urlbase)
            assert result[0] == 200 and 'PRIVATE LAYOUT HOME' in result[1], (layout, 'preserved output', result)
            result = request(urlbase+'preprocess/', 'user=preprocess')
            assert result[0] == 200 and 'Preprocessing done.' in result[1], (layout, 'replacement publication', result)
            assert (runtime / 'obj/target_root').is_file()
            assert not Path(str(runtime) + '-old').exists()
            count += 3
            for path in ['content/notes.md', 'system/login.php', 'vendor/composer/installed.json', 'modules-shared/title/default_config.php']:
                result = request(urlbase+path)
                assert result[0] == 404, (layout, path, result)
                count += 1
            for path in ['config.json', 'users.json']:
                assert request(urlbase+path)[0] == 404, (layout,path)
                count += 1
            if internal:
                for path in ['content/notes.md', 'vendor/composer/installed.json', 'system/login.php', 'modules/fixture-assets/processing.php', 'framework/index.php', 'framework/module-resource.php', 'framework/inc/core_init.php', 'config.json', 'users.json']:
                    assert request(urlbase+internal+'/'+path)[0] == 403, (layout,path)
                    count += 1
                for suffix, path in [('-dyn-preprocessing', 'obj/target_root'), ('-dyn-data', 'store/example.sqlite')]:
                    if suffix == '-dyn-data':
                        (store / 'example.sqlite').write_text('PERSISTENT PRIVATE DATA')
                    assert request(urlbase+internal+suffix+'/'+path)[0] == 403
                    count += 1
            for group in ['modules','modules-shared','modules-ext','modules-shared-ext']:
                for suffix in ['', '-css']:
                    if suffix:
                        compiled_url = urlbase+group+suffix+'/fixture-assets/'
                        for asset, mime in [('print.css', 'text/css'), ('print.css.map', 'application/json')]:
                            result = request(compiled_url+asset)
                            assert result[0] == 200 and mime in result[2], (layout, compiled_url+asset, result)
                            count += 1
                        for asset in ['processing.php', 'source.scss', 'leak.css']:
                            assert request(compiled_url+asset)[0] == 404, (layout, compiled_url+asset)
                            count += 1
                    prefix = urlbase+group+suffix+'/fixture-assets/res/'
                    for path, mime in [('test.js','application/javascript'), ('test.css','text/css'), ('test.wasm','application/wasm'), ('space%20%26%20name.txt','text/plain')]:
                        result = request(prefix+path)
                        assert result[0] == 200 and mime in result[2], (layout, prefix+path, result)
                        count += 1
                    assert 'Cache-Control: public, no-cache' in result[2] and 'Last-Modified:' in result[2], result
                    result = request(prefix+'test.js', method='HEAD')
                    assert result[0] == 200 and result[1] == '', result
                    assert request(prefix+'test.js', headers=['If-Modified-Since: Wed, 01 Jan 2031 00:00:00 GMT'])[0] == 304
                    count += 2
                    for path in ['secret.PHP', 'escape.txt', '.secret']:
                        result = request(prefix+path)
                        assert result[0] in (403,404), (layout,prefix+path,result)
                        count += 1
            for query in ['group=modules&module=fixture-assets&res=../processing.php', 'group=modules&module=fixture-assets&res=%2e%2e%2fprocessing.php', 'group=modules&module=not-configured&res=test.js']:
                assert request(urlbase+'module-resource.php?'+query)[0] == 404, (layout,query)
                count += 1
            result = request(urlbase+'res/example.txt')
            assert result[:2] == [200, 'AUTHORIZED CONTENT RESOURCE'], result
            assert 'Cache-Control: private, no-cache' in result[2] and 'Last-Modified:' in result[2], result
            result = request(urlbase+'res/example.txt', headers=['If-Modified-Since: Wed, 01 Jan 2031 00:00:00 GMT'])
            assert result[0] == 304 and result[1] == '', result
            result = request(urlbase+'login-profile/child/res/secret.txt')
            assert result[0] == 302 and 'SECRET AUTHORIZED RESOURCE' not in result[1], result
            assert 'Cache-Control: no-store' in result[2], result
            result = request(urlbase+'login-profile/child/res/secret.txt', headers=['If-Modified-Since: Wed, 01 Jan 2031 00:00:00 GMT'])
            assert result[0] == 302 and 'Last-Modified:' not in result[2], result
            assert request(urlbase+'login-profile/child/res/secret.txt', 'user=root')[:2] == [200, 'SECRET AUTHORIZED RESOURCE']
            count += 1
            for query in ['req=res&res=../index.php', 'req=res&res=../notes.md', 'req=res&inc=../&res=notes.md']:
                result = request(urlbase+'index.php?'+query)
                assert result[0] == 404, (layout,query,result)
                count += 1
            count += 2
            print(layout, 'passed', flush=True)
        except Exception as error:
            case.update(status='failed', reason=str(error))
            print(subprocess.check_output(['docker','logs','--tail','15',name], stderr=subprocess.STDOUT,text=True))
        finally:
            try:
                cleanup_paths = [config['path_preprocess'], config['path_store']]
                if internal:
                    cleanup_paths.append('/files/' + str(errorlog.relative_to(files)))
                subprocess.run(['docker', 'exec', name, 'chown', '-R', f'{os.getuid()}:{os.getgid()}', *cleanup_paths], check=True, capture_output=True)
            finally:
                subprocess.run(['docker','stop',name], check=True, capture_output=True)
    case['durationSeconds'] = round(time.monotonic() - started, 3)
    cases.append(case)
    report.parent.mkdir(parents=True, exist_ok=True)
    report.write_text(json.dumps({'tests': cases, 'assertions': count}, indent=4) + '\n')
print(f'{count} Apache/PHP assertions passed with actual preprocessing, rendering and authorization.')

raise SystemExit(int(any(case['status'] != 'passed' for case in cases)))
