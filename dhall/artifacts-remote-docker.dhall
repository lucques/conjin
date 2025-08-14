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
let extractDockerVolumeSources = common.extractDockerVolumeSources


-----------------------------
-- docker-compose Services --
-----------------------------

-- TODO: Needs env vars `USER_UID` and `USER_GID` to be set
let makeUploadJob =
    \(htdocsVols:      List Compose.ServiceVolume) ->
    \(rcloneRemote:    T.RClone) ->
    \(waitForServices: List Text) ->
    let rCloneConfigVol = makeVol rcloneRemote.configPath "/config/rclone"
    let createEmptyDirsCmd = 
        "rclone copy --progress --create-empty-src-dirs " ++ htdocsDir ++ " " ++ rcloneRemote.name ++ ":" ++ rcloneRemote.dir
    let syncCmd =
        "rclone sync --progress --create-empty-src-dirs --exclude=store/** --exclude=logs/error/** " ++ htdocsDir ++ " " ++ rcloneRemote.name ++ ":" ++ rcloneRemote.dir
    in
    Compose.Service::{
        , image      = Some "rclone/rclone"
        , volumes    = Some (htdocsVols # [rCloneConfigVol])
        , user       = Some "\${USER_UID}:\${USER_GID}"
        , entrypoint = Some (Compose.StringOrList.String "sh")
        , command    = Some (Compose.StringOrList.String 
                            ("-c \"" ++ createEmptyDirsCmd ++ " && " ++ syncCmd ++ "\""))
        , depends_on = Some waitForServices
    }
: Compose.Service.Type


-- TODO: Needs env vars `USER_UID` and `USER_GID` to be set
-- Needs env var `TIMESTAMP` to be set
-- The `path` must be relativ to `htdocs`
-- E.g., `store` corresponds to `htdocs/store`
let makeBackupJob =
    \(path:            Text) ->
    \(backupDir:       Text) ->
    \(rcloneRemote:    T.RClone) ->
    let backupVol       = makeVol backupDir               "/backup"
    let rCloneConfigVol = makeVol rcloneRemote.configPath "/config/rclone"
    in
    Compose.Service::{
        , image   = Some "rclone/rclone"
        , volumes = Some ([backupVol, rCloneConfigVol])
        , user    = Some "\${USER_UID}:\${USER_GID}"
        , command = Some (Compose.StringOrList.List [
                            "copy",
                            "--progress",
                            rcloneRemote.name ++ ":" ++ rcloneRemote.dir ++ "/" ++ path,
                            "/backup/snapshot-\${TIMESTAMP}",
        ])
    }
: Compose.Service.Type

-------------
-- Helpers --
-------------

let hasRemoteErrorLogging =
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

let hasRemoteStoreWithBackup =
    \(s: T.RemoteStore) ->
    merge {
        , None = \(_: {})                      -> False
        , WithoutBackup =  \(_: {})            -> False
        , WithBackup = \(_: {backupDir: Text}) -> True
    } s
    : Bool


---------------------------------
-- docker-compose config files --
---------------------------------

let makeRemoteDeplUploadCompose =
    \(config: T.RemoteDepl) ->
    \(omitSass: Bool) ->

    -- Add db module for server db if it is used
    -- let modules = merge {
    --     , None = config.depl.modules
    --     , Some = \(db: T.RemoteDb) -> config.depl.modules # [{mapKey = "db", mapValue =
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

    let htdocsVols = makeHtdocsVols
            config.depl.targetDir
            config.depl.appDir
            config.depl.cssVolDir
            config.depl.conjinDir
            config.depl.bareModules
            config.depl.modules
            config.depl.staticFiles
            (hasRemoteErrorLogging config.errors.logging)
            True
            (hasRemoteStore config.store)

    -- Define Services

    let Sass = {
        compilations: List (Entry Text Compose.Service.Type),
        services: List Text 
    }

    let sass =
        if omitSass then
            None Sass
        else
            let compilations = filterMap T.Module (Entry Text Compose.Service.Type)
                (\(m: T.Module) ->
                    if m.compileScss then
                        Some (keyValue Compose.Service.Type
                            ("sass-compile-" ++ (moduleToString m))
                            (makeTemplateSassCompilation config.depl.cssVolDir config.depl.conjinDir config.depl.appDir m))
                    else
                        None (Entry Text Compose.Service.Type))
                (P.Map.values Text T.Module config.depl.modules)
            let services =
                map (Entry Text Compose.Service.Type) Text (\(e: Entry Text Compose.Service.Type) -> e.mapKey) compilations
            in
            Some { compilations, services }
    : Optional Sass

    let uploadJob = makeUploadJob
        htdocsVols
        config.rcloneRemote
        (merge { None = P.List.empty Text, Some = (\(s: Sass) -> s.services) } sass)
    : Compose.Service.Type

    -- Wrap up

    let services
        : Compose.Services
        = toMap
            {
                uploadJob
            }
            #
            (merge { None = P.List.empty (Entry Text Compose.Service.Type), Some = (\(s: Sass) -> s.compilations) } sass)

    in Compose.Config::{ services = Some services }


let makeRemoteDeplBackupErrorLogCompose =
    \(backupDir:       Text) ->
    \(rcloneRemote:    T.RClone) ->
    let job = makeBackupJob
        "logs/error"
        backupDir
        rcloneRemote

    in Compose.Config::{
        services = Some (toMap { job })
    }


let makeRemoteDeplBackupStoreCompose =
    \(backupDir:       Text) ->
    \(rcloneRemote:    T.RClone) ->
    let job = makeBackupJob
        "store"
        backupDir
        rcloneRemote

    in Compose.Config::{
        services = Some (toMap { job })
    }


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

let hasRemoteStoreWithBackup =
    \(s: T.RemoteStore) ->
    merge {
        , None = \(_: {})                      -> False
        , WithoutBackup =  \(_: {})            -> False
        , WithBackup = \(_: {backupDir: Text}) -> True
    } s
    : Bool


in {
    , makeRemoteDeplUploadCompose
    , makeRemoteDeplBackupErrorLogCompose
    , makeRemoteDeplBackupStoreCompose
}