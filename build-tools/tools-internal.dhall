let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package.dhall -- 2024-08

let T       = ./types.dhall

let map = P.List.map
let filterMap = P.List.filterMap
let concatMap = P.List.concatMap
let unpackOptionals = P.List.unpackOptionals
let empty = P.List.empty
let Entry = P.Map.Entry
let keyValue = P.Map.keyValue


----------------------
-- Volumes and dirs --
----------------------

let makeVol =
    \(source: Text) ->
    \(target: Text) ->
    Compose.ServiceVolume.Long Compose.ServiceVolumeLong::{
        , type   = Some "bind"
        , source = Some source
        , target = Some target
    }
: Compose.ServiceVolume

let makeReadonlyVol = 
    \(source: Text) ->
    \(target: Text) ->
    Compose.ServiceVolume.Long Compose.ServiceVolumeLong::{
    , type      = Some "bind"
    , source    = Some source
    , target    = Some target
    , read_only = Some True
    }
: Compose.ServiceVolume

let makeModuleSourceDir =
    \(conjinDir:   Text) ->
    \(appDir:      Text) ->
    \(m: T.ModuleLocation) ->
        if      m.isShared && m.isExternal == False          then conjinDir  ++ "/src/modules-shared/" ++ m.dirName
        else if m.isShared && m.isExternal                   then conjinDir  ++ "/ext/modules-shared/" ++ m.dirName
        else if m.isShared == False && m.isExternal == False then appDir     ++ "/src/modules/"        ++ m.dirName
        else                                                      appDir     ++ "/ext/modules/"        ++ m.dirName
: Text

let makeModuleDir =
    \(pathPrefix: Text) ->
    \(dirSuffix: Optional Text) ->
    \(m: T.ModuleLocation) ->
        let suf = merge { None = "", Some = \(d: Text) -> "-" ++ d } dirSuffix in
        if      m.isShared && m.isExternal == False          then pathPrefix ++ "/modules-shared"     ++ suf ++ "/" ++ m.dirName
        else if m.isShared && m.isExternal                   then pathPrefix ++ "/modules-shared-ext" ++ suf ++ "/" ++ m.dirName
        else if m.isShared == False && m.isExternal == False then pathPrefix ++ "/modules"            ++ suf ++ "/" ++ m.dirName
        else                                                      pathPrefix ++ "/modules-ext"        ++ suf ++ "/" ++ m.dirName
: Text

let makeModuleTargetCssDir =
    \(targetDir: Text) ->
    \(m: T.ModuleLocation) ->
    (makeModuleDir (targetDir ++ "/htdocs") (Some "css") m)
: Text

let moduleToString =
    \(m: T.Module) -> (T.bareModuleToLocation m.bare).dirName ++ (if (T.bareModuleToLocation m.bare).isShared then "-shared" else "-local")
: Text


