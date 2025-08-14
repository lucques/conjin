let P       = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ../ext/dhall/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall

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
let makeModuleSourceDir = common.makeModuleSourceDir
let makeModuleDir = common.makeModuleDir
let makeModuleCssDir = common.makeModuleCssDir
let moduleToString = common.moduleToString
let htdocsDir = common.htdocsDir
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

    let preprocessVol = makeVol preprocessVolDir "/files/preprocess"

    let errorLogVol = P.Optional.map Text Compose.ServiceVolume
        (\(d: Text) -> makeVol d "/files/logs/error") errorLogVolDir

    let storeVol = P.Optional.map T.LocalStore Compose.ServiceVolume
        (\(d: T.LocalStore) -> makeVol d.volDir "/files/store")
        store
    let storeBackupVol = P.Optional.concat Compose.ServiceVolume (
        P.Optional.map T.LocalStore (Optional Compose.ServiceVolume)
        (\(d: T.LocalStore) -> P.Optional.map Text Compose.ServiceVolume (\(e: Text) -> makeVol e "/files/store-backup") d.backupDir)
        store)

    in
    Compose.Service::{
        , build       = Some (Compose.Build.String (conjinDir ++ "/docker/images/conjin-server"))
        , volumes     = Some (htdocsVols # [preprocessVol] # (unpackOptionals Compose.ServiceVolume [errorLogVol, storeVol, storeBackupVol]))
        , restart     = Some "always"
        , networks    = Some (Compose.ServiceNetworks.List ["default", "nginx-proxy_default"])
        , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "VIRTUAL_HOST" nginxVirtualHost]) 
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



---------------------------------
-- docker-compose config files --
---------------------------------

let makeLocalDeplCompose =
    \(config: T.LocalDepl) ->

    -- Add db module for docker db if it is used
    -- let modules = merge {
    --     , None = config.depl.modules
    --     , Some = \(db: T.LocalDb) -> config.depl.modules # [{mapKey = "db", mapValue = 
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

    let htdocsVols = makeHtdocsVols
                    config.depl.targetDir
                    config.depl.appDir
                    config.depl.cssVolDir
                    config.depl.conjinDir
                    config.depl.bareModules
                    config.depl.modules
                    config.depl.staticFiles
                    False
                    False
                    False

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


-- If run as root, then privileges are dropped -- this causes problems when the mounted volume is mounted as root, but the file is then to be written as non-root
-- TODO: dir `linkchecker` must be created as non-root -- some way around this?
-- TODO: Needs env vars `USER_UID` and `USER_GID` to be set
-- cf. https://stackoverflow.com/questions/56844746/how-to-set-uid-and-gid-in-docker-compose
let makeLinkCheckerCompose =
    \(config: T.LocalDepl) ->

    let linkchecker =
    Compose.Service::{
        , build        = Some (Compose.Build.String (config.depl.conjinDir ++ "/docker/images/conjin-linkchecker"))
        , user         = Some "\${USER_UID}:\${USER_GID}"
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


in {
    , makeLocalDeplCompose
    , makeLinkCheckerCompose
}