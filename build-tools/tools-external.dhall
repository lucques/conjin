let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package.dhall -- 2024-08

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
    \(hash: Text) -> {
        , mapKey   = user
        , mapValue = T.Password.Hash hash
    }
: Entry Text T.Password

let assignUser2Password = 
    \(user: Text) ->
    \(password: Text) -> {
        , mapKey   = user
        , mapValue = T.Password.Plain password
    }
: Entry Text T.Password

let addUserToGroup = 
    \(user: Text) ->
    \(group: Text) -> {
        , user
        , group
    }
: T.User2Group

let grantPreprocPrivToUser =
    \(user: Text) -> {
        actor = T.Actor.User user,
        privilege = T.Privilege.Preprocess {=}
    }
: T.Actor2Privilege

let grantDebugPrivToUser =
    \(user: Text) -> {
        actor = T.Actor.User user,
        privilege = T.Privilege.Debug {=}
    }
: T.Actor2Privilege

let allowViewActionForGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        actor = T.Actor.Group group,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Actor2TargetRule

let allowViewActionForUser = 
    \(targetIds: List Text) ->
    \(user: Text) -> {
        actor = T.Actor.User user,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Actor2TargetRule

let allowCustomActionForGroup = 
    \(targetIds: List Text) ->
    \(group: Text) ->
    \(action: Text) -> {
        actor = T.Actor.Group group,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Actor2TargetRule

let allowCustomActionForUser = 
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User user,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Actor2TargetRule

let denyViewActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        actor = T.Actor.Group group,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Actor2TargetRule

let denyViewActionToUser =
    \(targetIds: List Text) ->
    \(user: Text) -> {
        actor = T.Actor.User user,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.Actor2TargetRule

let denyCustomActionToGroup =
    \(targetIds: List Text) ->
    \(group: Text) ->
    \(action: Text) -> {
        actor = T.Actor.Group group,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Actor2TargetRule

let denyCustomActionToUser =
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User user,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.Actor2TargetRule


-------------
-- Modules --
-------------

-- Use dir-name as key. Implicit assumption: dir-names are unique.
let bareModuleListToMap =
    \(modules: List T.BareModule) ->
    map T.BareModule (Entry Text T.BareModule) (\(m: T.BareModule) -> { mapKey = (T.bareModuleToLocation m).dirName, mapValue = m }) modules
: P.Map.Type Text T.BareModule

-- Use dir-name as key. Implicit assumption: dir-names are unique.
let moduleListToMap =
    \(modules: List T.Module) ->
    map T.Module (Entry Text T.Module) (\(m: T.Module) -> { mapKey = (T.bareModuleToLocation m.bare).dirName, mapValue = m }) modules
: P.Map.Type Text T.Module

let makeDbModuleFromDockerDb =
    \(db: T.DockerDb) -> {
        , bare = T.BareModule.Db {=}
        , compileScss = False
        , config =
            P.JSON.object [
                , { mapKey = "host"    , mapValue = P.JSON.string "db" }
                , { mapKey = "user"    , mapValue = P.JSON.string db.user }
                , { mapKey = "password", mapValue = P.JSON.string db.userPassword }
            ]
    }
: T.Module

let makeDbModuleFromServerDb =
    \(db: T.ServerDb) -> {
        , bare = T.BareModule.Db {=}
        , compileScss = False
        , config =
            P.JSON.object [
                , { mapKey = "host"    , mapValue = P.JSON.string db.host }
                , { mapKey = "user"    , mapValue = P.JSON.string db.user }
                , { mapKey = "password", mapValue = P.JSON.string db.password }
            ]
    }
: T.Module

let makeLocalBareModuleWithoutDeps =
    \(dirName: Text) ->
    \(isExternal: Bool) -> {
        , location = {
            , dirName
            , isExternal
        }
        , scssModuleDeps = P.List.empty T.ModuleLocation
    }
: T.LocalBareModule


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

let makeDefaultDesktopIntegration = 
    \(name: Text) -> {
        , installSymlinksInLocalBin         = True
        , preprocessScriptUser              = "preprocess"
        , preprocessScriptPasswordLookupCmd = "secret-tool lookup password " ++ name ++ "-preprocess"
        , preprocessScriptPasswordRegisterCmd   = Some ("secret-tool store --label='Password for " ++ name ++ " preprocess script' password " ++ name ++ "-preprocess")
    }
: T.DesktopIntegration

let defaultUnsafeAuthentication = {
    , loginWithoutUserName = True
    , users2passwords = [
        , assignUser2Password "root"        "rutus"
        , assignUser2Password "preprocess"  "preprocess"
        , assignUser2Password "linkchecker" "linkchecker"
    ]
}

-- The db module is added automatically.
let makeDefaultDockerNginxDepl = 
    \(name: Text) ->
    \(conjinDir: Text) ->
    \(appDir: Text) ->
    \(deplDir: Text) ->
    \(authentication: T.Authentication) ->
    \(authorization: T.Authorization) ->
    \(db: Optional T.DockerDb) ->
    \(localBareModules: List T.LocalBareModule) ->  -- All local bare modules
    \(modules: List T.Module) ->                    -- Only modules that are specially configured
    let localBareModulesWrapped =
        P.List.map T.LocalBareModule T.BareModule (\(m: T.LocalBareModule) -> T.BareModule.LocalBareModule m) localBareModules
    let modulesWithDbModule = merge {
        , None = modules
        , Some = \(db: T.DockerDb) -> modules # [makeDbModuleFromDockerDb db]
    } db

    in

    {
        depl = {
            , name
            , dockerProjName       = name
            , conjinDir
            , appDir
            , targetDir            = makeDefaultTargetDir deplDir
            , authentication
            , authorization
            , bareModules          = bareModuleListToMap (T.sharedBareModules # localBareModulesWrapped)
            , modules              =     moduleListToMap modulesWithDbModule
            , desktopIntegration   = makeDefaultDesktopIntegration name
        }
      , nginxVirtualHost  = name ++ ".localhost"
      , linkcheckerVolDir = makeDefaultDockerVolDir deplDir ++ "/linkchecker"
      , preprocessVolDir  = makeDefaultDockerVolDir deplDir ++ "/preprocess"
      , db
      , linkcheckerUser                   = "linkchecker"
      , linkcheckerPasswordLookupCmd      = "secret-tool lookup password " ++ name ++ "-linkchecker"
      , linkcheckerPasswordRegisterCmd    = Some ("secret-tool store --label='Password for " ++ name ++ " linkchecker' password " ++ name ++ "-linkchecker")
    }
: T.DockerNginxDepl

let makeDefaultDockerSyncDepl = 
    \(name: Text) ->
    \(conjinDir: Text) ->
    \(appDir: Text) ->
    \(deplDir: Text) ->
    \(authentication: T.Authentication) ->
    \(authorization: T.Authorization) ->
    \(db: Optional T.ServerDb) ->
    \(localBareModules: List T.LocalBareModule) ->
    \(modules: List T.Module) ->
    \(host: Text) ->
    \(pathBase: Text) ->
    \(urlBase: Text) ->
    \(rcloneRemote: T.RCloneRemote) ->
    let localBareModulesWrapped =
        P.List.map T.LocalBareModule T.BareModule (\(m: T.LocalBareModule) -> T.BareModule.LocalBareModule m) localBareModules in

    {
        depl = {
            , name                 = name
            , dockerProjName       = name
            , conjinDir
            , appDir
            , targetDir            = makeDefaultTargetDir deplDir
            , authentication
            , authorization
            , bareModules          = bareModuleListToMap (T.sharedBareModules # localBareModulesWrapped)
            , modules              = moduleListToMap     modules
            , desktopIntegration   = makeDefaultDesktopIntegration name
        }
        , host
        , pathBase
        , urlBase
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
    , assignUser2Password
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

    , moduleListToMap
    , makeDbModuleFromDockerDb
    , makeDbModuleFromServerDb
    , makeLocalBareModuleWithoutDeps

    , defaultUnsafeAuthentication

    , makeDefaultDockerDb

    , makeDefaultDockerNginxDepl
    , makeDefaultDockerSyncDepl
}