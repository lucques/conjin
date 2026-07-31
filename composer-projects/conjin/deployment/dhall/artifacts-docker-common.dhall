let P       = ../../ext/dhall/dhall-lang/Prelude/package_resolved.dhall
let Compose = ../../ext/dhall/dhall-docker-compose/compose/v3/package_resolved.dhall

let T       = ./types.dhall

let map = P.List.map
let filterMap = P.List.filterMap
let concatMap = P.List.concatMap
let unpackOptionals = P.List.unpackOptionals
let empty = P.List.empty
let Entry = P.Map.Entry
let keyValue = P.Map.keyValue

let dockerImages = ./docker-images.dhall


--------------------
-- Volume helpers --
--------------------

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

let makeNamedVol =
    \(name: Text) ->
    \(target: Text) ->
    Compose.ServiceVolume.Long Compose.ServiceVolumeLong::{
        , type   = Some "volume"
        , source = Some name
        , target = Some target
    }
: Compose.ServiceVolume

let makeReadonlyNamedVol =
    \(name: Text) ->
    \(target: Text) ->
    Compose.ServiceVolume.Long Compose.ServiceVolumeLong::{
        , type      = Some "volume"
        , source    = Some name
        , target    = Some target
        , read_only = Some True
    }
: Compose.ServiceVolume

let makeDockerBuild =
    \(context: Text) ->
    \(baseImage: Text) ->
    Compose.Build.Object {
        , context = context
        , dockerfile = "Dockerfile"
        , args = Compose.ListOrDict.Dict [
            , P.Map.keyText "BASE_IMAGE" baseImage
        ]
        , ssh = Compose.ListOrDict.Dict ([] : List (Entry Text Text))
    }
: Compose.Build


-------------------
-- Health checks --
-------------------

-- Apache is ready for dependent jobs once it accepts connections inside its
-- own container. Application behaviour is checked separately by callers.
let makeWebserverHealthcheck =
    Compose.Healthcheck::{
        , test = Some (Compose.StringOrList.List [
            , "CMD-SHELL"
            , "php -r '\$\$socket = @fsockopen(\"127.0.0.1\", 80); exit(\$\$socket === false ? 1 : 0);'"
        ])
        , interval = Some "2s"
        , timeout = Some "2s"
        , retries = Some 30
    }
: Compose.Healthcheck.Type

