let P = ../../ext/dhall/dhall-lang/Prelude/package_resolved.dhall
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
    , https: Bool
    , host: Text
    , url_base: Text
    , authentication: T.AuthenticationWithoutPasswords
    , authorization: T.Authorization.Type
    , module_2_location: P.Map.Type Text T.ModuleLocation
    , module_2_config: P.Map.Type Text P.JSON.Type
    , mail: Optional T.Mail
    , errorlog_display: Bool
    , errorlog_dir: Optional Text
}

let ConfigJsonFileT = {
    , path_base: Text
    , path_preprocess: Text
    , path_store: Optional Text
    , https: Bool
    , host: Text
    , url_base: Text
    , authentication: TT.AuthenticationWithoutPasswordsT
    , authorization: TT.AuthorizationT
    , module_2_location: P.Map.Type Text T.ModuleLocation
    , module_2_config: P.Map.Type Text P.JSON.Type
    , mail: Optional T.Mail
    , errorlog_display: Bool
    , errorlog_dir: Optional Text
}

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

let tagConfigJsonFile = \(c: ConfigJsonFile) ->
    c // {
        , authentication = TT.tagAuthenticationWithoutPasswords c.authentication
        , authorization = TT.tagAuthorization c.authorization
    }: ConfigJsonFileT

in

{
    , ConfigJsonFile
    , ConfigJsonFileT
    , stripPasswordsOffAuthentication
    , tagConfigJsonFile
}
