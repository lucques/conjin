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

-- Docker common
let artifactsDockerCommon = ./artifacts-docker-common.dhall
let extractDockerVolumeSources = artifactsDockerCommon.extractDockerVolumeSources

-- Docker compose
let artifactsRemoteDocker = ./artifacts-remote-docker.dhall
let makeRemoteDeplUploadCompose = artifactsRemoteDocker.makeRemoteDeplUploadCompose
let makeRemoteDeplBackupErrorLogCompose = artifactsRemoteDocker.makeRemoteDeplBackupErrorLogCompose
let makeRemoteDeplBackupStoreCompose = artifactsRemoteDocker.makeRemoteDeplBackupStoreCompose

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
    let upload         = makeRemoteDeplUploadCompose config False
    let uploadOmitSass = makeRemoteDeplUploadCompose config True
    let backupErrorLog =
        merge {
            , None          = \(_: {}) -> None Compose.ComposeConfig
            , WithoutBackup = \(_: {}) -> None Compose.ComposeConfig
            , WithBackup    = \(s: {backupDir: Text}) -> Some (makeRemoteDeplBackupErrorLogCompose s.backupDir config.rcloneRemote)
        } config.errors.logging
    let backupStore =
        merge {
            , None          = \(_: {}) -> None Compose.ComposeConfig
            , WithoutBackup = \(_: {}) -> None Compose.ComposeConfig
            , WithBackup    = \(s: {backupDir: Text}) -> Some (makeRemoteDeplBackupStoreCompose s.backupDir config.rcloneRemote)
        } config.store
    let https =
        merge {
            , Off = \(_: {}) -> False
            , Prefer = \(_: {}) -> True
            , Force = \(_: {}) -> True
        } config.https
    in
    {
        , docker-compose-upload-yml = upload
            : Compose.ComposeConfig
        , docker-compose-upload-omit-sass-yml = uploadOmitSass
            : Compose.ComposeConfig
        , docker-compose-upload-yml-volume-sources = extractDockerVolumeSources upload
            : List Text
        , docker-compose-backup-errorlog-yml = backupErrorLog
            : Optional Compose.ComposeConfig
        , docker-compose-backup-store-yml = backupStore
            : Optional Compose.ComposeConfig
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