-- These volumes constitute the `htdocs` dir, except for:
-- - `htdocs/preprocess`
let makeAppVols =
    \(targetDir:      Text) ->
    \(appDir:         Text) ->
    \(conjinDir:      Text) ->
    \(bareModules:    P.Map.Type Text T.BareModule) ->
    \(modules:        P.Map.Type Text T.Module) ->
    \(preprocessDir:  Bool) ->

    let htdocsDir = "/files/htdocs"

    let makeModuleVol =
        \(m: T.BareModule) ->
        let location = T.bareModuleToLocation m in
        makeReadonlyVol (makeModuleSourceDir conjinDir appDir location) (makeModuleDir htdocsDir (None Text) location)

    let makeModuleCssVol =
        \(m: T.Module) ->
        let location = T.bareModuleToLocation m.bare in
        if m.compileScss
            then Some (makeReadonlyVol (makeModuleTargetCssDir targetDir location) (makeModuleDir htdocsDir (Some "css") location))
            else None Compose.ServiceVolume

    let preprocessVol =
        if preprocessDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/preprocess") (htdocsDir ++ "/preprocess"))
        else None Compose.ServiceVolume

    in
    [
        , makeReadonlyVol (appDir    ++ "/src/content")             (htdocsDir ++ "/content")
        , makeReadonlyVol (appDir    ++ "/src/system")              (htdocsDir ++ "/system")

        , makeReadonlyVol (conjinDir ++ "/src/conjin")              (htdocsDir ++ "/conjin")

        , makeReadonlyVol (targetDir ++ "/htdocs/.htaccess")        (htdocsDir ++ "/.htaccess")
        , makeReadonlyVol (targetDir ++ "/htdocs/config.json")      (htdocsDir ++ "/config.json")
        , makeReadonlyVol (targetDir ++ "/htdocs/users.json")       (htdocsDir ++ "/users.json")
    ]
    #
    map T.BareModule Compose.ServiceVolume makeModuleVol (P.Map.values Text T.BareModule bareModules)
    #
    unpackOptionals Compose.ServiceVolume (map T.Module (Optional Compose.ServiceVolume) makeModuleCssVol (P.Map.values Text T.Module modules))
    #
    unpackOptionals Compose.ServiceVolume [preprocessVol]
: List Compose.ServiceVolume


-----------------------------
-- docker-compose Services --
-----------------------------

let makeWebserver =
    \(conjinDir:        Text) ->
    \(appVols:          List Compose.ServiceVolume) ->
    \(preprocessVolDir: Text) ->
    \(nginxVirtualHost: Text) ->
    let preprocessVol = makeVol preprocessVolDir "/files/preprocess"
    in
    Compose.Service::{
        , build       = Some (Compose.Build.String (conjinDir ++ "/docker/images/conjin-server"))
        , volumes     = Some (appVols # [preprocessVol])
        , restart     = Some "always"
        , networks    = Some (Compose.ServiceNetworks.List ["default", "nginx-proxy_default"])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "VIRTUAL_HOST" nginxVirtualHost]) 
    }
: Compose.Service.Type

-- TODO: Needs env vars `UID` and `GID` to be set
let makeSyncJob =
    \(appVols:         List Compose.ServiceVolume) ->
    \(rcloneRemote:    T.RCloneRemote) ->
    \(waitForServices: List Text) ->
    let rCloneConfigVol = makeVol rcloneRemote.configPath "/config/rclone/rclone.conf"
    in
    Compose.Service::{
        , image       = Some "rclone/rclone"
        , volumes     = Some (appVols # [rCloneConfigVol])
        , user        = Some "\${UID}:\${GID}"
        , command = Some (Compose.StringOrList.List [
                            "sync",
                            "--progress",
                            "--create-empty-src-dirs",
                            "/files/htdocs",
                            rcloneRemote.name ++ ":" ++ rcloneRemote.dir])
        , depends_on = Some waitForServices
    }
: Compose.Service.Type

let makeDbService =
    \(db:            T.DockerDb) ->
    Compose.Service::{
        , image       = Some "mariadb:10"
        , volumes     = Some (P.List.unpackOptionals Compose.ServiceVolume [
                            , P.Optional.map Text Compose.ServiceVolume
                                (\(initFilesDir: Text) ->
                                    makeReadonlyVol initFilesDir "/docker-entrypoint-initdb.d/")
                                db.initFilesDir
                            , P.Optional.map Text Compose.ServiceVolume 
                                (\(dbVolDir: Text) ->
                                    makeVol dbVolDir "/var/lib/mysql")
                                db.volDir
                        ])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "MARIADB_ROOT_PASSWORD" db.rootPassword]) 
        , restart     = Some "always"
    }
: Compose.Service.Type

