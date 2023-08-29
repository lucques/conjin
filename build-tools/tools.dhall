let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/9e93bdd7f87cdd1a020f8536f1bf30d93e21e5ab/compose/v3/package.dhall -- 2023-08

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
    \(m: T.ModuleId) ->
        if      m.isShared && m.isExternal == False          then conjinDir  ++ "/src/modules-shared/" ++ m.name
        else if m.isShared && m.isExternal                   then conjinDir  ++ "/ext/modules-shared/" ++ m.name
        else if m.isShared == False && m.isExternal == False then appDir     ++ "/src/modules/"        ++ m.name
        else                                                      appDir     ++ "/ext/modules/"        ++ m.name
: Text

let makeModuleDir =
    \(pathPrefix: Text) ->
    \(dirSuffix: Optional Text) ->
    \(m: T.ModuleId) ->
        let suf = merge { None = "", Some = \(d: Text) -> "-" ++ d } dirSuffix in
        if      m.isShared && m.isExternal == False          then pathPrefix ++ "/modules-shared"     ++ suf ++ "/" ++ m.name
        else if m.isShared && m.isExternal                   then pathPrefix ++ "/modules-shared-ext" ++ suf ++ "/" ++ m.name
        else if m.isShared == False && m.isExternal == False then pathPrefix ++ "/modules"            ++ suf ++ "/" ++ m.name
        else                                                      pathPrefix ++ "/modules-ext"        ++ suf ++ "/" ++ m.name
: Text

let makeModuleTargetCssDir =
    \(targetDir: Text) ->
    \(m: T.ModuleId) ->
    (makeModuleDir (targetDir ++ "/htdocs") (Some "css") m)
: Text

let moduleToString =
    \(m: T.Module) -> m.id.name ++ (if m.id.isShared then "-shared" else "-local")
: Text


-- These volumes constitute the `htdocs` dir, except for:
-- - `htdocs/preprocess`
let makeAppVols =
    \(targetDir:      Text) ->
    \(appDir:         Text) ->
    \(conjinDir:      Text) ->
    \(faviconIcoFrom: Optional T.ModuleId) ->
    \(modules:        List T.Module) ->
    \(preprocessDir:  Bool) ->

    let htdocsDir = "/files/htdocs"

    let faviconVolume =
        P.Optional.map T.ModuleId Compose.ServiceVolume (\(t: T.ModuleId) ->
            makeReadonlyVol (makeModuleSourceDir conjinDir appDir t ++ "/res/favicon/favicon.ico")
                            (htdocsDir ++ "/favicon.ico"))
            faviconIcoFrom         

    let makeModuleVol =
        \(m: T.Module) ->
        makeReadonlyVol (makeModuleSourceDir conjinDir appDir m.id) (makeModuleDir htdocsDir (None Text) m.id)

    let makeModuleCssVol =
        \(m: T.Module) ->
        if m.compileScss
            then Some (makeReadonlyVol (makeModuleTargetCssDir targetDir m.id) (makeModuleDir htdocsDir (Some "css") m.id))
            else None Compose.ServiceVolume

    let preprocessVol =
        if preprocessDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/preprocess") (htdocsDir ++ "/preprocess"))
        else None Compose.ServiceVolume

    in
    [
        , makeReadonlyVol (appDir    ++ "/src/content")             (htdocsDir ++ "/content")

        , makeReadonlyVol (conjinDir ++ "/src/conjin")              (htdocsDir ++ "/conjin")

        , makeReadonlyVol (targetDir ++ "/htdocs/.htaccess")        (htdocsDir ++ "/.htaccess")
        , makeReadonlyVol (targetDir ++ "/htdocs/config.json")      (htdocsDir ++ "/config.json")
    ]
    #
    unpackOptionals Compose.ServiceVolume [faviconVolume]
    #
    map T.Module Compose.ServiceVolume makeModuleVol modules
    #
    unpackOptionals Compose.ServiceVolume (map T.Module (Optional Compose.ServiceVolume) makeModuleCssVol modules)
    #
    unpackOptionals Compose.ServiceVolume [preprocessVol]
: List Compose.ServiceVolume


-----------------------------
-- docker-compose Services --
-----------------------------

let makeWebserver =
    \(dockerImagePath:  Text) ->
    \(appVols:          List Compose.ServiceVolume) ->
    \(preprocessVolDir: Text) ->
    \(nginxVirtualHost: Text) ->
    let preprocessVol = makeVol preprocessVolDir "/files/preprocess"
    in
    Compose.Service::{
        , build       = Some (Compose.Build.String dockerImagePath)
        , volumes     = Some (appVols # [preprocessVol])
        , restart     = Some "always"
        , networks    = Some (Compose.Networks.List ["default", "nginx-proxy_default"])
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
        , networks = Some (Compose.Networks.List ["default", "nginx-proxy_default"])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "VIRTUAL_HOST" ("phpmyadmin." ++ nginxVirtualHost)]) 
    }
