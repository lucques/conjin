let P       = https://raw.githubusercontent.com/dhall-lang/dhall-lang/fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package.dhall           -- 2023-03
let Compose = https://raw.githubusercontent.com/lucques/dhall-docker-compose/b43debe297b6fddb451b503824275c4c4d9bda87/compose/v3/package.dhall -- 2023-08


-------------
-- Modules --
-------------

let ModuleLocation = { dirName: Text, isShared: Bool, isExternal: Bool }

let LocalBareModule = { location: { dirName: Text, isExternal: Bool }, scssModuleDeps: List ModuleLocation }
let BareModule = <
    Anchors: {} |
    Db: {} |
    DocExtensions: {} |
    Exercise: {} |
    Favicons: {} |
    Footnotes: {} |
    Grading: {} |
    Html: {} |
    JsStandardLib: {} |
    JsxGraphHelpers: {} |
    LocaleDe: {} |
    Markdown: {} |
    MathArith: {} |
    MathLogic: {} |
    MathValueTable: {} |
    MathjaxExtensions: {} |
    NavBuild: {} |
    NavCommon: {} |
    NavView: {} |
    PrintMode: {} |
    References: {} |
    SolMode: {} |
    Source: {} |
    SqlJsInline: {} |
    SubpagesAll: {} |
    SyncDims: {} |
    TemplateBootstrapped: {} |
    TemplateExam: {} |
    TemplateGeneric: {} |
    TemplateInterbook: {} |
    TemplateNavigable: {} |
    Timetable: {} |
    Title: {} |
    Bootstrap: {} |
    BootstrapIcons: {} |
    EskuelSuite: {} |
    Fullcalendar: {} |
    JsxGraph: {} |
    Mathjax: {} |
    Mathjs: {} |
    Nerdamer: {} |
    PagedJs: {} |
    Parsedown: {} |
    Prism: {} |
    SqlJs: {} |
    LocalBareModule: LocalBareModule
>

let Module = {
    bare: BareModule,
    compileScss: Bool,
    config: P.JSON.Type
}

