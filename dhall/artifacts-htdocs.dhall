let P = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall
let T = ./types.dhall
let TT = ./types-tagged.dhall


------------------------
-- `config.json` file --
------------------------

-- Format of the output `config.json` file
let ConfigJsonFile = {
    , path_base: Text
    , path_preprocess: Text
    , path_store: Optional Text
    , url_base: Text
    , authentication: T.AuthenticationWithoutPasswords
    , authorization: T.Authorization.Type
    , module_2_location: P.Map.Type Text T.ModuleLocation
    , module_2_config: P.Map.Type Text P.JSON.Type
    , errorlog_display: Bool
    , errorlog_dir: Optional Text
}

let ConfigJsonFileT = {
    , path_base: Text
    , path_preprocess: Text
    , path_store: Optional Text
    , url_base: Text
    , authentication: T.AuthenticationWithoutPasswords
    , authorization: TT.AuthorizationT
    , module_2_location: P.Map.Type Text T.ModuleLocation
    , module_2_config: P.Map.Type Text P.JSON.Type
    , errorlog_display: Bool
    , errorlog_dir: Optional Text
}

let tagConfigJsonFile = \(c: ConfigJsonFile) ->
    c // {
        authorization = TT.tagAuthorization c.authorization
    }: ConfigJsonFileT

in

{
    , ConfigJsonFile
    , ConfigJsonFileT
    , tagConfigJsonFile
}