let makePhpmyadmin = 
    \(nginxVirtualHost: Text) ->
    Compose.Service::{
        , image   = Some "phpmyadmin/phpmyadmin"
        , restart = Some "always"
        , networks = Some (Compose.ServiceNetworks.List ["default", "nginx-proxy_default"])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "VIRTUAL_HOST" ("phpmyadmin." ++ nginxVirtualHost)]) 
    }
: Compose.Service.Type

let makeTemplateSassWatcher = 
    \(targetDir: Text) ->
    \(conjinDir: Text) ->
    \(appDir:    Text) ->
    \(template:  T.Module) ->

    let location = T.bareModuleToLocation template.bare
    let scssDeps = T.bareModuleToScssDeps template.bare

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir location ++ "/scss") "/sass"
    let moduleCssVol  = makeVol         (makeModuleTargetCssDir targetDir location)                "/css"

    let makeModuleDependencyVol = 
        \(m: T.ModuleLocation) -> makeReadonlyVol (makeModuleSourceDir conjinDir appDir m ++ "/scss") (makeModuleDir   "" (None Text) m ++ "/scss")

    let volumes = [moduleSassVol, moduleCssVol] #
                  (map T.ModuleLocation Compose.ServiceVolume makeModuleDependencyVol scssDeps)
    let moduleDependencyLoadPaths =
        map T.ModuleLocation Text
        (\(m: T.ModuleLocation) -> "--load-path=" ++ (makeModuleDir "" (None Text) m ++ "/scss"))
        scssDeps
    in

    Compose.Service::{
        , image   = Some "michalklempa/dart-sass"
        , volumes = Some volumes
        , command = Some (Compose.StringOrList.List ([
            "/opt/dart-sass/sass"] #
            -- It seems that load paths are needed to trigger reloading whenever something in the reload path changes.
            -- Otherwise, relative paths get cached and are not reloaded for rendering.
            moduleDependencyLoadPaths # [
            "--watch",
            "/sass/:/css/"]))
        , restart = Some "always"
    }
: Compose.Service.Type

let makeTemplateSassCompilation = 
    \(targetDir: Text) ->
    \(conjinDir: Text) ->
    \(appDir:    Text) ->
    \(template:  T.Module) ->

    let location = T.bareModuleToLocation template.bare
    let scssDeps = T.bareModuleToScssDeps template.bare

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir location ++ "/scss") "/sass"
    let moduleCssVol  = makeVol         (makeModuleTargetCssDir targetDir location)                "/css"

    let makeModuleDependencyVol = 
        \(m: T.ModuleLocation) -> makeReadonlyVol (makeModuleSourceDir conjinDir appDir m ++ "/scss") (makeModuleDir   "" (None Text) m ++ "/scss")

    let volumes = [moduleSassVol, moduleCssVol] #
                  (map T.ModuleLocation Compose.ServiceVolume makeModuleDependencyVol scssDeps)
    in

    Compose.Service::{
        , image   = Some "michalklempa/dart-sass"
        , volumes = Some volumes
        , command = Some (Compose.StringOrList.List ([
            "/opt/dart-sass/sass",
            "/sass/:/css/"]))
    }
: Compose.Service.Type


---------------------------------
-- docker-compose config files --
---------------------------------

