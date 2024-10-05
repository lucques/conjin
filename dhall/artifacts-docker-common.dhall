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


----------------------
-- Volumes and dirs --
----------------------

let makeVol =
    \(source: Text) ->
    \(target: Text) ->
    Compose.ServiceVolume.Long Compose.ServiceVolumeLong::{
        , type   = Some "bind"
        , source = Some source
        , target = Some target
    }
: Compose.ServiceVolume

let makeReadonlyVol = 
    \(source: Text) ->
    \(target: Text) ->
    Compose.ServiceVolume.Long Compose.ServiceVolumeLong::{
    , type      = Some "bind"
    , source    = Some source
    , target    = Some target
    , read_only = Some True
    }
: Compose.ServiceVolume

let makeModuleSourceDir =
    \(conjinDir:   Text) ->
    \(appDir:      Text) ->
    \(m: T.ModuleLocation) ->
        if      m.isShared && m.isExternal == False          then conjinDir  ++ "/src/modules-shared/" ++ m.dirName
        else if m.isShared && m.isExternal                   then conjinDir  ++ "/ext/modules-shared/" ++ m.dirName
        else if m.isShared == False && m.isExternal == False then appDir     ++ "/src/modules/"        ++ m.dirName
        else                                                      appDir     ++ "/ext/modules/"        ++ m.dirName
: Text

let makeModuleDir =
    \(pathPrefix: Text) ->
    \(dirSuffix: Optional Text) ->
    \(m: T.ModuleLocation) ->
        let suf = merge { None = "", Some = \(d: Text) -> "-" ++ d } dirSuffix in
        if      m.isShared && m.isExternal == False          then pathPrefix ++ "/modules-shared"     ++ suf ++ "/" ++ m.dirName
        else if m.isShared && m.isExternal                   then pathPrefix ++ "/modules-shared-ext" ++ suf ++ "/" ++ m.dirName
        else if m.isShared == False && m.isExternal == False then pathPrefix ++ "/modules"            ++ suf ++ "/" ++ m.dirName
        else                                                      pathPrefix ++ "/modules-ext"        ++ suf ++ "/" ++ m.dirName
: Text

let makeModuleCssDir =
    \(cssVolDir: Text) ->
    \(m: T.ModuleLocation) ->
    (makeModuleDir cssVolDir (Some "css") m)
: Text

let moduleToString =
    \(m: T.Module) -> (T.bareModuleToLocation m.bare).dirName ++ (if (T.bareModuleToLocation m.bare).isShared then "-shared" else "-local")
: Text

let htdocsDir = "/files/htdocs"

-- These volumes constitute the `htdocs` dir, with the optional exception of:
-- - `htdocs/preprocess`
-- - `htdocs/store`
-- For local nginx deployments? False (the dirs are hooked in outside of htdocs)
-- For sync deployments?        True  (the dirs are placed inside htdocs)
let makeHtdocsVols =
    \(targetDir:         Text) ->
    \(appDir:            Text) ->
    \(cssVolDir:         Text) ->
    \(conjinDir:         Text) ->
    \(bareModules:       P.Map.Type Text T.BareModule) ->
    \(modules:           P.Map.Type Text T.Module) ->
    \(staticFiles:       P.Map.Type Text Text) ->
    \(withErrorLogDir:   Bool) ->
    \(withPreprocessDir: Bool) ->
    \(withStoreDir:      Bool) ->

    let makeModuleVol =
        \(m: T.BareModule) ->
        let location = T.bareModuleToLocation m in
        makeReadonlyVol (makeModuleSourceDir conjinDir appDir location) (makeModuleDir htdocsDir (None Text) location)

    let makeModuleCssVol =
        \(m: T.Module) ->
        let location = T.bareModuleToLocation m.bare in
        if m.compileScss
            then Some (makeReadonlyVol (makeModuleCssDir cssVolDir location) (makeModuleDir htdocsDir (Some "css") location))
            else None Compose.ServiceVolume

    let makeStaticFileVol =
        \(src: Text) ->
        \(dst: Text) ->
        makeReadonlyVol (appDir ++ "/src/static/" ++ src) (htdocsDir ++ "/" ++ dst)

    let errorLogVol =
        if withErrorLogDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/logs/error") (htdocsDir ++ "/logs/error"))
        else None Compose.ServiceVolume

    let preprocessVol =
        if withPreprocessDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/preprocess") (htdocsDir ++ "/preprocess"))
        else None Compose.ServiceVolume
    
    let storeVol =
        if withStoreDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/store") (htdocsDir ++ "/store"))
        else None Compose.ServiceVolume

    in
    [
        , makeReadonlyVol (appDir    ++ "/src/content")             (htdocsDir ++ "/content")
        , makeReadonlyVol (appDir    ++ "/src/system")              (htdocsDir ++ "/system")

        , makeReadonlyVol (conjinDir ++ "/src/conjin")              (htdocsDir ++ "/conjin")

        , makeReadonlyVol (conjinDir ++ "/vendor")                  (htdocsDir ++ "/vendor")

        , makeReadonlyVol (targetDir ++ "/htdocs/.htaccess")        (htdocsDir ++ "/.htaccess")
        , makeReadonlyVol (targetDir ++ "/htdocs/config.json")      (htdocsDir ++ "/config.json")
        , makeReadonlyVol (targetDir ++ "/htdocs/users.json")       (htdocsDir ++ "/users.json")
    ]
    #
    map T.BareModule Compose.ServiceVolume makeModuleVol (P.Map.values Text T.BareModule bareModules)
    #
    unpackOptionals Compose.ServiceVolume (map T.Module (Optional Compose.ServiceVolume) makeModuleCssVol (P.Map.values Text T.Module modules))
    #
    map (P.Map.Entry Text Text) Compose.ServiceVolume (\(e: P.Map.Entry Text Text) -> makeStaticFileVol e.mapKey e.mapValue) staticFiles
    #
    unpackOptionals Compose.ServiceVolume [errorLogVol, preprocessVol, storeVol]
