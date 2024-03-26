# Start web server + DB server as virtualhost locally via nginx
There are two docker-dompose projects
- Web app
    - Services:
        - Apache with PHP
        - Mariadb server
        - PHPmyadmin server
        - for each template:
            - SCSS compiler (watch mode) 
    - Auto-start
- Linkchecker
    - Services:
        - Linkchecker app
    - Started manually, and then terminates

## Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Write a `config.dhall` file that contains an expression of type `DockerNginxDepl`
        - Use the [/build-tools/generate-password-hash](../build-tools/generate-password-hash) script
        - Store password of the preprocessing user via `secret-tool store --label='dummy-preprocess' password dummy-preprocess`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
    - Integrate with your OS: Configure the virtual host in your OS in the `/etc/hosts` file, according to your setting in `config.dhall`, e.g.:
        ```
        127.0.0.1       my-project.localhost
        127.0.0.1       phpmyadmin.my-project.localhost
        ```
2. Build.
    - Run [/build-tools/build-deployment](../build-tools/build-deployment) script to build the deployment
    - This will build in `/target`:
        - Config files for docker-compose etc.
        - Bin files to run the app
        - Further dirs needed for deployment
    - This will not:
        - Preprocess
        - Render SCSS files
3. Run.
    - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
    - Run one of the generated bin scripts.
        - `./target/bin/up`
            - Start SCSS watchers
            - Start servers
            - After short delay: Call `./target/bin/preprocess`
        - `./target/bin/linkchecker`: Run link-checker
        - `./target/bin/down`: Stop SCSS watchers and servers
        - `./target/bin/preprocess`: See below
4. Preprocess.
    - This is a PHP script on the server and gets called via `https://host/preprocess/` or alternatively via `./target/bin/preprocess`