let makeDockerNginxDeplConfig =
    \(config: T.DockerNginxDepl) ->

    -- Add db module for docker db if it is used
    -- let modules = merge {
    --     , None = config.depl.modules
    --     , Some = \(db: T.DockerDb) -> config.depl.modules # [{mapKey = "db", mapValue = 
    --         {
    --             withDeps = {
    --                 location = {dirName = "db", isShared = True, isExternal = False},
    --                 scssModuleDeps = empty T.ModuleLocation,
    --             },
    --             config = P.JSON.object [
    --                 , { mapKey = "host"     , mapValue = P.JSON.string "db" }
    --                 , { mapKey = "user"     , mapValue = P.JSON.string db.user }
    --                 , { mapKey = "password" , mapValue = P.JSON.string db.userPassword }
    --             ],
    --             compileScss = False,
    --         }}]
    -- } config.db
    -- TODO
    -- let modules = config.depl.modules

    let appVols = makeAppVols
                    config.depl.targetDir
                    config.depl.appDir
                    config.depl.conjinDir
                    config.depl.bareModules
                    config.depl.modules
                    False

    -- Define Services

    let webserver = makeWebserver
                        config.depl.conjinDir
                        appVols
                        config.preprocessVolDir
                        config.nginxVirtualHost

    let db = merge {
        , None = None Compose.Service.Type
        , Some = \(db: T.DockerDb) ->
            Some (makeDbService db)
    } config.db

    let phpmyadmin = merge {
        , None = None Compose.Service.Type
        , Some = \(_: T.DockerDb) ->
            Some (makePhpmyadmin config.nginxVirtualHost)
    } config.db

    let templateSassWatchers =
        filterMap T.Module (Entry Text Compose.Service.Type)
            (\(m: T.Module) ->
                if m.compileScss then
                    Some (keyValue Compose.Service.Type
                        ("sass-watch-" ++ (moduleToString m))
                        (makeTemplateSassWatcher config.depl.targetDir config.depl.conjinDir config.depl.appDir m))
                else
                    None (Entry Text Compose.Service.Type))
            (P.Map.values Text T.Module config.depl.modules)


    -- Wrap up

    let services
        : Compose.Services
        = P.Map.unpackOptionals Text Compose.Service.Type (toMap
            { webserver  = Some webserver
            , db         = db
            , phpmyadmin = phpmyadmin
            })
          #
          templateSassWatchers
    
    let networks
        : Compose.Networks
        = Compose.Networks.Map [keyValue { name : Optional Text, external : Optional Bool } "nginx-proxy_default" { name = None Text, external = Some True }]

    in Compose.Config::{ services = Some services,
                         networks = Some networks }

let makeDockerSyncDeplConfig =
    \(config: T.DockerSyncDepl) ->

    -- Add db module for server db if it is used
    -- let modules = merge {
    --     , None = config.depl.modules
    --     , Some = \(db: T.ServerDb) -> config.depl.modules # [{mapKey = "db", mapValue =
    --     {
    --         withDeps = {
    --             location = {dirName = "db", isShared = True, isExternal = False},
    --             scssModuleDeps = empty T.ModuleLocation,
    --         },
    --         config = P.JSON.object [
    --             , { mapKey = "host"     , mapValue = P.JSON.string "localhost" }
    --             , { mapKey = "user"     , mapValue = P.JSON.string db.user }
    --             , { mapKey = "password" , mapValue = P.JSON.string db.password }
    --         ],
    --         compileScss = False,
    --     }}]
    -- } config.db
    -- TODO

    let appVols = makeAppVols
                    config.depl.targetDir
                    config.depl.appDir
                    config.depl.conjinDir
                    config.depl.bareModules
                    config.depl.modules
                    True

    -- Define Services

    let templateSassCompilations =
        filterMap T.Module (Entry Text Compose.Service.Type)
            (\(m: T.Module) ->
                if m.compileScss then
                    Some (keyValue Compose.Service.Type
                        ("sass-compile-" ++ (moduleToString m))
                        (makeTemplateSassCompilation config.depl.targetDir config.depl.conjinDir config.depl.appDir m))
                else
                    None (Entry Text Compose.Service.Type))
            (P.Map.values Text T.Module config.depl.modules)
    : P.Map.Type Text Compose.Service.Type
    
    let templateSassCompilationServices =
        map (Entry Text Compose.Service.Type) Text (\(e: Entry Text Compose.Service.Type) -> e.mapKey) templateSassCompilations
    : List Text

    let syncJob = makeSyncJob
        appVols
        config.rcloneRemote
        templateSassCompilationServices
    : Compose.Service.Type

    -- Wrap up

    let services
        : Compose.Services
        = toMap
            {
                syncJob
            }
            #
            templateSassCompilations

    in Compose.Config::{ services = Some services }


