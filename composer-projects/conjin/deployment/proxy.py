import json
import subprocess
import sys

from common import script_dir


proxy_compose_path = script_dir / 'proxy' / 'docker-compose.yml'
proxy_network_name = 'conjin-proxy'
proxy_project_name = 'nginx-proxy'
proxy_service_name = 'nginx-proxy'


def proxy_compose_command(*args):
    return [
        'docker',
        'compose',
        '--file',
        str(proxy_compose_path),
        '--project-name',
        proxy_project_name,
        *args,
    ]


def ensure_proxy_network():
    result = subprocess.run(
        ['docker', 'network', 'inspect', proxy_network_name],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL)

    if result.returncode != 0:
        subprocess.run(
            ['docker', 'network', 'create', proxy_network_name],
            check=True,
            stdout=subprocess.DEVNULL)


def proxy_status(quiet=False):
    result = subprocess.run(
        proxy_compose_command(
            'ps',
            '--status',
            'running',
            '--quiet',
            proxy_service_name),
        capture_output=True,
        text=True)
    container_ids = result.stdout.splitlines() if result.returncode == 0 else []

    proxy_is_ready = False
    if container_ids:
        inspect_result = subprocess.run(
            ['docker', 'inspect', container_ids[0]],
            capture_output=True,
            text=True)
        if inspect_result.returncode == 0:
            container = json.loads(inspect_result.stdout)[0]
            state = container['State']
            health = state.get('Health', {}).get('Status')
            networks = container['NetworkSettings']['Networks']
            proxy_is_ready = (
                state['Status'] == 'running'
                and health in (None, 'healthy')
                and proxy_network_name in networks)

    if proxy_is_ready:
        if not quiet:
            print('Conjin proxy is running.')
        return 0

    if not quiet and result.stderr:
        print(result.stderr.rstrip(), file=sys.stderr)
    print(
        'Conjin proxy is not running. Start it with `vendor/bin/depl proxy up`.',
        file=sys.stderr)
    return 1


def manage_proxy(action, quiet=False):
    if action == 'up':
        ensure_proxy_network()
        subprocess.run(
            proxy_compose_command(
                'up',
                '--detach',
                '--wait',
                '--wait-timeout',
                '90'),
            check=True)
        return proxy_status(quiet)

    if action == 'down':
        subprocess.run(proxy_compose_command('down'), check=True)
        return 0

    return proxy_status(quiet)
