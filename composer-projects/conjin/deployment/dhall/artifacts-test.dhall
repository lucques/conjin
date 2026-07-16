let P = ./vendor/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let Compose = ./vendor/dhall-docker-compose-f077072175ee1501e12efc9fd37963ae043596ab/compose/v3/package_resolved.dhall
let T = ./types.dhall

let artifactsTestDocker = ./artifacts-test-docker.dhall
let makeTestDeplCompose = artifactsTestDocker.makeTestDeplCompose
let artifactsHtdocs = ./artifacts-htdocs.dhall
let ConfigJsonFileT = artifactsHtdocs.ConfigJsonFileT
let stripPasswordsOffAuthentication = artifactsHtdocs.stripPasswordsOffAuthentication
let tagConfigJsonFile = artifactsHtdocs.tagConfigJsonFile


---------------------------------------------------
-- All config files bundled together in a record --
---------------------------------------------------

let makeArtifacts =
    \(config: T.TestDepl) ->
    {
        , docker-compose-test-yml = makeTestDeplCompose config : Compose.ComposeConfig
        , config-json = tagConfigJsonFile {
            , path_base = ".."
            , path_preprocess = "../../preprocess"
            , path_store = P.Optional.map T.TestStore Text (\(_: T.TestStore) -> "../../store") config.store
            , https = False
            , host = "webserver"
            , url_base = "/"
            , authentication = stripPasswordsOffAuthentication config.depl.authentication
            , authorization = config.depl.authorization
            , module_2_location = P.Map.map Text T.BareModule T.ModuleLocation T.bareModuleToLocation config.depl.bareModules
            , module_2_config = P.Map.map Text T.Module P.JSON.Type (\(m: T.Module) -> m.config) config.depl.modules
            , mail = None T.Mail
            , errorlog_display = True
            , errorlog_dir = None Text
        } : ConfigJsonFileT
    }

in {
    , makeArtifacts
}