: Compose.Service.Type

let makeTemplateSassWatcher = 
    \(targetDir: Text) ->
    \(conjinDir: Text) ->
    \(appDir:    Text) ->
    \(template:  T.Module) ->

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir template.id ++ "/scss") "/sass"
    let moduleCssVol  = makeVol         (makeModuleTargetCssDir targetDir template.id)                "/css"

    let makeModuleDependencyVol = 
        \(m: T.ModuleId) -> makeReadonlyVol (makeModuleSourceDir conjinDir appDir m ++ "/scss") (makeModuleDir   "" (None Text) m ++ "/scss")

    let volumes = [moduleSassVol, moduleCssVol] #
                  (map T.ModuleId Compose.ServiceVolume makeModuleDependencyVol template.scssModuleDeps)
    let moduleDependencyLoadPaths =
        map T.ModuleId Text
        (\(m: T.ModuleId) -> "--load-path=" ++ (makeModuleDir "" (None Text) m ++ "/scss"))
        template.scssModuleDeps
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

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir template.id ++ "/scss") "/sass"
    let moduleCssVol  = makeVol         (makeModuleTargetCssDir targetDir template.id)                "/css"

    let makeModuleDependencyVol = 
        \(m: T.ModuleId) -> makeReadonlyVol (makeModuleSourceDir conjinDir appDir m ++ "/scss") (makeModuleDir   "" (None Text) m ++ "/scss")

    let volumes = [moduleSassVol, moduleCssVol] #
                  (map T.ModuleId Compose.ServiceVolume makeModuleDependencyVol template.scssModuleDeps)
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

    let appVols = makeAppVols
                    config.depl.targetDir
                    config.depl.appDir
                    config.depl.conjinDir
                    config.depl.faviconIcoFrom
                    config.depl.modules
                    False

    -- Define Services

    let webserver = makeWebserver
                        (config.depl.conjinDir ++ "/docker/images/conjin-server")
                        appVols
                        config.preprocessVolDir
                        config.nginxVirtualHost

    let db = merge {
        , None = None Compose.Service.Type
        , Some = \(db: T.DockerDb) ->
            Some (makeDbService db)
    } config.db

    let phpmyadmin = makePhpmyadmin config.nginxVirtualHost

    let templateSassWatchers =
        filterMap T.Module (Entry Text Compose.Service.Type)
            (\(m: T.Module) ->
                if m.compileScss then
                    Some (keyValue Compose.Service.Type
                        ("sass-watch-" ++ (moduleToString m))
                        (makeTemplateSassWatcher config.depl.targetDir config.depl.conjinDir config.depl.appDir m))
                else
                    None (Entry Text Compose.Service.Type))
            config.depl.modules


    -- Wrap up

    let services
        : Compose.Services
        = P.Map.unpackOptionals Text Compose.Service.Type (toMap
            { webserver  = Some webserver
            , db         = db
            , phpmyadmin = Some phpmyadmin
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

    let appVols = makeAppVols
                    config.depl.targetDir
                    config.depl.appDir
                    config.depl.conjinDir
                    config.depl.faviconIcoFrom
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
            config.depl.modules
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
    \(nginxVirtualHost: Text) ->
    \(linkcheckerVolDir: Text) ->

    let linkchecker =
    Compose.Service::{
        , image        = Some "ghcr.io/linkchecker/linkchecker:latest"
        , user         = Some "\${UID}:\${GID}"
        , volumes      = Some ([ makeVol linkcheckerVolDir "/mnt" ])
        , network_mode = Some "host"
        , command      = Some (Compose.StringOrList.List [
                            "--verbose",
                            "--file-output",
                            "html/ascii/linkchecker-output.html",
                            "http://" ++ nginxVirtualHost ++ "/"
                            ])
    }

    let services
        : Compose.Services
        = toMap
            { linkchecker = linkchecker
            }

    in Compose.Config::{ services = Some services }


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
            = makeLinkCheckerCompose config.nginxVirtualHost config.linkcheckerVolDir
            : Compose.ComposeConfig
        , config-json
            = T.tagConfigJsonFile {
                path_root = "..",
                path_preprocess = "../../preprocess",
                url_root = "/",
                auth = P.Optional.map T.Auth T.AppAuth (\(a: T.Auth) -> a.app) config.depl.auth,
                modules_default_config =
                    map T.Module (P.Map.Entry Text P.JSON.Type)
                        (\(m: T.Module) -> keyValue P.JSON.Type m.id.name m.defaultConfig)
                        config.depl.modules
                    #
                    unpackOptionals (P.Map.Entry Text P.JSON.Type) [
                        P.Optional.map T.DockerDb (P.Map.Entry Text P.JSON.Type) (\(db: T.DockerDb) ->
                            keyValue P.JSON.Type "db" (P.JSON.object [
                                , { mapKey = "host"     , mapValue = P.JSON.string "db" }
                                , { mapKey = "user"     , mapValue = P.JSON.string db.user }
                                , { mapKey = "password" , mapValue = P.JSON.string db.userPassword }
                            ]))
                            config.db
                    ]
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
                path_root = "..",
                path_preprocess = "../preprocess",
                url_root = "/",
                auth = P.Optional.map T.Auth T.AppAuth (\(a: T.Auth) -> a.app) config.depl.auth,
                modules_default_config =
                    map T.Module (P.Map.Entry Text P.JSON.Type)
                        (\(m: T.Module) -> keyValue P.JSON.Type m.id.name m.defaultConfig)
                        config.depl.modules
                    #
                    unpackOptionals (P.Map.Entry Text P.JSON.Type) [
                        P.Optional.map T.ServerDb (P.Map.Entry Text P.JSON.Type) (\(db: T.ServerDb) ->
                            keyValue P.JSON.Type "db" (P.JSON.object [
                                , { mapKey = "host"     , mapValue = P.JSON.string "localhost" }
                                , { mapKey = "user"     , mapValue = P.JSON.string db.user }
                                , { mapKey = "password" , mapValue = P.JSON.string db.password }
                            ]))
                            config.db
                    ]
            }
            : T.ConfigJsonFileT
    }
    

-------------
-- Helpers --
-------------

let assignUser2PasswordHash = 
    \(user: Text) ->
    \(passwordHash: Text) -> {
        , mapKey   = user
        , mapValue = passwordHash
    }
: Entry Text Text

let addUserToGroup = 
    \(user: Text) ->
    \(group: Text) -> {
        , user
        , group = (T.Group.Custom group)
    }
: T.User2Group

let grantPreprocPrivToUser =
    \(user: Text) -> {
        group = T.Group.UserBased user,
        privilege = T.Privilege.Preprocess {=}
    }
: T.Group2Privilege

let grantViewActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        group = T.Group.Custom group,
        privilege = T.Privilege.Target {
            , targetIds
            , action = T.Action.View {=}
            , inherit = True
        }
    }
