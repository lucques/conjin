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


----------
-- Auth --
----------

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

let grantDebugPrivToUser =
    \(user: Text) -> {
        group = T.Group.UserBased user,
        privilege = T.Privilege.Debug {=}
    }
: T.Group2Privilege

let allowViewActionForGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        group = T.Group.Custom group,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Group2TargetRule

let allowViewActionForUser = 
    \(targetIds: List Text) ->
    \(user: Text) -> {
        group = T.Group.UserBased user,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Group2TargetRule

let allowCustomActionForGroup = 
    \(targetIds: List Text) ->
    \(group: Text) ->
    \(action: Text) -> {
        group = T.Group.Custom group,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Group2TargetRule

let allowCustomActionForUser = 
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        group = T.Group.UserBased user,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Group2TargetRule

let denyViewActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        group = T.Group.Custom group,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Group2TargetRule

let denyViewActionToUser =
    \(targetIds: List Text) ->
    \(user: Text) -> {
        group = T.Group.UserBased user,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Group2TargetRule

let denyCustomActionToGroup =
    \(targetIds: List Text) ->
    \(group: Text) ->
    \(action: Text) -> {
        group = T.Group.Custom group,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Group2TargetRule

let denyCustomActionToUser =
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        group = T.Group.UserBased user,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Group2TargetRule


-------------
-- Modules --
-------------

let makeModule = 
    \(dirName: Text) ->
    \(isShared: Bool) ->
    \(isExternal: Bool) -> {
        location = {dirName, isShared, isExternal},
        compileScss = False,
        scssModuleDeps = empty T.ModuleLocation,
        defaultConfig = P.JSON.null,
    }
: T.Module

let makeModules = 
    \(isShared: Bool) ->
    \(isExternal: Bool) ->
    \(names: List Text) ->
    map Text T.Module (\(n: Text) -> {
        location = {dirName = n, isShared, isExternal},
        compileScss = False,
        scssModuleDeps = empty T.ModuleLocation,
        defaultConfig = P.JSON.null,
    })
    names
: List T.Module

let moduleListToMap =
    \(modules: List T.Module) ->
    map T.Module (Entry Text T.Module) (\(m: T.Module) -> { mapKey = m.location.dirName, mapValue = m }) modules
: P.Map.Type Text T.Module


-------------------------
-- Default deployments --
-------------------------


let makeDefaultTargetDir    = \(deplDir: Text) -> deplDir ++ "/target"
let makeDefaultDockerVolDir = \(deplDir: Text) -> deplDir ++ "/docker_volumes"

let makeDefaultDockerDb =
    \(appDir: Text) ->
    \(deplDir: Text) ->
    {
        , user         = "root"
        , userPassword = "rutus"
        , rootPassword = "rutus"
        , volDir       = Some (makeDefaultDockerVolDir deplDir ++ "/var/database-storage")
        , initFilesDir = Some (appDir ++ "/src/database-init")
    }
: T.DockerDb

let makeDefaultDockerNginxDepl = 
    \(name: Text) ->
    \(conjinDir: Text) ->
    \(appDir: Text) ->
    \(deplDir: Text) ->
    \(auth: T.AppAuth) ->
    \(db: Optional T.DockerDb) ->
    \(mainTemplate: T.Module) ->
    \(modules: List T.Module) ->
    {
        depl = {
            , name
            , dockerProjName       = name
            , conjinDir
            , appDir
            , targetDir            = makeDefaultTargetDir deplDir
            , auth                 = Some {
                , app = auth
                , preprocessScriptUser        = "preprocess"
                , preprocessScriptPasswordCmd = "secret-tool lookup password " ++ name ++ "-preprocess"
            }
            , modules              = moduleListToMap modules
            , faviconIcoFrom       = Some mainTemplate.location
            , notFoundTemplate     = mainTemplate
            , unauthorizedTemplate = mainTemplate
        }
      , nginxVirtualHost  = name ++ ".localhost"
      , linkcheckerVolDir = makeDefaultDockerVolDir deplDir ++ "/linkchecker"
      , preprocessVolDir  = makeDefaultDockerVolDir deplDir ++ "/preprocess"
      , db
    }
: T.DockerNginxDepl

let makeDefaultDockerSyncDepl = 
    \(name: Text) ->
    \(conjinDir: Text) ->
    \(appDir: Text) ->
    \(deplDir: Text) ->
    \(auth: T.AppAuth) ->
    \(db: Optional T.ServerDb) ->
    \(mainTemplate: T.Module) ->
    \(modules: List T.Module) ->
    \(host: Text) ->
    \(rcloneRemote: T.RCloneRemote) ->
    {
        depl = {
            , name                 = name
            , dockerProjName       = name
            , conjinDir
            , appDir
            , targetDir            = makeDefaultTargetDir deplDir
            , auth                 = Some {
                , app                         = auth
                , preprocessScriptUser        = "preprocess"
                , preprocessScriptPasswordCmd = "secret-tool lookup password " ++ name ++ "-preprocess"
            }
            , modules              = moduleListToMap modules
            , faviconIcoFrom       = Some mainTemplate.location
            , notFoundTemplate     = mainTemplate
            , unauthorizedTemplate = mainTemplate
        }
        , host
        , preferHTTPS = True
        , forceHTTPS = True
        , activateCompression = True
        , rcloneRemote
        , db
    }
: T.DockerSyncDepl

in {
    , prelude = P
    , types = T

    , assignUser2PasswordHash
    , addUserToGroup
    , grantPreprocPrivToUser
    , grantDebugPrivToUser
    
    , allowViewActionForGroup
    , allowViewActionForUser
    , allowCustomActionForGroup
    , allowCustomActionForUser

    , denyViewActionToGroup
    , denyViewActionToUser
    , denyCustomActionToGroup
    , denyCustomActionToUser

    , makeModule
    , makeModules
    , moduleListToMap

    , makeDefaultDockerDb
    , makeDefaultDockerSyncDepl

    , makeDefaultDockerNginxDepl
}