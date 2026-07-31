let P       = ../../ext/dhall/dhall-lang/Prelude/package_resolved.dhall
let Compose = ../../ext/dhall/dhall-docker-compose/compose/v3/package_resolved.dhall

let T       = ./types.dhall

-- Docker common
let artifactsDockerCommon = ./artifacts-docker-common.dhall
let extractDockerVolumeSources = artifactsDockerCommon.extractDockerVolumeSources

-- Docker compose
let artifactsRemoteDocker = ./artifacts-remote-docker.dhall
let makeRemoteDeplCompose = artifactsRemoteDocker.makeRemoteDeplCompose

-- config.json
let artifactsHtdocs = ./artifacts-htdocs.dhall
let ConfigJsonFile = artifactsHtdocs.ConfigJsonFile
let ConfigJsonFileT = artifactsHtdocs.ConfigJsonFileT
let stripPasswordsOffAuthentication = artifactsHtdocs.stripPasswordsOffAuthentication
let tagConfigJsonFile = artifactsHtdocs.tagConfigJsonFile


-------------
-- Helpers --
-------------

let isRemoteErrorLogging =
    \(s: T.RemoteErrorLogging) ->
    merge {
        , None = \(_: {})                      -> False
        , WithoutBackup =  \(_: {})            -> True
        , WithBackup = \(_: {backupDir: Text}) -> True
    } s
    : Bool

let hasRemoteStore =
    \(s: T.RemoteStore) ->
    merge {
        , None = \(_: {})                      -> False
        , WithoutBackup =  \(_: {})            -> True
        , WithBackup = \(_: {backupDir: Text}) -> True
    } s
    : Bool


---------------------------------------------------
-- All config files bundled together in a record --
---------------------------------------------------

let makeArtifacts =
    \(config: T.RemoteDepl) ->
    let docker = makeRemoteDeplCompose config
    let https =
        merge {
            , Off = \(_: {}) -> False
            , Prefer = \(_: {}) -> True
            , Force = \(_: {}) -> True
        } config.https
    in
    {
        , docker-compose-yml = docker
            : Compose.ComposeConfig
        , docker-compose-yml-volume-sources = extractDockerVolumeSources docker
            : List Text
        , config-json
            = tagConfigJsonFile {
                path_base = config.pathBase,
                path_preprocess = config.pathBase ++ "/preprocess",
                path_store = if (hasRemoteStore config.store) then Some (config.pathBase ++ "/store") else None Text,
                https,
                host = config.host,
                url_base = config.urlBase,
                authentication = stripPasswordsOffAuthentication config.depl.authentication,
                authorization = config.depl.authorization,
                module_2_location = P.Map.map Text T.BareModule T.ModuleLocation T.bareModuleToLocation config.depl.bareModules,
                module_2_config = P.Map.map Text T.Module P.JSON.Type (\(m: T.Module) -> m.config) config.depl.modules,
                mail = config.mail,
                errorlog_display = config.errors.display,
                errorlog_dir = if (isRemoteErrorLogging config.errors.logging) then Some (config.pathBase ++ "/logs/error") else None Text
            }
            : ConfigJsonFileT
    }

in {
    , makeArtifacts
}