: T.Group2Privilege

let grantViewActionToUser = 
    \(targetIds: List Text) ->
    \(user: Text) -> {
        group = T.Group.UserBased user,
        privilege = T.Privilege.Target {
            , targetIds
            , action = T.Action.View {=}
            , inherit = True
        }
    }
: T.Group2Privilege

let grantCustomActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) ->
    \(action: Text) -> {
        group = T.Group.Custom group,
        privilege = T.Privilege.Target {
            , targetIds
            , action = T.Action.Custom action
            , inherit = True
        }
    }
: T.Group2Privilege

let grantCustomActionToUser = 
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        group = T.Group.UserBased user,
        privilege = T.Privilege.Target {
            , targetIds
            , action = T.Action.Custom action
            , inherit = True
        }
    }
: T.Group2Privilege

let makeModule = 
    \(name: Text) ->
    \(isShared: Bool) ->
    \(isExternal: Bool) -> {
        id = {name, isShared, isExternal},
        compileScss = False,
        scssModuleDeps = empty T.ModuleId,
        defaultConfig = P.JSON.null,
    }
: T.Module

let makeModules = 
    \(isShared: Bool) ->
    \(isExternal: Bool) ->
    \(names: List Text) ->
    map Text T.Module (\(n: Text) -> {
        id = {name = n, isShared, isExternal},
        compileScss = False,
        scssModuleDeps = empty T.ModuleId,
        defaultConfig = P.JSON.null,
    })
    names
: List T.Module


in {
    , makeDockerNginxConfigFiles
    , makeDockerSyncConfigFiles

    , assignUser2PasswordHash
    , addUserToGroup
    , grantPreprocPrivToUser
    , grantViewActionToGroup
    , grantViewActionToUser
    , grantCustomActionToGroup
    , grantCustomActionToUser

    , makeModule
    , makeModules
}