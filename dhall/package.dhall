{
    , prelude                 = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall

    , types                   = ./types.dhall
    , typesSharedModules      = ./types-shared-modules.dhall
    , typesTagged             = ./types-tagged.dhall

    , artifactsDockerCommon   = ./artifacts-docker-common.dhall
    , artifactsDockerLocal    = ./artifacts-docker-local.dhall
    , artifactsDockerRemote   = ./artifacts-docker-remote.dhall
    , artifactsHtdocs         = ./artifacts-htdocs.dhall

    , tools                   = ./tools.dhall

    , sharedModuleCollections = ./shared-module-collections.dhall
}