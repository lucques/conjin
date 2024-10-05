let P       = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ../ext/dhall/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall


--------------------
-- Shared Modules --
--------------------

let SharedModuleLocation       = (./types-shared-modules.dhall).SharedModuleLocation
let SharedBareModule           = (./types-shared-modules.dhall).SharedBareModule
let sharedBareModuleToLocation = (./types-shared-modules.dhall).sharedBareModuleToLocation
let sharedBareModuleToScssDeps = (./types-shared-modules.dhall).sharedBareModuleToScssDeps


-------------
-- Modules --
-------------

let ModuleLocation = { dirName: Text, isShared: Bool, isExternal: Bool }

let LocalBareModule = { location: { dirName: Text, isExternal: Bool }, scssModuleDeps: List ModuleLocation }

let BareModule = <
    Shared: SharedBareModule |
    Local:  LocalBareModule
>

let Module = {
    bare:        BareModule,
    compileScss: Bool,
    config:      P.JSON.Type
}

let bareModuleToScssDeps = \(m: BareModule) ->
    merge {
        Shared = \(sm: SharedBareModule) -> P.List.map SharedModuleLocation ModuleLocation (\(m: SharedModuleLocation) -> (m // { isShared = True })) (sharedBareModuleToScssDeps sm),
        Local  = \(lm: LocalBareModule)  -> lm.scssModuleDeps
    } m
: List ModuleLocation

let bareModuleToLocation = \(id: BareModule) ->
    merge {
        Shared = \(m: SharedBareModule) -> (sharedBareModuleToLocation m) // { isShared = True },
        Local  = \(m: LocalBareModule) -> {
            , dirName    = m.location.dirName
            , isShared   = False
            , isExternal = m.location.isExternal
        }
    } id
: ModuleLocation



----------
-- Auth --
----------

-- How to read the OpenID data. First, `key` is used to select an attribute.
-- If that attribute is a list (`isList`), all list items are used as markers.
-- If the list items are records, then the `subkey` is used to index into the
-- records.
-- Example: `{ key = "groups", isList = True, subkey = Some "act" }`
let MarkerAttribute = { key: Text, isList: Bool, subkey: Optional Text }

let OpenIdProvider = {
    , name: Text
    , server: Text
    , clientId: Text
    , clientSecret: Text
    , scopes: List Text     -- e.g. ["email", "openid"]
    , idAttribute: Text     -- which attribute is used as user ID, e.g. "email"
    , markerAttributes: List MarkerAttribute
}

let Password             = < Hash: Text | Plain: Text >
let OpenIdUser           = { providerName: Text, id: Text }
let User                 = < Static: Text | OpenId: OpenIdUser >

let Actor                = < User: User | Group: Text >
let Action               = < View: {} | Custom: Text >
let TargetPrivilege      = { targetIds: List Text, action: Action }
let TargetRule           = < Allow: TargetPrivilege | Deny: TargetPrivilege >
let Privilege            = < Debug: {} | Preprocess: {} | LoginLogout: {} | Target: TargetPrivilege >

-- The `X` stands for "cartesian product"
let UserXGroup           = { user: User, group: Text }
let ActorXPrivilege      = { actor: Actor, privilege: Privilege }
let ActorXTargetRule     = { actor: Actor, rule: TargetRule }

let OpenIdMarker         = { providerName: Text, marker: Optional Text }
let OpenIdMarkerXGroup   = { marker: OpenIdMarker, group: Text }

let PasswordList         = P.Map.Type Text Password


let Authentication = {
    Type = {
        , staticLoginWithoutUserName: Bool
        , staticUsers2passwords:      PasswordList
        , openIdProviders:            List OpenIdProvider
    },
    default = {
        , openIdProviders = P.List.empty OpenIdProvider
    }
}

let AuthenticationWithoutPasswords = {
    , staticLoginWithoutUserName: Bool
    , openIdProviders: List OpenIdProvider
}

let Authorization = {
    Type = {
        , staticRootUser:  Text
        , staticGuestUser: Text
        , usersXgroups:         List UserXGroup
        , actorsXprivileges:    List ActorXPrivilege
        , actorsXtargetRules:   List ActorXTargetRule
        , openIdMarkersXgroups: List OpenIdMarkerXGroup
    },
    default = {
        , staticRootUser       = "root"
        , staticGuestUser      = "guest"
        , usersXgroups         = P.List.empty UserXGroup
        , actorsXprivileges    = P.List.empty ActorXPrivilege
        , actorsXtargetRules   = P.List.empty ActorXTargetRule
        , openIdMarkersXgroups = P.List.empty OpenIdMarkerXGroup
    }
}


------------------------
-- Deployment: Common --
------------------------

 -- If `*PasswordInitCmd` is `Some`, then the password must have been configured
 -- in plaintext. That password is then fed into StdIn of that command which
 -- should register the password.
 
let DesktopIntegration = {
    , preprocessScriptUser                : Text
    , preprocessScriptPasswordLookupCmd   : Text
    , preprocessScriptPasswordRegisterCmd : Optional Text
}

-- General Docker deployment (base config for other deployments)
-- Note:
-- There is an additional abstraction layer w.r.t. the modules. The modules all
-- have a dir-name. But that dir-name is not the same as the module name. It
-- usually coincides, but not necessarily. In this type, the `bareModules` and
-- `modules` fields are used to map the module name to the module config and are
-- therefore defined in a slightly more general way. Creating these maps is done
-- by some utility function (and as described, usually just the dir-name is
-- used).
let DockerDepl = {
    , name                 : Text
    , dockerProjName       : Text
    , conjinDir            : Text
    , appDir               : Text
    , targetDir            : Text
    , cssVolDir            : Text

    -- , mode                 : 

    , authentication       : Authentication.Type
    , authorization        : Authorization.Type

    , bareModules          : P.Map.Type Text BareModule
    , modules              : P.Map.Type Text Module
    , staticFiles          : P.Map.Type Text Text  -- Static files to be served by nginx
    , permanentRedirects   : P.Map.Type Text Text  -- Sends HTTP 301; Source is a regex pattern
    , desktopIntegration   : DesktopIntegration
}


---------------------------------------------
-- Deployment: Local with Nginx and Docker --
---------------------------------------------

let LocalErrors = {
    , display:     Bool
    , logToVolDir: Optional Text
}

let LocalDb = { 
    , user                 : Text
    , userPassword         : Text
    , rootPassword         : Text
    , storageVolDir        : Optional Text  -- if `None`, then no permanent db storage beyond container lifetime
    , initFilesDir         : Optional Text  -- if `None`, then no init files; Read-only
    , backupFilesVolDir    : Optional Text  -- if `None`, then no backup possible
}

-- "Store" is a directory where application-wide dynamic data is stored, e.g.
-- Sqlite databases, uploaded files, etc.
let LocalStore = {
    , volDir            : Text
    , backupDir         : Optional Text
}

let LocalDepl = {
    , depl                           : DockerDepl
    , nginxVirtualHost               : Text
    , linkcheckerVolDir              : Text
    , preprocessVolDir               : Text
    , errors                         : LocalErrors
    , db                             : Optional LocalDb
    , store                          : Optional LocalStore
    , linkcheckerUser                : Text
    , linkcheckerPasswordLookupCmd   : Text
    , linkcheckerPasswordRegisterCmd : Optional Text
}


------------------------
-- Deployment: Remote --
------------------------

let RemoteErrorLogging = < None: {} | WithoutBackup: {} | WithBackup: { backupDir: Text } >

let RemoteErrors = {
    , display: Bool
    , logging: RemoteErrorLogging
}

-- Connection to a database on a remote server
-- Backup mechansim currently not implemented
let RemoteDb = {
    , host                 : Text
    , user                 : Text
    , password             : Text
}

-- Config for server-side store, with the possibility for local backup
let RemoteStore = <None: {} | WithoutBackup: {} | WithBackup: { backupDir: Text } >

let RClone = {
    , name                 : Text
    , dir                  : Text
    , configPath           : Text
}

let HTTPS = < Off: {} | Prefer: {} | Force: {} >

let WWWSubdomain = < Off: {} | On: {} | NoRedirect: {} >

let RemoteDepl = {
    , depl                : DockerDepl
    , host                : Text
    , pathBase            : Text   -- e.g. `/htdocs/my-app` base/root diretory in the filesystem
    , urlBase             : Text   -- e.g. `/my-app/`       corresponds to URLs with prefix `https://my-domain.org/my-app/`
    , https               : HTTPS
    , wwwSubdomain        : WWWSubdomain
    , activateCompression : Bool
    , rcloneRemote        : RClone
    , errors              : RemoteErrors
    , db                  : Optional RemoteDb
    , store               : RemoteStore -- If `WithBackup`, then a `store` directory is created next to the `preprocess` directory
}

in

{ SharedModuleLocation, SharedBareModule, sharedBareModuleToLocation, sharedBareModuleToScssDeps,
  ModuleLocation, LocalBareModule, BareModule, Module, bareModuleToLocation, bareModuleToScssDeps,
  Authentication, AuthenticationWithoutPasswords, Authorization, LocalDb, RemoteDb, LocalStore, RemoteStore, RClone, DockerDepl, DesktopIntegration,
  LocalErrors, RemoteErrors,
  HTTPS, WWWSubdomain,
  RemoteErrorLogging,
  LocalDepl, RemoteDepl,
  OpenIdProvider,
  Password, User, Actor, Action, TargetPrivilege, TargetRule, Privilege, UserXGroup, OpenIdMarker, OpenIdMarkerXGroup, ActorXPrivilege, ActorXTargetRule, PasswordList,
 }