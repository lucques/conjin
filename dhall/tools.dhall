let P       = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ../ext/dhall/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall

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

let addStaticUserToGroup = 
    \(user: Text) ->
    \(group: Text) -> {
        , user = T.User.Static user
        , group
    }
: T.UserXGroup

let addOpenIdUserToGroup = 
    \(providerName: Text) ->
    \(userId: Text) ->
    \(group: Text) -> {
        , user = T.User.OpenId {providerName, id = userId}
        , group
    }
: T.UserXGroup

let addOpenIdProviderToGroup = 
    \(providerName: Text) ->
    \(group: Text) -> {
        , marker = { providerName, marker = None Text }
        , group
    }
: T.OpenIdMarkerXGroup

let addOpenIdMarkerToGroup = 
    \(providerName: Text) ->
    \(marker: Text) ->
    \(group: Text) -> {
        , marker = { providerName, marker = Some marker }
        , group
    }
: T.OpenIdMarkerXGroup

let grantPreprocPrivToStaticUser =
    \(user: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        privilege = T.Privilege.Preprocess {=}
    }
: T.ActorXPrivilege

let grantDebugPrivToStaticUser =
    \(user: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        privilege = T.Privilege.Debug {=}
    }
: T.ActorXPrivilege

let grantViewActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        actor = T.Actor.Group group,
        privilege = T.Privilege.Target {
            , targetIds
            , action = T.Action.View {=}
        }
    }
: T.ActorXPrivilege

