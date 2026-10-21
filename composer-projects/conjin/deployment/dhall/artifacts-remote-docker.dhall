let P       = ../../ext/dhall/dhall-lang/Prelude/package_resolved.dhall
let Compose = ../../ext/dhall/dhall-docker-compose/compose/v3/package_resolved.dhall

let T       = ./types.dhall
let dockerImages = ./docker-images.dhall

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
let makeWebserverVols = common.makeWebserverVols
let makeTemplateSassCompilation = common.makeTemplateSassCompilation


-----------------------------
-- docker-compose Services --
-----------------------------

-- TODO: Needs env vars `USER_UID` and `USER_GID` to be set
let makeUploadJob =
    \(webserverVols: List Compose.ServiceVolume) ->
    \(rcloneRemote:    T.RClone) ->
    \(internalDir:     Text) ->
    \(withStore: Bool) ->
    \(withErrorLog: Bool) ->
    let rCloneConfigVol = makeVol rcloneRemote.configPath "/config/rclone/rclone.conf"
    let excludeConjinComposerPackage = "--exclude='/" ++ internalDir ++ conjinComposerPackagePath ++ "{,/**}'"
    let contentDir = internalDir ++ "/content"
    -- Sync content separately so deleting excluded dev trees cannot affect
    -- the deployment's other exclusions.
    let exclusions = excludeConjinComposerPackage
        ++ " --exclude=/" ++ internalDir ++ "-dyn-data/**"
        ++ " --exclude=/" ++ internalDir ++ "-dyn-preprocessing/**"
        ++ " --exclude=/" ++ internalDir ++ "-dyn-preprocessing-new/**"
        ++ " --exclude=/" ++ internalDir ++ "-dyn-preprocessing-old/**"
        ++ " --exclude='/" ++ contentDir ++ "/**'"
    -- Persistent data is entirely excluded from deployment sync. Install its
    -- access protection separately, and create optional runtime directories.
    let dataDir = internalDir ++ "-dyn-data"
    let remoteRoot = rcloneRemote.name ++ ":" ++ rcloneRemote.dir
    let protectDataCmd = "rclone copyto " ++ htdocsDir ++ "/" ++ dataDir ++ "/.htaccess " ++ remoteRoot ++ "/" ++ dataDir ++ "/.htaccess"
        ++ (if withStore then " && rclone mkdir " ++ remoteRoot ++ "/" ++ dataDir ++ "/store" else "")
        ++ (if withErrorLog then " && rclone mkdir " ++ remoteRoot ++ "/" ++ dataDir ++ "/logs/error" else "")
    let syncCmd =
        "rclone sync --progress --create-empty-src-dirs " ++ exclusions ++ " " ++ htdocsDir ++ " " ++ rcloneRemote.name ++ ":" ++ rcloneRemote.dir
    let syncContentCmd =
        "rclone sync --progress --create-empty-src-dirs --delete-excluded --exclude='dev/**' "
        ++ htdocsDir ++ "/" ++ contentDir ++ " " ++ remoteRoot ++ "/" ++ contentDir
    in
    Compose.Service::{
        , image      = Some dockerImages.imageRclone
        , volumes    = Some (webserverVols # [rCloneConfigVol])
        , user       = Some "\${USER_UID:-0}:\${USER_GID:-0}"
        , entrypoint = Some (Compose.StringOrList.String "sh")
        , command    = Some (Compose.StringOrList.String 
                            ("-c \"" ++ protectDataCmd ++ " && " ++ syncCmd ++ " && " ++ syncContentCmd ++ "\""))
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
        , image   = Some dockerImages.imageRclone
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

    let webserverVols = (makeWebserverVols
            config.depl.targetDir
            config.depl.appDir
            config.depl.conjinDir
            config.depl.bareModules
            config.depl.staticFiles
            config.depl.modules
            makeReadonlyModuleCssBindVol                   -- Bind vols
            (htdocsDir ++ "/" ++ config.internalDir)) # [
                makeReadonlyVol
                    (config.depl.targetDir ++ "/htdocs/" ++ config.internalDir ++ "-dyn-preprocessing")
                    (htdocsDir ++ "/" ++ config.internalDir ++ "-dyn-preprocessing"),
                makeReadonlyVol
                    (config.depl.targetDir ++ "/htdocs/" ++ config.internalDir ++ "-dyn-data")
                    (htdocsDir ++ "/" ++ config.internalDir ++ "-dyn-data")
            ]

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
        webserverVols
        config.rcloneRemote
        config.internalDir
        (hasRemoteStore config.store)
        (hasRemoteErrorLogging config.errors.logging)
    : Compose.Service.Type

    let backupErrorLog =
        merge {
            , None          = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithoutBackup = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithBackup    = \(s: {backupDir: Text}) -> [keyValue Compose.Service.Type "backup-errorlog" (makeBackupJob (config.internalDir ++ "-dyn-data/logs/error") s.backupDir config.rcloneRemote)]
        } config.errors.logging

    let backupStore =
        merge {
            , None          = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithoutBackup = \(_: {}) -> empty (Entry Text Compose.Service.Type)
            , WithBackup    = \(s: {backupDir: Text}) -> [keyValue Compose.Service.Type "backup-store" (makeBackupJob (config.internalDir ++ "-dyn-data/store") s.backupDir config.rcloneRemote)]
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
