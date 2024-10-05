from pathlib import Path
import stat
import textwrap

from common import get_template_css_dir, make_preprocess_target_bin_script, phase_build_bin_scripts, phase_build_dhall_artifacts, phase_build_dirs, phase_build_docker_images, phase_build_htaccess, phase_build_users_json, phase_check_conjin_version, phase_check_volume_source_paths_exist, phase_generate_password_hashes, phase_install_symlinks_in_local_bin_function, phase_read_config, phase_register_passwords, script_dir, dhall_package_path


##############
# Entrypoint #
##############

def build_local_depl(
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

    config = phase_read_config(config_path, 'tagLocalDepl')


    #########
    # Paths #
    #########

    conjin_dir = Path(config['depl']['conjinDir']).absolute()
    app_dir    = Path(config['depl']['appDir']).absolute()
    target_dir = Path(config['depl']['targetDir']).absolute()

    css_vol_dir          = Path(config['depl']['cssVolDir']).absolute()
    linkchecker_vol_dir  = Path(config['linkcheckerVolDir']).absolute()
    preprocess_vol_dir   = Path(config['preprocessVolDir']).absolute()
    
    errorlog_vol_dir     = Path(config['errors']['logToVolDir']).absolute()   if 'logToVolDir' in config['errors'] else None
    db_storage_vol_dir   = Path(config['db']['storageVolDir']).absolute()       if 'db' in config and 'storageVolDir'     in config['db'] else None
    store_vol_dir        = Path(config['store']['volDir']).absolute()           if 'store' in config else None
    
    store_backup_dir     = Path(config['store']['backupDir']).absolute()        if 'store' in config and 'backupDir'      in config['store'] else None   

    db_init_dir          = Path(config['db']['initFilesDir']).absolute()        if 'db' in config and 'initFilesDir'      in config['db'] else None
    db_backup_dir        = Path(config['db']['backupFilesVolDir']).absolute()   if 'db' in config and 'backupFilesVolDir' in config['db'] else None


    #####################################
    # Check that conjin version matches #
    #####################################

    if check_conjin_version:
        phase_check_conjin_version(conjin_dir, app_dir)


    ######################################
    # Create dir structure of target dir #
    ######################################

    target_dirs_to_build = [
        target_dir,
        target_dir / 'bin',
        target_dir / 'htdocs',
    ]

    phase_build_dirs('target', target_dirs_to_build)


    ###################################################
    # Create dir structure of environment (vols etc.) #
    ###################################################

    if create_env_dirs:
        env_dirs_to_build = [
            css_vol_dir,
            linkchecker_vol_dir,
            preprocess_vol_dir,
        ]

        for module_id,module in config['depl']['modules'].items():
            if module['compileScss']:
                env_dirs_to_build.append(css_vol_dir / get_template_css_dir(config['depl']['moduleLocations'][module_id]))

        if errorlog_vol_dir is not None:
            env_dirs_to_build.append(errorlog_vol_dir)

        if db_init_dir is not None:
            env_dirs_to_build.append(db_init_dir)
        if db_storage_vol_dir is not None:
            env_dirs_to_build.append(db_storage_vol_dir)
        if db_backup_dir is not None:
            env_dirs_to_build.append(db_backup_dir)

        if store_vol_dir is not None:
            env_dirs_to_build.append(store_vol_dir)
        if store_backup_dir is not None:
            env_dirs_to_build.append(store_backup_dir)

        phase_build_dirs('environment', env_dirs_to_build)

        # Make `preprocess` and `store` dirs writable for all users
        preprocess_vol_dir.chmod(preprocess_vol_dir.stat().st_mode | stat.S_IWGRP | stat.S_IWOTH)

        if errorlog_vol_dir is not None:
            errorlog_vol_dir.chmod(errorlog_vol_dir.stat().st_mode | stat.S_IWGRP | stat.S_IWOTH)
        if store_vol_dir is not None:
            store_vol_dir.chmod(store_vol_dir.stat().st_mode | stat.S_IWGRP | stat.S_IWOTH)


    #########################################
    # Build artifacts generated from Dhall #
    ########################################

    artifacts_config_files_docker = {
        'docker-compose-app-yml': {
            'path':   target_dir / 'docker-compose-app.yml',
            'format': 'yaml'
        },
        'docker-compose-linkchecker-yml': {
            'path':  target_dir / 'docker-compose-linkchecker.yml',
            'format': 'yaml'
        }
    }

    artifacts_config_files = {
        # Docker compose files
        **artifacts_config_files_docker,

        # htdocs files
        'config-json': {
            'path': target_dir / 'htdocs/config.json',
            'format': 'json'
        }
    }

    phase_build_dhall_artifacts(artifacts_config_files, config_path, 'artifactsDockerLocal.makeArtifacts')


    ########################################
    # Check that volume source paths exist #
    ########################################

    if check_vol_source_paths_exist:
        phase_check_volume_source_paths_exist(['docker-compose-app-yml-volume-sources'], config_path, 'artifactsDockerLocal.makeArtifacts')


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
        if 'linkcheckerPasswordRegisterCmd' in config:
            users_2_commands[config['linkcheckerUser']] = config['linkcheckerPasswordRegisterCmd']
        
        phase_register_passwords(config['depl']['authentication']['staticUsers2passwords'], users_2_commands)


    ############################
    # Build `htdocs/.htaccess` #
    ############################

    # HTTPS is not supported for LocalDeploy
    # Compression is already handled by the nginx image
    phase_build_htaccess(
        target_dir,
        conjin_dir,
        '/',
        force_https=False,
        force_www_off=False,
        force_www_on=False,
        activate_compression=False,
        permanent_redirects=config['depl']['permanentRedirects']
    )
    

    #######################
    # Build Docker images #
    #######################

    if force_build_docker_images:
        phase_build_docker_images(artifacts_config_files_docker, config['depl']['dockerProjName'])


    #########################
    # Build `bin/*` scripts #
    #########################

    target_bin_scripts = {
        'up': {
            'path': target_dir / 'bin' / 'up',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                docker compose \\
                        --file         {artifacts_config_files['docker-compose-app-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        up --detach \\
                && sleep 1 \\
                && {target_dir / 'bin' / 'preprocess'} \\
                ''')
        },
        'down': {
            'path': target_dir / 'bin' / 'down',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                docker compose \\
                        --file         {artifacts_config_files['docker-compose-app-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        down
                ''')
        },
        'linkchecker': {
            'path': target_dir / 'bin' / 'linkchecker',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                export LINKCHECKER_PREFIX=''

                # Iterate over all arguments passed to the script and append it
                for arg in "$@"; do
                    LINKCHECKER_PREFIX="$LINKCHECKER_PREFIX$arg/"
                done

                export LINKCHECKER_PASSWORD=`{config['linkcheckerPasswordLookupCmd']}` &&
                docker compose \\
                        --file         {artifacts_config_files['docker-compose-linkchecker-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        --user "$(id -u):$(id -g)" \\
                        up &&

                echo "Results are output in {config['linkcheckerVolDir']}/linkchecker-output.html, launching web browser now..."
                xdg-open "file://{config['linkcheckerVolDir']}/linkchecker-output.html"
                ''')
        }
    }

    target_bin_scripts['preprocess'] = make_preprocess_target_bin_script(
        target_dir,
        config['nginxVirtualHost'],
        '/',
        False, # HTTPS is not supported for LocalDepl
        config['depl']['desktopIntegration']['preprocessScriptUser'],
        config['depl']['desktopIntegration']['preprocessScriptPasswordLookupCmd'])
    
    if db_backup_dir is not None:
        target_bin_scripts['backup-db'] = {
            'path': target_dir / 'bin' / 'backup-db',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                # Command to pass to db container
                cmd="exec mysqldump -u root -p\"rutus\" --all-databases > /backup/backup-$(date '+%Y-%m-%d.%H-%M-%S').mysql.sql"

                docker compose \\
                        --file         {artifacts_config_files['docker-compose-app-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        exec --user "$(id -u):$(id -g)" db sh -c "${{cmd}}"
                ''')
        }
    
    if store_backup_dir is not None:
        target_bin_scripts['backup-store'] = {
            'path': target_dir / 'bin' / 'backup-store',
            'content': textwrap.dedent(f'''\
                #! /bin/bash

                # Command to pass to container
                backup_dir=backup-$(date '+%Y-%m-%d.%H-%M-%S')
                cmd="mkdir /files/store-backup/${{backup_dir}} && cp -r /files/store/* /files/store-backup/${{backup_dir}}/"

                docker compose \\
                        --file         {artifacts_config_files['docker-compose-app-yml']['path']} \\
                        --project-name {config['depl']['dockerProjName']} \\
                        exec --user "$(id -u):$(id -g)" webserver sh -c "${{cmd}}"
                ''')
        }

    phase_build_bin_scripts(target_bin_scripts)


    ####################
    # Install in ~/bin #
    ####################

    if install_symlinks_in_local_bin:
        phase_install_symlinks_in_local_bin_function(target_bin_scripts, config['depl']['name'])

    print('Done.')