let bareModuleToLocation = \(id: BareModule) ->
    merge {
        Anchors              = \(t: {}) -> { dirName = "anchors",               isShared = True, isExternal = False },
        Db                   = \(t: {}) -> { dirName = "db",                    isShared = True, isExternal = False },
        DocExtensions        = \(t: {}) -> { dirName = "doc-extensions",        isShared = True, isExternal = False },
        Exercise             = \(t: {}) -> { dirName = "exercise",              isShared = True, isExternal = False },
        Favicons             = \(t: {}) -> { dirName = "favicons",              isShared = True, isExternal = False },
        Footnotes            = \(t: {}) -> { dirName = "footnotes",             isShared = True, isExternal = False },
        Grading              = \(t: {}) -> { dirName = "grading",               isShared = True, isExternal = False },
        Html                 = \(t: {}) -> { dirName = "html",                  isShared = True, isExternal = False },
        JsStandardLib        = \(t: {}) -> { dirName = "js-standard-lib",       isShared = True, isExternal = False },
        JsxGraphHelpers      = \(t: {}) -> { dirName = "jsxgraph-helpers",      isShared = True, isExternal = False },
        LocaleDe             = \(t: {}) -> { dirName = "locale-de",             isShared = True, isExternal = False },
        Markdown             = \(t: {}) -> { dirName = "markdown",              isShared = True, isExternal = False },
        MathArith            = \(t: {}) -> { dirName = "math-arith",            isShared = True, isExternal = False },
        MathLogic            = \(t: {}) -> { dirName = "math-logic",            isShared = True, isExternal = False },
        MathValueTable       = \(t: {}) -> { dirName = "math-value-table",      isShared = True, isExternal = False },
        MathjaxExtensions    = \(t: {}) -> { dirName = "mathjax-extensions",    isShared = True, isExternal = False },
        NavBuild             = \(t: {}) -> { dirName = "nav-build",             isShared = True, isExternal = False },
        NavCommon            = \(t: {}) -> { dirName = "nav-common",            isShared = True, isExternal = False },
        NavView              = \(t: {}) -> { dirName = "nav-view",              isShared = True, isExternal = False },
        PrintMode            = \(t: {}) -> { dirName = "print-mode",            isShared = True, isExternal = False },
        References           = \(t: {}) -> { dirName = "references",            isShared = True, isExternal = False },
        SolMode              = \(t: {}) -> { dirName = "sol-mode",              isShared = True, isExternal = False },
        Source               = \(t: {}) -> { dirName = "source",                isShared = True, isExternal = False },
        SqlJsInline          = \(t: {}) -> { dirName = "sql-js-inline",         isShared = True, isExternal = False },
        SubpagesAll          = \(t: {}) -> { dirName = "subpages-all",          isShared = True, isExternal = False },
        SyncDims             = \(t: {}) -> { dirName = "sync-dims",             isShared = True, isExternal = False },
        TemplateBootstrapped = \(t: {}) -> { dirName = "template-bootstrapped", isShared = True, isExternal = False },
        TemplateExam         = \(t: {}) -> { dirName = "template-exam",         isShared = True, isExternal = False },
        TemplateGeneric      = \(t: {}) -> { dirName = "template-generic",      isShared = True, isExternal = False },
        TemplateInterbook    = \(t: {}) -> { dirName = "template-interbook",    isShared = True, isExternal = False },
        TemplateNavigable    = \(t: {}) -> { dirName = "template-navigable",    isShared = True, isExternal = False },
        Timetable            = \(t: {}) -> { dirName = "timetable",             isShared = True, isExternal = False },
        Title                = \(t: {}) -> { dirName = "title",                 isShared = True, isExternal = False },
        Bootstrap            = \(t: {}) -> { dirName = "bootstrap",             isShared = True, isExternal = True },
        BootstrapIcons       = \(t: {}) -> { dirName = "bootstrap-icons",       isShared = True, isExternal = True },
        EskuelSuite          = \(t: {}) -> { dirName = "eskuel-suite",          isShared = True, isExternal = True },
        Fullcalendar         = \(t: {}) -> { dirName = "fullcalendar",          isShared = True, isExternal = True },
        Mathjax              = \(t: {}) -> { dirName = "mathjax",               isShared = True, isExternal = True },
        Mathjs               = \(t: {}) -> { dirName = "mathjs",                isShared = True, isExternal = True },
        Nerdamer             = \(t: {}) -> { dirName = "nerdamer",              isShared = True, isExternal = True },
        JsxGraph             = \(t: {}) -> { dirName = "jsxgraph",              isShared = True, isExternal = True },
        PagedJs              = \(t: {}) -> { dirName = "paged-js",              isShared = True, isExternal = True },
        Parsedown            = \(t: {}) -> { dirName = "parsedown",             isShared = True, isExternal = True },
        Prism                = \(t: {}) -> { dirName = "prism",                 isShared = True, isExternal = True },
        SqlJs                = \(t: {}) -> { dirName = "sql-js",                isShared = True, isExternal = True },
        LocalBareModule      = \(localBareModule: LocalBareModule) -> {
            , dirName = localBareModule.location.dirName
            , isShared = False
            , isExternal = localBareModule.location.isExternal
        }
    } id
: ModuleLocation