-- If run as root, then privileges are dropped -- this causes problems when the mounted volume is mounted as root, but the file is then to be written as non-root
-- TODO: dir `linkchecker` must be created as non-root -- some way around this?
-- TODO: Needs env vars `UID` and `GID` to be set
-- cf. https://stackoverflow.com/questions/56844746/how-to-set-uid-and-gid-in-docker-compose
let makeLinkCheckerCompose =
    \(config: T.DockerNginxDepl) ->

    let linkchecker =
    Compose.Service::{
        , build        = Some (Compose.Build.String (config.depl.conjinDir ++ "/docker/images/conjin-linkchecker"))
        , user         = Some "\${UID}:\${GID}"
        , volumes      = Some ([ makeVol config.linkcheckerVolDir "/mnt" ])
        , network_mode = Some "host"
        , environment  = Some (Compose.ListOrDict.Dict [
            -- Host and user are determined, but password needs to be passed by host
            P.Map.keyText "LINKCHECKER_OUT_DIR" config.linkcheckerVolDir,
            P.Map.keyText "LINKCHECKER_HOST" config.nginxVirtualHost,
            P.Map.keyText "LINKCHECKER_USER" config.linkcheckerUser,
            P.Map.keyText "LINKCHECKER_PASSWORD" "\${LINKCHECKER_PASSWORD}",
            P.Map.keyText "LINKCHECKER_PREFIX" "\${LINKCHECKER_PREFIX}",
        ])
    }

    let services
        : Compose.Services
        = toMap
            { linkchecker = linkchecker
            }

    in Compose.Config::{ services = Some services }


-------------
-- Helpers --
-------------

let stripPasswordsOffAuthentication = 
    \(authentication: T.Authentication) ->
    {
        , loginWithoutUserName = authentication.loginWithoutUserName
    }: T.AuthenticationWithoutPasswords


---------------------------------------------------
-- All config files bundled together in a record --
---------------------------------------------------

let makeDockerNginxConfigFiles =
    \(config: T.DockerNginxDepl) ->
    {
        , docker-compose-app-yml
            = makeDockerNginxDeplConfig config
            : Compose.ComposeConfig     
        , docker-compose-linkchecker-yml
            = makeLinkCheckerCompose config
            : Compose.ComposeConfig
        , config-json
            = T.tagConfigJsonFile {
                path_base = "..",
                path_preprocess = "../../preprocess",
                url_base = "/",
                authentication = stripPasswordsOffAuthentication config.depl.authentication,
                authorization = config.depl.authorization,
                module_2_location = P.Map.map Text T.BareModule T.ModuleLocation T.bareModuleToLocation config.depl.bareModules,
                module_2_config = P.Map.map Text T.Module P.JSON.Type (\(m: T.Module) -> m.config) config.depl.modules,
            }
            : T.ConfigJsonFileT
    }

let makeDockerSyncConfigFiles =
    \(config: T.DockerSyncDepl) ->
    {
        , docker-compose-sync-yml
            = makeDockerSyncDeplConfig config
            : Compose.ComposeConfig
        , config-json
            = T.tagConfigJsonFile {
                path_base = config.pathBase,
                path_preprocess = config.pathBase ++ "/preprocess",
                url_base = config.urlBase,
                authentication = stripPasswordsOffAuthentication config.depl.authentication,
                authorization = config.depl.authorization,
                module_2_location = P.Map.map Text T.BareModule T.ModuleLocation T.bareModuleToLocation config.depl.bareModules,
                module_2_config = P.Map.map Text T.Module P.JSON.Type (\(m: T.Module) -> m.config) config.depl.modules,
            }
            : T.ConfigJsonFileT
    }

in {
    , types = T

    , makeDockerNginxConfigFiles
    , makeDockerSyncConfigFiles
}