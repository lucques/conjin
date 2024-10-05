let P       = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let T       = ./types.dhall


-- Types
let SharedModuleLocation       = T.SharedBareModule
let SharedBareModule           = T.SharedBareModule
let sharedBareModuleToLocation = T.sharedBareModuleToLocation
let sharedBareModuleToScssDeps = T.sharedBareModuleToScssDeps
let ModuleLocation = T.ModuleLocation
let LocalBareModule = T.LocalBareModule
let BareModule = T.BareModule
let Module = T.Module
let Authentication = T.Authentication
let AuthenticationWithoutPasswords = T.AuthenticationWithoutPasswords
let Authorization = T.Authorization
let LocalErrors = T.LocalErrors
let LocalDb = T.LocalDb
let RemoteDb = T.RemoteDb
let LocalStore = T.LocalStore
let RemoteStore = T.RemoteStore
let RClone = T.RClone
let DockerDepl = T.DockerDepl
let DesktopIntegration = T.DesktopIntegration
let HTTPS = T.HTTPS
let WWWSubdomain = T.WWWSubdomain
let RemoteErrorLogging = T.RemoteErrorLogging
let RemoteErrors = T.RemoteErrors
let LocalDepl = T.LocalDepl
let RemoteDepl = T.RemoteDepl
let OpenIdProvider = T.OpenIdProvider
let Password = T.Password
let User = T.User
let Actor = T.Actor
let Action = T.Action
let TargetPrivilege = T.TargetPrivilege
let TargetRule = T.TargetRule
let Privilege = T.Privilege
let UserXGroup = T.UserXGroup
let OpenIdMarker = T.OpenIdMarker
let OpenIdMarkerXGroup = T.OpenIdMarkerXGroup
let ActorXPrivilege = T.ActorXPrivilege
let ActorXTargetRule = T.ActorXTargetRule
let PasswordList = T.PasswordList

let bareModuleToLocation = T.bareModuleToLocation
let bareModuleToScssDeps = T.bareModuleToScssDeps



-------------
-- Tagging --
-------------

-- Configuration that is exported to JSON may contain all relevant information
-- but that information may only be retrievable in the Dhall format. Therefore
-- the JSON output must be tagged by additional information. There are two ways
-- in which this applies. The first one is purely technical and a limitation of
-- the `json-to-dhall` compiler. The second one 

-- 1. Union types
-- Much of the following lines of code is only present because union types
-- cannot be converted by `json-to-dhall` such that the tag is preserved. See
-- also https://github.com/dhall-lang/dhall-haskell/issues/1383
-- This problem should not be dealt with in Dhall, but in the translation
-- process from Dhall to JSON. However, such a cmd-line argument is not
-- available yet, so we stick with this workaround for now.

-- 2. Derived values
-- The second reason for tagging is that some values are derivable via function
-- calls. Since Dhall functions are not present in the JSON output, those values
-- are explicitly added.

let PasswordT             = P.JSON.Tagged Password
let UserT                 = P.JSON.Tagged User

let ActorTUntagged        = < User: UserT | Group: Text >
let ActorT                = P.JSON.Tagged ActorTUntagged

let ActionT               = P.JSON.Tagged Action
let TargetPrivilegeT      = { targetIds: List Text, action: ActionT }

let TargetRuleTUntagged   = < Allow: TargetPrivilegeT | Deny: TargetPrivilegeT >
let TargetRuleT           = P.JSON.Tagged TargetRuleTUntagged

let PrivilegeTUntagged    = < Debug: {} | Preprocess: {} | LoginLogout: {} | Target: TargetPrivilegeT >
let PrivilegeT            = P.JSON.Tagged PrivilegeTUntagged

let UserXGroupT           = { user: UserT, group: Text }
let ActorXPrivilegeT      = { actor: ActorT, privilege: PrivilegeT }
let ActorXTargetRuleT     = { actor: ActorT, rule: TargetRuleT }

