let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/b43debe297b6fddb451b503824275c4c4d9bda87/compose/v3/package.dhall -- 2023-08


-------------
-- Modules --
-------------

let FlatConfig = P.Map.Type Text Text

let ModuleId = { name: Text, isShared: Bool, isExternal: Bool }
let Module   = { id: ModuleId,
                 compileScss: Bool,
                 scssModuleDeps: List ModuleId,
                 defaultConfig: P.JSON.Type }


----------
-- Auth --
----------

let Group     = < UserBased: Text | Custom: Text >
let Action    = < View: {} | Custom: Text >
let Privilege = < Preprocess: {} | LoginLogout: {} | Target: { targetIds: List Text, action: Action, inherit: Bool } >

let User2Group      = { user: Text, group: Group }
let Group2Privilege = { group: Group, privilege: Privilege }

let AppAuth = {
    , rootUser:  Text
    , guestUser: Text
    , loginWithoutUserName: Bool
    , loginTemplate: Module
-- Generate hashes like this: `php -r 'print(password_hash("the_password", PASSWORD_DEFAULT));'`
    , users2passwordHashes: P.Map.Type Text Text
    , users2groups:         List User2Group
    , groups2privileges:    List Group2Privilege
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
    , modules              : List Module
    , faviconIcoFrom       : Optional ModuleId
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
    , depl               : DockerDepl
    , host               : Text
    , preferHTTPS        : Bool
    , forceHTTPS         : Bool
    , rcloneRemote       : RCloneRemote
    , db                 : Optional ServerDb
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
    , modules_default_config: P.Map.Type Text P.JSON.Type
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

let GroupT             = P.JSON.Tagged Group
let ActionT            = P.JSON.Tagged Action
let PrivilegeTUntagged = < Preprocess: {} | LoginLogout: {} | Target: { targetIds: List Text, action: ActionT, inherit: Bool } >
let PrivilegeT         = P.JSON.Tagged PrivilegeTUntagged

let User2GroupT        = { user: Text, group: GroupT }
let Group2PrivilegeT   = { group: GroupT, privilege: PrivilegeT }

let AppAuthT = {
    , rootUser:  Text
    , guestUser: Text
    , loginWithoutUserName: Bool
    , loginTemplate: Module
    , users2passwordHashes: P.Map.Type Text Text
    , users2groups:         List User2GroupT
    , groups2privileges:    List Group2PrivilegeT
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
    , modules_default_config: P.Map.Type Text P.JSON.Type
}

let tagGroup = \(g: Group) -> P.JSON.tagNested "contents" "tag" Group g
    : GroupT

let tagAction = \(a: Action) -> P.JSON.tagNested "contents" "tag" Action a
    : ActionT

let tagPrivilege = \(p: Privilege) ->
    P.JSON.tagNested "contents" "tag" PrivilegeTUntagged
    (merge {
        , Preprocess = \(t: {}) -> PrivilegeTUntagged.Preprocess {=}
        , LoginLogout = \(t: {}) -> PrivilegeTUntagged.LoginLogout {=}
        , Target = \(t: {targetIds: List Text, action: Action, inherit: Bool}) -> PrivilegeTUntagged.Target { targetIds = t.targetIds, action = tagAction t.action, inherit = t.inherit }
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

let tagConfigJsonFile =
    \(c: ConfigJsonFile) ->
    c // {
        auth = (P.Optional.map AppAuth AppAuthT
            (\(a: AppAuth) ->
                a // {
                    , users2groups      = P.List.map User2Group User2GroupT tagUser2Group a.users2groups
                    , groups2privileges = P.List.map Group2Privilege Group2PrivilegeT tagGroup2Privilege a.groups2privileges
                })
            c.auth)
    }: ConfigJsonFileT

in

{ FlatConfig, ModuleId, Module, AppAuth, Auth, DockerDb, ServerDb, RCloneRemote, DockerDepl,
  DockerNginxDepl, DockerSyncDepl,
  ConfigJsonFile, ConfigJsonFileT, tagConfigJsonFile,
  Group, Action, Privilege, User2Group, Group2Privilege }