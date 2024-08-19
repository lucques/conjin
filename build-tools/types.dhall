let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/b43debe297b6fddb451b503824275c4c4d9bda87/compose/v3/package.dhall -- 2023-08


-------------
-- Modules --
-------------

let ModuleLocation = { dirName: Text, isShared: Bool, isExternal: Bool }
let ModuleValue    = { location: ModuleLocation,
                       config: P.JSON.Type,
                       compileScss: Bool,
                       scssModuleDeps: List ModuleLocation }

let Module = { name: Text, config: P.JSON.Type }

----------
-- Auth --
----------

let Password         = < Hash: Text | Plain: Text >
let Actor            = < User: Text | Group: Text >
let Action           = < View: {} | Custom: Text >
let TargetPrivilege  = { targetIds: List Text, action: Action }
let TargetRule       = < Allow: TargetPrivilege | Deny: TargetPrivilege >
let Privilege        = < Debug: {} | Preprocess: {} | LoginLogout: {} | Target: TargetPrivilege >

let User2Group       = { user: Text, group: Text }
let Actor2Privilege  = { actor: Actor, privilege: Privilege }
let Actor2TargetRule = { actor: Actor, rule: TargetRule }

let Authentication = {
    , loginWithoutUserName: Bool
    , users2passwords:      P.Map.Type Text Password
}

let AuthenticationWithoutPasswords = {
    , loginWithoutUserName: Bool
}

let Authorization = {
    , rootUser:             Text
    , guestUser:            Text
    , users2groups:         List User2Group
    , actors2privileges:    List Actor2Privilege
    , actors2targetRules:   List Actor2TargetRule
}


----------------
-- Deployment --
----------------

-- Connection to a database started locally by Docker
let DockerDb = { 
    , user                 : Text
    , userPassword         : Text
    , rootPassword         : Text
    , volDir               : Optional Text  -- if `None`, then no db storage
    , initFilesDir         : Optional Text  -- if `None`, then no init files
}

-- Connection to a database on the server
let ServerDb = {
    , host                 : Text
    , user                 : Text
    , password             : Text
}

let RCloneRemote = {
    , name                 : Text
    , dir                  : Text
    , configPath           : Text
}

 -- If `*PasswordInitCmd` is `Some`, then the password must have been configured
 -- in plaintext. That password is then fed into StdIn of that command which
 -- should register the password.
 
let DesktopIntegration = {
    , installSymlinksInLocalBin           : Bool
    , preprocessScriptUser                : Text
    , preprocessScriptPasswordLookupCmd   : Text
    , preprocessScriptPasswordRegisterCmd : Optional Text
}

-- General Docker deployment (base config for other deployments)
let DockerDepl = {
    , name                 : Text
    , dockerProjName       : Text
    , conjinDir            : Text
    , appDir               : Text
    , targetDir            : Text
    , authentication       : Authentication
    , authorization        : Authorization
    , modules              : P.Map.Type Text ModuleValue
    , desktopIntegration   : DesktopIntegration
}

-- Local depl with Docker and nginx
let DockerNginxDepl = {
    , depl                           : DockerDepl
    , nginxVirtualHost               : Text
    , linkcheckerVolDir              : Text
    , preprocessVolDir               : Text
    , db                             : Optional DockerDb
    , linkcheckerUser                : Text
    , linkcheckerPasswordLookupCmd   : Text
    , linkcheckerPasswordRegisterCmd : Optional Text
}

-- External depl with docker and rclone
let DockerSyncDepl = {
    , depl                : DockerDepl
    , host                : Text
    , preferHTTPS         : Bool
    , forceHTTPS          : Bool
    , activateCompression : Bool
    , rcloneRemote        : RCloneRemote
    , db                  : Optional ServerDb
}


------------------------
-- `config.json` file --
------------------------

-- Format of the output `config.json` file
let ConfigJsonFile = {
    , path_root: Text
    , path_preprocess: Text
    , url_root: Text
    , authentication: AuthenticationWithoutPasswords
    , authorization: Authorization
    , modules: P.Map.Type Text ModuleValue
}


---------
-- Fix --
---------

-- The following lines of code are only present because union types cannot be
-- converted by `json-to-dall` such that the tag is preserved. See also
-- https://github.com/dhall-lang/dhall-haskell/issues/1383
-- This problem should not be dealt with in Dhall, but in the translation
-- process from Dhall to JSON. However, such a cmd-line argument is not
-- available yet, so we stick with this workaround for now.