let PasswordListT         = P.Map.Type Text PasswordT

let AuthenticationT = {
    , staticLoginWithoutUserName: Bool
    , staticUsers2passwords: PasswordListT
    , openIdProviders: List OpenIdProvider
}

let AuthorizationT = {
    , staticRootUser:  Text
    , staticGuestUser: Text
    , usersXgroups:         List UserXGroupT
    , actorsXprivileges:    List ActorXPrivilegeT
    , actorsXtargetRules:   List ActorXTargetRuleT
    , openIdMarkersXgroups: List OpenIdMarkerXGroup
}

let HTTPST = P.JSON.Tagged HTTPS

let WWWSubdomainT = P.JSON.Tagged WWWSubdomain

let RemoteErrorLoggingT = P.JSON.Tagged RemoteErrorLogging
let RemoteErrorsT = {
    , display: Bool
    , logging: RemoteErrorLoggingT
}

let RemoteStoreT = P.JSON.Tagged RemoteStore

let DockerDeplT = {
    , name                 : Text
    , dockerProjName       : Text
    , conjinDir            : Text
    , appDir               : Text
    , targetDir            : Text
    , cssVolDir               : Text

    , authentication       : AuthenticationT
    , authorization        : AuthorizationT

    , bareModules          : P.Map.Type Text BareModule
    , moduleLocations      : P.Map.Type Text ModuleLocation        -- Enriched
    , moduleSCSSDeps       : P.Map.Type Text (List ModuleLocation) -- Enriched

    , modules              : P.Map.Type Text Module
    , staticFiles          : P.Map.Type Text Text
    , permanentRedirects   : P.Map.Type Text Text
    , desktopIntegration   : DesktopIntegration
}

let LocalDeplT = {
    , depl: DockerDeplT
    , nginxVirtualHost: Text
    , linkcheckerVolDir: Text
    , preprocessVolDir: Text
    , store: Optional LocalStore
    , errors: LocalErrors
    , db: Optional LocalDb
    , linkcheckerUser: Text
    , linkcheckerPasswordLookupCmd: Text
    , linkcheckerPasswordRegisterCmd: Optional Text
}

let RemoteDeplT = {
    , depl: DockerDeplT
    , host: Text
    , pathBase: Text
    , urlBase: Text
    , https: HTTPST
    , wwwSubdomain: WWWSubdomainT
    , activateCompression: Bool
    , rcloneRemote: RClone
    , errors: RemoteErrorsT
    , db: Optional RemoteDb
    , store: RemoteStoreT
}



let tagHTTPS = \(h: HTTPS) -> P.JSON.tagNested "contents" "tag" HTTPS h
    : HTTPST

let tagWWWSubdomain = \(w: WWWSubdomain) -> P.JSON.tagNested "contents" "tag" WWWSubdomain w
    : WWWSubdomainT

let tagPassword = \(p: Password) -> P.JSON.tagNested "contents" "tag" Password p
    : PasswordT

let tagUser = \(u: User) -> P.JSON.tagNested "contents" "tag" User u
    : UserT

let tagActor = \(a: Actor) ->
    P.JSON.tagNested "contents" "tag" ActorTUntagged
    (merge {
        , User = \(u: User) -> ActorTUntagged.User (tagUser u)
        , Group  = \(g: Text) -> ActorTUntagged.Group g
    } a)
    : ActorT

let tagAction = \(a: Action) -> P.JSON.tagNested "contents" "tag" Action a
    : ActionT

let tagTargetPrivilege = \(tp: TargetPrivilege) ->
    tp // {
        , action = tagAction tp.action
    }: TargetPrivilegeT

let tagTargetRule = \(tr: TargetRule) ->
    P.JSON.tagNested "contents" "tag" TargetRuleTUntagged
    (merge {
        , Allow = \(tp: TargetPrivilege) -> TargetRuleTUntagged.Allow (tagTargetPrivilege tp)
        , Deny  = \(tp: TargetPrivilege) -> TargetRuleTUntagged.Deny (tagTargetPrivilege tp)
    } tr)
    : TargetRuleT