: List Compose.ServiceVolume


let makeTemplateSassCompilation = 
    \(cssVolDir: Text) ->
    \(conjinDir: Text) ->
    \(appDir:    Text) ->
    \(template:  T.Module) ->

    let location = T.bareModuleToLocation template.bare
    let scssDeps = T.bareModuleToScssDeps template.bare

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir location ++ "/scss") "/sass"
    let moduleCssVol  = makeVol         (makeModuleCssDir cssVolDir location)                      "/css"

    let makeModuleDependencyVol = 
        \(m: T.ModuleLocation) -> makeReadonlyVol (makeModuleSourceDir conjinDir appDir m ++ "/scss") (makeModuleDir   "" (None Text) m ++ "/scss")

    let volumes = [moduleSassVol, moduleCssVol] #
                  (map T.ModuleLocation Compose.ServiceVolume makeModuleDependencyVol scssDeps)
    in

    Compose.Service::{
        , image   = Some "michalklempa/dart-sass"
        , volumes = Some volumes
        , command = Some (Compose.StringOrList.List ([
            "/opt/dart-sass/sass",
            "/sass/:/css/"]))
    }
: Compose.Service.Type


-------------
-- Helpers --
-------------

let stripPasswordsOffAuthentication = 
    \(authentication: T.Authentication.Type) ->
    {
        , staticLoginWithoutUserName = authentication.staticLoginWithoutUserName
        , openIdProviders = authentication.openIdProviders
    }: T.AuthenticationWithoutPasswords


--------------------------------------------------------------------
-- Extract all Docker volume sources from a Docker compose config --
--------------------------------------------------------------------

let extractDockerVolumeSources =
    \(config: Compose.Config.Type) ->
        let extractFromServiceVolumeLong = 
            \(v: Compose.ServiceVolumeLong.Type) ->
                merge {
                    , None = empty Text
                    , Some = \(src: Text) -> [src]
                } v.source
        : List Text

        let extractFromServiceVolume = 
            \(v: Compose.ServiceVolume) ->
                merge {
                    , Long = extractFromServiceVolumeLong
                    , Short = \(s: Text) -> [s]
                } v
        : List Text

        let extractFromService =
            \(s: Compose.Service.Type) ->
                merge {
                    , None = empty Text
                    , Some = \(vols: List Compose.ServiceVolume) ->
                        P.List.concatMap Compose.ServiceVolume Text extractFromServiceVolume vols
                } s.volumes
        : List Text
        
        let extractFromServices = 
            \(s: Compose.Services) ->
                P.List.concatMap Compose.Service.Type Text extractFromService (P.Map.values Text Compose.Service.Type s)
        : List Text

        in merge {
            , None = empty Text
            , Some = extractFromServices
        } config.services
: List Text



in {
    , makeVol
    , makeReadonlyVol
    , makeModuleSourceDir
    , makeModuleDir
    , makeModuleCssDir
    , moduleToString
    , htdocsDir

    , makeHtdocsVols
    , makeTemplateSassCompilation

    , extractDockerVolumeSources
}