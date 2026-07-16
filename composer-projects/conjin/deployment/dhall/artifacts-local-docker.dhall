let P       = ./vendor/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ./vendor/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall

let T       = ./types.dhall

-- Prelude
let map = P.List.map
let filterMap = P.List.filterMap
let concatMap = P.List.concatMap
let unpackOptionals = P.List.unpackOptionals
let empty = P.List.empty
let Entry = P.Map.Entry
let keyValue = P.Map.keyValue

-- Artifacts: Common
let common = ./artifacts-docker-common.dhall
let makeVol = common.makeVol
let makeReadonlyVol = common.makeReadonlyVol
let makeWebserverHealthcheck = common.makeWebserverHealthcheck
let makeMariaDbHealthcheck = common.makeMariaDbHealthcheck
let makeModuleSourceDir = common.makeModuleSourceDir
let makeModuleDir = common.makeModuleDir
let makeModuleCssDir = common.makeModuleCssDir
let moduleToString = common.moduleToString
--let htdocsDir = common.htdocsDir
let makeHtdocsVols = common.makeHtdocsVols
let makeTemplateSassCompilation = common.makeTemplateSassCompilation


-----------------------------
-- docker-compose Services --
-----------------------------

let makeWebserver =
    \(conjinDir:        Text) ->
    \(htdocsVols:       List Compose.ServiceVolume) ->
    \(errorLogVolDir:   Optional Text) ->
    \(preprocessVolDir: Text) ->
    \(store:            Optional T.LocalStore) ->
    \(nginxVirtualHost: Text) ->

    -- The following volumes are placed outside of htdocs dir
    let preprocessVol = makeVol preprocessVolDir "/files/preprocess"

    let errorLogVol = P.Optional.map Text Compose.ServiceVolume
        (\(d: Text) -> makeVol d "/files/logs/error") errorLogVolDir

    let storeVol = P.Optional.map T.LocalStore Compose.ServiceVolume
        (\(d: T.LocalStore) -> makeVol d.volDir "/files/store")
        store

    let storeInitVol = P.Optional.concat Compose.ServiceVolume (
        P.Optional.map T.LocalStore (Optional Compose.ServiceVolume)
        (\(d: T.LocalStore) -> P.Optional.map Text Compose.ServiceVolume (\(e: Text) -> makeReadonlyVol e "/files/store-init") d.initFilesDir)
        store)

    let storeBackupVol = P.Optional.concat Compose.ServiceVolume (
        P.Optional.map T.LocalStore (Optional Compose.ServiceVolume)
        (\(d: T.LocalStore) -> P.Optional.map Text Compose.ServiceVolume (\(e: Text) -> makeVol e "/files/store-backup") d.backupDir)
        store)

    in
    Compose.Service::{
        , build       = Some (Compose.Build.String (conjinDir ++ "/deployment/docker/images/conjin-server"))
        , volumes     = Some (htdocsVols # [preprocessVol] # (unpackOptionals Compose.ServiceVolume [errorLogVol, storeVol, storeInitVol, storeBackupVol]))
        , restart     = Some "always"
        , networks    = Some (Compose.ServiceNetworks.Map [
            , keyValue Compose.ServiceNetwork.Type "default" {
                , aliases = Some ["webserver.internal"]
                , ipv4_address = None Text
                , ipv6_address = None Text
            }
            , keyValue Compose.ServiceNetwork.Type "nginx-proxy_default" {
                , aliases = None (List Text)
                , ipv4_address = None Text
                , ipv6_address = None Text
            }
        ])
        , environment = Some (Compose.ListOrDict.Dict [
            , P.Map.keyText "VIRTUAL_HOST" nginxVirtualHost
            , P.Map.keyText "VIRTUAL_PORT" "80"
            , P.Map.keyText "CONJIN_ENABLE_INTERNAL_AUTH" "1"
        ])
        , healthcheck = Some makeWebserverHealthcheck
    }
: Compose.Service.Type

let makeDbService =
    \(db:            T.LocalDb) ->
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
                                db.storageVolDir
                            , P.Optional.map Text Compose.ServiceVolume
                                (\(backupFilesVolDir: Text) ->
                                    makeVol backupFilesVolDir "/backup")
                                db.backupFilesVolDir
                        ])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "MARIADB_ROOT_PASSWORD" db.rootPassword]) 
        , restart     = Some "always"
        , healthcheck = Some makeMariaDbHealthcheck
    }
: Compose.Service.Type

let makePhpmyadmin = 
    \(nginxVirtualHost: Text) ->
    Compose.Service::{
        , image   = Some "phpmyadmin:5-apache"
        , restart = Some "always"
        , networks = Some (Compose.ServiceNetworks.List ["default", "nginx-proxy_default"])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "VIRTUAL_HOST" ("phpmyadmin." ++ nginxVirtualHost)]) 
    }
: Compose.Service.Type

let makeTemplateSassWatcher = 
    \(cssVolDir: Text) ->
    \(conjinDir: Text) ->
    \(appDir:    Text) ->
    \(template:  T.Module) ->

    let location = T.bareModuleToLocation template.bare
    let scssDeps = T.bareModuleToScssDeps template.bare

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir location ++ "/scss") "/sass"
    let moduleCssVol  = makeVol         (makeModuleCssDir cssVolDir location)                "/css"

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



