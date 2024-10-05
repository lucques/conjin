from pathlib import Path
import subprocess
import json
import os
import stat
import textwrap
from packaging import version, specifiers


###############
# Global vars #
###############

script_dir = Path(os.path.dirname(os.path.realpath(__file__)))
dhall_package_path = script_dir / '..' / 'dhall' / 'package.dhall'


####################
# Helper functions #
####################

def phase_discriminate_deployment_type(config_path):
    print('Discriminating deployment type...')

    input  = f'({config_path}): ({dhall_package_path}).types.LocalDepl'
    result = subprocess.run('dhall type', input=input, shell=True, text=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

    if (result.returncode == 0):
        return 'LocalDepl'
    else:
        input  = f'({config_path}): ({dhall_package_path}).types.RemoteDepl'
        result = subprocess.run('dhall type', input=input, shell=True, text=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

        if (result.returncode == 0):
            return 'RemoteDepl'
        else:
            # Provoke type error and write error to StdErr
            input  = f'{config_path}'
            result = subprocess.run('dhall type', input=input, shell=True, text=True, stdout=subprocess.DEVNULL)
            return None

    
def phase_read_config(config_path, function):
    print('Reading config file...')

    input = f'(({dhall_package_path}).typesTagged.{function} ({config_path}))'
    config_json = subprocess.check_output('dhall-to-json', input=input, shell=True, text=True)

    return json.loads(config_json)


def phase_read_password_list(password_list_path):
    print('Reading password list file...')

    input = f'(({dhall_package_path}).typesTagged.tagPasswordList ({password_list_path}))'
    password_list_json = subprocess.check_output('dhall-to-json', input=input, shell=True, text=True)

    return json.loads(password_list_json)


def phase_check_conjin_version(conjin_dir, app_dir):
    print('Checking conjin version...')

    conjin_metadata_file = conjin_dir / 'composer.json'
    app_metadata_file    = app_dir    / 'metadata.json'

    conjin_metadata = json.loads(conjin_metadata_file.read_text())
    app_metadata    = json.loads(app_metadata_file.read_text())

    conjin_version_given = version.parse(conjin_metadata['version'])
    conjin_version_spec  = specifiers.SpecifierSet(app_metadata['conjinVersion'])

    if conjin_version_given not in conjin_version_spec:
        raise Exception(f'Conjin version {conjin_version_spec} required, but only {conjin_version_given} is available.')


def phase_build_dirs(what, target_dirs):
    print(f'Building {what} dirs...')

    for path in target_dirs:
        path.mkdir(parents=True, exist_ok=True)


def phase_check_volume_source_paths_exist(path_artifacts, config_path, tools_function):
    print('Checking volume source paths exist...')

    for key in path_artifacts:
        print('- ' + key + '...')

        command = 'dhall-to-json'
        input = f'(({dhall_package_path}).{tools_function} ({config_path})).{key}'

        source_paths = subprocess.check_output(command, input=input, shell=True, text=True)
        source_paths = json.loads(source_paths)

        for source_path in source_paths:
            if not Path(source_path).exists():
                raise Exception(f'Volume source path {source_path} does not exist.')


def phase_build_dhall_artifacts(artifacts, config_path, tools_function):
    print('Building artifacts from Dhall config...')

    for key,file in artifacts.items():
        print('- ' + key + '...')

        command = 'dhall-to-' + file['format']
        input = f'(({dhall_package_path}).{tools_function} ({config_path})).{key}'

        content = subprocess.check_output(command, input=input, shell=True, text=True)

        file['path'].write_text(content)


def phase_generate_password_hashes(users2tagged_passwords):
    print('Generating password hashes...')

    users_2_hashes = {}

    for user, tagged_password in users2tagged_passwords.items():
        if tagged_password['tag'] == 'Plain':
            password = tagged_password['contents']

            print('- Hashing password for ' + user + '...')
            hash = subprocess.check_output(f"docker run --rm -it php:8.2-cli php -r 'print(password_hash(\"{ password }\", PASSWORD_DEFAULT));'", shell=True, text=True)
            users_2_hashes[user] = hash
        else:
            hash = tagged_password['contents']
            users_2_hashes[user] = hash

    return users_2_hashes


def phase_build_users_json(target_dir, users_2_hashes):
    print('Building `users.json`...')

    users_2_hashes_json = json.dumps(users_2_hashes, indent=4)
    (target_dir / 'htdocs/users.json').write_text(users_2_hashes_json)


def phase_register_passwords(users_2_tagged_passwords, users_2_commands):
    print('Registering passwords...')

    for user, command in users_2_commands.items():
        print(f'- Registering password for {user}...')

        assert user in users_2_tagged_passwords, f'No password given for user {user}.'
        assert users_2_tagged_passwords[user]['tag'] == 'Plain', f'Password for {user} must be in plain text.'

        subprocess.run(command, input=users_2_tagged_passwords[user]['contents'], shell=True, text=True)


def phase_build_htaccess(
        target_dir,
        conjin_dir,
        url_base,
        force_https: bool,
        force_www_off: bool,
        force_www_on: bool,
        activate_compression: bool,
        permanent_redirects: dict
    ):
    print('Building .htaccess file...')

    content = textwrap.dedent(f'''\
        RewriteEngine on
        RewriteBase {url_base}

        ''')
    
    if force_https:
        content += (conjin_dir / 'src/htaccess_force_https').read_text() + '\n\n'

    if force_www_off:
        content += (conjin_dir / 'src/htaccess_force_www_off').read_text() + '\n\n'

    if force_www_on:
        content += (conjin_dir / 'src/htaccess_force_www_on').read_text() + '\n\n'

    if activate_compression:
        content += (conjin_dir / 'src/htaccess_activate_compression').read_text() + '\n\n'

    # Add permanent redirects
    if permanent_redirects:
        content += '# Permanent redirects\n'
        for source, target in permanent_redirects.items():
            content += f'RewriteRule ^{source}$ {target} [R=301,L]\n'
        content += '\n\n'

    content += (conjin_dir / 'src/htaccess_main').read_text()

    (target_dir / 'htdocs/.htaccess').write_text(content)


def phase_build_docker_images(target_config_files, docker_proj_name):
    print('Building Docker images...')

    for file in target_config_files.values():
        print(f'- {file["path"]}...')
        subprocess.run(f'docker compose --file "{file["path"]}" --project-name "{docker_proj_name}" build', shell=True)


def make_preprocess_target_bin_script(target_dir: str, host: str, url_base: str, prefer_https: bool, user: str, password_retrieval_cmd: str):
    return {
        'path': target_dir / 'bin' / 'preprocess',
        'content': textwrap.dedent(f'''\
            #! /bin/bash
            # This script was auto-generated.

            # Read Password and send it to the server
            password=`{password_retrieval_cmd}` &&
            curl --cookie "user={user}; password=$password" {'https' if prefer_https else 'http'}://{host}{url_base}preprocess/
            ''')
    }


def phase_build_bin_scripts(target_bin_scripts):
    print('Building bin scripts...')

    for file in target_bin_scripts.values():
        file['path'].write_text(file['content'])
        # Make file executable
        st = os.stat(file['path'])
        os.chmod(file['path'], st.st_mode | stat.S_IEXEC)


def phase_install_symlinks_in_local_bin_function(bin_scripts, depl_name):
    print('Installing symlinks in ~/bin...')

    local_bin_dir = Path.home() / 'bin'
    local_bin_dir.mkdir(parents=True, exist_ok=True)

    for file in bin_scripts.values():
        link_path = local_bin_dir / (depl_name + '-' + file['path'].name)
        if link_path.exists():
            link_path.unlink()

        os.symlink(file['path'], link_path)


def get_template_css_dir(module_location):
    if module_location['isShared'] and module_location['isExternal']:
        return 'modules-shared-ext-css/' + module_location['dirName']
    elif module_location['isShared'] and not module_location['isExternal']:
        return 'modules-shared-css/' + module_location['dirName']
    elif not module_location['isShared'] and module_location['isExternal']:
        return 'modules-ext-css/' + module_location['dirName']
    else:
        return 'modules-css/' + module_location['dirName']