let bareModuleToScssDeps = \(id: BareModule) ->
    merge {
        Anchors              = \(t: {}) -> P.List.empty ModuleLocation,
        Db                   = \(t: {}) -> P.List.empty ModuleLocation,
        DocExtensions        = \(t: {}) -> P.List.empty ModuleLocation,
        Exercise             = \(t: {}) -> P.List.empty ModuleLocation,
        Favicons             = \(t: {}) -> P.List.empty ModuleLocation,
        Footnotes            = \(t: {}) -> P.List.empty ModuleLocation,
        Grading              = \(t: {}) -> P.List.empty ModuleLocation,
        Html                 = \(t: {}) -> P.List.empty ModuleLocation,
        JsStandardLib        = \(t: {}) -> P.List.empty ModuleLocation,
        JsxGraphHelpers      = \(t: {}) -> P.List.empty ModuleLocation,
        LocaleDe             = \(t: {}) -> P.List.empty ModuleLocation,
        Markdown             = \(t: {}) -> P.List.empty ModuleLocation,
        MathArith            = \(t: {}) -> P.List.empty ModuleLocation,
        MathLogic            = \(t: {}) -> P.List.empty ModuleLocation,
        MathValueTable       = \(t: {}) -> P.List.empty ModuleLocation,
        MathjaxExtensions    = \(t: {}) -> P.List.empty ModuleLocation,
        NavBuild             = \(t: {}) -> P.List.empty ModuleLocation,
        NavCommon            = \(t: {}) -> P.List.empty ModuleLocation,
        NavView              = \(t: {}) -> P.List.empty ModuleLocation,
        PrintMode            = \(t: {}) -> P.List.empty ModuleLocation,
        References           = \(t: {}) -> P.List.empty ModuleLocation,
        SolMode              = \(t: {}) -> P.List.empty ModuleLocation,
        Source               = \(t: {}) -> P.List.empty ModuleLocation,
        SqlJsInline          = \(t: {}) -> P.List.empty ModuleLocation,
        SubpagesAll          = \(t: {}) -> P.List.empty ModuleLocation,
        SyncDims             = \(t: {}) -> P.List.empty ModuleLocation,
        TemplateBootstrapped = \(t: {}) -> [
                                    , bareModuleToLocation (BareModule.Bootstrap {=})
                                ],
        TemplateExam         = \(t: {}) -> [
                                    , bareModuleToLocation (BareModule.Bootstrap {=})
                                    , bareModuleToLocation (BareModule.TemplateBootstrapped {=})
                                    , bareModuleToLocation (BareModule.TemplateNavigable {=})
                                ],
        TemplateGeneric      = \(t: {}) -> P.List.empty ModuleLocation,
        TemplateInterbook    = \(t: {}) -> [
                                    , bareModuleToLocation (BareModule.Bootstrap {=})
                                    , bareModuleToLocation (BareModule.TemplateBootstrapped {=})
                                    , bareModuleToLocation (BareModule.TemplateNavigable {=})
                                ],
        TemplateNavigable    = \(t: {}) -> [
                                    , bareModuleToLocation (BareModule.Bootstrap {=})
                                ],
        Timetable            = \(t: {}) -> P.List.empty ModuleLocation,
        Title                = \(t: {}) -> P.List.empty ModuleLocation,
        Bootstrap            = \(t: {}) -> P.List.empty ModuleLocation,
        BootstrapIcons       = \(t: {}) -> P.List.empty ModuleLocation,
        EskuelSuite          = \(t: {}) -> P.List.empty ModuleLocation,
        Fullcalendar         = \(t: {}) -> P.List.empty ModuleLocation,
        Mathjax              = \(t: {}) -> P.List.empty ModuleLocation,
        Mathjs               = \(t: {}) -> P.List.empty ModuleLocation,
        Nerdamer             = \(t: {}) -> P.List.empty ModuleLocation,
        JsxGraph             = \(t: {}) -> P.List.empty ModuleLocation,
        PagedJs              = \(t: {}) -> P.List.empty ModuleLocation,
        Parsedown            = \(t: {}) -> P.List.empty ModuleLocation,
        Prism                = \(t: {}) -> P.List.empty ModuleLocation,
        SqlJs                = \(t: {}) -> P.List.empty ModuleLocation,
        LocalBareModule      = \(localBareModule: LocalBareModule) -> localBareModule.scssModuleDeps
    } id
: List ModuleLocation

let sharedBareModules = [
    , BareModule.Anchors {=}
    , BareModule.Db {=}
    , BareModule.DocExtensions {=}
    , BareModule.Exercise {=}
    , BareModule.Favicons {=}
    , BareModule.Footnotes {=}
    , BareModule.Grading {=}
    , BareModule.Html {=}
    , BareModule.JsStandardLib {=}
    , BareModule.JsxGraphHelpers {=}
    , BareModule.LocaleDe {=}
    , BareModule.Markdown {=}
    , BareModule.MathArith {=}
    , BareModule.MathLogic {=}
    , BareModule.MathValueTable {=}
    , BareModule.MathjaxExtensions {=}
    , BareModule.NavBuild {=}
    , BareModule.NavCommon {=}
    , BareModule.NavView {=}
    , BareModule.PrintMode {=}
    , BareModule.References {=}
    , BareModule.SolMode {=}
    , BareModule.Source {=}
    , BareModule.SqlJsInline {=}
    , BareModule.SubpagesAll {=}
    , BareModule.SyncDims {=}
    , BareModule.TemplateBootstrapped {=}
    , BareModule.TemplateExam {=}
    , BareModule.TemplateGeneric {=}
    , BareModule.TemplateInterbook {=}
    , BareModule.TemplateNavigable {=}
    , BareModule.Timetable {=}
    , BareModule.Title {=}
    , BareModule.Bootstrap {=}
    , BareModule.BootstrapIcons {=}
    , BareModule.EskuelSuite {=}
    , BareModule.Fullcalendar {=}
    , BareModule.Mathjax {=}
    , BareModule.Mathjs {=}
    , BareModule.Nerdamer {=}
    , BareModule.JsxGraph {=}
    , BareModule.PagedJs {=}
    , BareModule.Parsedown {=}
    , BareModule.Prism {=}
    , BareModule.SqlJs {=}
]
: List BareModule


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

let PasswordList     = P.Map.Type Text Password