let makePreprocess =
    \(config: T.LocalDepl) ->
    Compose.Service::{
        , image      = Some "curlimages/curl:8.12.1"
        , command    = Some (Compose.StringOrList.List [
            , "--fail-with-body"
            , "--show-error"
            , "--cookie"
            , "user=" ++ config.preprocessUser
            , "http://webserver:8080/preprocess/"
        ])
        , depends_on = Some ["webserver"]
    }
: Compose.Service.Type

let makeLinkChecker =
    \(config: T.LocalDepl) ->
    let excludeTargets = P.Text.concatSep "\n"
        (P.List.map (List Text) Text (P.Text.concatSep "/") config.linkchecker.excludeTargets)
    in
    Compose.Service::{
        , build       = Some (Compose.Build.String (config.depl.conjinDir ++ "/deployment/docker/images/conjin-linkchecker"))
        , depends_on  = Some ["webserver"]
        , user        = Some "\${USER_UID:-0}:\${USER_GID:-0}"
        , volumes     = Some [makeVol config.linkcheckerVolDir "/mnt"]
        , environment = Some (Compose.ListOrDict.Dict [
            , P.Map.keyText "LINKCHECKER_OUT_DIR" config.linkcheckerVolDir
            , P.Map.keyText "LINKCHECKER_HOST" "webserver.internal"
            , P.Map.keyText "LINKCHECKER_ORIGIN" "http://webserver.internal:8080"
            , P.Map.keyText "LINKCHECKER_USER" config.linkchecker.user
            , P.Map.keyText "LINKCHECKER_PREFIX" "\${LINKCHECKER_PREFIX:-}"
            , P.Map.keyText "LINKCHECKER_EXCLUDE_TARGETS" excludeTargets
        ])
    }
: Compose.Service.Type



---------------------------------
-- docker-compose config files --
---------------------------------

let makeLocalDeplCompose =
    \(config: T.LocalDepl) ->

    let makeReadonlyModuleCssBindVol =
        \(location: T.ModuleLocation) ->
        \(target: Text) ->
            makeReadonlyVol (makeModuleCssDir config.depl.cssVolDir location)
                            target

    let makeModuleCssBindVol =
        \(location: T.ModuleLocation) ->
        \(target: Text) ->
            makeVol (makeModuleCssDir config.depl.cssVolDir location)
                    target

    let htdocsVols =
        makeHtdocsVols
            config.depl.targetDir
            config.depl.appDir
            config.depl.conjinDir
            config.depl.bareModules
            config.depl.staticFiles
            config.depl.modules
            makeReadonlyModuleCssBindVol -- Bind vols
            False                    -- Put outside of htdocs dir
            False                    -- Put outside of htdocs dir
            False                    -- Put outside of htdocs dir

    -- Define Services

    let webserver = makeWebserver
                        config.depl.conjinDir
                        htdocsVols
                        config.errors.logToVolDir
                        config.preprocessVolDir
                        config.store
                        config.nginxVirtualHost

    let db = merge {
        , None = None Compose.Service.Type
        , Some = \(db: T.LocalDb) ->
            Some (makeDbService db)
    } config.db

    let phpmyadmin = merge {
        , None = None Compose.Service.Type
        , Some = \(_: T.LocalDb) ->
            Some (makePhpmyadmin config.nginxVirtualHost)
    } config.db

    let templateSassWatchers =
        filterMap T.Module (Entry Text Compose.Service.Type)
            (\(m: T.Module) ->
                if m.compileScss then
                    Some (keyValue Compose.Service.Type
                        ("sass-watch-" ++ (moduleToString m))
                        (makeTemplateSassWatcher config.depl.cssVolDir config.depl.conjinDir config.depl.appDir m))
                else
                    None (Entry Text Compose.Service.Type))
            (P.Map.values Text T.Module config.depl.modules)

    let templateSassBuilds =
        filterMap T.Module (Entry Text Compose.Service.Type)
            (\(m: T.Module) ->
                if m.compileScss then
                    Some (keyValue Compose.Service.Type
                        ("sass-build-" ++ (moduleToString m))
                        (makeTemplateSassCompilation makeModuleCssBindVol config.depl.conjinDir config.depl.appDir m))
                else
                    None (Entry Text Compose.Service.Type))
            (P.Map.values Text T.Module config.depl.modules)

    let preprocess = makePreprocess config
    let linkchecker = makeLinkChecker config


    -- Wrap up

    let services
        : Compose.Services
        = P.Map.unpackOptionals Text Compose.Service.Type (toMap
            { webserver   = Some webserver
            , db          = db
            , phpmyadmin  = phpmyadmin
            , preprocess  = Some preprocess
            , linkchecker = Some linkchecker
            })
          #
          templateSassBuilds
          #
          templateSassWatchers
    
    let networks
        : Compose.Networks
        = Compose.Networks.Map [keyValue { name : Optional Text, external : Optional Bool } "nginx-proxy_default" { name = None Text, external = Some True }]

in Compose.Config::{ services = Some services,
                        networks = Some networks }


in {
    , makeLocalDeplCompose
}
