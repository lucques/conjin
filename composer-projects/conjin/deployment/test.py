from pathlib import Path

from common import (
    phase_build_dhall_artifacts,
    phase_build_dirs,
    phase_build_htaccess,
    phase_build_users_json,
    phase_generate_password_hashes,
    phase_read_config,
)


##############
# Entrypoint #
##############

def build_test_depl(config_path, check_vol_source_paths_exist):

    ####################
    # Read config file #
    ####################

    config = phase_read_config(config_path, 'tagTestDepl')


    #########
    # Paths #
    #########

    conjin_dir = Path(config['depl']['conjinDir']).absolute()
    app_dir = Path(config['depl']['appDir']).absolute()
    target_dir = Path(config['depl']['targetDir']).absolute()
    ######################
    # Create target dirs #
    ######################

    phase_build_dirs('target', [target_dir, target_dir / 'bin', target_dir / 'htdocs'])


    #########################################
    # Build artifacts generated from Dhall #
    #########################################

    artifacts = {
        'docker-compose-test-yml': {
            'path': target_dir / 'docker-compose-test.yml',
            'format': 'yaml',
        },
        'config-json': {
            'path': target_dir / 'htdocs' / 'config.json',
            'format': 'json',
        },
    }
    phase_build_dhall_artifacts(artifacts, config_path, 'artifactsTest.makeArtifacts')


    ############################
    # Build `htdocs/.htaccess` #
    ############################

    # HTTPS, host redirects, and compression are not used inside the isolated
    # Compose test network.
    phase_build_htaccess(
        target_dir,
        conjin_dir,
        '/',
        force_https=False,
        force_www_off=False,
        force_www_on=False,
        activate_compression=False,
        permanent_redirects=config['depl']['permanentRedirects'],
    )


    ############################
    # Generate password hashes #
    ############################

    users_2_hashes = phase_generate_password_hashes(config['depl']['authentication']['staticUsers2passwords'])


    ######################
    # Build `users.json` #
    ######################

    phase_build_users_json(target_dir, users_2_hashes)


    #################################
    # Check that bind sources exist #
    #################################

    if check_vol_source_paths_exist:

        #################################
        # Resolve module source folders #
        #################################

        def module_source_dir(location):
            if location['isShared'] and location['isExternal']:
                return conjin_dir / 'ext' / 'modules-shared' / location['dirName']
            if location['isShared']:
                return conjin_dir / 'src' / 'modules-shared' / location['dirName']
            if location['isExternal']:
                return app_dir / 'ext' / 'modules' / location['dirName']
            return app_dir / 'src' / 'modules' / location['dirName']


        ################################
        # Collect required input paths #
        ################################

        required_paths = {
            app_dir / 'src' / 'content',
            app_dir / 'src' / 'system',
            conjin_dir / 'src' / 'conjin',
            app_dir / 'vendor',
            target_dir / 'htdocs' / '.htaccess',
            target_dir / 'htdocs' / 'config.json',
            target_dir / 'htdocs' / 'users.json',
        }

        if config.get('playwrightTestsDir') is not None:
            required_paths.add(Path(config['playwrightTestsDir']))

        for location in config['depl']['moduleLocations'].values():
            required_paths.add(module_source_dir(location))

        for module_id, module in config['depl']['modules'].items():
            if module['compileScss']:
                required_paths.add(module_source_dir(config['depl']['moduleLocations'][module_id]) / 'scss')
                for dependency in config['depl']['moduleSCSSDeps'][module_id]:
                    required_paths.add(module_source_dir(dependency) / 'scss')

        for source in config['depl']['staticFiles'].keys():
            required_paths.add(app_dir / 'src' / 'static' / source)

        if config['db'] is not None and config['db']['initFilesDir'] is not None:
            required_paths.add(Path(config['db']['initFilesDir']))


        ########################
        # Report missing paths #
        ########################

        missing_paths = sorted(path for path in required_paths if not path.exists())
        if missing_paths:
            formatted_paths = '\n'.join(f'- {path}' for path in missing_paths)
            raise FileNotFoundError(f'Missing test deployment bind sources:\n{formatted_paths}')

    print('Done.')
