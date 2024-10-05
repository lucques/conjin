from pathlib import Path
import textwrap

from common import get_template_css_dir, make_preprocess_target_bin_script, phase_build_bin_scripts, phase_build_dhall_artifacts, phase_build_dirs, phase_build_docker_images, phase_build_htaccess, phase_build_users_json, phase_check_conjin_version, phase_check_volume_source_paths_exist, phase_generate_password_hashes, phase_install_symlinks_in_local_bin_function, phase_read_config, phase_register_passwords, script_dir, dhall_package_path


##############
# Entrypoint #
##############

def build_remote_depl(
        config_path,
        check_conjin_version,
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

    css_vol_dir = Path(config['depl']['cssVolDir']).absolute()

    errorlog_backup_dir = Path(config['errors']['logging']['contents']['backupDir']).absolute() if config['errors']['logging']['tag'] == 'WithBackup' else None

    store_backup_dir = Path(config['store']['contents']['backupDir']).absolute() if config['store']['tag'] == 'WithBackup' else None


    #####################################
    # Check that conjin version matches #
    #####################################

    if check_conjin_version:
        phase_check_conjin_version(conjin_dir, app_dir)


    ##################################
    # Create dir structure of target #
    ##################################

    target_dirs_to_build = [
        target_dir,
        target_dir / 'bin',
        target_dir / 'htdocs',
        # Create empty dir. Thereby on upload, any present preprocess data will
        # be removed on the remote and replaced by the empty dir.
        target_dir / 'htdocs' / 'preprocess',
    ]

    if config['store']['tag'] != 'None':
        # Create empty dir. If not present on upload it will get created.
        # On the remote, files *inside* will not be removed though, as the sync
        # is performed with the `--exclude` flag.
        target_dirs_to_build.append(target_dir / 'htdocs' / 'store')

    if config['errors']['logging'] != 'None':
        # Create empty dir. If not present on upload it will get created.
        # On the remote, files *inside* will not be removed though, as the sync
        # is performed with the `--exclude` flag.
        target_dirs_to_build.append(target_dir / 'htdocs' / 'logs' / 'error')


    phase_build_dirs('target', target_dirs_to_build)


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
        'docker-compose-upload-yml': {
            'path': target_dir / 'docker-compose-upload.yml',
            'format': 'yaml'
        },
        'docker-compose-upload-omit-sass-yml': {
            'path': target_dir / 'docker-compose-upload-omit-sass.yml',
            'format': 'yaml'
        }
    }

    artifacts = {
        # Docker compose files
        **artifacts_docker,
        
        # htdocs files
        'config-json': {
            'path': target_dir / 'htdocs/config.json',
            'format': 'json'
        }
    }

    if errorlog_backup_dir != None:
        artifacts['docker-compose-backup-errorlog-yml'] = {
            'path': target_dir / 'docker-compose-backup-errorlog.yml',
            'format': 'yaml'
        }

    if store_backup_dir != 'None':
        artifacts['docker-compose-backup-store-yml'] = {
            'path': target_dir / 'docker-compose-backup-store.yml',
            'format': 'yaml'
        }


    phase_build_dhall_artifacts(artifacts, config_path, 'artifactsDockerRemote.makeArtifacts')


    ########################################
    # Check that volume source paths exist #
    ########################################

    if check_vol_source_paths_exist:
        phase_check_volume_source_paths_exist(['docker-compose-upload-yml-volume-sources'], config_path, 'artifactsDockerRemote.makeArtifacts')


    ############################
    # Generate password hashes #
    ############################

    users_2_hashes = phase_generate_password_hashes(config['depl']['authentication']['staticUsers2passwords'])


    ######################
    # Build `users.json` #
    ######################

    phase_build_users_json(target_dir, users_2_hashes)


    ############################################################
    # Register passwords for preprocess script and linkchecker #
    ############################################################

    if register_passwords:
        users_2_commands = {}
        if 'preprocessScriptPasswordRegisterCmd' in config['depl']['desktopIntegration']:
            users_2_commands[config['depl']['desktopIntegration']['preprocessScriptUser']] = config['depl']['desktopIntegration']['preprocessScriptPasswordRegisterCmd']
        
        phase_register_passwords(config['depl']['authentication']['staticUsers2passwords'], users_2_commands)


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
    

    #######################
    # Build Docker images #
    #######################

    if force_build_docker_images:
        phase_build_docker_images(artifacts_docker, config['depl']['dockerProjName'])


    #########################
    # Build `bin/*` scripts #
    #########################

    target_bin_scripts = {
        'upload': {
            'path': target_dir / 'bin' / 'upload',
            'content': textwrap.dedent(f'''\
                #! /bin/bash
                
                export USER_UID=$(id -u)
                export USER_GID=$(id -g)

                docker compose \\
                        --file         {artifacts['docker-compose-upload-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']}-upload \\
                        up \\
                && sleep 1 \\
                && {target_dir / 'bin' / 'preprocess'} \\
            ''')
        },
        'upload-omit-sass': {
            'path': target_dir / 'bin' / 'upload-omit-sass',
            'content': textwrap.dedent(f'''\
                #! /bin/bash
                
                export USER_UID=$(id -u)
                export USER_GID=$(id -g)

                docker compose \\
                        --file         {artifacts['docker-compose-upload-omit-sass-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']}-upload \\
                        up \\
                && sleep 1 \\
                && {target_dir / 'bin' / 'preprocess'} \\
            ''')
        },
    }

    if errorlog_backup_dir is not None:
        target_bin_scripts['backup-errorlog'] = {
            'path': target_dir / 'bin' / 'backup-errorlog',
            'content': textwrap.dedent(f'''\
                #! /bin/bash
                                       
                export USER_UID=$(id -u)
                export USER_GID=$(id -g)
                                       
                export TIMESTAMP=$(date '+%Y-%m-%d.%H-%M-%S')
                                       
                docker compose \\
                        --file         {artifacts['docker-compose-backup-errorlog-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']}-backup-errorlog \\
                        up
            ''')
        }

    if store_backup_dir is not None:
        target_bin_scripts['backup-store'] = {
            'path': target_dir / 'bin' / 'backup-store',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                export USER_UID=$(id -u)
                export USER_GID=$(id -g)
                
                export TIMESTAMP=$(date '+%Y-%m-%d.%H-%M-%S')
                                                                              
                docker compose \\
                        --file         {artifacts['docker-compose-backup-store-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']}-backup-store \\
                        up
            ''')
        }

    target_bin_scripts['preprocess'] = make_preprocess_target_bin_script(
        target_dir,
        config['host'],
        config['urlBase'],
        config['https']['tag'] in ['Force', 'Prefer'],
        config['depl']['desktopIntegration']['preprocessScriptUser'],
        config['depl']['desktopIntegration']['preprocessScriptPasswordLookupCmd'])

    phase_build_bin_scripts(target_bin_scripts)


    ####################
    # Install in ~/bin #
    ####################

    if install_symlinks_in_local_bin:
        phase_install_symlinks_in_local_bin_function(target_bin_scripts, config['depl']['dockerProjName'])

    
    print('Done.')
