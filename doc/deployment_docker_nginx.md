# Start web server + DB server as virtualhost locally via nginx
There is one docker-dompose project
- Services with **autostart**:
    - Apache with PHP
    - Mariadb server
    - PHPmyadmin server
    - for each module where SCSS is activated (usually templates):
        - SCSS compiler (watch mode)
- Terminating services **without autostart**
    - Linkchecker app


## Get started
To deploy, the following four phases are needed.
1. Configuration.
    - Write a `config.dhall` file that contains an expression of type `DockerNginxDepl`
        - Use the [/build-tools/generate-password-hash](../build-tools/generate-password-hash) script
        - Store password of the preprocessing user via `secret-tool store --label='dummy-preprocess' password dummy-preprocess`, see also https://ict4g.net/adolfo/notes/admin/secret-tool.html
    - Integrate with your OS (needed for linkchecker to resolve the virtual host domain): Configure the virtual host in your OS in the `/etc/hosts` file, according to your setting in `config.dhall`, e.g.:
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
        - Render SCSS files
        - Preprocess
3. Run.
    1. **Web App**    
        - Run the `nginx-proxy` container (https://github.com/nginx-proxy/nginx-proxy).
        - Run one of the generated bin scripts.
            - `./target/bin/up`
                - Starts SCSS watchers
                - Starts servers
                - After short delay: Calls `./target/bin/preprocess`
            - `./target/bin/down`: Stops SCSS watchers and servers
            - `./target/bin/preprocess`: Alternatively this can be run by launching `https://host/preprocess/`.
    2. **Linkchecker**
        - Run `./target/bin/linkchecker`; the args given to this script are passed as the target id prefix to check. E.g., `./target/bin/linkchecker first second` will check all URLs that start with `http://host/first/second/`.