let PasswordT           = P.JSON.Tagged Password
let ActorT              = P.JSON.Tagged Actor
let ActionT             = P.JSON.Tagged Action
let TargetPrivilegeT    = { targetIds: List Text, action: ActionT }
let TargetRuleTUntagged = < Allow: TargetPrivilegeT | Deny: TargetPrivilegeT >
let TargetRuleT         = P.JSON.Tagged TargetRuleTUntagged
let PrivilegeTUntagged  = < Debug: {} | Preprocess: {} | LoginLogout: {} | Target: TargetPrivilegeT >
let PrivilegeT          = P.JSON.Tagged PrivilegeTUntagged

let Actor2PrivilegeT    = { actor: ActorT, privilege: PrivilegeT }
let Actor2TargetRuleT   = { actor: ActorT, rule: TargetRuleT }

let AuthenticationT = {
    , loginWithoutUserName: Bool
    , users2passwords:      P.Map.Type Text PasswordT
}

let AuthorizationT = {
    , rootUser:  Text
    , guestUser: Text
    , users2groups:         List User2Group
    , actors2privileges:    List Actor2PrivilegeT
    , actors2targetRules:   List Actor2TargetRuleT
}

let DockerDeplT = {
    , name: Text
    , dockerProjName: Text
    , conjinDir: Text
    , appDir: Text
    , targetDir: Text
    , authentication: AuthenticationT
    , authorization: AuthorizationT
    , modules: P.Map.Type Text ModuleValue
    , desktopIntegration: DesktopIntegration
}

let DockerNginxDeplT = {
    , depl: DockerDeplT
    , nginxVirtualHost: Text
    , linkcheckerVolDir: Text
    , preprocessVolDir: Text
    , db: Optional DockerDb
    , linkcheckerUser: Text
    , linkcheckerPasswordLookupCmd: Text
    , linkcheckerPasswordRegisterCmd: Optional Text
}

let DockerSyncDeplT = {
    , depl: DockerDeplT
    , host: Text
    , preferHTTPS: Bool
    , forceHTTPS: Bool
    , activateCompression: Bool
    , rcloneRemote: RCloneRemote
    , db: Optional ServerDb
}

let ConfigJsonFileT = {
    , path_root: Text
    , path_preprocess: Text
    , url_root: Text
    , authentication: AuthenticationWithoutPasswords
    , authorization: AuthorizationT
    , modules: P.Map.Type Text ModuleValue
}

let tagPassword = \(p: Password) -> P.JSON.tagNested "contents" "tag" Password p
    : PasswordT

let tagActor = \(g: Actor) -> P.JSON.tagNested "contents" "tag" Actor g
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

let tagActor2Privilege = \(a2p: Actor2Privilege) ->
    a2p // {
        , actor = tagActor a2p.actor
        , privilege = tagPrivilege a2p.privilege
    }: Actor2PrivilegeT

let tagActor2TargetRule = \(a2tr: Actor2TargetRule) ->
    a2tr // {
        , actor = tagActor a2tr.actor
        , rule = tagTargetRule a2tr.rule
    }: Actor2TargetRuleT

let tagAuthentication = \(a: Authentication) ->
    a // {
        , users2passwords = P.Map.map Text Password PasswordT tagPassword a.users2passwords
    }: AuthenticationT

let tagAuthorization = \(a: Authorization) ->
    a // {
        , actors2privileges  = P.List.map Actor2Privilege Actor2PrivilegeT tagActor2Privilege a.actors2privileges
        , actors2targetRules = P.List.map Actor2TargetRule Actor2TargetRuleT tagActor2TargetRule a.actors2targetRules
    }: AuthorizationT

let tagDockerDepl = \(d: DockerDepl) ->
    d // {
        , authentication = tagAuthentication d.authentication
        , authorization = tagAuthorization d.authorization
    }: DockerDeplT

let tagDockerNginxDepl = \(d: DockerNginxDepl) ->
    d // {
        , depl = tagDockerDepl d.depl
    }: DockerNginxDeplT

let tagDockerSyncDepl = \(d: DockerSyncDepl) ->
    d // {
        , depl = tagDockerDepl d.depl
    }: DockerSyncDeplT

let tagConfigJsonFile =
    \(c: ConfigJsonFile) ->
    c // {
        authorization = tagAuthorization c.authorization
    }: ConfigJsonFileT

in

{ ModuleLocation, ModuleValue, Module, Authentication, AuthenticationWithoutPasswords, Authorization, DockerDb, ServerDb, RCloneRemote, DockerDepl, DesktopIntegration,
  DockerNginxDepl, DockerSyncDepl,
  ConfigJsonFile, ConfigJsonFileT,
  Password, Actor, Action, TargetPrivilege, TargetRule, Privilege, User2Group, Actor2Privilege, Actor2TargetRule,
  tagDockerNginxDepl, tagDockerSyncDepl, tagConfigJsonFile, }