let tagPrivilege = \(p: Privilege) ->
    P.JSON.tagNested "contents" "tag" PrivilegeTUntagged
    (merge {
        , Debug = \(t: {}) -> PrivilegeTUntagged.Debug {=}
        , Preprocess = \(t: {}) -> PrivilegeTUntagged.Preprocess {=}
        , LoginLogout = \(t: {}) -> PrivilegeTUntagged.LoginLogout {=}
        , Target = \(tp: TargetPrivilege) ->
            PrivilegeTUntagged.Target { targetIds = tp.targetIds, action = tagAction tp.action }
    } p)
    : PrivilegeT

let tagUserXGroup = \(u2g: UserXGroup) ->
    u2g // {
        , user  = tagUser u2g.user
        , group = u2g.group
    }: UserXGroupT

let tagActorXPrivilege = \(a2p: ActorXPrivilege) ->
    a2p // {
        , actor = tagActor a2p.actor
        , privilege = tagPrivilege a2p.privilege
    }: ActorXPrivilegeT

let tagActorXTargetRule = \(a2tr: ActorXTargetRule) ->
    a2tr // {
        , actor = tagActor a2tr.actor
        , rule = tagTargetRule a2tr.rule
    }: ActorXTargetRuleT

let tagPasswordList = \(pl: PasswordList) ->
    P.Map.map Text Password PasswordT tagPassword pl
    : PasswordListT

let tagAuthentication = \(a: Authentication.Type) ->
    a // {
        , staticUsers2passwords = tagPasswordList a.staticUsers2passwords
    }: AuthenticationT

let tagAuthorization = \(a: Authorization.Type) ->
    a // {
        , usersXgroups       = P.List.map UserXGroup UserXGroupT tagUserXGroup a.usersXgroups
        , actorsXprivileges  = P.List.map ActorXPrivilege ActorXPrivilegeT tagActorXPrivilege a.actorsXprivileges
        , actorsXtargetRules = P.List.map ActorXTargetRule ActorXTargetRuleT tagActorXTargetRule a.actorsXtargetRules
    }: AuthorizationT

let tagRemoteLogfile = \(r: RemoteErrorLogging) ->
    P.JSON.tagNested "contents" "tag" RemoteErrorLogging r
    : RemoteErrorLoggingT

let tagRemoteErrors = \(r: RemoteErrors) ->
    r // {
        , logging = tagRemoteLogfile r.logging
    }: RemoteErrorsT

let tagRemoteStore = \(s: RemoteStore) ->
    P.JSON.tagNested "contents" "tag" RemoteStore s
    : (RemoteStoreT)

let tagDockerDepl = \(d: DockerDepl) ->
    d // {
        , authentication = tagAuthentication d.authentication
        , authorization = tagAuthorization d.authorization
        , moduleLocations = P.Map.map Text BareModule ModuleLocation bareModuleToLocation d.bareModules
        , moduleSCSSDeps = P.Map.map Text BareModule (List ModuleLocation) bareModuleToScssDeps d.bareModules
    }: DockerDeplT

let tagLocalDepl = \(d: LocalDepl) ->
    d // {
        , depl = tagDockerDepl d.depl
    }: LocalDeplT

let tagRemoteDepl = \(d: RemoteDepl) ->
    d // {
        , depl = tagDockerDepl d.depl
        , https = tagHTTPS d.https
        , wwwSubdomain = tagWWWSubdomain d.wwwSubdomain
        , errors = tagRemoteErrors d.errors
        , store = tagRemoteStore d.store
    }: RemoteDeplT

in

{
    , AuthorizationT

    , tagAuthorization
    , tagLocalDepl
    , tagRemoteDepl
    , tagPasswordList
}