let grantViewActionToStaticUser = 
    \(targetIds: List Text) ->
    \(user: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        privilege = T.Privilege.Target ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXPrivilege

let grantViewActionToOpenIdUser = 
    \(targetIds: List Text) ->
    \(providerName: Text) ->
    \(userId: Text) -> {
        actor = T.Actor.User (T.User.OpenId {providerName, id = userId}),
        privilege = T.Privilege.Target ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXPrivilege

let grantCustomActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) ->
    \(action: Text) -> {
        actor = T.Actor.Group group,
        privilege = T.Privilege.Target ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXPrivilege

let grantCustomActionToStaticUser = 
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        privilege = T.Privilege.Target ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXPrivilege

let grantCustomActionToOpenIdUser = 
    \(targetIds: List Text) ->
    \(providerName: Text) ->
    \(userId: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User (T.User.OpenId {providerName, id = userId}),
        privilege = T.Privilege.Target ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXPrivilege

let allowViewActionForGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        actor = T.Actor.Group group,
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXTargetRule

let allowViewActionForStaticUser = 
    \(targetIds: List Text) ->
    \(user: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXTargetRule

let allowViewActionForOpenIdUser = 
    \(targetIds: List Text) ->
    \(providerName: Text) ->
    \(userId: Text) -> {
        actor = T.Actor.User (T.User.OpenId {providerName, id = userId}),
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXTargetRule

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
: T.ActorXTargetRule

let allowCustomActionForStaticUser = 
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXTargetRule

let allowCustomActionForOpenIdUser = 
    \(targetIds: List Text) ->
    \(providerName: Text) ->
    \(userId: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User (T.User.OpenId {providerName, id = userId}),
        rule = T.TargetRule.Allow ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXTargetRule

let denyViewActionToGroup = 
    \(targetIds: List Text) ->
    \(group: Text) -> {
        actor = T.Actor.Group group,
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXTargetRule

let denyViewActionToStaticUser =
    \(targetIds: List Text) ->
    \(user: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXTargetRule

let denyViewActionToOpenIdUser =
    \(targetIds: List Text) ->
    \(providerName: Text) ->
    \(userId: Text) -> {
        actor = T.Actor.User (T.User.OpenId {providerName, id = userId}),
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.View {=}
        })
    }
: T.ActorXTargetRule

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
: T.ActorXTargetRule

let denyCustomActionToStaticUser =
    \(targetIds: List Text) ->
    \(user: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User (T.User.Static user),
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXTargetRule

let denyCustomActionToOpenIdUser =
    \(targetIds: List Text) ->
    \(providerName: Text) ->
    \(userId: Text) ->
    \(action: Text) -> {
        actor = T.Actor.User (T.User.OpenId {providerName, id = userId}),
        rule = T.TargetRule.Deny ({
            , targetIds
            , action = T.Action.Custom action
        })
    }
: T.ActorXTargetRule


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

let makeDbModuleFromLocalDb =
    \(db: T.LocalDb) -> {
        , bare = T.BareModule.Shared (T.SharedBareModule.DbMysql {=})
        , compileScss = False
        , config =
            P.JSON.object [
                , { mapKey = "host"    , mapValue = P.JSON.string "db" }
                , { mapKey = "user"    , mapValue = P.JSON.string db.user }
                , { mapKey = "password", mapValue = P.JSON.string db.userPassword }
            ]
    }
: T.Module

let makeDbModuleFromRemoteDb =
    \(db: T.RemoteDb) -> {
        , bare = T.BareModule.Shared (T.SharedBareModule.DbMysql {=})
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


------------------
-- Static files --
------------------

let makeStaticFile = 
    \(path: Text) -> {
        , mapKey = path
        , mapValue = path
    }
: P.Map.Entry Text Text


------------------------------------
-- Permanent redirects (HTTP 301) --
------------------------------------

let makePermanentRedirect = 
    \(from: Text) ->
    \(to: Text) -> {
        , mapKey   = from
        , mapValue = to
    }
: P.Map.Entry Text Text


-------------------------
-- Default deployments --
-------------------------

let makeDefaultTargetDir    = \(deplDir: Text) -> deplDir ++ "/target"
let makeDefaultDockerVolDir = \(deplDir: Text) -> deplDir ++ "/docker_volumes"
let makeDefaultBackupDir    = \(deplDir: Text) -> deplDir ++ "/backup"

let makeDefaultLocalDb =
    \(appDir: Text) ->
    \(deplDir: Text) ->
    {
        , user              = "root"
        , userPassword      = "rutus"
        , rootPassword      = "rutus"
        , storageVolDir     = Some (makeDefaultDockerVolDir deplDir ++ "/database-var")
        , initFilesDir      = Some (appDir ++ "/src/database-init")
        , backupFilesVolDir = Some (makeDefaultBackupDir deplDir ++ "/database")
    }
: T.LocalDb

let makeDefaultLocalStore =
    \(deplDir: Text) ->
    {
        , volDir    =       makeDefaultDockerVolDir deplDir ++ "/store"
        , backupDir = Some (makeDefaultBackupDir    deplDir ++ "/store")
    }
: T.LocalStore

let defaultLocalErrors = {
    , display     = True
    , logToVolDir = None Text
}: T.LocalErrors

let makeDefaultRemoteStore =
    \(deplDir: Text) ->
    T.RemoteStore.WithBackup {
        , backupDir = makeDefaultBackupDir deplDir ++ "/store"
    }
: T.RemoteStore

let makeDefaultRemoteErrors =
    \(deplDir: Text) -> {
    , display = False
    , logging = T.RemoteErrorLogging.WithBackup {
        , backupDir = makeDefaultBackupDir deplDir ++ "/logs" ++ "/error"
    }
}: T.RemoteErrors

let makeDefaultDesktopIntegration = 
    \(name: Text) -> {
        , preprocessScriptUser              = "preprocess"
        , preprocessScriptPasswordLookupCmd = "secret-tool lookup password " ++ name ++ "-preprocess"
        , preprocessScriptPasswordRegisterCmd   = Some ("secret-tool store --label='Password for " ++ name ++ " preprocess script' password " ++ name ++ "-preprocess")
    }
: T.DesktopIntegration

let defaultUnsafeAuthentication = {
    , staticLoginWithoutUserName = True
    , staticUsers2passwords = [
        , assignUser2Password "root"        "rutus"
        , assignUser2Password "preprocess"  "preprocess"
        , assignUser2Password "linkchecker" "linkchecker"
    ]
    , openIdProviders = P.List.empty T.OpenIdProvider
}: T.Authentication.Type

-- The db module is added automatically.
let makeDefaultLocalDepl = 
    \(name: Text) ->
    \(conjinDir: Text) ->
    \(appDir: Text) ->
    \(deplDir: Text) ->
    \(authentication: T.Authentication.Type) ->
    \(authorization: T.Authorization.Type) ->
    \(db: Optional T.LocalDb) ->
    \(withStore: Bool) ->
    \(sharedBareModules: List T.SharedBareModule) -> -- Selected collection of shared bare modules
    \(localBareModules: List T.LocalBareModule) ->   -- All local bare modules
    \(modules: List T.Module) ->                     -- Only modules that are specially configured
    let sharedBareModulesWrapped =
        P.List.map T.SharedBareModule T.BareModule (\(m: T.SharedBareModule) -> T.BareModule.Shared m) sharedBareModules
    let localBareModulesWrapped =
        P.List.map T.LocalBareModule T.BareModule (\(m: T.LocalBareModule) -> T.BareModule.Local m) localBareModules
    let modulesWithDbModule = merge {
        , None = modules
        , Some = \(db: T.LocalDb) -> modules # [makeDbModuleFromLocalDb db]
    } db

    in

    {
        depl = {
            , name
            , dockerProjName       = name
            , conjinDir
            , appDir
            , targetDir            = makeDefaultTargetDir    deplDir
            , cssVolDir            = makeDefaultDockerVolDir deplDir ++ "/css"
            , authentication
            , authorization
            , bareModules          = bareModuleListToMap (sharedBareModulesWrapped # localBareModulesWrapped)
            , modules              = moduleListToMap modulesWithDbModule
            , staticFiles          = P.Map.empty Text Text
            , permanentRedirects   = P.Map.empty Text Text
            , desktopIntegration   = makeDefaultDesktopIntegration name
        }
      , nginxVirtualHost  = name ++ ".localhost"
      , linkcheckerVolDir = makeDefaultDockerVolDir deplDir ++ "/linkchecker"
      , preprocessVolDir  = makeDefaultDockerVolDir deplDir ++ "/preprocess"
      , errors          = defaultLocalErrors
      , store             = if withStore then Some (makeDefaultLocalStore deplDir) else None T.LocalStore
      , db
      , linkcheckerUser                = "linkchecker"
      , linkcheckerPasswordLookupCmd   = "secret-tool lookup password " ++ name ++ "-linkchecker"
      , linkcheckerPasswordRegisterCmd = Some ("secret-tool store --label='Password for " ++ name ++ " linkchecker' password " ++ name ++ "-linkchecker")
    }
: T.LocalDepl

let makeDefaultRemoteDepl = 
    \(name: Text) ->
    \(conjinDir: Text) ->
    \(appDir: Text) ->
    \(deplDir: Text) ->
    \(authentication: T.Authentication.Type) ->
    \(authorization: T.Authorization.Type) ->
    \(withStore: Bool) ->
    \(sharedBareModules: List T.SharedBareModule) -> -- Selected collection of shared bare modules
    \(localBareModules: List T.LocalBareModule) ->   -- All local bare modules
    \(modules: List T.Module) ->                     -- Only modules that are specially configured
    \(host: Text) ->
    \(pathBase: Text) ->
    \(urlBase: Text) ->
    \(rcloneRemote: T.RClone) ->
    let sharedBareModulesWrapped =
        P.List.map T.SharedBareModule T.BareModule (\(m: T.SharedBareModule) -> T.BareModule.Shared m) sharedBareModules
    let localBareModulesWrapped =
        P.List.map T.LocalBareModule T.BareModule (\(m: T.LocalBareModule) -> T.BareModule.Local m) localBareModules in

    {
        depl = {
            , name                 = name
            , dockerProjName       = name
            , conjinDir
            , appDir
            , targetDir            = makeDefaultTargetDir deplDir
            , cssVolDir            = makeDefaultDockerVolDir deplDir ++ "/css"
            , authentication
            , authorization
            , bareModules          = bareModuleListToMap (sharedBareModulesWrapped # localBareModulesWrapped)
            , modules              = moduleListToMap     modules
            , staticFiles          = P.Map.empty Text Text
            , permanentRedirects   = P.Map.empty Text Text
            , desktopIntegration   = makeDefaultDesktopIntegration name
        }
        , host
        , pathBase
        , urlBase
        , https                  = T.HTTPS.Force {=}
        , wwwSubdomain           = T.WWWSubdomain.Off {=}
        , activateCompression    = True
        , rcloneRemote
        , errors = makeDefaultRemoteErrors deplDir
        , db = (None T.RemoteDb)
        , store = if withStore then makeDefaultRemoteStore deplDir else T.RemoteStore.None {=}
    }
: T.RemoteDepl

in {
    , assignUser2PasswordHash
    , assignUser2Password

    , addStaticUserToGroup
    , addOpenIdUserToGroup

    , addOpenIdProviderToGroup
    , addOpenIdMarkerToGroup
    
    , grantPreprocPrivToStaticUser
    , grantDebugPrivToStaticUser

    , grantViewActionToGroup
    , grantViewActionToStaticUser
    , grantViewActionToOpenIdUser
    , grantCustomActionToGroup
    , grantCustomActionToStaticUser
    , grantCustomActionToOpenIdUser
    
    , allowViewActionForGroup
    , allowViewActionForStaticUser
    , allowViewActionForOpenIdUser
    , allowCustomActionForGroup
    , allowCustomActionForStaticUser
    , allowCustomActionForOpenIdUser

    , denyViewActionToGroup
    , denyViewActionToStaticUser
    , denyViewActionToOpenIdUser
    , denyCustomActionToGroup
    , denyCustomActionToStaticUser
    , denyCustomActionToOpenIdUser
    
    , moduleListToMap
    , makeDbModuleFromLocalDb
    , makeDbModuleFromRemoteDb
    , makeLocalBareModuleWithoutDeps

    , makeStaticFile
    , makePermanentRedirect

    , defaultUnsafeAuthentication

    , makeDefaultLocalDb

    , makeDefaultLocalDepl
    , makeDefaultRemoteDepl
}