let P       = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ../ext/dhall/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall

let T       = ./types.dhall

-- Prelude
let map = P.List.map
let filterMap = P.List.filterMap
let concatMap = P.List.concatMap
let unpackOptionals = P.List.unpackOptionals
let empty = P.List.empty
let Entry = P.Map.Entry
let keyValue = P.Map.keyValue

-- Docker common
let artifactsDockerCommon = ./artifacts-docker-common.dhall
let extractDockerVolumeSources = artifactsDockerCommon.extractDockerVolumeSources

-- Docker compose
let artifactsLocalDocker = ./artifacts-local-docker.dhall
let makeLocalDeplCompose = artifactsLocalDocker.makeLocalDeplCompose
let makeLinkCheckerCompose = artifactsLocalDocker.makeLinkCheckerCompose

-- config.json
let artifactsHtdocs = ./artifacts-htdocs.dhall
let ConfigJsonFile = artifactsHtdocs.ConfigJsonFile
let ConfigJsonFileT = artifactsHtdocs.ConfigJsonFileT
let stripPasswordsOffAuthentication = artifactsHtdocs.stripPasswordsOffAuthentication
let tagConfigJsonFile = artifactsHtdocs.tagConfigJsonFile


---------------------------------------------------
-- All config files bundled together in a record --
---------------------------------------------------

let makeArtifacts =
    \(config: T.LocalDepl) ->
    {
        , docker-compose-app-yml
            = makeLocalDeplCompose config
            : Compose.ComposeConfig     
        , docker-compose-app-yml-volume-sources
            = extractDockerVolumeSources (makeLocalDeplCompose config)
            : List Text
        , docker-compose-linkchecker-yml
            = makeLinkCheckerCompose config
            : Compose.ComposeConfig
        , config-json
            = tagConfigJsonFile {
                path_base = "..",
                path_preprocess = "../../preprocess",
                path_store = P.Optional.map T.LocalStore Text (\(_: T.LocalStore) -> "../../store") config.store,
                https = False,
                host = "localhost",
                url_base = "/",
                authentication = stripPasswordsOffAuthentication config.depl.authentication,
                authorization = config.depl.authorization,
                module_2_location = P.Map.map Text T.BareModule T.ModuleLocation T.bareModuleToLocation config.depl.bareModules,
                module_2_config = P.Map.map Text T.Module P.JSON.Type (\(m: T.Module) -> m.config) config.depl.modules,
                mail = None T.Mail,
                errorlog_display = config.errors.display,
                errorlog_dir = P.Optional.map Text Text (\(_: Text) -> "../../logs/error") config.errors.logToVolDir
            }
            : ConfigJsonFileT
    }

in {
    , makeArtifacts
}