-- The MariaDB image provides this command specifically for determining when
-- connections and InnoDB are ready after startup and initial data import.
let makeMariaDbHealthcheck =
    Compose.Healthcheck::{
        , test = Some (Compose.StringOrList.List ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"])
        , interval = Some "2s"
        , timeout = Some "2s"
        , retries = Some 30
    }
: Compose.Healthcheck.Type


----------------------------
-- Paths and Volume names --
----------------------------

let makeModuleSourceDir =
    \(conjinDir:   Text) ->
    \(appDir:      Text) ->
    \(m: T.ModuleLocation) ->
        if      m.isShared && m.isExternal == False          then conjinDir  ++ "/src/modules-shared/" ++ m.dirName
        else if m.isShared && m.isExternal                   then conjinDir  ++ "/ext/modules-shared/" ++ m.dirName
        else if m.isShared == False && m.isExternal == False then appDir     ++ "/src/modules/"        ++ m.dirName
        else                                                      appDir     ++ "/ext/modules/"        ++ m.dirName
: Text

let makeModuleTag =
    \(location: T.ModuleLocation) ->
        if      location.isShared && location.isExternal == False          then "modules-shared"
        else if location.isShared && location.isExternal                   then "modules-shared-ext"
        else if location.isShared == False && location.isExternal == False then "modules"
        else                                                                    "modules-ext"
: Text

let makeModuleDir =
    \(pathPrefix: Text) ->
    \(dirSuffix: Optional Text) ->
    \(m: T.ModuleLocation) ->
        let suf = merge { None = "", Some = \(d: Text) -> "-" ++ d } dirSuffix in
        pathPrefix ++ "/" ++ (makeModuleTag m) ++ suf ++ "/" ++ m.dirName
: Text

let makeModuleCssDir =
    \(cssVolDir: Text) ->
    \(m: T.ModuleLocation) ->
    (makeModuleDir cssVolDir (Some "css") m)
: Text

let makeModuleCssVolName =
    \(m: T.ModuleLocation) ->
    (makeModuleTag m) ++ "-css-" ++ m.dirName
: Text

let moduleToString =
    \(m: T.Module) -> (T.bareModuleToLocation m.bare).dirName ++ (if (T.bareModuleToLocation m.bare).isShared then "-shared" else "-local")
: Text

let htdocsDir = "/files/htdocs"

-- Conjin is mounted into htdocs from its source directory, so its Composer
-- package must not also be copied as part of the application's vendor tree.
let conjinComposerPackagePath = "/vendor/lucques/conjin"


-------------
-- Volumes --
-------------

-- The following volumes are placed inside of htdocs dir
let makeHtdocsVols =
    \(targetDir:   Text) ->
    \(appDir:      Text) ->
    \(conjinDir:   Text) ->
    \(bareModules: P.Map.Type Text T.BareModule) ->
    \(staticFiles: P.Map.Type Text Text) ->
    \(modules:     P.Map.Type Text T.Module) ->
    -- Volumes may be named (test environment) or bind (otherwise). Therefore
    -- a function is passed
    \(makeModuleCssVol: T.ModuleLocation -> Text -> Compose.ServiceVolume) ->
    \(withErrorLogDir:   Bool) ->
    \(withPreprocessDir: Bool) ->
    \(withStoreDir:      Bool) ->

    let makeModuleVol =
        \(m: T.BareModule) ->
        let location = T.bareModuleToLocation m in
        makeReadonlyVol (makeModuleSourceDir conjinDir appDir location) (makeModuleDir htdocsDir (None Text) location)

    let makeStaticFileVol =
        \(src: Text) ->
        \(dst: Text) ->
        makeReadonlyVol (appDir ++ "/src/static/" ++ src) (htdocsDir ++ "/" ++ dst)

    let makeModuleCssVolOnCompile =
        \(m: T.Module) ->
        let location = T.bareModuleToLocation m.bare in
        let target = makeModuleDir htdocsDir (Some "css") location in
        if m.compileScss
            then Some (makeModuleCssVol location target)
            else None Compose.ServiceVolume
    : Optional Compose.ServiceVolume

    let errorLogVol =
        if withErrorLogDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/logs/error")
                                                      (htdocsDir ++ "/logs/error"))
        else None Compose.ServiceVolume
    : Optional Compose.ServiceVolume

    let preprocessVol =
        if withPreprocessDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/preprocess")
                                                        (htdocsDir ++ "/preprocess"))
        else None Compose.ServiceVolume
    : Optional Compose.ServiceVolume
    
    let storeVol =
        if withStoreDir then Some (makeReadonlyVol (targetDir ++ "/htdocs/store")
                                                   (htdocsDir ++ "/store"))
        else None Compose.ServiceVolume
    : Optional Compose.ServiceVolume
    in
    [
        , makeReadonlyVol (appDir    ++ "/src/content")        (htdocsDir ++ "/content")
        , makeReadonlyVol (appDir    ++ "/src/system")         (htdocsDir ++ "/system")
        , makeReadonlyVol (conjinDir ++ "/src/conjin")         (htdocsDir ++ "/conjin")
        , makeReadonlyVol (appDir    ++ "/vendor")             (htdocsDir ++ "/vendor")
        , makeReadonlyVol (targetDir ++ "/htdocs/.htaccess")   (htdocsDir ++ "/.htaccess")
        , makeReadonlyVol (targetDir ++ "/htdocs/config.json") (htdocsDir ++ "/config.json")
        , makeReadonlyVol (targetDir ++ "/htdocs/users.json")  (htdocsDir ++ "/users.json")
    ]
    #
    -- Bare modules
    map T.BareModule Compose.ServiceVolume makeModuleVol (P.Map.values Text T.BareModule bareModules)
    #
    -- Static files
    map (P.Map.Entry Text Text) Compose.ServiceVolume (\(e: P.Map.Entry Text Text) -> makeStaticFileVol e.mapKey e.mapValue) staticFiles
    #
    -- CSS volumes for modules that compile SCSS
    unpackOptionals Compose.ServiceVolume (map T.Module (Optional Compose.ServiceVolume) makeModuleCssVolOnCompile (P.Map.values Text T.Module modules))
    #
    -- Error log, preprocess and store volumes are optional (may be placed
    -- outside of htdocs dir for local deployments)
    unpackOptionals Compose.ServiceVolume [errorLogVol, preprocessVol, storeVol]
: List Compose.ServiceVolume


--------------
-- Services --
--------------

let makeTemplateSassCompilation = 
    \(makeModuleCssVol: T.ModuleLocation -> Text -> Compose.ServiceVolume) ->
    \(conjinDir: Text) ->
    \(appDir:    Text) ->
    \(template:  T.Module) ->

    let location = T.bareModuleToLocation template.bare
    let scssDeps = T.bareModuleToScssDeps template.bare

    let moduleSassVol = makeReadonlyVol (makeModuleSourceDir conjinDir appDir location ++ "/scss") "/sass"
    let moduleCssVol = makeModuleCssVol location "/css"

    let makeModuleDependencyVol = 
        \(m: T.ModuleLocation) -> makeReadonlyVol (makeModuleSourceDir conjinDir appDir m ++ "/scss") (makeModuleDir   "" (None Text) m ++ "/scss")

    let volumes = [moduleSassVol, moduleCssVol] #
                  (map T.ModuleLocation Compose.ServiceVolume makeModuleDependencyVol scssDeps)
    in

    Compose.Service::{
        , image   = Some dockerImages.imageDartSass
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
        , usersXgroups = authentication.usersXgroups
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
    , makeDockerBuild
    , makeVol
    , makeReadonlyVol
    , makeNamedVol
    , makeReadonlyNamedVol
    , makeWebserverHealthcheck
    , makeMariaDbHealthcheck
    , makeModuleSourceDir
    , makeModuleDir
    , makeModuleCssDir
    , makeModuleCssVolName
    , moduleToString
    , htdocsDir
    , conjinComposerPackagePath

    , makeHtdocsVols
    , makeTemplateSassCompilation

    , extractDockerVolumeSources
}
