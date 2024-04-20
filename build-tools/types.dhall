let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/b43debe297b6fddb451b503824275c4c4d9bda87/compose/v3/package.dhall -- 2023-08


-------------
-- Modules --
-------------

let FlatConfig = P.Map.Type Text Text

let ModuleLocation = { dirName: Text, isShared: Bool, isExternal: Bool }
let Module = { location: ModuleLocation,
               compileScss: Bool,
               scssModuleDeps: List ModuleLocation,
               defaultConfig: P.JSON.Type }


----------
-- Auth --
----------

let Group            = < UserBased: Text | Custom: Text >
let Action           = < View: {} | Custom: Text >
let TargetPrivilege  = { targetIds: List Text, action: Action }
let TargetRule       = < Allow: TargetPrivilege | Deny: TargetPrivilege >
let Privilege        = < Debug: {} | Preprocess: {} | LoginLogout: {} | Target: TargetPrivilege >

let User2Group       = { user: Text, group: Group }
let Group2Privilege  = { group: Group, privilege: Privilege }
let Group2TargetRule = { group: Group, rule: TargetRule }

let AppAuth = {
    , rootUser:  Text
    , guestUser: Text
    , loginWithoutUserName: Bool
    , loginTemplate: Module
    -- Generate hashes like this: `php -r 'print(password_hash("the_password", PASSWORD_DEFAULT));'`
    , users2passwordHashes: P.Map.Type Text Text
    , users2groups:         List User2Group
    , groups2privileges:    List Group2Privilege
    , groups2targetRules:   List Group2TargetRule
}

let Auth = {
    , app                         : AppAuth
    , preprocessScriptUser        : Text
    , preprocessScriptPasswordCmd : Text
}


----------------
-- Deployment --
----------------

-- Connection to a database started locally by Docker
let DockerDb = { 
    , user                : Text
    , userPassword        : Text
    , rootPassword        : Text
    , volDir              : Optional Text  -- if `None`, then no db storage
    , initFilesDir        : Optional Text  -- if `None`, then no init files
}

-- Connection to a database on the server
let ServerDb = {
    , user                 : Text
    , password             : Text
}

let RCloneRemote = {
    , name                 : Text
    , dir                  : Text
    , configPath           : Text
}

-- General Docker deployment (base config for other deployments)
let DockerDepl = {
    , name                 : Text
    , dockerProjName       : Text
    , conjinDir            : Text
    , appDir               : Text
    , targetDir            : Text
    , auth                 : Optional Auth
    , modules              : P.Map.Type Text Module
    , faviconIcoFrom       : Optional ModuleLocation
    , notFoundTemplate     : Module
    , unauthorizedTemplate : Module
}

-- Local depl with Docker and nginx
let DockerNginxDepl = {
    , depl                 : DockerDepl
    , nginxVirtualHost     : Text
    , linkcheckerVolDir    : Text
    , preprocessVolDir     : Text
    , db                   : Optional DockerDb
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
    , auth: Optional AppAuth
    , modules: P.Map.Type Text Module
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

let GroupT              = P.JSON.Tagged Group
let ActionT             = P.JSON.Tagged Action
let TargetPrivilegeT    = { targetIds: List Text, action: ActionT }
let TargetRuleTUntagged = < Allow: TargetPrivilegeT | Deny: TargetPrivilegeT >
let TargetRuleT         = P.JSON.Tagged TargetRuleTUntagged
let PrivilegeTUntagged  = < Debug: {} | Preprocess: {} | LoginLogout: {} | Target: TargetPrivilegeT >
let PrivilegeT         = P.JSON.Tagged PrivilegeTUntagged

let User2GroupT        = { user: Text, group: GroupT }
let Group2PrivilegeT   = { group: GroupT, privilege: PrivilegeT }
let Group2TargetRuleT  = { group: GroupT, rule: TargetRuleT }

let AppAuthT = {
    , rootUser:  Text
    , guestUser: Text
    , loginWithoutUserName: Bool
    , loginTemplate: Module
    , users2passwordHashes: P.Map.Type Text Text
    , users2groups:         List User2GroupT
    , groups2privileges:    List Group2PrivilegeT
    , groups2targetRules:   List Group2TargetRuleT
}

let AuthT = {
    , app                         : AppAuthT
    , preprocessScriptUser        : Text
    , preprocessScriptPasswordCmd : Text
}

let ConfigJsonFileT = {
    , path_root: Text
    , path_preprocess: Text
    , url_root: Text
    , auth: Optional AppAuthT
    , modules: P.Map.Type Text Module
}

let tagGroup = \(g: Group) -> P.JSON.tagNested "contents" "tag" Group g
    : GroupT

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

let tagUser2Group = \(u2g: User2Group) ->
    u2g // {
        , group = tagGroup u2g.group
    }: User2GroupT

let tagGroup2Privilege = \(g2p: Group2Privilege) ->
    g2p // {
        , group = tagGroup g2p.group
        , privilege = tagPrivilege g2p.privilege
    }: Group2PrivilegeT

let tagGroup2TargetRule = \(g2tr: Group2TargetRule) ->
    g2tr // {
        , group = tagGroup g2tr.group
        , rule = tagTargetRule g2tr.rule
    }: Group2TargetRuleT

let tagConfigJsonFile =
    \(c: ConfigJsonFile) ->
    c // {
        auth = (P.Optional.map AppAuth AppAuthT
            (\(a: AppAuth) ->
                a // {
                    , users2groups      = P.List.map User2Group User2GroupT tagUser2Group a.users2groups
                    , groups2privileges = P.List.map Group2Privilege Group2PrivilegeT tagGroup2Privilege a.groups2privileges
                    , groups2targetRules = P.List.map Group2TargetRule Group2TargetRuleT tagGroup2TargetRule a.groups2targetRules
                })
            c.auth)
    }: ConfigJsonFileT

in

{ FlatConfig, ModuleLocation, Module, AppAuth, Auth, DockerDb, ServerDb, RCloneRemote, DockerDepl,
  DockerNginxDepl, DockerSyncDepl,
  ConfigJsonFile, ConfigJsonFileT, tagConfigJsonFile,
  Group, Action, TargetPrivilege, TargetRule, Privilege, User2Group, Group2Privilege, Group2TargetRule }