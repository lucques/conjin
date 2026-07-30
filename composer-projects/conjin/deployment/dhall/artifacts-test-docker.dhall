let P       = ./vendor/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ./vendor/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall

let T       = ./types.dhall

-- Prelude
let map = P.List.map
let filterMap = P.List.filterMap
let unpackOptionals = P.List.unpackOptionals
let Entry = P.Map.Entry
let keyValue = P.Map.keyValue

-- Artifacts: Common
let common = ./artifacts-docker-common.dhall
let dockerImages = ./docker-images.dhall
let makeDockerBuild = common.makeDockerBuild
let makeVol = common.makeVol
let makeReadonlyVol = common.makeReadonlyVol
let makeNamedVol = common.makeNamedVol
let makeReadonlyNamedVol = common.makeReadonlyNamedVol
let makeWebserverHealthcheck = common.makeWebserverHealthcheck
let makeMariaDbHealthcheck = common.makeMariaDbHealthcheck
let makeModuleCssVolName = common.makeModuleCssVolName
let moduleToString = common.moduleToString
let makeHtdocsVols = common.makeHtdocsVols
let makeTemplateSassCompilation = common.makeTemplateSassCompilation


--------------------------------
-- docker-compose config file --
--------------------------------

let makeTestDeplCompose =
    \(config: T.TestDepl) ->

    let makeReadonlyModuleCssNamedVol =
            \(location: T.ModuleLocation) ->
            \(target: Text) ->
                (makeReadonlyNamedVol (makeModuleCssVolName location)
                                      target)

    let makeModuleCssNamedVol =
            \(location: T.ModuleLocation) ->
            \(target: Text) ->
                (makeNamedVol (makeModuleCssVolName location)
                              target)

    let htdocsVols = makeHtdocsVols
            config.depl.targetDir
            config.depl.appDir
            config.depl.conjinDir
            config.depl.bareModules
            config.depl.staticFiles
            config.depl.modules
            makeReadonlyModuleCssNamedVol  -- Named vols
            False                     -- Put outside of htdocs dir
            False                     -- Put outside of htdocs dir
            False                     -- Put outside of htdocs dir

    let withStore = P.Bool.not (P.Optional.null T.TestStore config.store)

    let storeInitVol = P.Optional.concat Compose.ServiceVolume (
        P.Optional.map T.TestStore (Optional Compose.ServiceVolume)
            (\(store: T.TestStore) ->
                P.Optional.map Text Compose.ServiceVolume
                    (\(initFilesDir: Text) -> makeReadonlyVol initFilesDir "/files/store-init")
                    store.initFilesDir)
            config.store)

    -- Define Services

    let webserver = Compose.Service::{
        , build = Some (makeDockerBuild (config.depl.conjinDir ++ "/deployment/docker/images/conjin-server") dockerImages.imagePHP)
        , depends_on = merge {
            , None = None (List Text)
            , Some = \(_: T.TestDb) -> Some ["db"]
        } config.db
        , networks = Some (Compose.ServiceNetworks.Map [
            , keyValue Compose.ServiceNetwork.Type "default" {
                , aliases = Some ["webserver.internal"]
                , ipv4_address = None Text
                , ipv6_address = None Text
            }
        ])
        , environment = Some (Compose.ListOrDict.Dict [
            , P.Map.keyText "CONJIN_ENABLE_INTERNAL_AUTH" "1"
        ])
        , volumes = Some (
            htdocsVols
            #
            -- No error log vol
            -- Preprocess data vol
            [makeNamedVol "preprocess-data" "/files/preprocess"]
            #
            -- Store data vol (if enabled)
            (if withStore then [makeNamedVol "store-data" "/files/store"] else [] : List Compose.ServiceVolume)
            #
            (unpackOptionals Compose.ServiceVolume [storeInitVol]))
        , healthcheck = Some makeWebserverHealthcheck
    }

    let db = P.Optional.map T.TestDb (Entry Text Compose.Service.Type)
        (\(db: T.TestDb) -> keyValue Compose.Service.Type "db" Compose.Service::{
            , image = Some dockerImages.imageMariaDB
            , environment = Some (Compose.ListOrDict.Dict [P.Map.keyText "MARIADB_ROOT_PASSWORD" db.rootPassword])
            , volumes = Some ([makeNamedVol "database-data" "/var/lib/mysql"] #
                (unpackOptionals Compose.ServiceVolume [
                    P.Optional.map Text Compose.ServiceVolume
                        (\(initFilesDir: Text) -> makeReadonlyVol initFilesDir "/docker-entrypoint-initdb.d/")
                        db.initFilesDir
                ]))
            , healthcheck = Some makeMariaDbHealthcheck
        }) config.db

    let sassBuildServices = filterMap T.Module (Entry Text Compose.Service.Type)
        (\(m: T.Module) ->
            if m.compileScss then
                Some (keyValue Compose.Service.Type ("sass-build-" ++ moduleToString m)
                    (makeTemplateSassCompilation makeModuleCssNamedVol config.depl.conjinDir config.depl.appDir m))
            else
                None (Entry Text Compose.Service.Type))
        (P.Map.values Text T.Module config.depl.modules)

    let playwright = P.Optional.map Text (Entry Text Compose.Service.Type)
        (\(playwrightTestsDir: Text) -> keyValue Compose.Service.Type "playwright" Compose.Service::{
            , build = Some (makeDockerBuild playwrightTestsDir dockerImages.imagePlaywright)
            , command = Some (Compose.StringOrList.List ["npm", "test"])
            , depends_on = Some ["webserver"]
            , environment = Some (Compose.ListOrDict.Dict [
                , P.Map.keyText "CI" "\${CI:-}"
                , P.Map.keyText "HOME" "/tmp"
                , P.Map.keyText "TEST_BASE_URL" "http://webserver"
                , P.Map.keyText "TEST_ARTIFACTS_DIR" "/artifacts"
                , P.Map.keyText "PREPROCESS_BASE_URL" "http://webserver.internal:8080"
                , P.Map.keyText "PREPROCESS_USER" config.preprocessUser
            ])
            , ipc = Some "host"
            , user = Some "\${USER_UID}:\${USER_GID}"
            , volumes = Some [makeVol "\${TEST_ARTIFACTS_DIR}" "/artifacts"]
        })
        config.playwrightTestsDir

    -- Docker creates named volumes as root. Unlike local bind-mounted dirs,
    -- they cannot be made writable during the deployment build, so this
    -- one-shot service prepares them for Apache before the webserver starts.
    let volumeInit = keyValue Compose.Service.Type "volume-init" Compose.Service::{
        , image = Some dockerImages.imageAlpine
        , command = Some (Compose.StringOrList.List [
            , "/bin/sh"
            , "-c"
            , "chmod 0777 /files/preprocess" ++ (if withStore then " /files/store" else "")
        ])
        , volumes = Some ([makeNamedVol "preprocess-data" "/files/preprocess"] #
            (if withStore then [makeNamedVol "store-data" "/files/store"] else [] : List Compose.ServiceVolume))
    }

    let linkcheckerExcludeTargets = P.Text.concatSep "\n"
        (P.List.map (List Text) Text (P.Text.concatSep "/") config.linkchecker.excludeTargets)

    let linkchecker = keyValue Compose.Service.Type "linkchecker" Compose.Service::{
        , build = Some (makeDockerBuild (config.depl.conjinDir ++ "/deployment/docker/images/conjin-linkchecker") dockerImages.imageLinkChecker)
        , depends_on = Some ["webserver"]
        , environment = Some (Compose.ListOrDict.Dict [
            , P.Map.keyText "LINKCHECKER_HOST" "webserver.internal"
            , P.Map.keyText "LINKCHECKER_ORIGIN" "http://webserver.internal:8080"
            , P.Map.keyText "LINKCHECKER_USER" config.linkchecker.user
            , P.Map.keyText "LINKCHECKER_PREFIX" ""
            , P.Map.keyText "LINKCHECKER_EXCLUDE_TARGETS" linkcheckerExcludeTargets
        ])
        , user = Some "\${USER_UID}:\${USER_GID}"
        , volumes = Some [makeVol "\${TEST_ARTIFACTS_DIR}" "/mnt"]
    }

    let services = [keyValue Compose.Service.Type "webserver" webserver]
        # (unpackOptionals (Entry Text Compose.Service.Type) [db])
        # sassBuildServices
        # [volumeInit]
        # (unpackOptionals (Entry Text Compose.Service.Type) [playwright])
        # [linkchecker]

    let cssVolumes = filterMap T.Module (Entry Text (Optional Compose.Volume.Type))
        (\(m: T.Module) ->
            if m.compileScss then
                let location = T.bareModuleToLocation m.bare in
                Some (keyValue (Optional Compose.Volume.Type) (makeModuleCssVolName location) (Some Compose.Volume::{=}))
            else
                None (Entry Text (Optional Compose.Volume.Type)))
        (P.Map.values Text T.Module config.depl.modules)

    let volumes = [
        , keyValue (Optional Compose.Volume.Type) "preprocess-data" (Some Compose.Volume::{=})
    ]
    # (merge {
        , None = [] : List (Entry Text (Optional Compose.Volume.Type))
        , Some = \(_: T.TestDb) -> [keyValue (Optional Compose.Volume.Type) "database-data" (Some Compose.Volume::{=})]
    } config.db)
    # (if withStore then [keyValue (Optional Compose.Volume.Type) "store-data" (Some Compose.Volume::{=})] else [] : List (Entry Text (Optional Compose.Volume.Type)))
    # cssVolumes

    in Compose.Config::{
        , services = Some services
        , volumes = Some volumes
    }

in {
    , makeTestDeplCompose
}
