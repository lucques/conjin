let P       = ./vendor/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ./vendor/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall

let T       = ./types.dhall

-- Prelude
let filterMap = P.List.filterMap
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
let conjinComposerPackagePath = common.conjinComposerPackagePath
let makeHtdocsVols = common.makeHtdocsVols
let makeTemplateSassCompilation = common.makeTemplateSassCompilation


-----------------------------
-- docker-compose Services --
-----------------------------

-- TODO: Needs env vars `USER_UID` and `USER_GID` to be set
let makeUploadJob =
    \(htdocsVols:      List Compose.ServiceVolume) ->
    \(rcloneRemote:    T.RClone) ->
    let rCloneConfigVol = makeVol rcloneRemote.configPath "/config/rclone/rclone.conf"
    let excludeConjinComposerPackage = "--exclude='" ++ conjinComposerPackagePath ++ "{,/**}'"
    let createEmptyDirsCmd = 
        "rclone copy --progress --create-empty-src-dirs " ++ excludeConjinComposerPackage ++ " " ++ htdocsDir ++ " " ++ rcloneRemote.name ++ ":" ++ rcloneRemote.dir
    let syncCmd =
        "rclone sync --progress --create-empty-src-dirs " ++ excludeConjinComposerPackage ++ " --exclude=store/** --exclude=logs/error/** " ++ htdocsDir ++ " " ++ rcloneRemote.name ++ ":" ++ rcloneRemote.dir
    in
    Compose.Service::{
        , image      = Some "rclone/rclone"
        , volumes    = Some (htdocsVols # [rCloneConfigVol])
        , user       = Some "\${USER_UID:-0}:\${USER_GID:-0}"
        , entrypoint = Some (Compose.StringOrList.String "sh")
        , command    = Some (Compose.StringOrList.String 
                            ("-c \"" ++ createEmptyDirsCmd ++ " && " ++ syncCmd ++ "\""))
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
    let rCloneConfigVol = makeVol rcloneRemote.configPath "/config/rclone/rclone.conf"
    in
    Compose.Service::{
        , image   = Some "rclone/rclone"
        , volumes = Some ([backupVol, rCloneConfigVol])
        , user    = Some "\${USER_UID:-0}:\${USER_GID:-0}"
        , command = Some (Compose.StringOrList.List [
                            "copy",
                            "--progress",
                            rcloneRemote.name ++ ":" ++ rcloneRemote.dir ++ "/" ++ path,
                            "/backup/snapshot-\${TIMESTAMP:-unknown}",
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

---------------------------------
-- docker-compose config files --
---------------------------------

let makeRemoteDeplCompose =
    \(config: T.RemoteDepl) ->

    let makeReadonlyModuleCssBindVol =
            \(location: T.ModuleLocation) ->
            \(target: Text) ->
                (makeReadonlyVol (makeModuleCssDir config.depl.cssVolDir location)
                                 target)

    let makeModuleCssBindVol =
            \(location: T.ModuleLocation) ->
            \(target: Text) ->
                (makeVol (makeModuleCssDir config.depl.cssVolDir location)
                         target)

    let htdocsVols = makeHtdocsVols
            config.depl.targetDir
            config.depl.appDir
            config.depl.conjinDir
            config.depl.bareModules
            config.depl.staticFiles
            config.depl.modules
            makeReadonlyModuleCssBindVol                   -- Bind vols
            (hasRemoteErrorLogging config.errors.logging)  -- Put in htdocs (if at all)
            True                                           -- Put in htdocs
            (hasRemoteStore config.store)                  -- Put in htdocs (if at all)

    -- Define Services

    let sassCompilations = filterMap T.Module (Entry Text Compose.Service.Type)
        (\(m: T.Module) ->
            if m.compileScss then
                Some (keyValue Compose.Service.Type
                    ("sass-compile-" ++ (moduleToString m))
                    (makeTemplateSassCompilation makeModuleCssBindVol config.depl.conjinDir config.depl.appDir m))
            else
                None (Entry Text Compose.Service.Type))
        (P.Map.values Text T.Module config.depl.modules)

    let uploadJob = makeUploadJob
        htdocsVols
        config.rcloneRemote
    : Compose.Service.Type

    let backupErrorLog =
        merge {
            , None          = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithoutBackup = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithBackup    = \(s: {backupDir: Text}) -> [keyValue Compose.Service.Type "backup-errorlog" (makeBackupJob "logs/error" s.backupDir config.rcloneRemote)]
        } config.errors.logging

    let backupStore =
        merge {
            , None          = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithoutBackup = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithBackup    = \(s: {backupDir: Text}) -> [keyValue Compose.Service.Type "backup-store" (makeBackupJob "store" s.backupDir config.rcloneRemote)]
        } config.store

    let services
        : Compose.Services
        = toMap
            {
                upload = uploadJob
            }
            #
            sassCompilations
            #
            backupErrorLog
            #
            backupStore

    in Compose.Config::{ services = Some services }


in {
    , makeRemoteDeplCompose
}
