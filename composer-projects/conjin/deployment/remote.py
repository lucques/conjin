from pathlib import Path
import textwrap

from remote_internal import validate_internal_dir

from common import get_template_css_dir, phase_build_bin_scripts, phase_build_dhall_artifacts, phase_build_dirs, phase_build_docker_images, phase_build_htaccess, phase_build_public_entrypoints, phase_build_users_json, phase_check_volume_source_paths_exist, phase_generate_password_hashes, phase_install_symlinks_in_local_bin_function, phase_read_config, phase_register_passwords, script_dir, dhall_package_path


##############
# Entrypoint #
##############

def build_remote_depl(
        config_path,
        create_env_dirs,
        check_vol_source_paths_exist,
        register_passwords,
        install_symlinks_in_local_bin,
        force_build_docker_images
    ):

    ####################
    # Read config file #
    ####################

    config = phase_read_config(config_path, 'tagRemoteDepl')


    #########
    # Paths #
    #########

    conjin_dir = Path(config['depl']['conjinDir']).absolute()
    app_dir    = Path(config['depl']['appDir']).absolute()
    target_dir = Path(config['depl']['targetDir']).absolute()

    internal_name = validate_internal_dir(config['internalDir'], config['depl']['staticFiles'])
    preprocessing_dir = target_dir / 'htdocs' / (internal_name + '-dyn-preprocessing')
    data_dir = target_dir / 'htdocs' / (internal_name + '-dyn-data')

    css_vol_dir = Path(config['depl']['cssVolDir']).absolute()

    errorlog_backup_dir = Path(config['errors']['logging']['contents']['backupDir']).absolute() if config['errors']['logging']['tag'] == 'WithBackup' else None

    store_backup_dir = Path(config['store']['contents']['backupDir']).absolute() if config['store']['tag'] == 'WithBackup' else None


    ##################################
    # Create dir structure of target #
    ##################################

    target_dirs_to_build = [
        target_dir, target_dir / 'bin', target_dir / 'htdocs',
        target_dir / 'conjin', preprocessing_dir, data_dir,
    ]
    phase_build_dirs('target', target_dirs_to_build)
    phase_build_public_entrypoints(target_dir, internal_name + '/framework')
    for directory in (preprocessing_dir, data_dir):
        (directory / '.htaccess').write_text('Require all denied\n')

    ###################################################
    # Create dir structure of environment (vols etc.) #
    ###################################################

    if create_env_dirs:
        env_dirs_to_build = [
            css_vol_dir,
        ]

        for module_id,module in config['depl']['modules'].items():
            if module['compileScss']:
                env_dirs_to_build.append(css_vol_dir / get_template_css_dir(config['depl']['moduleLocations'][module_id]))

        if errorlog_backup_dir is not None:
            env_dirs_to_build.append(errorlog_backup_dir)

        if store_backup_dir is not None:
            env_dirs_to_build.append(store_backup_dir)


        phase_build_dirs('environment', env_dirs_to_build)


    #########################################
    # Build artifacts generated from Dhall #
    ########################################

    artifacts_docker = {
        'docker-compose-yml': {
            'path': target_dir / 'docker-compose.yml',
            'format': 'yaml'
        }
    }

    artifacts = {
        # Docker compose files
        **artifacts_docker,
        
        # htdocs files
        'config-json': {
            'path': target_dir / 'conjin/config.json',
            'format': 'json'
        }
    }

    phase_build_dhall_artifacts(artifacts, config_path, 'artifactsRemote.makeArtifacts')

    ############################
    # Build `htdocs/.htaccess` #
    ############################

    phase_build_htaccess(
        target_dir,
        conjin_dir,
        config['urlBase'],
        force_https=(config['https']['tag'] == 'Force'),
        force_www_off=(config['wwwSubdomain']['tag'] == 'Off'),
        force_www_on=(config['wwwSubdomain']['tag'] == 'On'),
        activate_compression=config['activateCompression'],
        permanent_redirects=config['depl']['permanentRedirects']
    )


    ############################
    # Generate password hashes #
    ############################

    users_2_hashes = phase_generate_password_hashes(config['depl']['authentication']['staticUsers2passwords'])


    ######################
    # Build `users.json` #
    ######################

    phase_build_users_json(target_dir, users_2_hashes)


    ########################################
    # Check that volume source paths exist #
    ########################################

    if check_vol_source_paths_exist:
        phase_check_volume_source_paths_exist(['docker-compose-yml-volume-sources'], config_path, 'artifactsRemote.makeArtifacts')


    ############################################
    # Register passwords for preprocess script #
    ############################################

    if register_passwords:
        users_2_commands = {}
        if 'preprocessScriptPasswordRegisterCmd' in config['desktopIntegration']:
            users_2_commands[config['desktopIntegration']['preprocessScriptUser']] = config['desktopIntegration']['preprocessScriptPasswordRegisterCmd']
        
        phase_register_passwords(config['depl']['authentication']['staticUsers2passwords'], users_2_commands)
    

    #######################
    # Build Docker images #
    #######################

    if force_build_docker_images:
        phase_build_docker_images(artifacts_docker, config['depl']['dockerProjName'])


    #########################
    # Build `bin/*` scripts #
    #########################

    # Define some bash commands that are used in multiple scripts
    compose_command = textwrap.indent(textwrap.dedent(f'''\
        compose=(docker compose \\
                --file {artifacts['docker-compose-yml']['path']} \\
                --project-name {config['depl']['dockerProjName']})
        ''').strip(), '                ')

    sass_compile_commands = []
    for module_id, module in config['depl']['modules'].items():
        if module['compileScss']:
            location = config['depl']['moduleLocations'][module_id]
            location_type = 'shared' if location['isShared'] else 'local'
            service = f'sass-compile-{location["dirName"]}-{location_type}'
            sass_compile_commands.append(f'"${{compose[@]}}" run --rm --no-deps "{service}"')

    sass_compile_commands_indented = textwrap.indent('\n'.join(sass_compile_commands), '                ')

    password_lookup = textwrap.indent(textwrap.dedent(f'''\
        password=$({config['desktopIntegration']['preprocessScriptPasswordLookupCmd']})
        if [[ -z "$password" ]]; then
            echo 'Preprocessing password lookup returned an empty password.' >&2
            exit 1
        fi
        ''').strip(), '                ')

    # Now build the scripts
    target_bin_scripts = {
        'upload': {
            'path': target_dir / 'bin' / 'upload',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                set -e
                
{password_lookup}

                export USER_UID=$(id -u)
                export USER_GID=$(id -g)

{compose_command}
{sass_compile_commands_indented}

                "${{compose[@]}}" run --rm --no-deps upload
                printf '%s\\n' "$password" | "{target_dir / 'bin' / 'preprocess'}" --password-stdin
            ''')
        },
        'upload-omit-sass': {
            'path': target_dir / 'bin' / 'upload-omit-sass',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                set -e
                
{password_lookup}

                export USER_UID=$(id -u)
                export USER_GID=$(id -g)

{compose_command}
                "${{compose[@]}}" run --rm --no-deps upload
                printf '%s\\n' "$password" | "{target_dir / 'bin' / 'preprocess'}" --password-stdin
            ''')
        },
        'preprocess': {
            'path': target_dir / 'bin' / 'preprocess',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                set -e

                if [[ $# -eq 1 && "$1" == '--password-stdin' ]]; then
                    IFS= read -r password
                elif [[ $# -eq 0 ]]; then
{textwrap.indent(password_lookup, '    ')}
                else
                    echo 'Usage: preprocess [--password-stdin]' >&2
                    exit 1
                fi
                if [[ -z "$password" ]]; then
                    echo 'Preprocessing password must not be empty.' >&2
                    exit 1
                fi
                curl --fail-with-body --show-error --cookie "user={config['desktopIntegration']['preprocessScriptUser']}; password=$password" {'https' if config['https']['tag'] in ['Force', 'Prefer'] else 'http'}://{config['host']}{config['urlBase']}preprocess/
                ''')
        },
    }

    if errorlog_backup_dir is not None:
        target_bin_scripts['backup-errorlog'] = {
            'path': target_dir / 'bin' / 'backup-errorlog',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                set -e
                                       
                export USER_UID=$(id -u)
                export USER_GID=$(id -g)
                                       
                export TIMESTAMP=$(date '+%Y-%m-%d.%H-%M-%S')
                                       
                docker compose \\
                        --file         {artifacts['docker-compose-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        run --rm --no-deps backup-errorlog
            ''')
        }

    if store_backup_dir is not None:
        target_bin_scripts['backup-store'] = {
            'path': target_dir / 'bin' / 'backup-store',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                set -e

                export USER_UID=$(id -u)
                export USER_GID=$(id -g)
                
                export TIMESTAMP=$(date '+%Y-%m-%d.%H-%M-%S')
                                                                              
                docker compose \\
                        --file         {artifacts['docker-compose-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        run --rm --no-deps backup-store
            ''')
        }

    phase_build_bin_scripts(target_bin_scripts)


    ####################
    # Install in ~/bin #
    ####################

    if install_symlinks_in_local_bin:
        phase_install_symlinks_in_local_bin_function(target_bin_scripts, config['depl']['dockerProjName'])

    
    print('Done.')