let Authentication = {
    , loginWithoutUserName: Bool
    , users2passwords:      PasswordList
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
    , authentication       : Authentication
    , authorization        : Authorization
    , bareModules          : P.Map.Type Text BareModule
    , modules              : P.Map.Type Text Module
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
    , pathBase            : Text   -- e.g. `/htdocs/my-app` base/root diretory in the filesystem
    , urlBase             : Text   -- e.g. `/my-app/`       corresponds to URLs with prefix `https://my-domain.org/my-app/`
    , preferHTTPS         : Bool
    , forceHTTPS          : Bool
    , activateCompression : Bool
    , rcloneRemote        : RCloneRemote
    , db                  : Optional ServerDb
}

let Depl = < DockerNginx: DockerNginxDepl | DockerSync: DockerSyncDepl >


------------------------
-- `config.json` file --
------------------------

-- Format of the output `config.json` file
let ConfigJsonFile = {
    , path_base: Text
    , path_preprocess: Text
    , url_base: Text
    , authentication: AuthenticationWithoutPasswords
    , authorization: Authorization
    , module_2_location: P.Map.Type Text ModuleLocation
    , module_2_config: P.Map.Type Text P.JSON.Type
}


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
-- cannot be converted by `json-to-dall` such that the tag is preserved. See
-- also https://github.com/dhall-lang/dhall-haskell/issues/1383
-- This problem should not be dealt with in Dhall, but in the translation
-- process from Dhall to JSON. However, such a cmd-line argument is not
-- available yet, so we stick with this workaround for now.

-- 2. Derived values
-- The second reason for tagging is that some values are derivable via function
-- calls. Since Dhall functions are not present in the JSON output, those values
-- are explicitly added.

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

let PassowrdListT       = P.Map.Type Text PasswordT

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
    , name                 : Text
    , dockerProjName       : Text
    , conjinDir            : Text
    , appDir               : Text
    , targetDir            : Text
    , authentication       : AuthenticationT
    , authorization        : AuthorizationT

    , bareModules          : P.Map.Type Text BareModule
    , moduleLocations      : P.Map.Type Text ModuleLocation        -- Enriched
    , moduleSCSSDeps       : P.Map.Type Text (List ModuleLocation) -- Enriched

    , modules              : P.Map.Type Text Module
    , desktopIntegration   : DesktopIntegration
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
    , pathBase: Text
    , urlBase: Text
    , preferHTTPS: Bool
    , forceHTTPS: Bool
    , activateCompression: Bool
    , rcloneRemote: RCloneRemote
    , db: Optional ServerDb
}

let ConfigJsonFileT = {
    , path_base: Text
    , path_preprocess: Text
    , url_base: Text
    , authentication: AuthenticationWithoutPasswords
    , authorization: AuthorizationT
    , module_2_location: P.Map.Type Text ModuleLocation
    , module_2_config: P.Map.Type Text P.JSON.Type
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

let tagPasswordList = \(pl: PasswordList) ->
    P.Map.map Text Password PasswordT tagPassword pl
    : PassowrdListT

let tagAuthentication = \(a: Authentication) ->
    a // {
        , users2passwords = tagPasswordList a.users2passwords
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
        , moduleLocations = P.Map.map Text BareModule ModuleLocation bareModuleToLocation d.bareModules
        , moduleSCSSDeps = P.Map.map Text BareModule (List ModuleLocation) bareModuleToScssDeps d.bareModules
    }: DockerDeplT

let tagDockerNginxDepl = \(d: DockerNginxDepl) ->
    d // {
        , depl = tagDockerDepl d.depl
    }: DockerNginxDeplT

let tagDockerSyncDepl = \(d: DockerSyncDepl) ->
    d // {
        , depl = tagDockerDepl d.depl
    }: DockerSyncDeplT

let tagConfigJsonFile = \(c: ConfigJsonFile) ->
    c // {
        authorization = tagAuthorization c.authorization
    }: ConfigJsonFileT

in

{ ModuleLocation, LocalBareModule, BareModule, Module, sharedBareModules, bareModuleToLocation, bareModuleToScssDeps,
  Authentication, AuthenticationWithoutPasswords, Authorization, DockerDb, ServerDb, RCloneRemote, DockerDepl, DesktopIntegration,
  DockerNginxDepl, DockerSyncDepl, Depl,
  Password, Actor, Action, TargetPrivilege, TargetRule, Privilege, User2Group, Actor2Privilege, Actor2TargetRule, PasswordList,
  ConfigJsonFileT,
  tagDockerNginxDepl, tagDockerSyncDepl, tagPasswordList, tagConfigJsonFile }